<?php
/**
 * The public pickups table: waiver and free-agent adds, scored by what they
 * did for the team that added them.
 *
 * Every add is its own pickup. A pickup's points are regular-season points in
 * that team's starting lineup, from the week of the add until the same team
 * adds the player again, so a player who was dropped and picked back up has
 * two rows and no game counts twice.
 *
 * Three point totals, each inside the one before it:
 * total_points   his regular season, on any team or as a free agent
 * roster_points  on this team since the pickup, bench included
 * points         in this team's starting lineup since the pickup
 */

const MFL_PICKUP_POSITIONS = ['QB', 'RB', 'WR', 'TE', 'K', 'DEF'];
const MFL_PICKUP_DEFAULT_POSITIONS = ['QB', 'RB', 'WR', 'TE'];
const MFL_PICKUP_SORTS = ['name', 'added_on', 'pickups', 'starts', 'total_points', 'roster_points', 'points', 'per_start'];

/** Every pickup of a season, oldest first, including ones that never started. */
function mfl_pickup_list(int $season): array
{
    $stmt = db()->prepare('SELECT week, franchise_id, starters_json, starter_points_json, player_points_json FROM mfl_matchups WHERE season = ? AND is_playoff = 0');
    $stmt->execute([$season]);
    $started = $rostered = [];
    foreach ($stmt as $m) {
        foreach (json_decode((string) $m['player_points_json'], true) ?: [] as $pid => $pts) {
            $rostered[$m['franchise_id']][$pid][(int) $m['week']] = (float) $pts;
        }
        $points = json_decode((string) $m['starter_points_json'], true) ?: [];
        foreach (json_decode((string) $m['starters_json'], true) ?: [] as $i => $pid) {
            $started[$m['franchise_id']][$pid][(int) $m['week']] = (float) ($points[$i] ?? 0);
        }
    }

    // Walk the transactions in order, opening a pickup on each add and closing
    // the matching one when that team drops or trades the player.
    $stmt = db()->prepare("SELECT week, type, created_at, adds_json, drops_json FROM mfl_transactions
                           WHERE season = ? AND status = 'complete' ORDER BY created_at, id");
    $stmt->execute([$season]);
    $pickups = $open = [];
    foreach ($stmt as $t) {
        foreach (json_decode((string) $t['drops_json'], true) ?: [] as $pid => $fid) {
            if (!isset($open["$fid-$pid"])) continue;
            $pickups[$open["$fid-$pid"]]['left_at'] = $t['created_at'];
            $pickups[$open["$fid-$pid"]]['left_how'] = $t['type'] === 'trade' ? 'Traded' : 'Dropped';
            unset($open["$fid-$pid"]);
        }
        if (!in_array($t['type'], ['waiver', 'free_agent'], true)) continue;
        foreach (json_decode((string) $t['adds_json'], true) ?: [] as $pid => $fid) {
            $pickups[] = ['player_id' => (string) $pid, 'franchise_id' => (int) $fid, 'week' => (int) $t['week'],
                          'type' => $t['type'], 'added_at' => $t['created_at'], 'left_at' => null, 'left_how' => null];
            $open["$fid-$pid"] = array_key_last($pickups);
        }
    }

    // A start belongs to the team's most recent add at or before that week.
    $weeks = [];
    foreach ($pickups as $i => $p) $weeks["{$p['franchise_id']}-{$p['player_id']}"][$i] = $p['week'];

    $players = [];
    foreach (db()->query('SELECT * FROM mfl_players') as $p) $players[$p['id']] = $p;
    $stmt = db()->prepare('SELECT franchise_id, team_name FROM mfl_team_seasons WHERE season = ?');
    $stmt->execute([$season]);
    $teams = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $timesAdded = array_count_values(array_column($pickups, 'player_id'));
    $totals = mfl_player_totals($season, true);

    foreach ($pickups as $i => &$p) {
        $next = null;
        foreach ($weeks["{$p['franchise_id']}-{$p['player_id']}"] as $j => $week) {
            if ($j > $i) { $next = $week; break; }
        }
        $inPickup = fn($w) => $w >= $p['week'] && ($next === null || $w < $next);
        $games = array_filter($started[$p['franchise_id']][$p['player_id']] ?? [], $inPickup, ARRAY_FILTER_USE_KEY);
        $held = array_filter($rostered[$p['franchise_id']][$p['player_id']] ?? [], $inPickup, ARRAY_FILTER_USE_KEY);
        $player = $players[$p['player_id']] ?? [];
        $p += [
            'name' => ($player['full_name'] ?? '') ?: $p['player_id'], 'position' => $player['position'] ?? '',
            'team_name' => $teams[$p['franchise_id']] ?? '', 'added_on' => central_time($p['added_at'], 'Y-m-d'),
            'added_label' => mfl_transaction_week($season, $p['week'], $p['added_at']) . ' (' . central_time($p['added_at'], 'M j') . ')',
            'times_added' => $timesAdded[$p['player_id']], 'starts' => count($games), 'points' => array_sum($games),
            'roster_points' => array_sum($held), 'total_points' => $totals[$p['player_id']] ?? 0.0,
            'per_start' => $games ? array_sum($games) / count($games) : 0, 'pickups' => 1,
        ];
    }
    return $pickups;
}

/** Read and clean the filters from the URL. */
function mfl_pickup_filters(array $q, array $seasons): array
{
    $date = fn($key) => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($q[$key] ?? '')) ? $q[$key] : '';
    $positions = array_values(array_intersect(MFL_PICKUP_POSITIONS, (array) ($q['pos'] ?? [])));
    $sort = in_array($q['sort'] ?? '', MFL_PICKUP_SORTS, true) ? $q['sort'] : 'points';
    return [
        'season' => in_array((int) ($q['season'] ?? 0), $seasons, true) ? (int) $q['season'] : ($seasons[0] ?? 0),
        'view' => ($q['view'] ?? '') === 'players' ? 'players' : 'pickups',
        'pos' => $positions ?: MFL_PICKUP_DEFAULT_POSITIONS,
        'from' => $date('from'), 'to' => $date('to'),
        'team' => (int) ($q['team'] ?? 0),
        'type' => in_array($q['type'] ?? '', ['waiver', 'free_agent'], true) ? $q['type'] : '',
        'unstarted' => !empty($q['unstarted']),
        'sort' => $sort,
        'dir' => in_array($q['dir'] ?? '', ['asc', 'desc'], true) ? $q['dir'] : ($sort === 'name' ? 'asc' : 'desc'),
    ];
}

/** Rows for the table: filtered, combined by player if asked, then sorted. */
function mfl_pickup_rows(array $f): array
{
    $rows = array_filter(mfl_pickup_list($f['season']), fn($p) =>
        in_array($p['position'], $f['pos'], true)
        && ($f['from'] === '' || $p['added_on'] >= $f['from'])
        && ($f['to'] === '' || $p['added_on'] <= $f['to'])
        && (!$f['team'] || $p['franchise_id'] === $f['team'])
        && ($f['type'] === '' || $p['type'] === $f['type']));

    if ($f['view'] === 'players') {
        $byPlayer = [];
        foreach ($rows as $p) {
            $row = &$byPlayer[$p['player_id']];
            $row ??= $p + ['teams' => []];
            if ($row['added_at'] !== $p['added_at'] || $row['franchise_id'] !== $p['franchise_id']) {
                $row['pickups']++;
                // total_points is his whole season, so it isn't added up again here.
                $row['starts'] += $p['starts'];
                $row['points'] += $p['points'];
                $row['roster_points'] += $p['roster_points'];
            }
            $row['teams'][$p['franchise_id']] = $p['team_name'];
            unset($row);
        }
        foreach ($byPlayer as &$row) $row['per_start'] = $row['starts'] ? $row['points'] / $row['starts'] : 0;
        unset($row);
        $rows = $byPlayer;
    }

    if (!$f['unstarted']) $rows = array_filter($rows, fn($r) => $r['starts'] > 0);
    $rows = array_values($rows);
    $sign = $f['dir'] === 'asc' ? 1 : -1;
    usort($rows, fn($a, $b) => $sign * ($a[$f['sort']] <=> $b[$f['sort']]) ?: $b['points'] <=> $a['points']);
    return $rows;
}

/** A /pickups link that keeps the current filters. Defaults stay out of the URL. */
function mfl_pickup_url(array $f, array $change = []): string
{
    $f = $change + $f;
    $q = array_filter([
        'season' => $f['season'], 'view' => $f['view'] === 'players' ? 'players' : '',
        'pos' => $f['pos'] === MFL_PICKUP_DEFAULT_POSITIONS ? [] : $f['pos'],
        'from' => $f['from'], 'to' => $f['to'], 'team' => $f['team'], 'type' => $f['type'], 'unstarted' => $f['unstarted'] ? 1 : 0,
        'sort' => $f['sort'] === 'points' ? '' : $f['sort'],
        'dir' => $f['dir'] === ($f['sort'] === 'name' ? 'asc' : 'desc') ? '' : $f['dir'],
    ]);
    return '/pickups' . ($q ? '?' . http_build_query($q) : '');
}

/** A sortable column heading. Clicking the current column flips its direction. */
function mfl_pickup_th(array $f, string $key, string $label, string $class = ''): string
{
    $active = $f['sort'] === $key;
    $default = $key === 'name' ? 'asc' : 'desc';
    $dir = $active ? ($f['dir'] === 'asc' ? 'desc' : 'asc') : $default;
    $aria = $active ? ' aria-sort="' . ($f['dir'] === 'asc' ? 'ascending' : 'descending') . '"' : '';
    $arrow = $active ? ($f['dir'] === 'asc' ? ' ▲' : ' ▼') : '';
    return '<th scope="col" class="' . esc($class) . '"' . $aria . '><a class="sort-link" href="'
        . esc(mfl_pickup_url($f, ['sort' => $key, 'dir' => $dir])) . '">' . esc($label) . $arrow . '</a></th>';
}

/** "Free agent · Dropped Oct 20 · Picked up 3 times this season" */
function mfl_pickup_note(array $r): string
{
    $parts = [$r['type'] === 'waiver' ? 'Waiver claim' : 'Free agent'];
    if ($r['left_at']) $parts[] = $r['left_how'] . ' ' . central_time($r['left_at'], 'M j');
    if ($r['times_added'] > 1) $parts[] = "Picked up {$r['times_added']} times";
    return implode(' · ', $parts);
}
