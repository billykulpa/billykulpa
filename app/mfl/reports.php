<?php
/** CMS reports built from the synced league data. */

const MFL_PICKUP_POSITIONS = [
    'skill' => 'No K or DEF', 'all' => 'All positions',
    'QB' => 'QB', 'RB' => 'RB', 'WR' => 'WR', 'TE' => 'TE', 'K' => 'K', 'DEF' => 'DEF',
];
const MFL_PICKUP_SORTS = ['points' => 'Points', 'per_start' => 'Per start', 'starts' => 'Started'];

/**
 * Waiver and free-agent adds, scored by what they did for the team that added
 * them: regular-season points in that team's starting lineup, from the week of
 * the add on. Bench points don't count. A player added twice by one team is one
 * row, counted from the first add; two teams adding him are two rows.
 *
 * Adds that never made a starting lineup are left out.
 */
function mfl_pickups(int $season, string $position, bool $afterWeekOne, string $sort): array
{
    $stmt = db()->prepare('SELECT week, franchise_id, starters_json, starter_points_json FROM mfl_matchups WHERE season = ? AND is_playoff = 0');
    $stmt->execute([$season]);
    $started = [];
    foreach ($stmt as $m) {
        $points = json_decode((string) $m['starter_points_json'], true) ?: [];
        foreach (json_decode((string) $m['starters_json'], true) ?: [] as $i => $pid) {
            $started[$m['franchise_id']][$pid][(int) $m['week']] = (float) ($points[$i] ?? 0);
        }
    }

    $stmt = db()->prepare("SELECT week, type, created_at, adds_json FROM mfl_transactions
                           WHERE season = ? AND status = 'complete' AND type IN ('waiver', 'free_agent') ORDER BY created_at");
    $stmt->execute([$season]);
    $adds = [];
    foreach ($stmt as $t) {
        foreach (json_decode((string) $t['adds_json'], true) ?: [] as $pid => $fid) {
            $adds["$fid-$pid"] ??= ['player_id' => (string) $pid, 'franchise_id' => (int) $fid,
                'week' => (int) $t['week'], 'type' => $t['type'], 'added_at' => $t['created_at']];
        }
    }

    $players = [];
    foreach (db()->query('SELECT * FROM mfl_players') as $p) $players[$p['id']] = $p;
    $stmt = db()->prepare('SELECT franchise_id, team_name, roster_json FROM mfl_team_seasons WHERE season = ?');
    $stmt->execute([$season]);
    $teams = [];
    foreach ($stmt as $t) $teams[$t['franchise_id']] = $t + ['roster' => json_decode((string) $t['roster_json'], true) ?: []];

    $rows = [];
    foreach ($adds as $a) {
        $p = $players[$a['player_id']] ?? [];
        $pos = $p['position'] ?? '';
        if ($position === 'skill' && in_array($pos, ['K', 'DEF'], true)) continue;
        if (!in_array($position, ['skill', 'all'], true) && $pos !== $position) continue;
        if ($afterWeekOne && $a['week'] <= 1) continue;

        $weeks = array_filter($started[$a['franchise_id']][$a['player_id']] ?? [], fn($week) => $week >= $a['week'], ARRAY_FILTER_USE_KEY);
        if (!$weeks) continue;
        $team = $teams[$a['franchise_id']] ?? null;
        $rows[] = $a + [
            'name' => ($p['full_name'] ?? '') ?: $a['player_id'], 'position' => $pos, 'nfl_team' => $p['nfl_team'] ?? '',
            'team_name' => $team['team_name'] ?? '', 'starts' => count($weeks), 'points' => array_sum($weeks),
            'per_start' => array_sum($weeks) / count($weeks),
            'on_roster' => in_array($a['player_id'], $team['roster'] ?? [], true),
        ];
    }
    usort($rows, fn($a, $b) => [$b[$sort], $b['points']] <=> [$a[$sort], $a['points']]);
    return $rows;
}
