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

/**
 * Every pickup, oldest first, including ones that never started. Pass a season
 * for that season alone, or NULL for all-time, where a pickup keeps counting in
 * later seasons for as long as the team holds him. That's the point of a dynasty
 * pickup, so a season's numbers can be smaller than the same pickup all-time.
 */
function mfl_pickup_list(?int $season): array
{
    $allTime = $season === null;
    $data = mfl_weekly_rosters();
    $seasons = mfl_seasons();

    // Walk the transactions in order, opening a pickup on each add and closing
    // the matching one when that team drops or trades the player.
    $sql = "SELECT season, week, type, created_at, adds_json, drops_json FROM mfl_transactions WHERE status = 'complete'";
    $stmt = db()->prepare($sql . ($allTime ? '' : ' AND season = ?') . ' ORDER BY created_at, id');
    $stmt->execute($allTime ? [] : [$season]);
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
            $pickups[] = ['player_id' => (string) $pid, 'franchise_id' => (int) $fid, 'season' => (int) $t['season'], 'week' => (int) $t['week'],
                          'type' => $t['type'], 'added_at' => $t['created_at'], 'left_at' => null, 'left_how' => null];
            $open["$fid-$pid"] = array_key_last($pickups);
        }
    }

    // A start belongs to the team's most recent add at or before that week.
    $adds = [];
    foreach ($pickups as $i => $p) $adds["{$p['franchise_id']}-{$p['player_id']}"][$i] = [$p['season'], $p['week']];

    $players = [];
    foreach (db()->query('SELECT * FROM mfl_players') as $p) $players[$p['id']] = $p;
    $teams = [];
    foreach (db()->query('SELECT ts.season, ts.franchise_id, ts.team_name, ts.owner_id, o.display_name, o.real_name
                            FROM mfl_team_seasons ts LEFT JOIN mfl_owners o ON o.id = ts.owner_id') as $t) {
        $teams[$t['season']][$t['franchise_id']] = $t;
    }
    $timesAdded = array_count_values(array_column($pickups, 'player_id'));
    $dateFormat = $allTime ? 'M j, Y' : 'M j';

    foreach ($pickups as $i => &$p) {
        $pid = $p['player_id'];
        $fid = $p['franchise_id'];
        $next = null;
        foreach ($adds["$fid-$pid"] as $j => $when) {
            if ($j > $i) { $next = $when; break; }
        }

        $starts = 0;
        $starter = $roster = 0.0;
        $years = [];
        foreach (mfl_weeks_held($fid, $pid, $p['season'], $p['week']) as $key) {
            [$year, $week] = $data['weeks'][$key];
            if ($next !== null && [$year, $week] >= $next) break;
            if (!$allTime && $year !== $p['season']) break;
            if ($week >= (int) $seasons[$year]['playoff_week_start']) continue;
            $years[$year] = true;
            $roster += $data['points'][$key][$fid][$pid] ?? 0;
            if (isset($data['starts'][$key][$fid][$pid])) {
                $starts++;
                $starter += $data['starts'][$key][$fid][$pid];
            }
        }

        $held = array_keys($years) ?: [$p['season']];
        $total = 0.0;
        foreach ($held as $year) $total += mfl_player_totals($year, true)[$pid] ?? 0;
        $team = $teams[$p['season']][$fid] ?? [];
        $player = $players[$pid] ?? [];
        $p += [
            'name' => ($player['full_name'] ?? '') ?: $pid, 'position' => $player['position'] ?? '',
            'team_name' => $team['team_name'] ?? '', 'owner_id' => $team['owner_id'] ?? null,
            'owner_name' => $team['display_name'] ?? null, 'real_name' => $team['real_name'] ?? null,
            'added_on' => central_time($p['added_at'], 'Y-m-d'),
            'added_label' => mfl_transaction_week($p['season'], $p['week'], $p['added_at']) . ' (' . central_time($p['added_at'], $dateFormat) . ')',
            'left_label' => $p['left_at'] ? central_time($p['left_at'], $dateFormat) : '',
            'times_added' => $timesAdded[$pid], 'starts' => $starts, 'points' => $starter, 'roster_points' => $roster,
            'total_points' => $total, 'years' => $held, 'per_start' => $starts ? $starter / $starts : 0, 'pickups' => 1,
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
    $view = in_array($q['view'] ?? '', ['players', 'owners'], true) ? $q['view'] : 'pickups';
    // By Owner is an all-time view unless a season is picked.
    $season = in_array((int) ($q['season'] ?? 0), $seasons, true) ? (int) $q['season']
        : (($q['season'] ?? '') === 'all' || ($view === 'owners' && !isset($q['season'])) ? 'all' : ($seasons[0] ?? 0));
    return [
        'season' => $season,
        'view' => $view,
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
    $rows = array_filter(mfl_pickup_list($f['season'] === 'all' ? null : $f['season']), fn($p) =>
        in_array($p['position'], $f['pos'], true)
        && ($f['from'] === '' || $p['added_on'] >= $f['from'])
        && ($f['to'] === '' || $p['added_on'] <= $f['to'])
        && (!$f['team'] || $p['franchise_id'] === $f['team'])
        && ($f['type'] === '' || $p['type'] === $f['type']));

    if ($f['view'] === 'players') {
        $byPlayer = [];
        foreach ($rows as $p) {
            $row = &$byPlayer[$p['player_id']];
            $row ??= ['pickups' => 0, 'starts' => 0, 'points' => 0.0, 'roster_points' => 0.0, 'teams' => [], 'years' => []] + $p;
            $row['pickups']++;
            $row['starts'] += $p['starts'];
            $row['points'] += $p['points'];
            $row['roster_points'] += $p['roster_points'];
            $row['teams'][$p['franchise_id']] = $p['team_name'];
            $row['years'] = array_values(array_unique([...$row['years'], ...$p['years']]));
            unset($row);
        }
        foreach ($byPlayer as $pid => &$row) {
            // Each season's total counts once, however many pickups fell in it.
            $row['total_points'] = array_sum(array_map(fn($year) => mfl_player_totals($year, true)[$pid] ?? 0, $row['years']));
            $row['per_start'] = $row['starts'] ? $row['points'] / $row['starts'] : 0;
        }
        unset($row);
        $rows = $byPlayer;
    }

    if (!$f['unstarted']) $rows = array_filter($rows, fn($r) => $r['starts'] > 0);
    $rows = array_values($rows);
    $sign = $f['dir'] === 'asc' ? 1 : -1;
    usort($rows, fn($a, $b) => $sign * ($a[$f['sort']] <=> $b[$f['sort']]) ?: $b['points'] <=> $a['points']);
    return $rows;
}

/**
 * The By Owner view: each owner's best pickups, biggest overall contribution
 * first. An owner is whoever held the team in the season of the pickup, so a
 * new owner isn't credited with a previous owner's moves.
 */
function mfl_pickup_owners(array $f, int $limit = 5): array
{
    $owners = [];
    foreach (mfl_pickup_rows($f) as $r) {
        $key = $r['owner_id'] ?? 'team-' . $r['franchise_id'];
        $owners[$key] ??= ['franchise_id' => $r['franchise_id'], 'owner_name' => $r['owner_name'], 'real_name' => $r['real_name'],
                           'count' => 0, 'points' => 0.0, 'rows' => []];
        $owners[$key]['count']++;
        $owners[$key]['points'] += $r['points'];
        if (count($owners[$key]['rows']) < $limit) $owners[$key]['rows'][] = $r;
    }
    usort($owners, fn($a, $b) => $b['points'] <=> $a['points']);
    return $owners;
}

/** A /pickups link that keeps the current filters. Defaults stay out of the URL. */
function mfl_pickup_url(array $f, array $change = []): string
{
    $f = $change + $f;
    $q = array_filter([
        'season' => $f['season'], 'view' => $f['view'] === 'pickups' ? '' : $f['view'],
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
    if ($r['left_at']) $parts[] = $r['left_how'] . ' ' . $r['left_label'];
    if ($r['times_added'] > 1) $parts[] = "Picked up {$r['times_added']} times";
    return implode(' · ', $parts);
}
