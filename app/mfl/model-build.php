<?php
/**
 * Builds app/mfl/model.json, the slow-moving half of the trade value model:
 * how players age, how long they last, and what each rookie draft slot has
 * historically turned into. Run it locally about once a year, after the NFL
 * season, and commit the result:
 *
 *   php app/mfl/model-build.php
 *
 * Sources: Sleeper's season stat totals (2012 on), scored with this league's
 * settings, and nflverse's NFL draft list (github.com/nflverse/nflverse-data),
 * which supplies the draft order of each rookie class. Sleeper's own rookie
 * rankings only go back to about 2021, too few classes to learn from.
 */

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

define('APP_DIR', dirname(__DIR__));
require __DIR__ . '/sleeper.php';
require __DIR__ . '/values.php';   // mfl_replacement(), mfl_age_in()
ini_set('memory_limit', '1G');

const BUILD_FIRST_SEASON = 2012;
const BUILD_POSITIONS = ['QB', 'RB', 'WR', 'TE'];
const BUILD_DRAFT_CSV = 'https://github.com/nflverse/nflverse-data/releases/download/draft_picks/draft_picks.csv';
const BUILD_PICKS = 48;   // 4 rounds of 12
const BUILD_ROOKIE_YEARS = 4;

$lastSeason = (int) date('Y') - (date('n') >= 3 ? 1 : 2);   // the latest finished NFL season
$league = sleeper_get('/league/' . MFL_SEED_LEAGUE_ID);
$scoring = $league['scoring_settings'];
$players = sleeper_get_many(['/players/nfl'], 120)['/players/nfl'];
fwrite(STDERR, 'players: ' . count($players) . PHP_EOL);

// Every season's totals, scored the league's way.
$seasons = [];   // season => pid => ['pos', 'pts', 'gp', 'ppg', 'age']
$paths = [];
for ($y = BUILD_FIRST_SEASON; $y <= $lastSeason; $y++) $paths[$y] = "/stats/nfl/regular/$y";
$totals = sleeper_get_many(array_values($paths), 120);
foreach ($paths as $y => $path) {
    foreach ($totals[$path] ?? [] as $pid => $line) {
        $pos = $players[$pid]['position'] ?? '';
        $gp = (int) ($line['gp'] ?? 0);
        if (!in_array($pos, BUILD_POSITIONS, true) || $gp < 1) continue;
        $pts = 0.0;
        foreach ($line as $k => $v) if (is_numeric($v) && isset($scoring[$k])) $pts += $v * $scoring[$k];
        $seasons[$y][$pid] = ['pos' => $pos, 'pts' => $pts, 'gp' => $gp, 'ppg' => $pts / $gp,
                              'age' => mfl_age_in($players[$pid]['birth_date'] ?? null, $y)];
    }
    fwrite(STDERR, "$y: " . count($seasons[$y] ?? []) . PHP_EOL);
}

$repl = [];
foreach ($seasons as $y => $rows) {
    $byPos = [];
    foreach ($rows as $r) if ($r['gp'] >= 6) $byPos[$r['pos']][] = $r['ppg'];
    $repl[$y] = mfl_replacement($byPos);
}

// Aging: how a player's points per game change from one season to the next,
// by position and age. Every player with real playing time in both seasons
// counts, good year or bad, so a hot season cooling off (or a cold one
// recovering) washes out and what's left is age. Averaged as log ratios,
// weighted by games and scoring, so a depth player's jump from 3 to 12 points
// can't outweigh a starter's season.
// Staying power is separate: the share of fantasy-relevant players who play
// again the next season, and how many games those who do play.
$pairs = $stay = [];
foreach ($seasons as $y => $rows) {
    if (!isset($seasons[$y + 1])) continue;
    $next = $seasons[$y + 1];
    $schedule = $y + 1 >= 2021 ? 17 : 16;
    foreach ($rows as $pid => $r) {
        if ($r['age'] === null || $r['gp'] < 6) continue;
        $n = $next[$pid] ?? null;
        if ($n && $n['gp'] >= 6 && $r['ppg'] >= 3 && $n['ppg'] >= 3) {
            // Weighted toward players who matter in fantasy, using both seasons
            // equally so neither one's luck tilts the result.
            $pairs[$r['pos']][$r['age']][] = ['log' => log($n['ppg'] / $r['ppg']),
                'w' => 2 / (1 / $r['gp'] + 1 / $n['gp']) * sqrt($r['ppg'] * $n['ppg'])];
        }
        if ($r['ppg'] >= 0.6 * $repl[$y][$r['pos']]) {
            $stay[$r['pos']][$r['age']][] = ['next_gp' => $n['gp'] ?? 0, 'schedule' => $schedule];
        }
    }
}

$aging = $retain = $avail = $samples = $spread = [];
foreach (BUILD_POSITIONS as $pos) {
    $logSum = $wSum = $kept = $total = [];
    $gamesShare = $gamesN = 0;
    foreach ($pairs[$pos] ?? [] as $age => $list) {
        foreach ($list as $p) {
            $logSum[$age] = ($logSum[$age] ?? 0) + $p['w'] * $p['log'];
            $wSum[$age] = ($wSum[$age] ?? 0) + $p['w'];
        }
    }
    foreach ($stay[$pos] ?? [] as $age => $list) {
        foreach ($list as $p) {
            $total[$age] = ($total[$age] ?? 0) + 1;
            if ($p['next_gp'] >= 4) {
                $kept[$age] = ($kept[$age] ?? 0) + 1;
                $gamesShare += $p['next_gp'] / $p['schedule'];
                $gamesN++;
            }
        }
    }
    $avail[$pos] = round($gamesShare / max(1, $gamesN), 3);
    // Spread: how far a player's next season typically lands from what age
    // alone predicts (standard deviation of the log ratio, after age).
    $dev = $dw = 0;
    foreach ($pairs[$pos] ?? [] as $age => $list) {
        $mean = ($logSum[$age] ?? 0) / max(1e-9, $wSum[$age] ?? 0);
        foreach ($list as $p) { $dev += $p['w'] * ($p['log'] - $mean) ** 2; $dw += $p['w']; }
    }
    $spread[$pos] = round(sqrt($dev / max(1e-9, $dw)), 3);
    // Smooth across neighboring ages (1-2-1), since a single age can be thin.
    for ($age = 20; $age <= 40; $age++) {
        $l = $lw = $k = $t = 0;
        foreach ([-1 => 1, 0 => 2, 1 => 1] as $off => $w) {
            $a = $age + $off;
            $l += $w * ($logSum[$a] ?? 0);
            $lw += $w * ($wSum[$a] ?? 0);
            $k += $w * ($kept[$a] ?? 0);
            $t += $w * ($total[$a] ?? 0);
        }
        $samples[$pos][$age] = count($pairs[$pos][$age] ?? []);
        if ($t < 24 || $lw < 1200) continue;   // too few players at this age to say anything
        $aging[$pos][$age] = round(max(0.5, min(1.35, exp($l / $lw))), 3);
        $retain[$pos][$age] = round($k / $t, 3);
    }
    // Ages outside the data borrow from the nearest age that has enough.
    $known = array_keys($aging[$pos]);
    $lo = min($known); $hi = max($known);
    for ($age = 20; $age <= 40; $age++) {
        if (isset($aging[$pos][$age])) continue;
        $from = $age < $lo ? $lo : $hi;
        $aging[$pos][$age] = $age < $lo ? $aging[$pos][$from] : round(max(0.5, $aging[$pos][$from] - 0.03 * ($age - $from)), 3);
        $retain[$pos][$age] = $age < $lo ? $retain[$pos][$from] : round(max(0.2, $retain[$pos][$from] - 0.05 * ($age - $from)), 3);
    }
    ksort($aging[$pos]); ksort($retain[$pos]);
}

// Rookie classes, ordered the way dynasty rookie drafts go. NFL draft order is
// the starting point; these adjustments were fit to Sleeper's 2021-25 rookie
// rankings (rank agreement 0.68 on NFL order alone, 0.86 with them): 1QB
// leagues take quarterbacks much later, running backs much earlier, and tight
// ends a bit later than the NFL does.
$csv = file_get_contents(BUILD_DRAFT_CSV);
if (!$csv) { fwrite(STDERR, "Couldn't download the NFL draft list.\n"); exit(1); }
$lines = array_map('str_getcsv', explode("\n", trim($csv)));
$head = array_flip(array_shift($lines));

$norm = fn($s) => preg_replace('/\b(jr|sr|ii|iii|iv|v)\b/', '', preg_replace('/[^a-z ]/', '', strtolower((string) $s)));
$byName = [];
foreach ($players as $pid => $p) {
    if (!in_array($p['position'] ?? '', BUILD_POSITIONS, true)) continue;
    $byName[trim(preg_replace('/\s+/', ' ', $norm(($p['first_name'] ?? '') . ' ' . ($p['last_name'] ?? ''))))][] = $pid;
}

$lastClass = $lastSeason - BUILD_ROOKIE_YEARS + 1;
$classes = [];
$unmatched = 0;
foreach ($lines as $row) {
    $season = (int) ($row[$head['season']] ?? 0);
    $pos = $row[$head['position']] ?? '';
    if ($season < BUILD_FIRST_SEASON || $season > $lastClass || !in_array($pos, BUILD_POSITIONS, true)) continue;
    $name = trim(preg_replace('/\s+/', ' ', $norm($row[$head['pfr_player_name']])));
    // Same name and position, and Sleeper agrees on the rookie year (or played that year).
    $pid = null;
    foreach ($byName[$name] ?? [] as $cand) {
        $rookie = (int) ($players[$cand]['metadata']['rookie_year'] ?? 0);
        if (($players[$cand]['position'] ?? '') !== $pos) continue;
        if ($rookie === $season || isset($seasons[$season][$cand]) || isset($seasons[$season + 1][$cand])) { $pid = $cand; break; }
    }
    if (!$pid) { $unmatched++; }
    $overall = (int) $row[$head['pick']];
    $classes[$season][] = ['pid' => $pid, 'name' => $row[$head['pfr_player_name']], 'pos' => $pos,
                           'order' => match ($pos) { 'QB' => 3 * $overall + 24, 'RB' => 0.5 * $overall, 'TE' => 1.5 * $overall, default => $overall }];
}
fwrite(STDERR, "unmatched drafted players: $unmatched\n");

// What each slot produced in its first four seasons, in points above replacement.
$slotYears = array_fill(1, BUILD_PICKS, array_fill(0, BUILD_ROOKIE_YEARS, 0.0));
$examples = [];
foreach ($classes as $season => $list) {
    usort($list, fn($a, $b) => $a['order'] <=> $b['order']);
    foreach (array_slice($list, 0, BUILD_PICKS) as $i => $r) {
        $slot = $i + 1;
        $sum = 0;
        for ($k = 0; $k < BUILD_ROOKIE_YEARS; $k++) {
            $s = $seasons[$season + $k][$r['pid']] ?? null;
            $par = $s ? max(0, $s['ppg'] - $repl[$season + $k][$r['pos']]) * $s['gp'] : 0;
            $slotYears[$slot][$k] += $par / count($classes);
            $sum += $par;
        }
        $examples[$slot][] = [$season, $r['name'], $r['pos'], (int) round($sum)];
    }
}

// Smooth each slot with its neighbors, then make sure an earlier pick is never
// worth less than a later one (pool-adjacent-violators).
$totalsBySlot = array_map('array_sum', $slotYears);
$smooth = [];
foreach ($totalsBySlot as $slot => $v) {
    $win = [];
    for ($j = max(1, $slot - 2); $j <= min(BUILD_PICKS, $slot + 2); $j++) $win[] = $totalsBySlot[$j];
    $smooth[$slot] = array_sum($win) / count($win);
}
$blocks = [];
foreach ($smooth as $slot => $v) {
    $blocks[] = ['sum' => $v, 'n' => 1];
    while (count($blocks) > 1) {
        $b = $blocks[count($blocks) - 1]; $a = $blocks[count($blocks) - 2];
        if ($a['sum'] / $a['n'] >= $b['sum'] / $b['n']) break;
        array_splice($blocks, -2, 2, [['sum' => $a['sum'] + $b['sum'], 'n' => $a['n'] + $b['n']]]);
    }
}
$fitted = [];
foreach ($blocks as $b) for ($i = 0; $i < $b['n']; $i++) $fitted[] = $b['sum'] / $b['n'];
// Spread each slot's total over its four seasons the way rookies actually develop.
$shape = array_fill(0, BUILD_ROOKIE_YEARS, 0.0);
foreach ($slotYears as $years) foreach ($years as $k => $v) $shape[$k] += $v;
$shapeSum = array_sum($shape) ?: 1;
$picks = [];
foreach ($fitted as $i => $total) {
    $picks[$i + 1] = array_map(fn($s) => round($total * $s / $shapeSum, 1), $shape);
}
foreach ($examples as &$list) usort($list, fn($a, $b) => $b[0] <=> $a[0]);
unset($list);

$model = [
    'built_at' => date('Y-m-d'),
    'seasons' => [BUILD_FIRST_SEASON, $lastSeason],
    'rookie_classes' => [BUILD_FIRST_SEASON, $lastClass],
    'aging' => $aging,
    'retain' => $retain,
    'availability' => $avail,
    'spread' => $spread,
    'replacement' => $repl,
    'picks' => $picks,
    'pick_examples' => $examples,
];
file_put_contents(__DIR__ . '/model.json', json_encode($model, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n");
fwrite(STDERR, 'wrote ' . __DIR__ . "/model.json\n");
