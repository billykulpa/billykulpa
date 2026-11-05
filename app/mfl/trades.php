<?php
/**
 * The Trades page: what each team received and how it has worked out.
 *
 * A player's time with the new team comes from Sleeper's weekly rosters, not
 * the trade date, because a trade sits in review for days. It runs from the
 * first week he was on the roster until the first week he wasn't. Games count
 * the regular season and the title path of the playoffs, as Records does.
 */

/** Weekly rosters, starts and points. 'scored' is [season][player][week], whoever held him. */
function mfl_weekly_rosters(): array
{
    static $data = null;
    if ($data !== null) return $data;
    $data = ['weeks' => [], 'rosters' => [], 'starts' => [], 'points' => [], 'scored' => []];
    foreach (db()->query('SELECT season, week, franchise_id, players_json, player_points_json, starters_json, starter_points_json FROM mfl_matchups ORDER BY season, week') as $m) {
        $key = "{$m['season']}-{$m['week']}";
        $data['weeks'][$key] = [(int) $m['season'], (int) $m['week']];
        $data['rosters'][$key][$m['franchise_id']] = array_flip(array_map('strval', json_decode((string) $m['players_json'], true) ?: []));
        $points = json_decode((string) $m['starter_points_json'], true) ?: [];
        foreach (json_decode((string) $m['starters_json'], true) ?: [] as $i => $pid) {
            $data['starts'][$key][$m['franchise_id']][(string) $pid] = (float) ($points[$i] ?? 0);
        }
        foreach (json_decode((string) $m['player_points_json'], true) ?: [] as $pid => $pts) {
            $data['points'][$key][$m['franchise_id']][(string) $pid] = (float) $pts;
            $data['scored'][(int) $m['season']][(string) $pid][(int) $m['week']] = (float) $pts;
        }
    }
    return $data;
}

/**
 * The weeks a player was on a team, from a starting week. Looks one week past
 * the start for his arrival, since a review period can carry a trade over.
 */
function mfl_weeks_held(int $franchise, string $player, int $season, int $week): array
{
    $data = mfl_weekly_rosters();
    $held = [];
    $missed = 0;
    foreach ($data['weeks'] as $key => [$s, $w]) {
        if ([$s, $w] < [$season, $week]) continue;
        if (isset($data['rosters'][$key][$franchise][$player])) {
            $held[] = $key;
        } elseif ($held || ++$missed > 1) {
            break;
        }
    }
    return $held;
}

/**
 * Starts, points and the team's record over the weeks a player was held.
 *
 * Three point totals, each inside the one before it:
 * total_points   every week of the seasons he was held, on any team's roster
 * roster_points  while on this team, bench included
 * points         while in this team's starting lineup
 */
function mfl_held_stats(int $franchise, string $player, array $weeks, int $season): array
{
    static $games = null;
    if ($games === null) {
        $games = [];
        foreach (mfl_games() as $g) $games["{$g['season']}-{$g['week']}"][$g['franchise_id']] = $g;
    }
    $data = mfl_weekly_rosters();
    $starts = $data['starts'];
    $s = ['weeks' => count($weeks), 'starts' => 0, 'points' => 0.0, 'roster_points' => 0.0, 'total_points' => 0.0, 'wins' => 0, 'losses' => 0, 'ties' => 0];
    $seasons = array_unique(array_map(fn($key) => (int) explode('-', $key)[0], $weeks)) ?: [$season];
    foreach ($seasons as $year) $s['total_points'] += array_sum($data['scored'][$year][$player] ?? []);
    foreach ($weeks as $key) {
        $g = $games[$key][$franchise] ?? null;
        if (!$g || $g['kind'] === 'other') continue;
        $s['roster_points'] += $data['points'][$key][$franchise][$player] ?? 0;
        if (isset($starts[$key][$franchise][$player])) {
            $s['starts']++;
            $s['points'] += $starts[$key][$franchise][$player];
        }
        if ($g['result']) $s[['W' => 'wins', 'L' => 'losses', 'T' => 'ties'][$g['result']]]++;
    }
    return $s;
}

/** The team each player is on now: [player_id => franchise_id]. */
function mfl_current_rosters(): array
{
    static $map = null;
    if ($map !== null) return $map;
    $map = [];
    $latest = (int) db()->query('SELECT MAX(season) FROM mfl_seasons')->fetchColumn();
    $stmt = db()->prepare('SELECT franchise_id, roster_json FROM mfl_team_seasons WHERE season = ?');
    $stmt->execute([$latest]);
    foreach ($stmt as $t) {
        foreach (json_decode((string) $t['roster_json'], true) ?: [] as $pid) $map[(string) $pid] = (int) $t['franchise_id'];
    }
    return $map;
}

/** Every move of every player, oldest first: [player_id => [moves]]. */
function mfl_player_moves(): array
{
    static $moves = null;
    if ($moves !== null) return $moves;
    $moves = [];
    foreach (db()->query("SELECT id, season, type, COALESCE(completed_at, created_at) AS at, adds_json, drops_json
                          FROM mfl_transactions WHERE status = 'complete' ORDER BY at, id") as $t) {
        $adds = json_decode((string) $t['adds_json'], true) ?: [];
        $drops = json_decode((string) $t['drops_json'], true) ?: [];
        foreach (array_keys($adds + $drops) as $pid) {
            $moves[(string) $pid][] = ['id' => $t['id'], 'season' => (int) $t['season'], 'type' => $t['type'], 'at' => $t['at'],
                'to' => isset($adds[$pid]) ? (int) $adds[$pid] : null, 'from' => isset($drops[$pid]) ? (int) $drops[$pid] : null];
        }
    }
    return $moves;
}

function mfl_move_label(array $m, array $names): string
{
    $date = central_time($m['at'], 'M j');
    $to = $names[$m['to']] ?? '';
    $from = $names[$m['from']] ?? '';
    if ($m['type'] === 'trade') return "Traded to $to, $date";
    if ($m['to'] === null) return "Dropped by $from, $date";
    return ($m['type'] === 'waiver' ? 'Claimed off waivers' : 'Signed as a free agent') . " by $to, $date";
}

/**
 * Where a player is now and how he got there, since a point in time.
 * 'path' holds each move as text, with a link when the move was another trade.
 */
function mfl_player_status(string $player, int $team, string $since, array $names): array
{
    $moves = array_filter(mfl_player_moves()[$player] ?? [], fn($m) => $m['at'] > $since);
    $path = array_map(fn($m) => [
        'text' => mfl_move_label($m, $names),
        'href' => $m['type'] === 'trade' ? '/trades?season=' . $m['season'] . '#trade-' . $m['id'] : null,
    ], array_values($moves));
    $holder = mfl_current_rosters()[$player] ?? null;
    if ($holder === null) return ['label' => 'Not on a roster', 'path' => $path, 'state' => 'gone'];
    if ($holder === $team) return ['label' => $moves ? 'Back on this roster' : 'Still on this roster', 'path' => $path, 'state' => 'here'];
    return ['label' => 'Now on ' . ($names[$holder] ?? "Team $holder"), 'path' => $path, 'state' => 'moved'];
}

/** What a traded pick became, if that draft has happened. */
function mfl_pick_outcome(array $pick): ?array
{
    $stmt = db()->prepare("SELECT p.*, s.num_teams, d.started_at, d.season
                             FROM mfl_draft_picks p
                             JOIN mfl_drafts d ON d.id = p.draft_id
                             JOIN mfl_seasons s ON s.season = d.season
                            WHERE d.season = ? AND d.status = 'complete'");
    $stmt->execute([(int) $pick['season']]);
    $picks = $stmt->fetchAll();
    // A team's slot stays the same every round, so its own picks reveal it.
    $slot = null;
    foreach ($picks as $p) {
        if ((int) $p['franchise_id'] === (int) $pick['roster_id'] && (int) $p['round'] !== (int) $pick['round']) $slot = (int) $p['draft_slot'];
    }
    foreach ($picks as $p) {
        if ((int) $p['round'] === (int) $pick['round'] && (int) $p['draft_slot'] === $slot) return $p;
    }
    return null;
}

/** A season's completed trades, newest first, with what each side received. */
function mfl_trades(int $season): array
{
    $names = db()->query('SELECT id, name FROM mfl_franchises')->fetchAll(PDO::FETCH_KEY_PAIR);
    $stmt = db()->prepare(
        'SELECT ts.franchise_id, ts.team_name, o.display_name AS owner_name, o.real_name
           FROM mfl_team_seasons ts LEFT JOIN mfl_owners o ON o.id = ts.owner_id WHERE ts.season = ?'
    );
    $stmt->execute([$season]);
    $teams = [];
    foreach ($stmt as $t) $teams[(int) $t['franchise_id']] = $t;
    foreach ($teams as $fid => $t) $names[$fid] = $t['team_name'];

    $players = [];
    foreach (db()->query('SELECT * FROM mfl_players') as $p) $players[$p['id']] = $p;
    $traded = [];
    foreach (db()->query('SELECT * FROM mfl_traded_picks') as $p) $traded["{$p['pick_season']}-{$p['round']}-{$p['original_franchise_id']}"] = (int) $p['current_franchise_id'];

    $stmt = db()->prepare("SELECT * FROM mfl_transactions WHERE season = ? AND type = 'trade' AND status = 'complete' ORDER BY COALESCE(completed_at, created_at) DESC");
    $stmt->execute([$season]);
    $trades = [];
    foreach ($stmt as $t) {
        $at = $t['completed_at'] ?: $t['created_at'];
        $sides = [];
        foreach (json_decode($t['franchise_ids_json'], true) ?: [] as $fid) {
            $sides[(int) $fid] = ['franchise_id' => (int) $fid, 'team' => $teams[(int) $fid] ?? null, 'assets' => [], 'points' => 0.0];
        }

        foreach (json_decode((string) $t['adds_json'], true) ?: [] as $pid => $fid) {
            $pid = (string) $pid;
            $p = $players[$pid] ?? [];
            $stats = mfl_held_stats((int) $fid, $pid, mfl_weeks_held((int) $fid, $pid, $season, (int) $t['week']), $season);
            $sides[(int) $fid]['assets'][] = $stats + [
                'name' => ($p['full_name'] ?? '') ?: $pid, 'position' => $p['position'] ?? '', 'note' => '',
                'status' => mfl_player_status($pid, (int) $fid, $at, $names),
            ];
            $sides[(int) $fid]['points'] += $stats['points'];
        }

        foreach (json_decode((string) $t['picks_json'], true) ?: [] as $pick) {
            $fid = (int) $pick['owner_id'];
            $label = $pick['season'] . ' round ' . $pick['round'] . ' pick';
            $original = (int) $pick['roster_id'];
            if ($original !== (int) $pick['previous_owner_id']) $label .= ' (originally ' . ($names[$original] ?? "Team $original") . ')';
            $outcome = mfl_pick_outcome($pick);
            if ($outcome) {
                $pid = (string) $outcome['player_id'];
                $by = (int) $outcome['franchise_id'];
                $stats = mfl_held_stats($by, $pid, mfl_weeks_held($by, $pid, (int) $outcome['season'], 1), (int) $outcome['season']);
                $sides[$fid]['assets'][] = $stats + [
                    'name' => $outcome['player_name'], 'position' => $outcome['position'],
                    'note' => $label . ', used at ' . mfl_pick_label($outcome) . ($by !== $fid ? ' by ' . ($names[$by] ?? '') : ''),
                    'status' => mfl_player_status($pid, $by, (string) $outcome['started_at'], $names),
                ];
                $sides[$fid]['points'] += $stats['points'];
                continue;
            }
            $holder = $traded["{$pick['season']}-{$pick['round']}-$original"] ?? $fid;
            $sides[$fid]['assets'][] = [
                'name' => ucfirst($label), 'position' => 'Pick', 'note' => '', 'weeks' => 0, 'starts' => 0, 'points' => 0.0, 'roster_points' => 0.0, 'total_points' => 0.0,
                'wins' => 0, 'losses' => 0, 'ties' => 0, 'is_pick' => true,
                'status' => ['label' => $holder === $fid ? 'Not drafted yet' : 'Now held by ' . ($names[$holder] ?? "Team $holder"), 'path' => [], 'state' => 'pick'],
            ];
        }
        $trades[] = ['id' => $t['id'], 'week' => (int) $t['week'], 'proposed_at' => $t['created_at'], 'completed_at' => $at, 'sides' => array_values($sides)];
    }
    return $trades;
}
