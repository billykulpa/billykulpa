<?php
/** Power rankings: reads for the site, draft and publish for the CMS. */

/** Week numbers 1-17 are real weeks; these two bracket the season. */
const MFL_PRESEASON_WEEK = 0;
const MFL_FINAL_WEEK = 18;

function mfl_week_label(int $week): string
{
    return match ($week) {
        MFL_PRESEASON_WEEK => 'Preseason',
        MFL_FINAL_WEEK     => 'Final',
        default            => "Week $week",
    };
}

function mfl_week_slug(int $week): string
{
    return strtolower(str_replace(' ', '-', mfl_week_label($week)));
}

function mfl_week_from_slug(string $slug): ?int
{
    if ($slug === 'preseason') return MFL_PRESEASON_WEEK;
    if ($slug === 'final') return MFL_FINAL_WEEK;
    if (preg_match('/^week-(\d{1,2})$/', $slug, $m) && (int) $m[1] >= 1 && (int) $m[1] < MFL_FINAL_WEEK) return (int) $m[1];
    return null;
}

function mfl_ranking_title(array $r): string
{
    return $r['title'] !== '' ? $r['title'] : mfl_week_label((int) $r['week']) . ' Power Rankings';
}

function mfl_ranking_url(array $r): string
{
    return '/rankings/' . (int) $r['season'] . '/' . mfl_week_slug((int) $r['week']);
}

/** The week a new set of rankings most likely covers. */
function mfl_next_ranking_week(array $season): int
{
    if ($season['status'] === 'complete') return MFL_FINAL_WEEK;
    return min((int) $season['last_scored_week'] + 1, MFL_FINAL_WEEK - 1);
}

/**
 * Write-ups come from guest accounts and render on a public page, so they get
 * this small converter rather than markdown_to_html(), which passes raw HTML
 * through for the owner's own posts. Everything is escaped first; only bold,
 * italic, http(s) links and paragraph breaks are turned back on.
 */
function mfl_writeup_html(?string $md): string
{
    $md = trim(str_replace(["\r\n", "\r"], "\n", (string) $md));
    if ($md === '') return '';
    $html = [];
    foreach (preg_split('/\n{2,}/', $md) as $para) {
        $s = esc($para);
        $s = preg_replace('/\[([^\]]+)\]\((https?:\/\/[^)\s]+)\)/', '<a class="link" href="$2" rel="nofollow noopener">$1</a>', $s);
        $s = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $s);
        $s = preg_replace('/\*([^*]+)\*/', '<em>$1</em>', $s);
        $html[] = '<p>' . nl2br($s, false) . '</p>';
    }
    return implode("\n", $html);
}

/** Published rankings, oldest first, each with its [franchise_id => rank] map. */
function mfl_rank_history(): array
{
    $history = db()->query("SELECT * FROM mfl_rankings WHERE status = 'published' ORDER BY season, week")->fetchAll();
    if (!$history) return [];
    $ranks = [];
    foreach (db()->query("SELECT e.ranking_id, e.franchise_id, e.rank_no FROM mfl_ranking_entries e
                          JOIN mfl_rankings r ON r.id = e.ranking_id WHERE r.status = 'published'") as $e) {
        $ranks[$e['ranking_id']][$e['franchise_id']] = (int) $e['rank_no'];
    }
    foreach ($history as &$r) $r['ranks'] = $ranks[$r['id']] ?? [];
    return $history;
}

/** A ranking's rows, with movement since the set before it and the rank history so far. */
function mfl_ranking_rows(array $ranking, array $history): array
{
    $stmt = db()->prepare(
        'SELECT e.*, o.display_name AS owner_name, o.real_name
           FROM mfl_ranking_entries e
           LEFT JOIN mfl_owners o ON o.id = e.owner_id
          WHERE e.ranking_id = ?
          ORDER BY e.rank_no'
    );
    $stmt->execute([$ranking['id']]);
    $rows = $stmt->fetchAll();

    $sofar = [];
    foreach ($history as $h) {
        $sofar[] = $h;
        if ((int) $h['id'] === (int) $ranking['id']) break;
    }
    $previous = count($sofar) > 1 ? $sofar[count($sofar) - 2] : null;
    $records = mfl_records_before((int) $ranking['season'], (int) $ranking['week']);

    foreach ($rows as &$row) {
        $fid = (int) $row['franchise_id'];
        $was = $previous['ranks'][$fid] ?? null;
        $row['delta'] = $was === null ? null : $was - (int) $row['rank_no'];
        $row['record'] = $records[$fid] ?? '0–0';
        $row['trail'] = array_values(array_filter(array_map(fn($h) => $h['ranks'][$fid] ?? null, $sofar)));
    }
    return $rows;
}

/**
 * Regular-season records going into a week, from the matchups, so an old
 * ranking keeps showing the record that team had then. Week 9 rankings show
 * the record through week 8; "Final" shows the whole regular season.
 */
function mfl_records_before(int $season, int $week): array
{
    $stmt = db()->prepare(
        "SELECT franchise_id, SUM(result = 'W') w, SUM(result = 'L') l, SUM(result = 'T') t
           FROM mfl_matchups
          WHERE season = ? AND week < ? AND is_playoff = 0
          GROUP BY franchise_id"
    );
    $stmt->execute([$season, $week]);
    $records = [];
    foreach ($stmt as $r) {
        $records[$r['franchise_id']] = (int) $r['w'] . '–' . (int) $r['l'] . ((int) $r['t'] ? '–' . (int) $r['t'] : '');
    }
    return $records;
}

/** All-time rankings summary per franchise, ordered by the latest set. */
function mfl_ranking_team_stats(array $history): array
{
    if (!$history) return [];
    $names = db()->query('SELECT id, name FROM mfl_franchises')->fetchAll(PDO::FETCH_KEY_PAIR);
    $latest = end($history);
    $teams = [];
    foreach ($names as $fid => $name) {
        $ranks = array_values(array_filter(array_map(fn($h) => $h['ranks'][$fid] ?? null, $history)));
        if (!$ranks) continue;
        $teams[] = [
            'name' => $name, 'ranks' => $ranks, 'by_set' => array_map(fn($h) => $h['ranks'][$fid] ?? null, $history),
            'latest' => $latest['ranks'][$fid] ?? null,
            'best' => min($ranks), 'worst' => max($ranks),
            'average' => array_sum($ranks) / count($ranks),
            'first_place' => count(array_keys($ranks, 1, true)),
        ];
    }
    usort($teams, fn($a, $b) => ($a['latest'] ?? 99) <=> ($b['latest'] ?? 99));
    return $teams;
}

/** What the trend chart draws. Rendered client-side so type stays true size on phones. */
function mfl_trend_data(array $history): array
{
    $multiSeason = count(array_unique(array_column($history, 'season'))) > 1;
    $teams = [];
    foreach (mfl_ranking_team_stats($history) as $t) {
        $teams[] = ['name' => $t['name'], 'ranks' => $t['by_set']];
    }
    return [
        'teams' => $teams,
        'n' => $history ? max(array_map(fn($h) => count($h['ranks']), $history)) : 0,
        'sets' => array_map(fn($h) => [
            'short' => (int) $h['week'] === MFL_PRESEASON_WEEK ? 'Pre'
                : ((int) $h['week'] === MFL_FINAL_WEEK ? 'Final' : 'W' . (int) $h['week']),
            'full' => mfl_week_label((int) $h['week']) . ', ' . (int) $h['season'],
            'season' => $multiSeason ? (int) $h['season'] : null,
        ], $history),
    ];
}

/** Movement marker. Direction is carried by the arrow and a label, not color alone. */
function mfl_movement(?int $delta): string
{
    if ($delta === null) return '<span class="move move--new">New</span>';
    if ($delta === 0) return '<span class="move" aria-label="No change">—</span>';
    $up = $delta > 0;
    return sprintf(
        '<span class="move move--%s" aria-label="%s %d">%s %d</span>',
        $up ? 'up' : 'down', $up ? 'Up' : 'Down', abs($delta), $up ? '▲' : '▼', abs($delta)
    );
}

/** Small inline rank history. Rank 1 sits at the top. */
function mfl_sparkline(array $trail, int $teams): string
{
    if (count($trail) < 2) return '';
    $w = 96; $h = 28; $pad = 4;
    $step = ($w - 2 * $pad) / (count($trail) - 1);
    $y = fn($rank) => $pad + (($rank - 1) / max($teams - 1, 1)) * ($h - 2 * $pad);
    $points = [];
    foreach ($trail as $i => $rank) $points[] = round($pad + $i * $step, 1) . ',' . round($y($rank), 1);
    $end = explode(',', end($points));
    return '<svg class="spark" viewBox="0 0 ' . $w . ' ' . $h . '" width="' . $w . '" height="' . $h . '" role="img"'
        . ' aria-label="Rank history: ' . esc(implode(', ', $trail)) . '">'
        . '<polyline points="' . implode(' ', $points) . '"/>'
        . '<circle cx="' . $end[0] . '" cy="' . $end[1] . '" r="3"/></svg>';
}

/* ------------------------------- CMS writes ------------------------------- */

/** Load a ranking the signed-in user may change. Authors edit their own; the admin edits all. */
function mfl_ranking_for_edit(int $id, array $user): array
{
    $stmt = db()->prepare('SELECT * FROM mfl_rankings WHERE id = ?');
    $stmt->execute([$id]);
    $ranking = $stmt->fetch();
    if (!$ranking) { http_response_code(404); exit('Not found.'); }
    if ($user['role'] !== 'admin' && (int) $ranking['author_user_id'] !== (int) $user['id']) {
        http_response_code(403);
        exit('Only the author can edit these rankings.');
    }
    return $ranking;
}

/** Start a draft in the last published order, or the standings if there is none. NULL if the week is taken. */
function mfl_ranking_create(int $season, int $week, array $user): ?int
{
    $db = db();
    try {
        $stmt = $db->prepare('INSERT INTO mfl_rankings (season, week, author_user_id, author_name, created_at) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$season, $week, $user['id'], mfl_user_name($user), date('Y-m-d H:i:s')]);
    } catch (PDOException $e) {
        if ($e->getCode() !== '23000') throw $e;
        return null;
    }
    $id = (int) $db->lastInsertId();

    $history = array_filter(mfl_rank_history(), fn($h) => [(int) $h['season'], (int) $h['week']] < [$season, $week]);
    $last = $history ? end($history)['ranks'] : [];
    $standings = mfl_standings($season);
    $place = array_flip(array_column($standings, 'franchise_id'));
    usort($standings, fn($a, $b) =>
        [$last[$a['franchise_id']] ?? 99, $place[$a['franchise_id']]] <=> [$last[$b['franchise_id']] ?? 99, $place[$b['franchise_id']]]);

    $stmt = $db->prepare('INSERT INTO mfl_ranking_entries (ranking_id, franchise_id, rank_no, team_name, owner_id) VALUES (?, ?, ?, ?, ?)');
    foreach ($standings as $i => $t) {
        $stmt->execute([$id, $t['franchise_id'], $i + 1, $t['team_name'], $t['owner_id']]);
    }
    mfl_ranking_log($id, 'created', mfl_user_name($user), '', mfl_ranking_snapshot($id));
    return $id;
}

function mfl_user_name(array $user): string
{
    return $user['display_name'] ?: 'The Commissioner';
}

/** A set of rankings as it stands now, in the same shape the editor's form posts. */
function mfl_ranking_snapshot(int $id): array
{
    $stmt = db()->prepare('SELECT week, title, intro_md FROM mfl_rankings WHERE id = ?');
    $stmt->execute([$id]);
    $r = $stmt->fetch();
    $stmt = db()->prepare('SELECT franchise_id, writeup_md FROM mfl_ranking_entries WHERE ranking_id = ? ORDER BY rank_no');
    $stmt->execute([$id]);
    $order = $writeups = [];
    foreach ($stmt as $e) {
        $order[] = (int) $e['franchise_id'];
        $writeups[(int) $e['franchise_id']] = (string) $e['writeup_md'];
    }
    return ['week' => (int) $r['week'], 'title' => (string) $r['title'], 'intro_md' => (string) $r['intro_md'],
            'order' => $order, 'writeup' => $writeups];
}

/** What changed between two snapshots, in words. Empty if nothing did. */
function mfl_ranking_diff(array $before, array $after): string
{
    $parts = [];
    $moved = count(array_filter($after['order'], fn($fid, $i) => ($before['order'][$i] ?? null) !== $fid, ARRAY_FILTER_USE_BOTH));
    if ($moved) $parts[] = "Moved $moved " . ($moved === 1 ? 'team' : 'teams');
    $edited = count(array_filter($after['writeup'], fn($text, $fid) => ($before['writeup'][$fid] ?? '') !== $text, ARRAY_FILTER_USE_BOTH));
    if ($edited) $parts[] = "Edited $edited " . ($edited === 1 ? 'write-up' : 'write-ups');
    if ($before['title'] !== $after['title']) $parts[] = 'Changed the title';
    if ($before['intro_md'] !== $after['intro_md']) $parts[] = 'Changed the intro';
    if ($before['week'] !== $after['week']) $parts[] = 'Changed the week';
    return implode(' · ', $parts);
}

function mfl_ranking_log(int $id, string $action, string $name, string $summary, array $snapshot, ?string $at = null): void
{
    $stmt = db()->prepare('INSERT INTO mfl_ranking_revisions (ranking_id, action, user_name, summary, snapshot_json, created_at) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$id, $action, $name, $summary, json_encode($snapshot, JSON_UNESCAPED_UNICODE), $at ?? date('Y-m-d H:i:s')]);
}

/** Save history for the editor, newest first. */
function mfl_ranking_revisions(int $id): array
{
    $stmt = db()->prepare('SELECT id, action, user_name, summary, created_at FROM mfl_ranking_revisions WHERE ranking_id = ? ORDER BY id DESC');
    $stmt->execute([$id]);
    return $stmt->fetchAll();
}

/** One saved version's snapshot and date, only if it belongs to this ranking. */
function mfl_ranking_revision(int $rankingId, int $revisionId): ?array
{
    $stmt = db()->prepare('SELECT * FROM mfl_ranking_revisions WHERE id = ? AND ranking_id = ?');
    $stmt->execute([$revisionId, $rankingId]);
    $row = $stmt->fetch();
    if (!$row) return null;
    $row['snapshot'] = json_decode($row['snapshot_json'], true);
    return $row;
}

function mfl_revision_label(string $action): string
{
    return match ($action) {
        'created'     => 'Created',
        'baseline'    => 'Earlier version',
        'published'   => 'Published',
        'unpublished' => 'Unpublished',
        default       => 'Saved',
    };
}

/** Rows for the editor, in saved order, or in the posted order after a rejected save. */
function mfl_editor_rows(array $ranking, ?array $posted): array
{
    $stmt = db()->prepare(
        'SELECT e.*, ts.team_name AS current_name
           FROM mfl_ranking_entries e
           LEFT JOIN mfl_team_seasons ts ON ts.season = ? AND ts.franchise_id = e.franchise_id
          WHERE e.ranking_id = ?
          ORDER BY e.rank_no'
    );
    $stmt->execute([$ranking['season'], $ranking['id']]);
    $rows = $stmt->fetchAll();

    $records = mfl_records_before((int) $ranking['season'], (int) $ranking['week']);
    $earlier = array_filter(mfl_rank_history(), fn($h) => [(int) $h['season'], (int) $h['week']] < [(int) $ranking['season'], (int) $ranking['week']]);
    $last = $earlier ? end($earlier)['ranks'] : [];
    foreach ($rows as &$row) {
        $fid = (int) $row['franchise_id'];
        $row['record'] = $records[$fid] ?? '0–0';
        $row['last_rank'] = $last[$fid] ?? null;
        if ($posted) $row['writeup_md'] = (string) ($posted['writeup'][$fid] ?? $row['writeup_md']);
    }
    unset($row);
    if ($posted) {
        $at = array_flip(array_map('intval', (array) ($posted['order'] ?? [])));
        usort($rows, fn($a, $b) => ($at[$a['franchise_id']] ?? 99) <=> ($at[$b['franchise_id']] ?? 99));
    }
    return $rows;
}

/**
 * Save the form. $action is save, publish or unpublish. Returns an error
 * message, or NULL on success.
 *
 * Saving keeps the current status, so fixing a typo in published rankings
 * doesn't pull them down. Team names and owners freeze at publish.
 */
function mfl_ranking_save(array $ranking, array $in, string $action, array $user): ?string
{
    $db = db();
    $stmt = $db->prepare('SELECT franchise_id, team_name, owner_id FROM mfl_team_seasons WHERE season = ?');
    $stmt->execute([$ranking['season']]);
    $teams = [];
    foreach ($stmt as $t) $teams[(int) $t['franchise_id']] = $t;

    $order = array_map('intval', (array) ($in['order'] ?? []));
    $check = $order;
    sort($check);
    $expected = array_keys($teams);
    sort($expected);
    if ($check !== $expected) return 'Every team needs exactly one rank.';

    $week = (int) ($in['week'] ?? -1);
    if ($week < 0 || $week > MFL_FINAL_WEEK) return 'Choose a week.';

    $now = date('Y-m-d H:i:s');
    $wasDraft = $ranking['status'] === 'draft';
    $status = match ($action) {
        'publish'   => 'published',
        'unpublish' => 'draft',
        default     => $ranking['status'],
    };
    $publishedAt = $status === 'draft' ? null : ($ranking['published_at'] ?: $now);
    $publicAt = $status === 'draft' ? null : $now;
    $freeze = $wasDraft || $status === 'draft';

    $id = (int) $ranking['id'];
    $before = mfl_ranking_snapshot($id);

    $db->beginTransaction();
    try {
        $stmt = $db->prepare('UPDATE mfl_rankings SET week = ?, title = ?, intro_md = ?, status = ?, published_at = ?, public_updated_at = ? WHERE id = ?');
        $stmt->execute([
            $week, mb_substr(trim((string) ($in['title'] ?? '')), 0, 160),
            mb_substr(trim((string) ($in['intro_md'] ?? '')), 0, 20000),
            $status, $publishedAt, $publicAt, $ranking['id'],
        ]);
    } catch (PDOException $e) {
        $db->rollBack();
        if ($e->getCode() !== '23000') throw $e;
        return mfl_week_label($week) . ' already has rankings. There\'s one set per week.';
    }

    $writeups = (array) ($in['writeup'] ?? []);
    $save = $db->prepare('UPDATE mfl_ranking_entries SET rank_no = ?, writeup_md = ? WHERE ranking_id = ? AND franchise_id = ?');
    $snapshot = $db->prepare('UPDATE mfl_ranking_entries SET team_name = ?, owner_id = ? WHERE ranking_id = ? AND franchise_id = ?');
    foreach ($order as $i => $fid) {
        $save->execute([$i + 1, mb_substr(trim((string) ($writeups[$fid] ?? '')), 0, 20000), $ranking['id'], $fid]);
        if ($freeze) $snapshot->execute([$teams[$fid]['team_name'], $teams[$fid]['owner_id'], $ranking['id'], $fid]);
    }

    $after = mfl_ranking_snapshot($id);
    $summary = mfl_ranking_diff($before, $after);
    $logged = match ($action) {
        'publish'   => 'published',
        'unpublish' => 'unpublished',
        default     => 'saved',
    };
    // A save that changed nothing isn't worth a row. Sets written before the
    // log existed get their pre-save state recorded, so that stays recoverable.
    if ($logged !== 'saved' || $summary !== '') {
        $stmt = $db->prepare('SELECT COUNT(*) FROM mfl_ranking_revisions WHERE ranking_id = ?');
        $stmt->execute([$id]);
        if (!$stmt->fetchColumn()) mfl_ranking_log($id, 'baseline', $ranking['author_name'], '', $before, $ranking['created_at']);
        mfl_ranking_log($id, $logged, mfl_user_name($user), $summary, $after);
    }
    $db->commit();
    return null;
}
