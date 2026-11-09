<?php
/**
 * Copies the league's Sleeper history into the mfl_ tables.
 *
 * Runs from the daily cron (sync-cli.php) and the CMS "Sync now" button.
 * Finished seasons are only imported once; --full on the CLI redoes them.
 */

require_once __DIR__ . '/sleeper.php';
require_once __DIR__ . '/values.php';

/** Returns ['status' => ok|error|busy, 'summary' => string]. */
function mfl_sync(string $trigger, ?int $userId = null, bool $full = false, bool $forceValues = false): array
{
    // Cron and the button can collide, and two runs would race on tenures.
    // MySQL locks are server-wide, so the name carries the league.
    $lock = 'mfl_sync_' . mfl_league()['key'];
    $stmt = league_db()->prepare('SELECT GET_LOCK(?, 0)');
    $stmt->execute([$lock]);
    if (!(int) $stmt->fetchColumn()) {
        return ['status' => 'busy', 'summary' => 'A sync is already running.'];
    }

    $stmt = league_db()->prepare('INSERT INTO mfl_sync_log (started_at, trigger_source, user_id) VALUES (?, ?, ?)');
    $stmt->execute([date('Y-m-d H:i:s'), $trigger, $userId]);
    $logId = (int) league_db()->lastInsertId();

    try {
        // Columns added after a season finished are empty until it's re-imported.
        $full = $full || (int) league_db()->query('SELECT COUNT(*) FROM mfl_matchups WHERE player_points_json IS NULL')->fetchColumn() > 0;
        $stored = league_db()->query('SELECT season, status FROM mfl_seasons')->fetchAll(PDO::FETCH_KEY_PAIR);
        $done = [];
        $chain = mfl_league_chain();
        foreach ($chain as $league) {
            $season = (int) $league['season'];
            if (!$full && ($stored[$season] ?? '') === 'complete') continue;
            mfl_sync_season($league);
            $done[] = $season;
        }
        // Sleeper doesn't document this feed, so losing it shouldn't cost the
        // rest of the sync. Season totals just stay as they were.
        $statsNote = '';
        try {
            foreach ($chain as $league) mfl_sync_player_weeks($league);
        } catch (Throwable $e) {
            if (league_db()->inTransaction()) league_db()->rollBack();
            $statsNote = ' Season totals not updated.';
        }
        $players = mfl_sync_players();
        // Trade values lean on two unofficial feeds, so like the stats above,
        // a failure here leaves yesterday's values and doesn't fail the sync.
        $valuesNote = '';
        try {
            // The daily cron always recomputes. A "Sync now" click only does if
            // the last run is a day old or a trade hasn't been valued yet,
            // since it downloads Sleeper's full player list, which Sleeper
            // asks to fetch at most daily.
            $valued = mfl_sync_values($forceValues || $trigger === 'cron');
            if ($valued) $valuesNote = " Valued {$valued} players.";
        } catch (Throwable $e) {
            if (league_db()->inTransaction()) league_db()->rollBack();
            $valuesNote = ' Trade values not updated.';
        }
        $summary = 'Synced ' . ($done ? implode(', ', $done) : 'nothing new') . '.'
                 . ($players ? " Added {$players} player names." : '') . $statsNote . $valuesNote;
        $result = ['status' => 'ok', 'summary' => $summary];
    } catch (Throwable $e) {
        if (league_db()->inTransaction()) league_db()->rollBack();
        $result = ['status' => 'error', 'summary' => substr($e->getMessage(), 0, 500)];
    }

    $stmt = league_db()->prepare('UPDATE mfl_sync_log SET finished_at = ?, status = ?, summary = ? WHERE id = ?');
    $stmt->execute([date('Y-m-d H:i:s'), $result['status'], $result['summary'], $logId]);
    league_db()->prepare('SELECT RELEASE_LOCK(?)')->execute([$lock]);
    return $result;
}

/** Every season's league object, oldest first. */
function mfl_league_chain(): array
{
    $newest = league_db()->query('SELECT league_id FROM mfl_seasons ORDER BY season DESC LIMIT 1')->fetchColumn()
        ?: mfl_league()['seed_league_id'];

    $chain = [];
    for ($id = $newest; $id; $id = $league['previous_league_id'] ?? null) {
        $league = sleeper_get("/league/$id");
        if (!$league) throw new RuntimeException("Sleeper has no league $id.");
        array_unshift($chain, $league);
        if (!(int) ($league['previous_league_id'] ?? 0)) break;
    }

    // Sleeper gives a renewed league a new id and only links it backward, so
    // next season has to be found through the commissioner's league list.
    while (true) {
        $last = end($chain);
        $next = null;
        foreach (sleeper_get('/user/' . mfl_league()['tracker_user_id'] . '/leagues/nfl/' . ((int) $last['season'] + 1)) ?? [] as $l) {
            if (($l['previous_league_id'] ?? '') === $last['league_id']) $next = $l;
        }
        if (!$next) return $chain;
        $chain[] = $next;
    }
}

function mfl_sync_season(array $league): void
{
    $id     = $league['league_id'];
    $season = (int) $league['season'];
    $s      = $league['settings'];
    $scored = (int) ($s['last_scored_leg'] ?? 0);
    $complete = $league['status'] === 'complete';
    // Offseason trades land in week 0; a finished season can hold late moves.
    $lastTxWeek = $complete ? 18 : max($scored, (int) ($s['leg'] ?? 0));

    $paths = [
        'users'    => "/league/$id/users",
        'rosters'  => "/league/$id/rosters",
        'drafts'   => "/league/$id/drafts",
        'traded'   => "/league/$id/traded_picks",
        'winners'  => "/league/$id/winners_bracket",
        'losers'   => "/league/$id/losers_bracket",
    ];
    for ($w = 1; $w <= $scored; $w++) $paths["m$w"] = "/league/$id/matchups/$w";
    for ($w = 0; $w <= $lastTxWeek; $w++) $paths["t$w"] = "/league/$id/transactions/$w";

    // All the fetching happens before the database transaction opens, so a
    // Sleeper failure can't leave a season half written.
    $raw = sleeper_get_many(array_values($paths));
    $data = [];
    foreach ($paths as $key => $path) $data[$key] = $raw[$path] ?? [];

    $picks = [];
    foreach ($data['drafts'] as $d) $picks[$d['draft_id']] = sleeper_get("/draft/{$d['draft_id']}/picks") ?? [];

    $users = [];
    foreach ($data['users'] as $u) $users[$u['user_id']] = $u;

    $winners = $data['winners'] ?: [];
    $title = current(array_filter($winners, fn($m) => (int) ($m['p'] ?? 0) === 1)) ?: [];

    $db = league_db();
    $db->beginTransaction();

    mfl_upsert('mfl_seasons', [
        'season' => $season, 'league_id' => $id, 'name' => trim($league['name']),
        'status' => $league['status'], 'num_teams' => (int) $s['num_teams'],
        'playoff_teams' => (int) $s['playoff_teams'],
        'playoff_week_start' => (int) $s['playoff_week_start'],
        'last_scored_week' => $scored,
        'champion_franchise_id' => $title['w'] ?? null,
        'runner_up_franchise_id' => $title['l'] ?? null,
        'synced_at' => date('Y-m-d H:i:s'),
    ], ['season']);

    foreach ($users as $u) {
        mfl_upsert('mfl_owners', [
            'id' => $u['user_id'], 'display_name' => trim($u['display_name']), 'avatar' => $u['avatar'] ?? null,
        ], ['id']);
    }

    foreach ($data['rosters'] as $r) {
        $rid   = (int) $r['roster_id'];
        $owner = $r['owner_id'] ?: null;
        $name  = mfl_team_name($users[$owner] ?? null, $rid);
        $st    = $r['settings'];
        mfl_upsert('mfl_franchises', ['id' => $rid, 'name' => $name], ['id']);
        mfl_upsert('mfl_team_seasons', [
            'season' => $season, 'franchise_id' => $rid, 'owner_id' => $owner, 'team_name' => $name,
            'wins' => (int) ($st['wins'] ?? 0), 'losses' => (int) ($st['losses'] ?? 0), 'ties' => (int) ($st['ties'] ?? 0),
            'points_for' => mfl_points($st, 'fpts'),
            'points_against' => mfl_points($st, 'fpts_against'),
            'max_points' => mfl_points($st, 'ppts'),
            'faab_used' => (int) ($st['waiver_budget_used'] ?? 0),
            'roster_json' => json_encode($r['players'] ?? []),
        ], ['season', 'franchise_id']);
        if (isset($users[$owner])) mfl_sync_tenure($rid, $owner, $season, $league);
    }

    for ($w = 1; $w <= $scored; $w++) {
        $byMatch = [];
        foreach ($data["m$w"] as $m) {
            if (!empty($m['matchup_id'])) $byMatch[$m['matchup_id']][] = $m;
        }
        foreach ($data["m$w"] as $m) {
            $opp = null;
            foreach ($byMatch[$m['matchup_id'] ?? 0] ?? [] as $other) {
                if ($other['roster_id'] !== $m['roster_id']) $opp = $other;
            }
            $points = (float) ($m['points'] ?? 0);
            $result = null;
            if ($opp) $result = ['L', 'T', 'W'][($points <=> (float) ($opp['points'] ?? 0)) + 1];
            mfl_upsert('mfl_matchups', [
                'season' => $season, 'week' => $w, 'franchise_id' => (int) $m['roster_id'],
                'opponent_franchise_id' => $opp ? (int) $opp['roster_id'] : null,
                'points' => $points,
                'result' => $result,
                'is_playoff' => (int) ($w >= (int) $s['playoff_week_start']),
                'starters_json' => json_encode($m['starters'] ?? []),
                'starter_points_json' => json_encode($m['starters_points'] ?? []),
                'players_json' => json_encode($m['players'] ?? []),
                'player_points_json' => json_encode($m['players_points'] ?? []),
            ], ['season', 'week', 'franchise_id']);
        }
    }

    foreach (['winners' => $winners, 'losers' => $data['losers'] ?: []] as $bracket => $games) {
        foreach ($games as $g) {
            mfl_upsert('mfl_brackets', [
                'season' => $season, 'bracket' => $bracket, 'match_id' => (int) $g['m'], 'round' => (int) $g['r'],
                'team1_franchise_id' => $g['t1'] ?? null, 'team2_franchise_id' => $g['t2'] ?? null,
                'winner_franchise_id' => $g['w'] ?? null, 'loser_franchise_id' => $g['l'] ?? null,
                'placement' => $g['p'] ?? null,
            ], ['season', 'bracket', 'match_id']);
        }
    }

    foreach ($data['drafts'] as $d) {
        mfl_upsert('mfl_drafts', [
            'id' => $d['draft_id'], 'season' => $season, 'type' => $d['type'], 'status' => $d['status'],
            'rounds' => (int) ($d['settings']['rounds'] ?? 0),
            'started_at' => !empty($d['start_time']) ? date('Y-m-d H:i:s', intdiv((int) $d['start_time'], 1000)) : null,
        ], ['id']);
        foreach ($picks[$d['draft_id']] as $p) {
            $meta = $p['metadata'] ?? [];
            mfl_upsert('mfl_draft_picks', [
                'draft_id' => $d['draft_id'], 'pick_no' => (int) $p['pick_no'], 'round' => (int) $p['round'],
                'draft_slot' => (int) $p['draft_slot'], 'franchise_id' => (int) $p['roster_id'],
                'player_id' => (string) $p['player_id'],
                'player_name' => trim(($meta['first_name'] ?? '') . ' ' . ($meta['last_name'] ?? '')),
                'position' => $meta['position'] ?? '', 'nfl_team' => $meta['team'] ?? '',
            ], ['draft_id', 'pick_no']);
        }
    }

    for ($w = 0; $w <= $lastTxWeek; $w++) {
        foreach ($data["t$w"] as $t) {
            mfl_upsert('mfl_transactions', [
                'id' => $t['transaction_id'], 'season' => $season, 'week' => (int) ($t['leg'] ?? $w),
                'type' => $t['type'], 'status' => $t['status'],
                'created_at' => date('Y-m-d H:i:s', intdiv((int) $t['created'], 1000)),
                'completed_at' => date('Y-m-d H:i:s', intdiv((int) ($t['status_updated'] ?? $t['created']), 1000)),
                'franchise_ids_json' => json_encode($t['roster_ids'] ?? []),
                'adds_json' => json_encode($t['adds'] ?? null), 'drops_json' => json_encode($t['drops'] ?? null),
                'picks_json' => json_encode($t['draft_picks'] ?? []),
                'faab_json' => json_encode($t['waiver_budget'] ?? []),
                'settings_json' => json_encode($t['settings'] ?? null),
            ], ['id']);
        }
    }

    // Only the newest season's list is the truth about who holds what now.
    // Chain order is oldest first, so the newest season writes last.
    $db->exec('DELETE FROM mfl_traded_picks');
    foreach ($data['traded'] as $tp) {
        mfl_upsert('mfl_traded_picks', [
            'pick_season' => (int) $tp['season'], 'round' => (int) $tp['round'],
            'original_franchise_id' => (int) $tp['roster_id'],
            'current_franchise_id' => (int) $tp['owner_id'],
            'previous_franchise_id' => $tp['previous_owner_id'] ?? null,
        ], ['pick_season', 'round', 'original_franchise_id']);
    }

    $db->commit();
}

/**
 * Open, or hand over, a franchise's ownership record.
 *
 * Sleeper keeps no owner history: a past season only shows who held the team
 * at the end. So a change spotted between seasons starts in week 1, and one
 * spotted mid-season starts in the week the sync first saw it.
 */
function mfl_sync_tenure(int $franchise, string $owner, int $season, array $league): void
{
    $stmt = league_db()->prepare('SELECT * FROM mfl_tenures WHERE franchise_id = ? AND end_season IS NULL');
    $stmt->execute([$franchise]);
    $open = $stmt->fetch();

    if ($open && ($open['owner_id'] === $owner || $season < (int) $open['start_season'])) return;

    $week = 1;
    if ($open) {
        $live = in_array($league['status'], ['in_season', 'post_season'], true);
        $week = $live ? max(1, (int) ($league['settings']['leg'] ?? 1)) : 1;
        // Handed over twice in one week: the earlier handover never played.
        if ((int) $open['start_season'] === $season && (int) $open['start_week'] === $week) {
            $stmt = league_db()->prepare('UPDATE mfl_tenures SET owner_id = ? WHERE id = ?');
            $stmt->execute([$owner, $open['id']]);
            return;
        }
        $stmt = league_db()->prepare('UPDATE mfl_tenures SET end_season = ?, end_week = ? WHERE id = ?');
        $stmt->execute($week > 1 ? [$season, $week - 1, $open['id']] : [$season - 1, null, $open['id']]);
    }
    $stmt = league_db()->prepare('INSERT INTO mfl_tenures (franchise_id, owner_id, start_season, start_week) VALUES (?, ?, ?, ?)');
    $stmt->execute([$franchise, $owner, $season, $week]);
}

/**
 * Store what every player scored each week under this league's scoring.
 * Sleeper's weekly stats times the league's scoring settings reproduces its
 * own fantasy points exactly. Weeks already stored are skipped, except the
 * latest two of a live season, which is where stat corrections land.
 */
function mfl_sync_player_weeks(array $league): void
{
    $season = (int) $league['season'];
    $last = (int) ($league['settings']['last_scored_leg'] ?? 0);
    if ($last < 1) return;

    $stmt = league_db()->prepare('SELECT DISTINCT week FROM mfl_player_weeks WHERE season = ?');
    $stmt->execute([$season]);
    $stored = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    $weeks = array_diff(range(1, $last), $stored);
    if ($league['status'] !== 'complete') $weeks = array_unique([...$weeks, ...array_filter([$last - 1, $last], fn($w) => $w >= 1)]);

    ini_set('memory_limit', '512M');
    $scoring = $league['scoring_settings'] ?? [];
    foreach (array_chunk(array_values($weeks), 4) as $batch) {
        $paths = array_map(fn($w) => "/stats/nfl/regular/$season/$w", $batch);
        $stats = sleeper_get_many($paths, 45);
        foreach ($batch as $i => $week) {
            $rows = [];
            foreach ($stats[$paths[$i]] ?? [] as $pid => $line) {
                $points = 0.0;
                foreach ($line as $key => $value) {
                    if (is_numeric($value) && isset($scoring[$key])) $points += $value * $scoring[$key];
                }
                if (round($points, 2) != 0) $rows[] = [$season, $week, (string) $pid, round($points, 2)];
            }
            league_db()->beginTransaction();
            league_db()->prepare('DELETE FROM mfl_player_weeks WHERE season = ? AND week = ?')->execute([$season, $week]);
            foreach (array_chunk($rows, 300) as $chunk) {
                league_db()->prepare('INSERT INTO mfl_player_weeks (season, week, player_id, points) VALUES '
                    . implode(',', array_fill(0, count($chunk), '(?,?,?,?)')))->execute(array_merge(...$chunk));
            }
            league_db()->commit();
        }
    }
}

/**
 * Fill in names for players the league has touched. Sleeper's full player
 * dump is several MB and they ask for it at most once a day, so it's fetched
 * only when an unknown id shows up, and a failure here never fails the sync.
 */
function mfl_sync_players(): int
{
    $ids = [];
    foreach (league_db()->query('SELECT roster_json FROM mfl_team_seasons')->fetchAll(PDO::FETCH_COLUMN) as $json) {
        foreach (json_decode((string) $json, true) ?: [] as $pid) $ids[$pid] = true;
    }
    foreach (league_db()->query('SELECT adds_json, drops_json FROM mfl_transactions') as $row) {
        foreach ([$row['adds_json'], $row['drops_json']] as $json) {
            foreach (array_keys(json_decode((string) $json, true) ?: []) as $pid) $ids[$pid] = true;
        }
    }
    foreach (league_db()->query('SELECT player_id FROM mfl_draft_picks')->fetchAll(PDO::FETCH_COLUMN) as $pid) $ids[$pid] = true;
    foreach (league_db()->query('SELECT DISTINCT player_id FROM mfl_player_weeks')->fetchAll(PDO::FETCH_COLUMN) as $pid) $ids[$pid] = true;

    $known = array_flip(league_db()->query('SELECT id FROM mfl_players')->fetchAll(PDO::FETCH_COLUMN));
    $missing = array_diff_key($ids, $known);
    if (!$missing) return 0;

    $lastFetch = (string) league_db()->query("SELECT MAX(started_at) FROM mfl_sync_log WHERE status = 'ok' AND summary LIKE '%player names%'")->fetchColumn();
    // One new name can wait for tomorrow's download. A big gap can't.
    if (count($missing) < 50 && $lastFetch !== '' && strtotime($lastFetch) > time() - 86400) return 0;

    try {
        ini_set('memory_limit', '512M');
        $all = sleeper_get_many(['/players/nfl'], 90)['/players/nfl'];
    } catch (Throwable $e) {
        return 0;
    }

    $added = 0;
    foreach (array_keys($missing) as $pid) {
        // An id Sleeper no longer lists still gets a row, or every later sync
        // would see it as missing and download the whole dump again.
        $p = $all[$pid] ?? [];
        // Team defenses have an id like "CHI" and no full_name.
        $name = trim($p['full_name'] ?? (($p['first_name'] ?? '') . ' ' . ($p['last_name'] ?? '')));
        mfl_upsert('mfl_players', [
            'id' => (string) $pid, 'full_name' => $name,
            'position' => $p['position'] ?? '', 'nfl_team' => $p['team'] ?? '',
        ], ['id']);
        $added++;
    }
    return $added;
}

/** Sleeper team names are optional; an owner without one shows as "Team username". */
function mfl_team_name(?array $user, int $rosterId): string
{
    $name = trim((string) ($user['metadata']['team_name'] ?? ''));
    if ($name !== '') return $name;
    return $user ? 'Team ' . trim($user['display_name']) : "Team $rosterId";
}

/** Sleeper splits scores into whole and hundredths fields. */
function mfl_points(array $settings, string $field): float
{
    return (int) ($settings[$field] ?? 0) + (int) ($settings[$field . '_decimal'] ?? 0) / 100;
}

/** Insert or update one row. Table and column names only ever come from this file. */
function mfl_upsert(string $table, array $row, array $keys): void
{
    static $statements = [];
    $cols = array_keys($row);
    $sig = $table . ':' . implode(',', $cols);
    if (!isset($statements[$sig])) {
        $updates = array_map(fn($c) => "$c = VALUES($c)", array_diff($cols, $keys));
        // A table of only key columns has nothing to update.
        $tail = $updates ? implode(', ', $updates) : "{$keys[0]} = {$keys[0]}";
        $statements[$sig] = league_db()->prepare(
            "INSERT INTO $table (" . implode(', ', $cols) . ') VALUES ('
            . implode(', ', array_fill(0, count($cols), '?')) . ") ON DUPLICATE KEY UPDATE $tail"
        );
    }
    $statements[$sig]->execute(array_values($row));
}
