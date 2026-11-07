<?php
/**
 * Season awards, worked out from the league data, so the page keeps itself
 * current. Comments are the only stored part (mfl_award_notes).
 *
 * Each award is ['key', 'title', 'measures', 'winners' => [html links], 'value',
 * 'detail']. Tied winners all appear. An award with nothing to report yet is
 * left out, so an early-season page stays honest.
 */

function mfl_award_title(int $season): string
{
    return (mfl_seasons()[$season]['status'] ?? '') === 'complete' ? "$season Season Awards" : 'Midseason Awards';
}

/** The items sharing the best value, to two decimals, so ties stay ties. */
function mfl_award_best(array $items, callable $value, bool $highest = true): array
{
    if (!$items) return [];
    $scores = array_map(fn($i) => round((float) $value($i), 2), $items);
    $best = $highest ? max($scores) : min($scores);
    return array_values(array_filter($items, fn($i) => round((float) $value($i), 2) === $best));
}

function mfl_awards(int $season): array
{
    $db = db();
    $stmt = $db->prepare('SELECT * FROM mfl_team_seasons WHERE season = ?');
    $stmt->execute([$season]);
    $teams = $stmt->fetchAll();
    $names = array_column($teams, 'team_name', 'franchise_id');
    $games = array_values(array_filter(mfl_games(), fn($g) => $g['season'] === $season && $g['kind'] === 'regular'));
    if (!$games) return [];

    $team = fn($fid) => mfl_team_link((int) $fid, $names[$fid] ?? '');
    $span = (mfl_seasons()[$season]['status'] ?? '') === 'complete' ? 'Year' : 'Half';
    $points = fn($n) => number_format((float) $n, 2);
    $awards = [];
    $add = function (string $key, string $title, string $measures, array $winners, string $value, string $detail = '') use (&$awards) {
        if ($winners) $awards[] = compact('key', 'title', 'measures', 'winners', 'value', 'detail');
    };

    // Player of the season so far.
    $players = array_filter(mfl_player_data(), fn($p) => $p['name'] !== '' && ($p['seasons'][$season]['starts'] ?? 0) > 0);
    $mvp = mfl_award_best($players, fn($p) => $p['seasons'][$season]['starter']);
    $add('mvp', 'The Golden Cob', 'MVP: most points as a starter',
        array_map(fn($p) => mfl_player_link($p['id'], $p['name']), $mvp),
        $mvp ? $points($mvp[0]['seasons'][$season]['starter']) : '', $mvp ? $mvp[0]['position'] : '');

    // Lineup decisions. Sleeper's max_points is the best lineup a team could have set.
    $scored = array_filter($teams, fn($t) => (float) $t['max_points'] > 0);
    $share = fn($t) => $t['points_for'] / $t['max_points'] * 100;
    $coach = mfl_award_best($scored, $share);
    $add('coach', "Coach of the $span", 'Closest to the best possible lineup',
        array_map(fn($t) => $team($t['franchise_id']), $coach), $coach ? number_format($share($coach[0]), 1) . '%' : '');
    $regret = mfl_award_best($scored, $share, false);
    $add('regret', 'Set It and Regret It', 'Furthest from the best possible lineup',
        array_map(fn($t) => $team($t['franchise_id']), $regret), $regret ? number_format($share($regret[0]), 1) . '%' : '',
        $regret ? $points($regret[0]['max_points'] - $regret[0]['points_for']) . ' left on the bench' : '');

    // Every team against every other team, every week: a schedule-free record.
    $weeks = [];
    foreach ($games as $g) $weeks[$g['week']][] = $g;
    $allPlay = [];
    foreach ($weeks as $week) {
        foreach ($week as $a) {
            foreach ($week as $b) {
                if ($a['franchise_id'] === $b['franchise_id']) continue;
                $allPlay[$a['franchise_id']] ??= ['fid' => $a['franchise_id'], 'w' => 0, 'l' => 0, 't' => 0];
                $allPlay[$a['franchise_id']][$a['points'] > $b['points'] ? 'w' : ($a['points'] < $b['points'] ? 'l' : 't')]++;
            }
        }
    }
    $record = fn($r) => $r['w'] . '–' . $r['l'] . ($r['t'] ? '–' . $r['t'] : '');
    $best = mfl_award_best($allPlay, fn($r) => $r['w'] + $r['t'] / 2);
    $add('best_team', 'Best Team, No Trophy', 'Best record against every team, every week',
        array_map(fn($r) => $team($r['fid']), $best), $best ? $record($best[0]) : '');

    // Luck: actual wins against what the all-play record says they'd earned.
    $byId = array_column($teams, null, 'franchise_id');
    $luck = array_map(function ($r) use ($byId, $weeks) {
        $t = $byId[$r['fid']];
        $games = $r['w'] + $r['l'] + $r['t'];
        $expected = $games ? ($r['w'] + $r['t'] / 2) / $games * count($weeks) : 0;
        return $r + ['diff' => $t['wins'] + $t['ties'] / 2 - $expected,
                     'actual' => $t['wins'] . '–' . $t['losses'] . ($t['ties'] ? '–' . $t['ties'] : '')];
    }, $allPlay);
    foreach ([['horseshoe', 'Horseshoe Award', 'Luckiest: most wins above their all-play pace', true],
              ['black_cat', 'Black Cat Award', 'Unluckiest: most wins below their all-play pace', false]] as [$key, $title, $measures, $high]) {
        $winners = mfl_award_best($luck, fn($r) => $r['diff'], $high);
        $add($key, $title, $measures, array_map(fn($r) => $team($r['fid']), $winners),
            $winners ? $winners[0]['actual'] : '', $winners ? 'All-play record: ' . $record($winners[0]) : '');
    }

    // The best team left out. The playoff field is only final once a season is.
    if ($span === 'Year') {
        $stmt = $db->prepare("SELECT team1_franchise_id, team2_franchise_id FROM mfl_brackets WHERE season = ? AND bracket = 'winners'");
        $stmt->execute([$season]);
        $inPlayoffs = array_flip(array_filter(array_merge(...$stmt->fetchAll(PDO::FETCH_NUM))));
        if ($inPlayoffs) {
            $hosed = mfl_award_best(array_diff_key($luck, $inPlayoffs), fn($r) => $r['w'] + $r['t'] / 2);
            $add('hosed', 'Hosed', 'Best team to miss the playoffs, by record against every team, every week',
                array_map(fn($r) => $team($r['fid']), $hosed), $hosed ? $hosed[0]['actual'] : '',
                $hosed ? 'All-play record: ' . $record($hosed[0]) : '');
        }
    }

    // The draft. A startup draft has no rookies to single out.
    $stmt = $db->prepare('SELECT p.*, d.rounds, s.num_teams FROM mfl_draft_picks p JOIN mfl_drafts d ON d.id = p.draft_id
                            JOIN mfl_seasons s ON s.season = d.season WHERE d.season = ?');
    $stmt->execute([$season]);
    $picks = $stmt->fetchAll();
    $totals = mfl_player_totals($season, true);
    $scoredPts = fn($p) => $totals[$p['player_id']] ?? 0;
    if ($picks && (int) $picks[0]['rounds'] <= 6) {
        $rookie = mfl_award_best($picks, $scoredPts);
        $add('rookie', "Rookie of the $span", 'Top scorer from this year\'s draft',
            array_map(fn($p) => mfl_player_link($p['player_id'], $p['player_name']), $rookie), $rookie ? $points($scoredPts($rookie[0])) : '',
            $rookie ? mfl_pick_label($rookie[0]) . ', ' . ($names[$rookie[0]['franchise_id']] ?? '') : '');
    }
    $regrets = mfl_award_best(array_filter($picks, fn($p) => (int) $p['round'] === 1), $scoredPts, false);
    $add('draft_regret', 'Draft Day Regret', 'Lowest-scoring first-round pick',
        array_map(fn($p) => mfl_player_link($p['player_id'], $p['player_name']), $regrets), $regrets ? $points($scoredPts($regrets[0])) : '',
        $regrets ? mfl_pick_label($regrets[0]) . ', ' . ($names[$regrets[0]['franchise_id']] ?? '') : '');

    // Moves that paid off.
    $pickups = mfl_pickup_rows(mfl_pickup_filters(['season' => (string) $season], [$season]));
    $hero = mfl_award_best($pickups, fn($p) => $p['points']);
    $add('waiver', 'Waiver Hero', 'Best pickup: most points as a starter',
        array_map(fn($p) => mfl_player_link($p['player_id'], $p['name']), $hero), $hero ? $points($hero[0]['points']) : '',
        $hero ? $hero[0]['team_name'] : '');

    $assets = [];
    foreach (mfl_trades($season) as $trade) {
        foreach ($trade['sides'] as $side) {
            foreach ($side['assets'] as $a) {
                if (empty($a['is_pick'])) $assets[] = $a + ['team_name' => $side['team']['team_name'] ?? ''];
            }
        }
    }
    $trade = mfl_award_best($assets, fn($a) => $a['season']['points'] ?? 0);
    $add('trade', 'Trade Winner', 'Most points as a starter from a trade',
        array_map(fn($a) => mfl_player_link($a['player_id'], $a['name']), $trade), $trade ? $points($trade[0]['season']['points']) : '',
        $trade ? $trade[0]['team_name'] : '');

    // Single games.
    $losses = array_values(array_filter($games, fn($g) => $g['result'] === 'L'));
    $wins = array_values(array_filter($games, fn($g) => $g['result'] === 'W'));
    $score = fn($g) => $points($g['points']) . ' to ' . $points($g['opponent_points']) . ' vs ' . $g['opponent_name'] . ', week ' . $g['week'];
    foreach ([['heartbreaker', 'Heartbreaker', 'Closest loss', $losses, fn($g) => (float) $g['opponent_points'] - $g['points'], false],
              ['robbed', 'Robbed', 'Highest score in a loss', $losses, fn($g) => $g['points'], true],
              ['won_ugly', 'Won Ugly', 'Lowest score in a win', $wins, fn($g) => $g['points'], false],
              ['dumpster', 'Dumpster Fire', 'Lowest single game', $games, fn($g) => $g['points'], false]] as [$key, $title, $measures, $list, $value, $high]) {
        $winners = mfl_award_best($list, $value, $high);
        $add($key, $title, $measures, array_map(fn($g) => $team($g['franchise_id']), $winners),
            $winners ? $points($value($winners[0])) : '', $winners ? $score($winners[0]) : '');
    }

    // The bench mistake that cost a game: a same-position swap would have won it.
    $data = mfl_weekly_rosters();
    $all = mfl_player_data();
    $mistakes = [];
    foreach ($losses as $g) {
        $key = "$season-{$g['week']}";
        $fid = $g['franchise_id'];
        $margin = (float) $g['opponent_points'] - $g['points'];
        $starters = $data['starts'][$key][$fid] ?? [];
        foreach ($data['points'][$key][$fid] ?? [] as $pid => $benched) {
            if (isset($starters[$pid])) continue;
            foreach ($starters as $sid => $started) {
                if (($all[$pid]['position'] ?? '') !== ($all[$sid]['position'] ?? '') || $benched - $started <= $margin) continue;
                $mistakes[] = ['fid' => $fid, 'gap' => $benched - $started, 'week' => $g['week'], 'margin' => $margin,
                               'bench' => $all[$pid]['name'] ?? '', 'bench_pts' => $benched, 'start' => $all[$sid]['name'] ?? '', 'start_pts' => $started];
            }
        }
    }
    $worst = mfl_award_best($mistakes, fn($m) => $m['gap']);
    $add('bench', "Should've Started Him", 'Biggest bench mistake in a loss',
        array_map(fn($m) => $team($m['fid']), array_slice($worst, 0, 1)), $worst ? $points($worst[0]['gap']) . ' swing' : '',
        $worst ? sprintf('Week %d: %s (%s) sat for %s (%s), lost by %s', $worst[0]['week'], $worst[0]['bench'], $points($worst[0]['bench_pts']),
            $worst[0]['start'], $points($worst[0]['start_pts']), $points($worst[0]['margin'])) : '');

    // Roster churn.
    $stmt = $db->prepare("SELECT franchise_ids_json FROM mfl_transactions WHERE season = ? AND status = 'complete' AND type IN ('waiver', 'free_agent')");
    $stmt->execute([$season]);
    $moves = [];
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $json) {
        foreach (json_decode($json, true) ?: [] as $fid) {
            $moves[$fid] = ['fid' => $fid, 'n' => ($moves[$fid]['n'] ?? 0) + 1];
        }
    }
    $addict = mfl_award_best($moves, fn($m) => $m['n']);
    $add('wire', 'Wire Addict', 'Most waiver and free-agent moves', array_map(fn($m) => $team($m['fid']), $addict),
        $addict ? (string) $addict[0]['n'] . ' moves' : '');

    return $awards;
}

/** [award_key => comment] for a season. */
function mfl_award_notes(int $season): array
{
    $stmt = db()->prepare('SELECT award_key, note_md FROM mfl_award_notes WHERE season = ?');
    $stmt->execute([$season]);
    return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
}
