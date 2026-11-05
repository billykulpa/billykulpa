<?php
/**
 * All-time player numbers. Every week a player scored falls into one bucket:
 *
 * starter     in a starting lineup in a game that counted: the regular season
 *             or the title path of the playoffs
 * bench       on a roster but not in such a lineup, which includes consolation
 *             games, since those points decided nothing
 * free_agent  on no MFL roster that week
 *
 * So starter + bench + free_agent always equals total.
 */

const MFL_PLAYER_POSITIONS = ['QB', 'RB', 'WR', 'TE', 'K', 'DEF'];
const MFL_PLAYER_LIST_LIMIT = 100;

/** [player_id => numbers], with per-season and per-team breakdowns. */
function mfl_player_data(): array
{
    static $players = null;
    if ($players !== null) return $players;

    $counted = [];
    foreach (mfl_games() as $g) {
        if ($g['kind'] !== 'other') $counted["{$g['season']}-{$g['week']}"][$g['franchise_id']] = true;
    }

    $blank = fn() => ['total' => 0.0, 'starter' => 0.0, 'bench' => 0.0, 'free_agent' => 0.0, 'starts' => 0, 'weeks' => 0];
    $players = [];
    $add = function (string $pid) use (&$players, $blank) {
        $players[$pid] ??= $blank() + ['id' => $pid, 'seasons' => [], 'teams' => []];
    };

    $data = mfl_weekly_rosters();
    $holder = [];
    foreach ($data['rosters'] as $key => $teams) {
        $season = $data['weeks'][$key][0];
        foreach ($teams as $fid => $roster) {
            foreach (array_keys($roster) as $pid) {
                $pid = (string) $pid;
                $holder[$key][$pid] = (int) $fid;
                $add($pid);
                $players[$pid]['weeks']++;
                $players[$pid]['seasons'][$season] ??= $blank();
                $players[$pid]['seasons'][$season]['weeks']++;
                $players[$pid]['teams'][$fid] ??= $blank();
                $players[$pid]['teams'][$fid]['weeks']++;
                if (isset($data['starts'][$key][$fid][$pid], $counted[$key][$fid])) {
                    $players[$pid]['starts']++;
                    $players[$pid]['seasons'][$season]['starts']++;
                    $players[$pid]['teams'][$fid]['starts']++;
                }
            }
        }
    }

    foreach (db()->query('SELECT w.season, w.week, w.player_id, w.points FROM mfl_player_weeks w
                          JOIN mfl_seasons s ON s.season = w.season WHERE w.week <= s.last_scored_week') as $w) {
        $pid = (string) $w['player_id'];
        $season = (int) $w['season'];
        $key = "$season-{$w['week']}";
        $points = (float) $w['points'];
        $fid = $holder[$key][$pid] ?? null;
        $bucket = $fid === null ? 'free_agent'
            : (isset($data['starts'][$key][$fid][$pid], $counted[$key][$fid]) ? 'starter' : 'bench');

        $add($pid);
        $players[$pid]['seasons'][$season] ??= $blank();
        foreach ([&$players[$pid], &$players[$pid]['seasons'][$season]] as &$line) {
            $line['total'] += $points;
            $line[$bucket] += $points;
        }
        unset($line);
        if ($fid !== null) {
            $players[$pid]['teams'][$fid]['total'] += $points;
            $players[$pid]['teams'][$fid][$bucket] += $points;
        }
    }

    $names = [];
    foreach (db()->query('SELECT * FROM mfl_players') as $p) $names[$p['id']] = $p;
    foreach ($players as $pid => &$p) {
        $p['name'] = $names[$pid]['full_name'] ?? '';
        $p['position'] = $names[$pid]['position'] ?? '';
        $p['nfl_team'] = $names[$pid]['nfl_team'] ?? '';
    }
    return $players;
}

function mfl_player_link(string $id, string $name): string
{
    return '<a class="team-link" href="/players/' . rawurlencode($id) . '">' . esc($name) . '</a>';
}

/** Read and clean the /players filters. */
function mfl_player_filters(array $q): array
{
    $counted = !empty($q['counted']);
    $seasons = array_map(fn($y) => "s$y", array_keys(mfl_seasons()));
    $allowed = array_merge(['name', 'starts', 'total', 'starter', 'bench', 'free_agent'], $seasons);
    $default = $counted ? 'starter' : 'total';
    $sort = in_array($q['sort'] ?? '', $allowed, true) ? $q['sort'] : $default;
    $positions = array_values(array_intersect(MFL_PLAYER_POSITIONS, (array) ($q['pos'] ?? [])));
    return [
        'pos' => $positions ?: MFL_PLAYER_POSITIONS,
        'counted' => $counted, 'all' => !empty($q['all']), 'sort' => $sort, 'default' => $default,
        'dir' => in_array($q['dir'] ?? '', ['asc', 'desc'], true) ? $q['dir'] : ($sort === 'name' ? 'asc' : 'desc'),
    ];
}

/** Players matching the filters, sorted. Counted mode reads season columns as starter points. */
function mfl_player_rows(array $f): array
{
    $rows = array_filter(mfl_player_data(), fn($p) => $p['name'] !== '' && in_array($p['position'], $f['pos'], true)
        && ($f['counted'] ? $p['starts'] > 0 : $p['total'] != 0));
    $metric = $f['counted'] ? 'starter' : 'total';
    $value = function ($p) use ($f, $metric) {
        if ($f['sort'] === 'name') return mb_strtolower($p['name']);
        if ($f['sort'][0] === 's' && ctype_digit(substr($f['sort'], 1))) return $p['seasons'][(int) substr($f['sort'], 1)][$metric] ?? 0;
        return $p[$f['sort']];
    };
    $sign = $f['dir'] === 'asc' ? 1 : -1;
    usort($rows, fn($a, $b) => $sign * ($value($a) <=> $value($b)) ?: $b[$metric] <=> $a[$metric]);
    return $rows;
}

/** The leader overall and at each position, for the cards. */
function mfl_player_leaders(bool $counted): array
{
    $metric = $counted ? 'starter' : 'total';
    $named = array_filter(mfl_player_data(), fn($p) => $p['name'] !== '' && $p['position'] !== '');
    usort($named, fn($a, $b) => $b[$metric] <=> $a[$metric]);
    $cards = $named ? ['Top scorer' => $named[0]] : [];
    foreach (MFL_PLAYER_POSITIONS as $pos) {
        foreach ($named as $p) {
            if ($p['position'] === $pos) { $cards[$pos] = $p; break; }
        }
    }
    return $cards;
}

/** A /players link that keeps the current filters. */
function mfl_players_url(array $f, array $change = []): string
{
    $f = $change + $f;
    $q = array_filter([
        'pos' => $f['pos'] === MFL_PLAYER_POSITIONS ? [] : $f['pos'],
        'counted' => $f['counted'] ? 1 : 0, 'all' => $f['all'] ? 1 : 0,
        'sort' => $f['sort'] === ($f['counted'] ? 'starter' : 'total') ? '' : $f['sort'],
        'dir' => $f['dir'] === ($f['sort'] === 'name' ? 'asc' : 'desc') ? '' : $f['dir'],
    ]);
    return '/players' . ($q ? '?' . http_build_query($q) : '');
}

function mfl_players_th(array $f, string $key, string $label, string $class = 'num'): string
{
    $active = $f['sort'] === $key;
    $dir = $active ? ($f['dir'] === 'asc' ? 'desc' : 'asc') : ($key === 'name' ? 'asc' : 'desc');
    $aria = $active ? ' aria-sort="' . ($f['dir'] === 'asc' ? 'ascending' : 'descending') . '"' : '';
    $arrow = $active ? ($f['dir'] === 'asc' ? ' ▲' : ' ▼') : '';
    return '<th scope="col" class="' . esc($class) . '"' . $aria . '><a class="sort-link" href="'
        . esc(mfl_players_url($f, ['sort' => $key, 'dir' => $dir])) . '">' . esc($label) . $arrow . '</a></th>';
}

/** Draft picks and transactions for one player, oldest first, as text with optional links. */
function mfl_player_timeline(string $id): array
{
    $names = db()->query('SELECT id, name FROM mfl_franchises')->fetchAll(PDO::FETCH_KEY_PAIR);
    $events = [];
    $stmt = db()->prepare('SELECT p.*, d.season, d.started_at, s.num_teams FROM mfl_draft_picks p
                             JOIN mfl_drafts d ON d.id = p.draft_id JOIN mfl_seasons s ON s.season = d.season
                            WHERE p.player_id = ?');
    $stmt->execute([$id]);
    foreach ($stmt as $p) {
        $events[] = ['at' => (string) $p['started_at'], 'href' => null,
            'text' => 'Drafted by ' . ($names[$p['franchise_id']] ?? '') . ' at ' . mfl_pick_label($p) . ', ' . central_time($p['started_at'], 'M j, Y')];
    }
    foreach (mfl_player_moves()[$id] ?? [] as $m) {
        $events[] = ['at' => $m['at'], 'text' => mfl_move_label($m, $names) . ', ' . central_time($m['at'], 'Y'),
            'href' => $m['type'] === 'trade' ? '/trades?season=' . $m['season'] . '#trade-' . $m['id'] : null];
    }
    usort($events, fn($a, $b) => $b['at'] <=> $a['at']);
    return $events;
}
