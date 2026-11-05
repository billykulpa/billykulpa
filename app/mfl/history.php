<?php
/**
 * League history: teams, owners, standings, drafts, records.
 *
 * Everything is worked out from mfl_matchups in PHP. A season is about 200
 * rows, so this stays fast for decades, and the stat rules are readable here
 * rather than buried in SQL.
 *
 * A franchise's numbers cover every game it has played. An owner's numbers are
 * the same games narrowed to their mfl_tenures rows, which is what lets a new
 * owner see only their own run with an inherited team.
 */

/** Every game, oldest first, each marked regular, playoff (the title path) or other. */
function mfl_games(): array
{
    static $games = null;
    if ($games !== null) return $games;

    $titlePath = [];
    foreach (db()->query(
        "SELECT b.season, b.round, b.team1_franchise_id t1, b.team2_franchise_id t2, s.playoff_week_start
           FROM mfl_brackets b JOIN mfl_seasons s ON s.season = b.season
          WHERE b.bracket = 'winners' AND (b.placement IS NULL OR b.placement = 1)"
    ) as $b) {
        $week = (int) $b['playoff_week_start'] + (int) $b['round'] - 1;
        foreach ([$b['t1'], $b['t2']] as $fid) {
            if ($fid) $titlePath["{$b['season']}-$week-$fid"] = true;
        }
    }

    $games = db()->query(
        'SELECT m.season, m.week, m.franchise_id, m.opponent_franchise_id, m.points, m.result, m.is_playoff,
                ts.team_name, ots.team_name AS opponent_name, om.points AS opponent_points
           FROM mfl_matchups m
           JOIN mfl_team_seasons ts ON ts.season = m.season AND ts.franchise_id = m.franchise_id
           LEFT JOIN mfl_team_seasons ots ON ots.season = m.season AND ots.franchise_id = m.opponent_franchise_id
           LEFT JOIN mfl_matchups om ON om.season = m.season AND om.week = m.week AND om.franchise_id = m.opponent_franchise_id
          ORDER BY m.season, m.week, m.franchise_id'
    )->fetchAll();

    foreach ($games as &$g) {
        $g['season'] = (int) $g['season'];
        $g['week'] = (int) $g['week'];
        $g['franchise_id'] = (int) $g['franchise_id'];
        $g['points'] = (float) $g['points'];
        $key = "{$g['season']}-{$g['week']}-{$g['franchise_id']}";
        $g['kind'] = !$g['is_playoff'] ? 'regular' : (isset($titlePath[$key]) ? 'playoff' : 'other');
    }
    return $games;
}

function mfl_in_tenure(array $t, int $season, int $week): bool
{
    if ([$season, $week] < [(int) $t['start_season'], (int) $t['start_week']]) return false;
    if ($t['end_season'] === null) return true;
    if ($season !== (int) $t['end_season']) return $season < (int) $t['end_season'];
    return $t['end_week'] === null || $week <= (int) $t['end_week'];
}

/** Games for a set of tenures, or for a whole franchise when no tenure is given. */
function mfl_games_for(int $franchise, ?array $tenure = null): array
{
    return array_values(array_filter(mfl_games(), fn($g) => $g['franchise_id'] === $franchise
        && (!$tenure || mfl_in_tenure($tenure, $g['season'], $g['week']))));
}

/** Record and points. Records and points are regular season; playoffs count the title path only. */
function mfl_summary(array $games): array
{
    $s = ['wins' => 0, 'losses' => 0, 'ties' => 0, 'pf' => 0.0, 'pa' => 0.0, 'games' => 0,
          'high' => null, 'low' => null, 'playoff_wins' => 0, 'playoff_losses' => 0];
    foreach ($games as $g) {
        if ($g['kind'] === 'playoff' && $g['result']) {
            if ($g['result'] === 'W') $s['playoff_wins']++;
            if ($g['result'] === 'L') $s['playoff_losses']++;
        }
        if ($g['kind'] !== 'regular') continue;
        $s['games']++;
        $s['pf'] += $g['points'];
        $s['pa'] += (float) $g['opponent_points'];
        $s['high'] = max($s['high'] ?? $g['points'], $g['points']);
        $s['low'] = min($s['low'] ?? $g['points'], $g['points']);
        if ($g['result'] === 'W') $s['wins']++;
        if ($g['result'] === 'L') $s['losses']++;
        if ($g['result'] === 'T') $s['ties']++;
    }
    $s['win_pct'] = $s['games'] ? ($s['wins'] + $s['ties'] / 2) / $s['games'] : 0;
    $s['average'] = $s['games'] ? $s['pf'] / $s['games'] : 0;
    return $s;
}

function mfl_record(array $s): string
{
    return $s['wins'] . '–' . $s['losses'] . ($s['ties'] ? '–' . $s['ties'] : '');
}

/** .615, the way a sports table shows it. */
function mfl_pct(float $pct): string
{
    return $pct >= 1 ? '1.000' : ltrim(number_format($pct, 3), '0');
}

function mfl_seasons(): array
{
    static $seasons = null;
    if ($seasons === null) {
        $seasons = [];
        foreach (db()->query('SELECT * FROM mfl_seasons ORDER BY season') as $s) $seasons[(int) $s['season']] = $s;
    }
    return $seasons;
}

/**
 * Final places for the playoff teams: [season][franchise] => place.
 * Only the winners bracket decides a place. Sleeper's losers bracket runs the
 * opposite way, and the league doesn't treat it as a final order.
 */
function mfl_finishes(): array
{
    static $places = null;
    if ($places !== null) return $places;
    $places = [];
    foreach (db()->query("SELECT season, placement, winner_franchise_id w, loser_franchise_id l FROM mfl_brackets
                          WHERE bracket = 'winners' AND placement IS NOT NULL AND winner_franchise_id IS NOT NULL") as $b) {
        $places[(int) $b['season']][(int) $b['w']] = (int) $b['placement'];
        $places[(int) $b['season']][(int) $b['l']] = (int) $b['placement'] + 1;
    }
    return $places;
}

function mfl_finish_label(?int $place, int $season): string
{
    $s = mfl_seasons()[$season] ?? null;
    if (!$s || $s['status'] !== 'complete') return '—';
    return match (true) {
        $place === 1    => 'Champion',
        $place === 2    => 'Runner-up',
        $place === 3    => '3rd',
        $place === null => 'Missed playoffs',
        default         => $place . 'th',
    };
}

/**
 * One row per season a set of games covers, newest first. A finish only counts
 * if these games reach the season's last scored week, so an owner who left
 * mid-season isn't credited with the result of the team they handed over.
 */
function mfl_season_rows(array $games): array
{
    $bySeason = [];
    foreach ($games as $g) $bySeason[$g['season']][$g['franchise_id']][] = $g;

    $stmt = db()->query('SELECT ts.season, ts.franchise_id, o.display_name, o.real_name FROM mfl_team_seasons ts LEFT JOIN mfl_owners o ON o.id = ts.owner_id');
    $owners = [];
    foreach ($stmt as $o) $owners[$o['season']][$o['franchise_id']] = $o;

    $rows = [];
    foreach ($bySeason as $season => $franchises) {
        foreach ($franchises as $fid => $list) {
            $last = end($list)['week'];
            $held = $last === (int) (mfl_seasons()[$season]['last_scored_week'] ?? 0);
            $complete = (mfl_seasons()[$season]['status'] ?? '') === 'complete';
            $place = mfl_finishes()[$season][$fid] ?? null;
            $rows[] = mfl_summary($list) + [
                'season' => $season, 'franchise_id' => $fid, 'team_name' => $list[0]['team_name'],
                'owner_name' => $owners[$season][$fid]['display_name'] ?? null,
                'real_name' => $owners[$season][$fid]['real_name'] ?? null,
                'place' => $held ? $place : null,
                'finish' => $held ? mfl_finish_label($place, $season) : '—',
                'made_playoffs' => $held && $complete && $place !== null,
            ];
        }
    }
    usort($rows, fn($a, $b) => $b['season'] <=> $a['season']);
    return $rows;
}

/** Titles, runner-ups and playoff trips from season rows. */
function mfl_honors(array $seasonRows): array
{
    return [
        'titles'   => count(array_filter($seasonRows, fn($r) => $r['place'] === 1)),
        'runner_ups' => count(array_filter($seasonRows, fn($r) => $r['place'] === 2)),
        'playoffs' => count(array_filter($seasonRows, fn($r) => $r['made_playoffs'])),
        'seasons'  => count(array_unique(array_column($seasonRows, 'season'))),
    ];
}

/** Every franchise's all-time line, best record first. */
function mfl_team_table(): array
{
    $rows = [];
    foreach (mfl_franchises() as $f) {
        $games = mfl_games_for((int) $f['id']);
        $rows[] = $f + mfl_summary($games) + mfl_honors(mfl_season_rows($games));
    }
    usort($rows, fn($a, $b) => [$b['titles'], $b['win_pct'], $b['pf']] <=> [$a['titles'], $a['win_pct'], $a['pf']]);
    return $rows;
}

function mfl_tenures(string $column, string $value): array
{
    $column = $column === 'owner' ? 'owner_id' : 'franchise_id';
    $stmt = db()->prepare(
        "SELECT t.*, o.display_name AS owner_name, o.real_name, f.name AS franchise_name
           FROM mfl_tenures t
           JOIN mfl_owners o ON o.id = t.owner_id
           JOIN mfl_franchises f ON f.id = t.franchise_id
          WHERE t.$column = ?
          ORDER BY t.start_season, t.start_week"
    );
    $stmt->execute([$value]);
    return $stmt->fetchAll();
}

/** "2025–present" or "2027, week 9–2029". */
function mfl_tenure_span(array $t): string
{
    $start = (int) $t['start_week'] > 1 ? $t['start_season'] . ', week ' . $t['start_week'] : (string) $t['start_season'];
    if ($t['end_season'] === null) return "{$start}–present";
    if ($t['end_season'] === $t['start_season']) {
        return $t['start_season'] . ', weeks ' . $t['start_week'] . '–' . ($t['end_week'] ?? 'end');
    }
    $end = $t['end_week'] !== null ? $t['end_season'] . ', week ' . $t['end_week'] : (string) $t['end_season'];
    return "{$start}–{$end}";
}

/** Record against each opponent, regular season and title-path games together. */
function mfl_head_to_head(array $games): array
{
    $names = db()->query('SELECT id, name FROM mfl_franchises')->fetchAll(PDO::FETCH_KEY_PAIR);
    $rows = [];
    foreach ($games as $g) {
        if ($g['kind'] === 'other' || !$g['opponent_franchise_id'] || !$g['result']) continue;
        $o = (int) $g['opponent_franchise_id'];
        $rows[$o] ??= ['id' => $o, 'name' => $names[$o] ?? "Team $o", 'wins' => 0, 'losses' => 0, 'ties' => 0, 'pf' => 0.0, 'pa' => 0.0, 'games' => 0];
        $rows[$o]['games']++;
        $rows[$o]['pf'] += $g['points'];
        $rows[$o]['pa'] += (float) $g['opponent_points'];
        $rows[$o][['W' => 'wins', 'L' => 'losses', 'T' => 'ties'][$g['result']]]++;
    }
    usort($rows, fn($a, $b) => [$b['wins'] - $b['losses'], $b['pf'] - $b['pa']] <=> [$a['wins'] - $a['losses'], $a['pf'] - $a['pa']]);
    return $rows;
}

/** A franchise's picks in one draft-order list, newest draft first. A tenure narrows it to that owner's drafts. */
function mfl_franchise_picks(int $franchise, ?array $tenure = null): array
{
    $stmt = db()->prepare(
        'SELECT p.*, d.season, d.type, s.num_teams
           FROM mfl_draft_picks p
           JOIN mfl_drafts d ON d.id = p.draft_id
           JOIN mfl_seasons s ON s.season = d.season
          WHERE p.franchise_id = ?
          ORDER BY d.season DESC, p.pick_no'
    );
    $stmt->execute([$franchise]);
    return array_values(array_filter($stmt->fetchAll(), fn($p) => !$tenure || mfl_in_tenure($tenure, (int) $p['season'], 1)));
}

/** 1.01, 3.12: round, then the pick within it. */
function mfl_pick_label(array $p): string
{
    return $p['round'] . '.' . str_pad((string) ((int) $p['pick_no'] - ((int) $p['round'] - 1) * (int) $p['num_teams']), 2, '0', STR_PAD_LEFT);
}

/** Who's on the roster now, grouped by position. */
function mfl_current_roster(int $franchise): array
{
    $stmt = db()->prepare('SELECT roster_json FROM mfl_team_seasons WHERE franchise_id = ? ORDER BY season DESC LIMIT 1');
    $stmt->execute([$franchise]);
    $ids = json_decode((string) $stmt->fetchColumn(), true) ?: [];
    if (!$ids) return [];
    $stmt = db()->prepare('SELECT * FROM mfl_players WHERE id IN (' . implode(',', array_fill(0, count($ids), '?')) . ') ORDER BY full_name');
    $stmt->execute($ids);
    $groups = array_fill_keys(['QB', 'RB', 'WR', 'TE', 'K', 'DEF'], []);
    foreach ($stmt as $p) $groups[$p['position'] ?: 'Other'][] = $p;
    return array_filter($groups);
}

/** Picks in undrafted classes that have changed hands: ['owned' => [...], 'traded' => [...]]. */
function mfl_future_picks(int $franchise): array
{
    $names = db()->query('SELECT id, name FROM mfl_franchises')->fetchAll(PDO::FETCH_KEY_PAIR);
    $done = db()->query("SELECT season FROM mfl_drafts WHERE status = 'complete'")->fetchAll(PDO::FETCH_COLUMN);
    $out = ['owned' => [], 'traded' => []];
    foreach (db()->query('SELECT * FROM mfl_traded_picks ORDER BY pick_season, round') as $p) {
        if (in_array($p['pick_season'], $done)) continue;
        $from = (int) $p['original_franchise_id'];
        $to = (int) $p['current_franchise_id'];
        if ($from === $to) continue;
        $label = $p['pick_season'] . ' round ' . $p['round'];
        if ($to === $franchise) $out['owned'][] = $label . ' (from ' . ($names[$from] ?? "Team $from") . ')';
        if ($from === $franchise) $out['traded'][] = $label . ' (to ' . ($names[$to] ?? "Team $to") . ')';
    }
    return $out;
}

/** Title-path playoff games for a season, grouped by round name, final first. */
function mfl_playoff_rounds(int $season): array
{
    $s = mfl_seasons()[$season] ?? null;
    if (!$s) return [];
    $stmt = db()->prepare("SELECT * FROM mfl_brackets WHERE season = ? AND bracket = 'winners' AND (placement IS NULL OR placement = 1) AND team1_franchise_id IS NOT NULL AND team2_franchise_id IS NOT NULL ORDER BY round DESC, match_id");
    $stmt->execute([$season]);
    $bracket = $stmt->fetchAll();
    if (!$bracket) return [];

    $byKey = [];
    foreach (mfl_games() as $g) $byKey["{$g['season']}-{$g['week']}-{$g['franchise_id']}"] = $g;
    $lastRound = (int) $bracket[0]['round'];
    $rounds = [];
    foreach ($bracket as $b) {
        $week = (int) $s['playoff_week_start'] + (int) $b['round'] - 1;
        $name = [0 => 'Championship', 1 => 'Semifinals', 2 => 'Quarterfinals'][$lastRound - (int) $b['round']] ?? 'Round ' . $b['round'];
        $game = $byKey["$season-$week-{$b['team1_franchise_id']}"] ?? null;
        if (!$game) continue;
        $rounds[$name][] = $game;
    }
    return $rounds;
}

function mfl_draft_board(int $season, ?int $team): array
{
    $stmt = db()->prepare(
        'SELECT p.*, d.season, s.num_teams, ts.team_name
           FROM mfl_draft_picks p
           JOIN mfl_drafts d ON d.id = p.draft_id
           JOIN mfl_seasons s ON s.season = d.season
           LEFT JOIN mfl_team_seasons ts ON ts.season = d.season AND ts.franchise_id = p.franchise_id
          WHERE d.season = ?
          ORDER BY p.pick_no'
    );
    $stmt->execute([$season]);
    $rounds = [];
    foreach ($stmt as $p) {
        if ($team !== null && (int) $p['franchise_id'] !== $team) continue;
        $rounds[(int) $p['round']][] = $p;
    }
    return $rounds;
}

/** Seasons that have a draft on file, newest first. */
function mfl_draft_seasons(): array
{
    return db()->query('SELECT DISTINCT d.season FROM mfl_drafts d JOIN mfl_draft_picks p ON p.draft_id = d.id ORDER BY d.season DESC')->fetchAll(PDO::FETCH_COLUMN);
}

/** Roster moves per franchise, from completed transactions. */
function mfl_move_counts(): array
{
    $counts = [];
    foreach (db()->query("SELECT type, franchise_ids_json FROM mfl_transactions WHERE status = 'complete'") as $t) {
        foreach (json_decode($t['franchise_ids_json'], true) ?: [] as $fid) {
            $counts[$fid] ??= ['moves' => 0, 'trades' => 0];
            $counts[$fid][$t['type'] === 'trade' ? 'trades' : 'moves']++;
        }
    }
    return $counts;
}

/** Longest runs of one result per franchise, across seasons. Ties end a run. */
function mfl_streaks(string $result): array
{
    $streaks = $current = [];
    foreach (mfl_games() as $g) {
        if ($g['kind'] === 'other' || !$g['result']) continue;
        $fid = $g['franchise_id'];
        if ($g['result'] !== $result) {
            if (isset($current[$fid])) $streaks[] = $current[$fid];
            unset($current[$fid]);
            continue;
        }
        $current[$fid] ??= ['franchise_id' => $fid, 'team_name' => $g['team_name'], 'length' => 0, 'from' => $g, 'active' => false];
        $current[$fid]['length']++;
        $current[$fid]['to'] = $g;
    }
    foreach ($current as $run) $streaks[] = ['active' => true] + $run;
    usort($streaks, fn($a, $b) => $b['length'] <=> $a['length']);
    return array_slice($streaks, 0, 5);
}

/** All-time bests and worsts. Consolation and placement games don't count. */
function mfl_records(): array
{
    $played = array_filter(mfl_games(), fn($g) => $g['kind'] !== 'other' && $g['result']);
    $sorted = function (array $list, callable $key, bool $desc = true) {
        usort($list, fn($a, $b) => $desc ? $key($b) <=> $key($a) : $key($a) <=> $key($b));
        return array_slice($list, 0, 5);
    };
    // Each game is stored once per team; the winner's row stands for the game.
    $decided = array_filter($played, fn($g) => $g['result'] === 'W');
    $margin = fn($g) => $g['points'] - (float) $g['opponent_points'];

    $seasons = [];
    foreach (mfl_seasons() as $year => $s) {
        if ($s['status'] !== 'complete') continue;
        foreach (mfl_games() as $g) {
            if ($g['season'] !== $year || $g['kind'] !== 'regular') continue;
            $seasons["$year-{$g['franchise_id']}"][] = $g;
        }
    }
    $seasonLines = array_map(fn($list) => mfl_summary($list) + [
        'season' => $list[0]['season'], 'franchise_id' => $list[0]['franchise_id'], 'team_name' => $list[0]['team_name'],
    ], array_values($seasons));

    $moves = [];
    $names = db()->query('SELECT id, name FROM mfl_franchises')->fetchAll(PDO::FETCH_KEY_PAIR);
    foreach (mfl_move_counts() as $fid => $c) $moves[] = $c + ['franchise_id' => $fid, 'team_name' => $names[$fid] ?? "Team $fid"];

    return [
        'high_scores'  => $sorted($played, fn($g) => $g['points']),
        'low_scores'   => $sorted($played, fn($g) => $g['points'], false),
        'blowouts'     => $sorted($decided, $margin),
        'close_games'  => $sorted($decided, $margin, false),
        'most_wins'    => $sorted($seasonLines, fn($s) => [$s['win_pct'], $s['pf']]),
        'most_points'  => $sorted($seasonLines, fn($s) => $s['pf']),
        'fewest_points' => $sorted($seasonLines, fn($s) => $s['pf'], false),
        'win_streaks'  => mfl_streaks('W'),
        'loss_streaks' => mfl_streaks('L'),
        'moves'        => $sorted($moves, fn($m) => $m['moves']),
    ];
}

/** "2025 · Week 6", with playoff rounds left as weeks. */
function mfl_game_label(array $g): string
{
    return $g['season'] . ' · Week ' . $g['week'];
}

function mfl_team_link(int $id, string $name): string
{
    return '<a class="team-link" href="/teams/' . $id . '">' . esc($name) . '</a>';
}

function mfl_owner_link(?string $username, ?string $realName): string
{
    if (!$username) return 'Vacant';
    return '<a class="owner-link" href="/owners/' . rawurlencode($username) . '">' . esc(mfl_owner_label($username, $realName)) . '</a>';
}

/** Handle a history URL. Returns false if the path isn't one of these. */
function mfl_history_route(string $path): bool
{
    if ($path === 'teams') {
        mfl_render('teams', ['nav' => 'teams', 'title' => 'Teams', 'teams' => mfl_team_table()]);
        return true;
    }

    if (preg_match('#^teams/(\d{1,3})$#', $path, $m)) {
        $id = (int) $m[1];
        $team = current(array_filter(mfl_franchises(), fn($f) => (int) $f['id'] === $id));
        if (!$team) return false;
        $tenures = mfl_tenures('franchise', (string) $id);
        $era = null;
        foreach ($tenures as $t) {
            if ((int) ($_GET['era'] ?? 0) === (int) $t['id']) $era = $t;
        }
        $games = mfl_games_for($id, $era);
        $seasons = mfl_season_rows($games);
        $history = mfl_rank_history();
        $trail = [];
        foreach ($history as $h) {
            if (isset($h['ranks'][$id])) $trail[] = $h['ranks'][$id];
        }
        mfl_render('team', [
            'nav' => 'teams', 'title' => $team['name'], 'team' => $team, 'tenures' => $tenures, 'era' => $era,
            'summary' => mfl_summary($games), 'honors' => mfl_honors($seasons), 'seasons' => $seasons,
            'headToHead' => mfl_head_to_head($games), 'picks' => mfl_franchise_picks($id, $era),
            'roster' => mfl_current_roster($id), 'future' => mfl_future_picks($id),
            'moves' => mfl_move_counts()[$id] ?? ['moves' => 0, 'trades' => 0],
            'trail' => $trail, 'teamCount' => count(mfl_franchises()),
        ]);
        return true;
    }

    if (preg_match('#^owners/([^/]+)$#', $path, $m)) {
        $stmt = db()->prepare('SELECT * FROM mfl_owners WHERE display_name = ?');
        $stmt->execute([rawurldecode($m[1])]);
        $owner = $stmt->fetch();
        if (!$owner) return false;
        $tenures = mfl_tenures('owner', $owner['id']);
        $games = [];
        foreach ($tenures as $t) $games = array_merge($games, mfl_games_for((int) $t['franchise_id'], $t));
        usort($games, fn($a, $b) => [$a['season'], $a['week']] <=> [$b['season'], $b['week']]);
        $seasons = mfl_season_rows($games);
        mfl_render('owner', [
            'nav' => 'teams', 'title' => $owner['display_name'], 'owner' => $owner, 'tenures' => $tenures,
            'summary' => mfl_summary($games), 'honors' => mfl_honors($seasons), 'seasons' => $seasons,
            'headToHead' => mfl_head_to_head($games),
        ]);
        return true;
    }

    if ($path === 'standings' || preg_match('#^standings/(\d{4})$#', $path, $m)) {
        $seasons = array_keys(mfl_seasons());
        $season = (int) ($m[1] ?? (end($seasons) ?: 0));
        if (!in_array($season, $seasons, true)) return false;
        $rows = mfl_standings($season);
        $places = mfl_finishes()[$season] ?? [];
        foreach ($rows as &$r) $r['finish'] = mfl_finish_label($places[$r['franchise_id']] ?? null, $season);
        unset($r);
        mfl_render('standings', [
            'nav' => 'standings', 'title' => "$season Standings", 'season' => mfl_seasons()[$season], 'seasons' => array_reverse($seasons),
            'rows' => $rows, 'rounds' => mfl_playoff_rounds($season),
        ]);
        return true;
    }

    if ($path === 'drafts' || preg_match('#^drafts/(\d{4})$#', $path, $m)) {
        $seasons = array_map('intval', mfl_draft_seasons());
        $season = (int) ($m[1] ?? ($seasons[0] ?? 0));
        if (!in_array($season, $seasons, true)) return false;
        $team = isset($_GET['team']) && $_GET['team'] !== '' ? (int) $_GET['team'] : null;
        mfl_render('drafts', [
            'nav' => 'drafts', 'title' => "$season Draft", 'season' => $season, 'seasons' => $seasons, 'team' => $team,
            'rounds' => mfl_draft_board($season, $team), 'teams' => mfl_standings($season),
        ]);
        return true;
    }

    if ($path === 'pickups') {
        $seasons = array_reverse(array_keys(mfl_seasons()));
        $f = mfl_pickup_filters($_GET, $seasons);
        mfl_render('pickups', [
            'nav' => 'pickups', 'title' => 'Best Pickups', 'script' => true, 'f' => $f, 'seasons' => $seasons,
            'rows' => mfl_pickup_rows($f), 'teams' => mfl_standings($f['season']),
        ]);
        return true;
    }

    if ($path === 'records') {
        mfl_render('records', ['nav' => 'records', 'title' => 'Records', 'records' => mfl_records()]);
        return true;
    }

    return false;
}
