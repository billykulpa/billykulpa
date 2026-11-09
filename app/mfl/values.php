<?php
/**
 * Trade values, two ways.
 *
 * Model: points above a replacement starter, this season and the next four,
 * from the player's current scoring, how players at the position age and how
 * long they last (model.json, built by model-build.php). Picks are worth what
 * their draft slot has historically produced.
 *
 * Market: FantasyCalc's dynasty values, which come from real trades in leagues
 * like this one (12 teams, 1 QB, PPR). It's an unofficial feed, so the model
 * works without it.
 *
 * mfl_sync_values() refreshes the stored player values once a day from the
 * sync. Pick values are cheap and are worked out on each request.
 */

require_once __DIR__ . '/sleeper.php';

/** Seasons valued: this one (what's left of it) and the next four. */
const MFL_VALUE_YEARS = 5;
/**
 * How much each of those seasons counts, nearest first. This is the balanced
 * default; the trade machine lets an owner lean toward now or later.
 */
const MFL_VALUE_WEIGHTS = [1.0, 0.85, 0.7, 0.55, 0.4];
const MFL_VALUE_POSITIONS = ['QB', 'RB', 'WR', 'TE'];
const MFL_MARKET_URL = 'https://api.fantasycalc.com/values/current?isDynasty=true&numQbs=1&numTeams=12&ppr=1';
const MFL_LINEUP_TEAMS = 12;

function mfl_model(): array
{
    static $model = null;
    return $model ??= json_decode((string) file_get_contents(__DIR__ . '/model.json'), true) ?: [];
}

/**
 * Replacement level: points per game of the best players who wouldn't start
 * in a 12-team league with this lineup (QB, 2 RB, 2 WR, TE, FLEX).
 * $ppgByPos is [position => [ppg, ...]].
 */
function mfl_replacement(array $ppgByPos, int $teams = MFL_LINEUP_TEAMS): array
{
    foreach (MFL_VALUE_POSITIONS as $p) {
        $ppgByPos[$p] ??= [];
        rsort($ppgByPos[$p]);
    }
    $taken = ['QB' => $teams, 'RB' => 2 * $teams, 'WR' => 2 * $teams, 'TE' => $teams];
    // FLEX goes to whoever is best among the next RB, WR and TE.
    for ($i = 0; $i < $teams; $i++) {
        $best = null;
        foreach (['RB', 'WR', 'TE'] as $p) {
            if ($best === null || ($ppgByPos[$p][$taken[$p]] ?? 0) > ($ppgByPos[$best][$taken[$best]] ?? 0)) $best = $p;
        }
        $taken[$best]++;
    }
    $repl = [];
    foreach ($taken as $p => $n) {
        // Average a few players past the cutoff, so one fluke doesn't set the bar.
        $slice = array_slice($ppgByPos[$p], $n, 3);
        $repl[$p] = $slice ? array_sum($slice) / count($slice) : 0;
    }
    return $repl;
}

/**
 * Expected points per game above replacement when the true rate is uncertain:
 * E[max(0, X - r)] for X normal with mean $mean and spread $sd. A player just
 * under replacement still has a real chance of being a starter, which a flat
 * cutoff would value at zero.
 */
function mfl_expected_above(float $mean, float $repl, float $sd): float
{
    if ($sd <= 0) return max(0, $mean - $repl);
    $d = ($mean - $repl) / $sd;
    $cdf = 0.5 * (1 + mfl_erf($d / M_SQRT2));
    $pdf = exp(-$d * $d / 2) / sqrt(2 * M_PI);
    return max(0, ($mean - $repl) * $cdf + $sd * $pdf);
}

/** Error function (Abramowitz and Stegun 7.1.26, good to about 1e-7). */
function mfl_erf(float $x): float
{
    $sign = $x < 0 ? -1 : 1;
    $x = abs($x);
    $t = 1 / (1 + 0.3275911 * $x);
    $y = 1 - (((((1.061405429 * $t - 1.453152027) * $t) + 1.421413741) * $t - 0.284496736) * $t + 0.254829592) * $t * exp(-$x * $x);
    return $sign * $y;
}

/** Age in whole years on September 1 of a season. */
function mfl_age_in(?string $birth, int $season): ?int
{
    if (!$birth || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $birth)) return null;
    return (int) (new DateTime($birth))->diff(new DateTime("$season-09-01"))->y;
}

/** Weighted value of a list of per-season numbers, nearest season first. */
function mfl_weighted(array $bySeason, array $weights = MFL_VALUE_WEIGHTS): float
{
    $sum = 0.0;
    foreach (array_values($bySeason) as $i => $v) $sum += $v * ($weights[$i] ?? 0);
    return $sum;
}

/**
 * The season being valued and the fantasy weeks still to play in it. Between
 * seasons that's next season, all of it.
 */
function mfl_value_window(): array
{
    $s = db()->query('SELECT * FROM mfl_seasons ORDER BY season DESC LIMIT 1')->fetch();
    $rounds = (int) ceil(log(max(2, (int) $s['playoff_teams']), 2));
    $last = (int) $s['playoff_week_start'] + $rounds - 1;
    if ($s['status'] === 'complete') {
        return ['season' => (int) $s['season'] + 1, 'from' => 1, 'to' => $last, 'league_id' => $s['league_id'], 'live' => false];
    }
    $from = in_array($s['status'], ['in_season', 'post_season'], true) ? (int) $s['last_scored_week'] + 1 : 1;
    return ['season' => (int) $s['season'], 'from' => $from, 'to' => $last, 'league_id' => $s['league_id'], 'live' => $from > 1];
}

/**
 * [player_id => franchise_id] for the latest season's rosters. The same as
 * mfl_current_rosters(), which the sync can't load without the whole site.
 */
function mfl_value_rosters(): array
{
    $map = [];
    $stmt = db()->query('SELECT franchise_id, roster_json FROM mfl_team_seasons WHERE season = (SELECT MAX(season) FROM mfl_seasons)');
    foreach ($stmt as $t) {
        foreach (json_decode((string) $t['roster_json'], true) ?: [] as $pid) $map[(string) $pid] = (int) $t['franchise_id'];
    }
    return $map;
}

/** Score a Sleeper stat line with the league's settings. */
function mfl_score_line(array $line, array $scoring): float
{
    $pts = 0.0;
    foreach ($line as $k => $v) if (is_numeric($v) && isset($scoring[$k])) $pts += $v * $scoring[$k];
    return $pts;
}

function mfl_value_meta(string $name): ?array
{
    $stmt = db()->prepare('SELECT value_json FROM mfl_value_meta WHERE name = ?');
    $stmt->execute([$name]);
    $json = $stmt->fetchColumn();
    return $json === false ? null : json_decode((string) $json, true);
}

function mfl_set_value_meta(string $name, array $value): void
{
    $stmt = db()->prepare('INSERT INTO mfl_value_meta (name, value_json, updated_at) VALUES (?, ?, ?)
                           ON DUPLICATE KEY UPDATE value_json = VALUES(value_json), updated_at = VALUES(updated_at)');
    $stmt->execute([$name, json_encode($value), date('Y-m-d H:i:s')]);
}

/** FantasyCalc's values, or NULL if the feed is down. */
function mfl_market_fetch(): ?array
{
    $ch = curl_init(MFL_MARKET_URL);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30, CURLOPT_CONNECTTIMEOUT => 10,
                            CURLOPT_ENCODING => '', CURLOPT_USERAGENT => 'mfl.billykulpa.com']);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    $data = $code === 200 && is_string($body) ? json_decode($body, true) : null;
    if (!is_array($data) || !$data) return null;
    $players = $picks = [];
    foreach ($data as $row) {
        $p = $row['player'] ?? [];
        if (($p['position'] ?? '') === 'PICK') $picks[$p['name']] = (int) $row['value'];
        elseif (!empty($p['sleeperId'])) $players[(string) $p['sleeperId']] = ['value' => (int) $row['value'], 'trend' => (int) ($row['trend30Day'] ?? 0)];
    }
    return ['players' => $players, 'picks' => $picks];
}

/**
 * Every player's model value as of a point in a season.
 *
 * Live (the daily run) uses Sleeper's projection for each remaining week, so
 * byes and injuries count. As of an old trade it uses only the projection for
 * the trade's own week, so an injury that happened later can't leak into what
 * the trade looked like then.
 *
 * $o: season; from, to (fantasy weeks left to play); through (last week of
 * results to count); scoring; players (Sleeper's full list); weekly (live
 * mode); before (only rookie drafts started before this time count).
 * Returns ['players' => [pid => row], 'replacement' => [pos => ppg]].
 */
function mfl_compute_values(array $o): array
{
    $model = mfl_model();
    if (!$model) throw new RuntimeException('model.json is missing.');
    $season = (int) $o['season'];
    $weeksLeft = max(0, $o['to'] - $o['from'] + 1);

    // Projections: every remaining week when live, otherwise just the next one
    // or two (the second covers a bye).
    $proj = [];
    $last = $o['weekly'] ? $o['to'] : min($o['to'], $o['from'] + 1);
    $paths = [];
    for ($wk = $o['from']; $wk <= $last; $wk++) $paths[$wk] = "/projections/nfl/regular/$season/$wk";
    if ($paths) {
        $feeds = sleeper_get_many(array_values($paths), 45);
        foreach ($paths as $wk => $path) {
            foreach ($feeds[$path] ?? [] as $pid => $line) {
                $pts = mfl_score_line((array) $line, $o['scoring']);
                if ($pts > 0) $proj[(string) $pid][$wk] = $pts;
            }
        }
        unset($feeds);
    }

    // Results: this season up to the cutoff, and all of last season.
    $actual = [];
    $stmt = db()->prepare('SELECT player_id, season, COUNT(*) games, SUM(points) pts FROM mfl_player_weeks
                            WHERE ((season = ? AND week <= ?) OR season = ?) AND points <> 0 GROUP BY player_id, season');
    $stmt->execute([$season, (int) $o['through'], $season - 1]);
    foreach ($stmt as $r) $actual[(string) $r['player_id']][(int) $r['season']] = ['games' => (int) $r['games'], 'pts' => (float) $r['pts']];

    $rostered = mfl_value_rosters();
    // Each player's scoring rate: Sleeper's projection, this season's points
    // per game and last season's, blended by how much each has to say.
    $players = [];
    foreach ($o['players'] as $pid => $p) {
        $pid = (string) $pid;
        $pos = $p['position'] ?? '';
        if (!in_array($pos, MFL_VALUE_POSITIONS, true)) continue;
        $team = (string) ($p['team'] ?? '');
        $now = $actual[$pid][$season] ?? null;
        $prev = $actual[$pid][$season - 1] ?? null;
        if ($team === '' && !isset($rostered[$pid]) && !$now && !$prev) continue;
        $parts = [];
        if (!empty($proj[$pid])) $parts[] = [array_sum($proj[$pid]) / count($proj[$pid]), 0.5];
        if ($now) $parts[] = [$now['pts'] / $now['games'], 0.35 * min(1, $now['games'] / 6)];
        if ($prev) $parts[] = [$prev['pts'] / $prev['games'], 0.15 * min(1, $prev['games'] / 8)];
        $weight = array_sum(array_column($parts, 1));
        $players[$pid] = ['pid' => $pid, 'pos' => $pos, 'team' => $team,
                          'ppg' => $weight ? array_sum(array_map(fn($x) => $x[0] * $x[1], $parts)) / $weight : 0.0,
                          'name' => trim($p['full_name'] ?? (($p['first_name'] ?? '') . ' ' . ($p['last_name'] ?? ''))),
                          'birth' => $p['birth_date'] ?? null, 'injury' => $o['weekly'] ? (string) ($p['injury_status'] ?? '') : ''];
    }

    $byPos = [];
    foreach ($players as $p) if ($p['ppg'] > 0 && ($p['team'] !== '' || !$o['weekly'])) $byPos[$p['pos']][] = $p['ppg'];
    $repl = mfl_replacement($byPos);

    // League rookie picks: a young player is still partly the pick he was,
    // since one partial season says little about his future. That weight is
    // 50% in his rookie season and 25% the year after.
    $rookies = [];
    $stmt = db()->prepare('SELECT p.player_id, p.pick_no, d.season, d.started_at FROM mfl_draft_picks p JOIN mfl_drafts d ON d.id = p.draft_id
                            WHERE d.rounds <= 6 AND d.season BETWEEN ? AND ?');
    $stmt->execute([$season - 1, $season]);
    foreach ($stmt as $r) {
        if (!empty($o['before']) && (!$r['started_at'] || $r['started_at'] >= $o['before'])) continue;
        $rookies[(string) $r['player_id']] = ['pick' => (int) $r['pick_no'], 'draft' => (int) $r['season']];
    }
    $restOfSeason = $o['to'] > 0 ? $weeksLeft / $o['to'] : 0;

    $out = [];
    foreach ($players as $pid => $p) {
        $pos = $p['pos'];
        $par = [];
        if ($o['weekly']) {
            $ros = 0.0;
            foreach ($proj[$pid] ?? [] as $wk => $pts) $ros += max(0, $pts - $repl[$pos]);
        } else {
            $ros = $weeksLeft * ($model['availability'][$pos] ?? 0.82) * max(0, $p['ppg'] - $repl[$pos]);
        }
        $par[] = $ros;
        // Later seasons: age the scoring rate, and expect fewer games the
        // older a player gets and the further out we look. The further out,
        // the wider the range of outcomes, which is where young players'
        // upside comes from.
        $ppg = $p['ppg'];
        $alive = $p['team'] === '' && $o['weekly'] ? 0.5 : 1.0;
        for ($k = 1; $k < MFL_VALUE_YEARS; $k++) {
            $age = max(20, min(40, mfl_age_in($p['birth'], $season + $k - 1) ?? 26));
            $ppg *= $model['aging'][$pos][$age] ?? 0.9;
            $alive *= $model['retain'][$pos][$age] ?? 0.8;
            $games = 17 * ($model['availability'][$pos] ?? 0.82) * $alive;
            $sd = $ppg * min(0.6, ($model['spread'][$pos] ?? 0.3) * sqrt($k));
            $par[] = mfl_expected_above($ppg, $repl[$pos], $sd) * $games;
        }
        if (isset($rookies[$pid])) {
            $r = $rookies[$pid];
            $blend = [0 => 0.5, 1 => 0.25][$season - $r['draft']] ?? 0;
            $years = $model['picks'][(string) $r['pick']] ?? [];
            if ($blend && $years) {
                for ($offset = 0; $offset < MFL_VALUE_YEARS; $offset++) {
                    // Past the four seasons the history covers, the slot holds its last value.
                    $v = $years[$season + $offset - $r['draft']] ?? end($years);
                    $slot = $offset === 0 ? $v * $restOfSeason : $v;
                    $par[$offset] = (1 - $blend) * $par[$offset] + $blend * $slot;
                }
            }
        }
        $out[$pid] = $p + ['par' => $par, 'value' => mfl_weighted($par)];
    }
    return ['players' => $out, 'replacement' => $repl];
}

/**
 * Recompute every player's value. Runs from the sync at most once a day,
 * since it downloads Sleeper's full player list, which Sleeper asks to fetch
 * no more than daily. Also saves today's market prices and values any new
 * trade as it looked when it happened. Returns the number of players valued,
 * or 0 if skipped.
 */
function mfl_sync_values(bool $force = false): int
{
    $run = mfl_value_meta('run');
    // Within a day of the last run, skip, unless a trade is still waiting to
    // be valued (a new trade, or the first run after scorecards shipped).
    $waiting = (int) db()->query("SELECT COUNT(*) FROM mfl_transactions t WHERE t.type = 'trade' AND t.status = 'complete'
                                  AND NOT EXISTS (SELECT 1 FROM mfl_trade_values v WHERE v.trade_id = t.id)")->fetchColumn();
    if (!$force && !$waiting && $run && strtotime($run['computed_at']) > time() - 20 * 3600) return 0;

    ini_set('memory_limit', '512M');
    $w = mfl_value_window();
    $season = $w['season'];
    $league = sleeper_get('/league/' . $w['league_id']);
    $scoring = $league['scoring_settings'] ?? [];
    $all = sleeper_get_many(['/players/nfl'], 90)['/players/nfl'];
    $through = $w['live'] ? $w['from'] - 1 : ($w['from'] > 1 ? $w['from'] - 1 : 0);
    $computed = mfl_compute_values(['season' => $season, 'from' => $w['from'], 'to' => $w['to'], 'through' => $through,
                                    'scoring' => $scoring, 'players' => $all, 'weekly' => true]);
    $rostered = mfl_value_rosters();
    $market = mfl_market_fetch();

    $rows = [];
    foreach ($computed['players'] as $pid => $p) {
        $m = $market['players'][$pid] ?? null;
        if ($p['team'] === '' && !isset($rostered[$pid])) continue;
        if ($p['value'] < 1 && !$m && !isset($rostered[$pid])) continue;
        $rows[] = [
            'player_id' => $pid, 'name' => $p['name'], 'position' => $p['pos'], 'nfl_team' => $p['team'],
            'birth_date' => $p['birth'] && preg_match('/^\d{4}-\d{2}-\d{2}$/', $p['birth']) ? $p['birth'] : null,
            'injury' => substr($p['injury'], 0, 20), 'ppg' => round($p['ppg'], 2),
            'par_json' => json_encode(array_map(fn($v) => round($v, 1), $p['par'])),
            'model_value' => round($p['value'], 1),
            'market_value' => $m['value'] ?? null, 'market_trend' => $m['trend'] ?? null,
        ];
    }

    db()->beginTransaction();
    db()->exec('DELETE FROM mfl_values');
    foreach (array_chunk($rows, 200) as $chunk) {
        $cols = array_keys($chunk[0]);
        $stmt = db()->prepare('INSERT INTO mfl_values (' . implode(', ', $cols) . ') VALUES '
            . implode(', ', array_fill(0, count($chunk), '(' . implode(', ', array_fill(0, count($cols), '?')) . ')')));
        $stmt->execute(array_merge(...array_map('array_values', $chunk)));
    }
    mfl_set_value_meta('run', [
        'computed_at' => date('Y-m-d H:i:s'), 'season' => $season, 'weeks' => [$w['from'], $w['to']],
        'replacement' => array_map(fn($v) => round($v, 2), $computed['replacement']), 'market' => $market !== null,
    ]);
    if ($market) {
        // A failed market fetch keeps yesterday's pick prices rather than none.
        mfl_set_value_meta('market_picks', $market['picks']);
        // Today's prices, so a trade made today can later show its market value at the time.
        $snap = [];
        foreach ($market['players'] as $pid => $m) $snap[] = [date('Y-m-d'), "p$pid", $m['value']];
        foreach ($market['picks'] as $name => $v) $snap[] = [date('Y-m-d'), "n$name", $v];
        foreach (array_chunk($snap, 300) as $chunk) {
            db()->prepare('INSERT IGNORE INTO mfl_market_snapshots (day, item, value) VALUES '
                . implode(',', array_fill(0, count($chunk), '(?,?,?)')))->execute(array_merge(...$chunk));
        }
    }
    db()->commit();

    // Old trades as they looked at the time. Done once per trade, and a
    // failure here never costs today's values.
    try {
        mfl_sync_trade_values($all, $scoring);
    } catch (Throwable $e) {
        if (db()->inTransaction()) db()->rollBack();
    }
    return count($rows);
}

/** Stored player values: [player_id => row], with par as an array. */
function mfl_player_values(): array
{
    static $values = null;
    if ($values !== null) return $values;
    $values = [];
    foreach (db()->query('SELECT * FROM mfl_values') as $r) {
        $r['par'] = json_decode((string) $r['par_json'], true) ?: [];
        $values[$r['player_id']] = $r;
    }
    return $values;
}

/** One season's regular-season results so far: [franchise_id => wins, pf, games, allplay]. */
function mfl_season_form(int $season, int $before): array
{
    $stmt = db()->prepare('SELECT week, franchise_id, points, result FROM mfl_matchups
                            WHERE season = ? AND week < ? AND result IS NOT NULL');
    $stmt->execute([$season, $before]);
    $weeks = [];
    $form = [];
    foreach ($stmt as $g) {
        $fid = (int) $g['franchise_id'];
        $form[$fid] ??= ['wins' => 0.0, 'pf' => 0.0, 'games' => 0, 'ap_w' => 0.0, 'ap_n' => 0];
        $form[$fid]['wins'] += ['W' => 1, 'T' => 0.5][$g['result']] ?? 0;
        $form[$fid]['pf'] += (float) $g['points'];
        $form[$fid]['games']++;
        $weeks[(int) $g['week']][$fid] = (float) $g['points'];
    }
    foreach ($weeks as $scores) {
        foreach ($scores as $fid => $pts) {
            foreach ($scores as $other => $q) {
                if ($other === $fid) continue;
                $form[$fid]['ap_w'] += $pts > $q ? 1 : ($pts == $q ? 0.5 : 0);
                $form[$fid]['ap_n']++;
            }
        }
    }
    return ['teams' => $form, 'weeks' => count($weeks)];
}

/** Final standings order, best first: wins, then points for. */
function mfl_standings_order(array $form): array
{
    $ids = array_keys($form);
    usort($ids, fn($a, $b) => [$form[$b]['wins'], $form[$b]['pf']] <=> [$form[$a]['wins'], $form[$a]['pf']]);
    return $ids;
}

/**
 * Where each team will pick in the rookie draft that follows a season, as
 * [franchise_id => [slot => probability]], or NULL before the season has a
 * result. This league drafts in reverse order of finish: teams that miss the
 * playoffs by standings (worst record first), then playoff teams by how far
 * they got, so the champion picks last.
 *
 * A finished season gives the exact order. One in progress is simulated: the
 * rest of the regular season from each team's record against every team,
 * every week, then the bracket. $through counts only results up to that week,
 * for valuing an old trade as it looked at the time.
 */
function mfl_draft_slot_odds(int $season, ?int $through = null): ?array
{
    static $cache = [];
    $key = $season . '-' . ($through ?? 'all');
    if (array_key_exists($key, $cache)) return $cache[$key];
    $stmt = db()->prepare('SELECT * FROM mfl_seasons WHERE season = ?');
    $stmt->execute([$season]);
    $s = $stmt->fetch();
    if (!$s) return $cache[$key] = null;
    $regular = (int) $s['playoff_week_start'] - 1;
    $cut = $through === null ? $regular + 1 : min($regular, $through) + 1;
    $f = mfl_season_form($season, $cut);
    $form = $f['teams'];
    $n = count($form);
    $playoffs = (int) $s['playoff_teams'];
    if (!$f['weeks'] || $n < 2) return $cache[$key] = null;

    $odds = array_fill_keys(array_keys($form), array_fill(1, $n, 0.0));
    // Finished: the bracket's placement games give places 1 to 6.
    if ($s['status'] === 'complete' && $through === null) {
        $place = [];
        $stmt = db()->prepare("SELECT winner_franchise_id w, loser_franchise_id l, placement FROM mfl_brackets
                                WHERE season = ? AND bracket = 'winners' AND placement IS NOT NULL");
        $stmt->execute([$season]);
        foreach ($stmt as $b) {
            if ($b['w']) $place[(int) $b['w']] = (int) $b['placement'];
            if ($b['l']) $place[(int) $b['l']] = (int) $b['placement'] + 1;
        }
        if (count($place) === $playoffs) {
            $next = $playoffs;
            foreach (mfl_standings_order($form) as $fid) if (!isset($place[$fid])) $place[$fid] = ++$next;
            foreach ($place as $fid => $p) $odds[$fid][$n + 1 - $p] = 1.0;
            return $cache[$key] = $odds;
        }
    }

    // In progress: simulate. Early-season form is shrunk toward average, since
    // a few weeks say little.
    $remaining = max(0, $regular - $f['weeks']);
    $trust = $f['weeks'] / ($f['weeks'] + 4);
    $p = $ppg = [];
    $avgPpg = array_sum(array_column($form, 'pf')) / max(1, array_sum(array_column($form, 'games')));
    foreach ($form as $fid => $t) {
        $ap = $t['ap_n'] ? $t['ap_w'] / $t['ap_n'] : 0.5;
        $p[$fid] = min(0.95, max(0.05, 0.5 + $trust * ($ap - 0.5)));
        $ppg[$fid] = $t['games'] ? $t['pf'] / $t['games'] : $avgPpg;
    }
    // Head to head, from each team's chance against an average one (log5).
    $beats = fn($a, $b) => mt_rand() / mt_getrandmax() < $p[$a] * (1 - $p[$b]) / ($p[$a] * (1 - $p[$b]) + $p[$b] * (1 - $p[$a]));
    // The same answer all day, so pages don't flicker between loads.
    mt_srand(crc32("$key-" . date('Y-m-d')));
    $runs = 3000;
    for ($i = 0; $i < $runs; $i++) {
        $final = [];
        foreach ($form as $fid => $t) {
            $wins = $t['wins'];
            for ($g = 0; $g < $remaining; $g++) if (mt_rand() / mt_getrandmax() < $p[$fid]) $wins++;
            $final[$fid] = ['wins' => $wins, 'pf' => $t['pf'] + $remaining * $ppg[$fid] * (0.85 + 0.3 * mt_rand() / mt_getrandmax())];
        }
        $seeds = mfl_standings_order($final);
        $place = [];
        if ($playoffs === 6 && $n >= 6) {
            // Seeds 1 and 2 get byes; 3 plays 6 and 4 plays 5, without reseeding.
            [$s1, $s2, $s3, $s4, $s5, $s6] = array_slice($seeds, 0, 6);
            [$w36, $l36] = $beats($s3, $s6) ? [$s3, $s6] : [$s6, $s3];
            [$w45, $l45] = $beats($s4, $s5) ? [$s4, $s5] : [$s5, $s4];
            [$wa, $la] = $beats($s1, $w45) ? [$s1, $w45] : [$w45, $s1];
            [$wb, $lb] = $beats($s2, $w36) ? [$s2, $w36] : [$w36, $s2];
            [$place[$wa], $place[$wb]] = $beats($wa, $wb) ? [1, 2] : [2, 1];
            [$place[$la], $place[$lb]] = $beats($la, $lb) ? [3, 4] : [4, 3];
            [$place[$l36], $place[$l45]] = $beats($l36, $l45) ? [5, 6] : [6, 5];
        } else {
            // Other bracket sizes: playoff teams finish in seed order.
            foreach (array_slice($seeds, 0, $playoffs) as $k => $fid) $place[$fid] = $k + 1;
        }
        $next = count($place);
        foreach ($seeds as $fid) if (!isset($place[$fid])) $place[$fid] = ++$next;
        foreach ($place as $fid => $pl) $odds[$fid][$n + 1 - $pl] += 1 / $runs;
    }
    return $cache[$key] = $odds;
}

/** A pick's points above replacement for each valued season, starting with $season. */
function mfl_pick_par(int $draft, int $round, array $slotOdds, int $season): array
{
    $model = mfl_model();
    $n = count($slotOdds);
    $par = array_fill(0, MFL_VALUE_YEARS, 0.0);
    foreach ($slotOdds as $slot => $p) {
        if ($p <= 0) continue;
        foreach ($model['picks'][(string) (($round - 1) * $n + $slot)] ?? [] as $i => $v) {
            $offset = $draft - $season + $i;
            if ($offset >= 0 && $offset < MFL_VALUE_YEARS) $par[$offset] += $p * $v;
        }
    }
    return $par;
}

/** FantasyCalc's name for a pick: "2027 1st (Early)" when its slot is known well enough, else "2027 1st". */
function mfl_pick_market_name(int $draft, int $round, ?array $slotOdds, array $prices): string
{
    $ordinal = [1 => '1st', 2 => '2nd', 3 => '3rd', 4 => '4th', 5 => '5th'];
    $base = "$draft " . ($ordinal[$round] ?? "{$round}th");
    if (!$slotOdds) return $base;
    $n = count($slotOdds);
    $expected = array_sum(array_map(fn($s, $p) => $s * $p, array_keys($slotOdds), $slotOdds));
    $tier = $expected <= $n / 3 ? ' (Early)' : ($expected <= 2 * $n / 3 ? ' (Mid)' : ' (Late)');
    return isset($prices[$base . $tier]) ? $base . $tier : $base;
}

/**
 * Every tradable future pick with its owner and both values.
 * Returns rows of [season, round, original, owner, near, slot_odds,
 * expected_slot, par (per valued season), model_value, market_value, market_name].
 */
function mfl_pick_values(): array
{
    static $rows = null;
    if ($rows !== null) return $rows;
    $rows = [];
    $run = mfl_value_meta('run');
    if (!mfl_model() || !$run) return $rows;
    $season = (int) $run['season'];
    $marketPicks = mfl_value_meta('market_picks') ?? [];

    $latest = db()->query("SELECT d.season, d.rounds, d.status FROM mfl_drafts d WHERE d.rounds <= 6 ORDER BY d.season DESC LIMIT 1")->fetch();
    $rounds = (int) ($latest['rounds'] ?? 4);
    // This year's draft is tradable until it's done.
    $first = ($latest && (int) $latest['season'] === $season && $latest['status'] === 'complete') ? $season + 1 : $season;
    $teams = array_map('intval', db()->query('SELECT id FROM mfl_franchises')->fetchAll(PDO::FETCH_COLUMN));
    $n = count($teams);
    $traded = [];
    foreach (db()->query('SELECT * FROM mfl_traded_picks') as $t) {
        $traded["{$t['pick_season']}-{$t['round']}-{$t['original_franchise_id']}"] = (int) $t['current_franchise_id'];
    }
    $uniform = array_fill(1, $n, 1 / $n);

    for ($draft = $first; $draft <= $first + 2; $draft++) {
        // A draft's order comes from the season before it, once that has results.
        $odds = mfl_draft_slot_odds($draft - 1);
        foreach ($teams as $fid) {
            $slotOdds = $odds[$fid] ?? $uniform;
            $expected = array_sum(array_map(fn($s, $p) => $s * $p, array_keys($slotOdds), $slotOdds));
            for ($round = 1; $round <= $rounds; $round++) {
                $par = mfl_pick_par($draft, $round, $slotOdds, $season);
                $marketName = mfl_pick_market_name($draft, $round, $odds ? $slotOdds : null, $marketPicks);
                $rows[] = [
                    'season' => $draft, 'round' => $round, 'original' => $fid,
                    'owner' => $traded["$draft-$round-$fid"] ?? $fid,
                    'near' => $odds !== null, 'slot_odds' => $slotOdds, 'expected_slot' => $expected,
                    'par' => $par, 'model_value' => mfl_weighted($par),
                    'market_value' => $marketPicks[$marketName] ?? null, 'market_name' => $marketName,
                ];
            }
        }
    }
    return $rows;
}

/**
 * Value every trade that hasn't been yet, as it looked when it happened:
 * players by the model as of that week, picks by the draft order as it stood
 * then, and market prices from that day's snapshot if there is one (snapshots
 * start with this feature, so older trades have none).
 */
function mfl_sync_trade_values(array $all, array $scoring): void
{
    $done = array_flip(db()->query('SELECT DISTINCT trade_id FROM mfl_trade_values')->fetchAll(PDO::FETCH_COLUMN));
    $trades = array_filter(db()->query("SELECT * FROM mfl_transactions WHERE type = 'trade' AND status = 'complete'")->fetchAll(),
                           fn($t) => !isset($done[$t['id']]));
    if (!$trades) return;
    $seasons = [];
    foreach (db()->query('SELECT * FROM mfl_seasons') as $s) $seasons[(int) $s['season']] = $s;
    $n = (int) db()->query('SELECT COUNT(*) FROM mfl_franchises')->fetchColumn();
    $uniform = array_fill(1, $n, 1 / $n);
    $computed = [];

    foreach ($trades as $t) {
        $season = (int) $t['season'];
        $s = $seasons[$season] ?? null;
        if (!$s) continue;
        $at = $t['completed_at'] ?: $t['created_at'];
        $rounds = (int) ceil(log(max(2, (int) $s['playoff_teams']), 2));
        $lastWeek = (int) $s['playoff_week_start'] + $rounds - 1;
        // Before the NFL season (September) a trade sees the whole season ahead.
        $preseason = $at < "$season-09-01";
        $from = $preseason ? 1 : max(1, min($lastWeek, (int) $t['week']));
        $through = $from - 1;
        $key = "$season-$from";
        $computed[$key] ??= mfl_compute_values(['season' => $season, 'from' => $from, 'to' => $lastWeek, 'through' => $through,
                                                'scoring' => $scoring, 'players' => $all, 'weekly' => false, 'before' => $at]);
        $players = $computed[$key]['players'];

        $stmt = db()->prepare('SELECT item, value FROM mfl_market_snapshots WHERE day = ?');
        $stmt->execute([substr($at, 0, 10)]);
        $prices = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        $rows = [];
        foreach (array_keys(json_decode((string) $t['adds_json'], true) ?: []) as $pid) {
            $pid = (string) $pid;
            $rows[] = [$t['id'], "p$pid", round($players[$pid]['value'] ?? 0, 1), $prices["p$pid"] ?? null];
        }
        foreach (json_decode((string) $t['picks_json'], true) ?: [] as $pick) {
            $draft = (int) $pick['season'];
            $round = (int) $pick['round'];
            $original = (int) $pick['roster_id'];
            // The order then: settled if the season before the draft was over, a
            // simulation from the results so far if it was under way.
            $basis = $draft - 1;
            $odds = $basis < $season ? mfl_draft_slot_odds($basis) : ($basis === $season && !$preseason ? mfl_draft_slot_odds($basis, $through) : null);
            $slotOdds = $odds[$original] ?? $uniform;
            $value = mfl_weighted(mfl_pick_par($draft, $round, $slotOdds, $season));
            $names = [];
            foreach ($prices as $item => $v) if ($item[0] === 'n') $names[substr($item, 1)] = $v;
            $name = mfl_pick_market_name($draft, $round, $odds ? $slotOdds : null, $names);
            $rows[] = [$t['id'], "k$draft-$round-$original", round($value, 1), $names[$name] ?? null];
        }
        if (!$rows) continue;
        db()->prepare('INSERT IGNORE INTO mfl_trade_values (trade_id, item, model_then, market_then) VALUES '
            . implode(',', array_fill(0, count($rows), '(?,?,?,?)')))->execute(array_merge(...$rows));
    }
}

/** "1.03–1.07": the likely range of a pick, or just the round when it's anyone's guess. */
function mfl_pick_range(array $pick): string
{
    if (!$pick['near']) return 'Round ' . $pick['round'];
    $odds = $pick['slot_odds'];
    $lo = $hi = null;
    $cum = 0;
    foreach ($odds as $slot => $p) {
        $cum += $p;
        if ($lo === null && $cum >= 0.15) $lo = $slot;
        if ($hi === null && $cum >= 0.85) $hi = $slot;
    }
    $fmt = fn($s) => $pick['round'] . '.' . str_pad((string) $s, 2, '0', STR_PAD_LEFT);
    return $lo === $hi ? $fmt($lo) : $fmt($lo) . '–' . $fmt($hi);
}

/** Players who were taken near this pick in past rookie drafts, most recent first. */
function mfl_pick_examples(array $pick, int $limit = 4): array
{
    $model = mfl_model();
    $n = count($pick['slot_odds']);
    $slot = (int) round($pick['expected_slot']);
    $out = [];
    foreach ($model['pick_examples'][(string) (($pick['round'] - 1) * $n + $slot)] ?? [] as $e) {
        $out[] = ['season' => $e[0], 'name' => $e[1], 'position' => $e[2], 'par' => $e[3]];
        if (count($out) >= $limit) break;
    }
    return $out;
}

/* ---- The Trade Values page ---- */

const MFL_VALUE_SORTS = ['name', 'age', 'model', 'market', 'lean', 'ppg', 's0', 's1', 's2', 's3', 's4'];

/** Read and clean the page's filters. team: '' = every rostered player, 'fa' = free agents, or a franchise id. */
function mfl_values_filters(array $q): array
{
    $sort = in_array($q['sort'] ?? '', MFL_VALUE_SORTS, true) ? $q['sort'] : 'model';
    $team = (string) ($q['team'] ?? '');
    $positions = array_values(array_intersect(MFL_VALUE_POSITIONS, (array) ($q['pos'] ?? [])));
    return [
        'view' => ($q['view'] ?? '') === 'picks' ? 'picks' : 'players',
        'pos' => $positions ?: MFL_VALUE_POSITIONS,
        'team' => $team === 'fa' || ctype_digit($team) ? $team : '',
        'sort' => $sort,
        'dir' => in_array($q['dir'] ?? '', ['asc', 'desc'], true) ? $q['dir'] : (in_array($sort, ['name', 'age'], true) ? 'asc' : 'desc'),
    ];
}

function mfl_values_url(array $f, array $change = []): string
{
    $f = $change + $f;
    $defaultDir = in_array($f['sort'], ['name', 'age'], true) ? 'asc' : 'desc';
    $q = array_filter([
        'view' => $f['view'] === 'picks' ? 'picks' : '',
        'pos' => $f['pos'] === MFL_VALUE_POSITIONS ? [] : $f['pos'],
        'team' => $f['team'],
        'sort' => $f['sort'] === 'model' ? '' : $f['sort'],
        'dir' => $f['dir'] === $defaultDir ? '' : $f['dir'],
    ]);
    return '/trades/values' . ($q ? '?' . http_build_query($q) : '');
}

function mfl_values_th(array $f, string $key, string $label, string $class = 'num'): string
{
    $active = $f['sort'] === $key;
    $first = in_array($key, ['name', 'age'], true) ? 'asc' : 'desc';
    $dir = $active ? ($f['dir'] === 'asc' ? 'desc' : 'asc') : $first;
    $aria = $active ? ' aria-sort="' . ($f['dir'] === 'asc' ? 'ascending' : 'descending') . '"' : '';
    $arrow = $active ? ($f['dir'] === 'asc' ? ' ▲' : ' ▼') : '';
    return '<th scope="col" class="' . esc($class) . '"' . $aria . '><a class="sort-link" href="'
        . esc(mfl_values_url($f, ['sort' => $key, 'dir' => $dir])) . '">' . esc($label) . $arrow . '</a></th>';
}

/** Age today, to one decimal. */
function mfl_age_now(?string $birth): ?float
{
    if (!$birth) return null;
    return round((time() - strtotime($birth)) / (365.25 * 86400), 1);
}

/**
 * Players for the page, with ranks under both views and the gap between
 * them. A positive lean means the model ranks the player higher than the market.
 */
function mfl_values_rows(array $f): array
{
    $values = mfl_player_values();
    $holders = mfl_current_rosters();
    // Ranks among every player the market lists, so the two are comparable.
    $listed = array_filter($values, fn($v) => $v['market_value'] !== null);
    $byModel = $listed; uasort($byModel, fn($a, $b) => $b['model_value'] <=> $a['model_value']);
    $byMarket = $listed; uasort($byMarket, fn($a, $b) => $b['market_value'] <=> $a['market_value']);
    $modelRank = array_flip(array_keys($byModel));
    $marketRank = array_flip(array_keys($byMarket));

    $rows = [];
    foreach ($values as $id => $v) {
        $holder = $holders[$id] ?? null;
        if ($f['team'] === 'fa' ? $holder !== null : ($f['team'] === '' ? $holder === null : $holder !== (int) $f['team'])) continue;
        if (!in_array($v['position'], $f['pos'], true)) continue;
        $v['holder'] = $holder;
        $v['age'] = mfl_age_now($v['birth_date']);
        $v['model_rank'] = isset($modelRank[$id]) ? $modelRank[$id] + 1 : null;
        $v['market_rank'] = isset($marketRank[$id]) ? $marketRank[$id] + 1 : null;
        $gap = $v['model_rank'] && $v['market_rank'] ? $v['market_rank'] - $v['model_rank'] : null;
        $v['lean_label'] = mfl_lean_label($gap, $v['model_rank'], $v['market_rank']);
        // Only gaps big enough to label count when sorting by lean.
        $v['lean'] = $v['lean_label'] !== '' ? $gap : null;
        $rows[] = $v;
    }
    $key = fn($v) => match ($f['sort']) {
        'name' => mb_strtolower($v['name']),
        'age' => $v['age'] ?? 99,
        'market' => $v['market_value'] ?? -1,
        'lean' => $v['lean'] ?? ($f['dir'] === 'asc' ? PHP_INT_MAX : PHP_INT_MIN),
        'ppg' => (float) $v['ppg'],
        's0', 's1', 's2', 's3', 's4' => $v['par'][(int) $f['sort'][1]] ?? 0,
        default => (float) $v['model_value'],
    };
    $sign = $f['dir'] === 'asc' ? 1 : -1;
    usort($rows, fn($a, $b) => $sign * ($key($a) <=> $key($b)) ?: $b['model_value'] <=> $a['model_value']);
    return $rows;
}

/** How the model and market disagree, as a short label, or '' when they roughly agree. */
function mfl_lean_label(?int $lean, ?int $modelRank, ?int $marketRank): string
{
    if ($lean === null) return '';
    // Past the top 80 or so both views are guessing, and ranks swing on tiny differences.
    if (min($modelRank, $marketRank) > 80) return '';
    $threshold = max(6, (int) round(0.3 * min($modelRank, $marketRank)));
    if ($lean >= $threshold) return 'Model +' . $lean;
    if ($lean <= -$threshold) return 'Market +' . -$lean;
    return '';
}

/**
 * Each position's scoring by age as a share of its peak, for the FAQ:
 * [position => [age => percent]], chaining the year-to-year aging factors.
 */
function mfl_aging_table(int $from = 21, int $to = 34): array
{
    $out = [];
    foreach (MFL_VALUE_POSITIONS as $pos) {
        $level = 1.0;
        $raw = [$from => 1.0];
        for ($age = $from; $age < $to; $age++) {
            $level *= mfl_model()['aging'][$pos][$age] ?? 1;
            $raw[$age + 1] = $level;
        }
        $peak = max($raw);
        $out[$pos] = array_map(fn($v) => (int) round($v / $peak * 100), $raw);
    }
    return $out;
}

/* ---- The trade machine ---- */

/**
 * How much each season counts under each team "clock" the trade machine
 * offers, nearest season first. Balanced is the site-wide default.
 */
const MFL_CLOCKS = [
    ['label' => 'Win now',     'weights' => [1.0, 0.55, 0.3, 0.15, 0.08]],
    ['label' => 'Lean now',    'weights' => [1.0, 0.7, 0.5, 0.35, 0.24]],
    ['label' => 'Balanced',    'weights' => MFL_VALUE_WEIGHTS],
    ['label' => 'Lean future', 'weights' => [0.7, 0.9, 0.85, 0.75, 0.65]],
    ['label' => 'Rebuild',     'weights' => [0.4, 0.9, 1.0, 1.0, 0.95]],
];

/**
 * Everything the trade machine needs, for the page to embed as JSON: teams,
 * every rostered player with a value, and every tradable pick. The browser
 * does the rest, so trying trades costs no round trips.
 */
function mfl_trade_machine_data(array $owner): array
{
    $values = mfl_player_values();
    $run = mfl_value_meta('run');
    $season = (int) ($run['season'] ?? date('Y'));
    $teams = [];
    $mine = null;
    foreach (mfl_franchises() as $f) {
        $teams[] = ['id' => (int) $f['id'], 'name' => $f['name'], 'owner' => mfl_owner_label($f['owner_name'], $f['real_name'])];
        if ($f['owner_id'] === $owner['id']) $mine = (int) $f['id'];
    }
    // Kickers and defenses have no value, so they never show up here.
    $players = [];
    foreach (mfl_current_rosters() as $pid => $fid) {
        $v = $values[$pid] ?? null;
        if (!$v) continue;
        $players[] = [
            'id' => 'p' . $pid, 'team' => $fid, 'name' => $v['name'], 'pos' => $v['position'],
            'nfl' => $v['nfl_team'], 'age' => mfl_age_now($v['birth_date']), 'ppg' => (float) $v['ppg'],
            'injury' => $v['injury'], 'par' => $v['par'],
            'market' => $v['market_value'] !== null ? (int) $v['market_value'] : null,
        ];
    }
    $ordinal = [1 => '1st', 2 => '2nd', 3 => '3rd', 4 => '4th', 5 => '5th'];
    $names = array_column($teams, 'name', 'id');
    $picks = [];
    foreach (mfl_pick_values() as $p) {
        $picks[] = [
            'id' => "k{$p['season']}-{$p['round']}-{$p['original']}", 'team' => $p['owner'],
            'name' => $p['season'] . ' ' . ($ordinal[$p['round']] ?? "{$p['round']}th"),
            'from' => $p['owner'] === $p['original'] ? '' : ($names[$p['original']] ?? ''),
            'range' => mfl_pick_range($p), 'season' => $p['season'], 'round' => $p['round'],
            'par' => array_map(fn($v) => round($v, 1), $p['par']),
            'market' => $p['market_value'] !== null ? (int) $p['market_value'] : null,
        ];
    }
    $seasons = [];
    for ($k = 0; $k < MFL_VALUE_YEARS; $k++) $seasons[] = $k === 0 ? "$season rest" : (string) ($season + $k);
    return ['teams' => $teams, 'mine' => $mine, 'players' => $players, 'picks' => $picks,
            'clocks' => MFL_CLOCKS, 'balanced' => 2, 'seasons' => $seasons,
            'updated' => $run ? central_time($run['computed_at'], 'M j, g:i a') . ' CT' : null];
}

/**
 * Reference points for reading the numbers, for the FAQ: the value at a few
 * ranks under each view, and where first-round picks land among players.
 * Ranks are across every valued player, rostered or not.
 */
function mfl_value_anchors(array $ranks = [6, 12, 24, 50, 100]): array
{
    $values = array_values(mfl_player_values());
    if (!$values) return [];
    $model = array_map('floatval', array_column($values, 'model_value'));
    rsort($model);
    $market = array_map('intval', array_filter(array_column($values, 'market_value'), fn($v) => $v !== null));
    rsort($market);
    $tiers = [];
    foreach ($ranks as $r) $tiers[$r] = ['model' => $model[$r - 1] ?? null, 'market' => $market[$r - 1] ?? null];
    $rankOf = fn(array $list, ?float $v) => $v === null ? null : count(array_filter($list, fn($x) => $x > $v)) + 1;
    $picks = [];
    $first = array_filter(mfl_pick_values(), fn($p) => $p['round'] === 1);
    if ($first) {
        $near = array_filter($first, fn($p) => $p['near']);
        $later = array_filter($first, fn($p) => !$p['near']);
        usort($near, fn($a, $b) => $b['model_value'] <=> $a['model_value']);
        $rows = [];
        if ($near) {
            $rows['An early ' . $near[0]['season'] . ' 1st'] = $near[0];
            $rows['A late ' . $near[0]['season'] . ' 1st'] = end($near);
        }
        if ($later) $rows['A ' . reset($later)['season'] . ' 1st'] = reset($later);
        foreach ($rows as $label => $p) {
            $picks[] = ['label' => $label, 'model' => $p['model_value'], 'market' => $p['market_value'],
                        'model_rank' => $rankOf($model, $p['model_value']), 'market_rank' => $rankOf($market, $p['market_value'])];
        }
    }
    return ['tiers' => $tiers, 'picks' => $picks];
}

/* ---- Trade scorecards ---- */

/**
 * How a past trade looked then and how it looks now, for each side, from what
 * that side received:
 *   then    the model and market value when the trade happened
 *   so_far  points above a replacement starter in games the player started for this team since
 *   held    today's value of whatever this team still has from the deal (a
 *           pick that was used counts as the player it became)
 *   now     so_far + held
 * Something traded on again counts here only until it left: its value moves
 * to the later trade. Returns NULL until the trade has been valued.
 */
function mfl_trade_scorecard(array $trade): ?array
{
    static $then = null;
    if ($then === null) {
        $then = [];
        foreach (db()->query('SELECT * FROM mfl_trade_values') as $r) $then[$r['trade_id']][$r['item']] = $r;
    }
    if (!isset($then[$trade['id']])) return null;
    $run = mfl_value_meta('run');
    $repl = $run['replacement'] ?? [];
    $values = mfl_player_values();
    $holders = mfl_current_rosters();
    $picks = [];
    foreach (mfl_pick_values() as $p) $picks["k{$p['season']}-{$p['round']}-{$p['original']}"] = $p;

    $sides = [];
    foreach ($trade['sides'] as $side) {
        $fid = $side['franchise_id'];
        $card = ['franchise_id' => $fid, 'model_then' => 0.0, 'market_then' => 0, 'market_then_known' => true,
                 'so_far' => 0.0, 'held' => 0.0, 'held_market' => 0, 'left' => []];
        foreach ($side['assets'] as $a) {
            $item = $a['item'] ?? null;
            $t = $item ? ($then[$trade['id']][$item] ?? null) : null;
            $card['model_then'] += (float) ($t['model_then'] ?? 0);
            if ($t && $t['market_then'] !== null) $card['market_then'] += (int) $t['market_then'];
            else $card['market_then_known'] = false;
            if (!empty($a['is_pick'])) {
                // Not drafted yet: worth today's pick value if this team still has it.
                $p = $picks[$item] ?? null;
                if ($p && ($a['holder'] ?? null) === $fid) {
                    $card['held'] += $p['model_value'];
                    $card['held_market'] += (int) ($p['market_value'] ?? 0);
                }
                continue;
            }
            if (($a['used_by'] ?? $fid) !== $fid) continue;   // the pick was traded on before the draft
            $pos = $a['position'] ?? '';
            if (isset($repl[$pos])) $card['so_far'] += max(0, (float) ($a['all']['points'] ?? 0) - ($a['all']['starts'] ?? 0) * $repl[$pos]);
            if (($holders[$a['player_id']] ?? null) === $fid) {
                $card['held'] += (float) ($values[$a['player_id']]['model_value'] ?? 0);
                $card['held_market'] += (int) ($values[$a['player_id']]['market_value'] ?? 0);
            } else {
                $card['left'][] = $a['name'];
            }
        }
        $card['now'] = $card['so_far'] + $card['held'];
        $sides[] = $card;
    }
    return $sides;
}

/** "Jim Sorgi by 18%", or "Even" within 5%, comparing one number across two sides. */
function mfl_scorecard_edge(array $cards, string $key, array $names): string
{
    if (count($cards) !== 2) return '';
    [$a, $b] = $cards;
    $hi = max($a[$key], $b[$key]);
    if ($hi <= 0) return 'Even';
    $gap = abs($a[$key] - $b[$key]) / $hi;
    if ($gap < 0.05) return 'Even';
    $win = $a[$key] > $b[$key] ? $a : $b;
    return ($names[$win['franchise_id']] ?? 'Team') . ' by ' . round($gap * 100) . '%';
}
