<?php
/**
 * Job watch — polls ATS boards directly (no search-engine lag).
 *
 * Fetches the public JSON feeds of the companies in app/jobwatch-companies.php
 * across Greenhouse, Lever, Ashby, and SmartRecruiters in parallel (curl_multi,
 * ~10s for 120+ boards), filters for creative-leadership titles, and returns
 * JSON. Results are cached for 45 minutes so repeated checks don't hammer
 * anyone's API. Access requires ?k=<key>; the data is public job postings,
 * the key just keeps crawlers off. Append &fresh=1 to bypass the cache.
 */

declare(strict_types=1);

define('APP_DIR', is_dir(__DIR__ . '/../../app') ? __DIR__ . '/../../app' : __DIR__ . '/../app');

const JOBWATCH_KEY = 'bk-jobwatch-2026';
const CACHE_TTL = 2700; // 45 minutes
/* TITLE_RX is the wide net (small companies call the top creative seat
   "art director" or "creative manager"); BAR_RX marks the titles that
   clear Billy's bar on their own. Every match carries tier: bar | flag. */
const TITLE_RX = '/creative\s+director|director[,]?\s+(of\s+)?creative|executive\s+creative|group\s+creative|head\s+of\s+(creative|brand|design|content|marketing\s+creative)|vp[,]?\s+(of\s+)?(creative|brand|design)|creative\s+(lead|manager)|brand\s+(director|lead)|design\s+(director|manager)|art\s+director/i';
const BAR_RX = '/creative\s+director|director[,]?\s+(of\s+)?creative|executive\s+creative|group\s+creative|head\s+of\s+(creative|brand|design)|vp[,]?\s+(of\s+)?(creative|brand|design)|brand\s+director/i';

header('Content-Type: application/json; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow');

if (($_GET['k'] ?? '') !== JOBWATCH_KEY) {
    http_response_code(404);
    echo json_encode(['error' => 'not found']);
    exit;
}

$cacheDir = APP_DIR . '/cache';
$cacheFile = $cacheDir . '/jobwatch.json';
$lockFile = $cacheDir . '/jobwatch.lock';
$cacheAge = is_file($cacheFile) ? time() - filemtime($cacheFile) : PHP_INT_MAX;
$wantFresh = !empty($_GET['fresh']) || $cacheAge >= CACHE_TTL;
/* One rebuild at a time: a lock younger than 6 minutes means another
   request is already polling, so just serve what we have. */
$rebuilding = is_file($lockFile) && (time() - filemtime($lockFile)) < 360;

/* Serve-stale-then-rebuild: whenever a cache exists, answer with it
   IMMEDIATELY (marked stale if a rebuild is due), close the connection,
   and only then do the slow poll. Callers never wait on 350 boards; the
   next request gets the fresh file. Only the very first run (no cache at
   all) blocks. */
if (is_file($cacheFile) && (!$wantFresh || $rebuilding)) {
    readfile($cacheFile);
    exit;
}
if (is_file($cacheFile)) {
    $stale = (string) file_get_contents($cacheFile);
    $stale = preg_replace('/^\{/', '{"stale": true, "rebuilding": true, ', $stale, 1);
    header('Content-Length: ' . strlen($stale));
    header('Connection: close');
    echo $stale;
    if (function_exists('fastcgi_finish_request')) { fastcgi_finish_request(); }
    elseif (function_exists('litespeed_finish_request')) { litespeed_finish_request(); }
    else { @ob_end_flush(); flush(); }
}

@set_time_limit(300);
ignore_user_abort(true);
if (!is_dir($cacheDir)) @mkdir($cacheDir);
@touch($lockFile);
/* Hard deadline for the whole poll: whatever is done by then gets written.
   Hostinger kills long scripts; a partial cache beats none. */
define('POLL_DEADLINE', microtime(true) + 200);
register_shutdown_function(fn() => @unlink($lockFile));
$companies = require APP_DIR . '/jobwatch-companies.php';

/* ---- Self-healing slug fixes: previously discovered ATS corrections
        override the hand-written watchlist, and slugs that repeatedly
        probe dead get skipped and reported as prune candidates. ---- */
$fixFile = APP_DIR . '/cache/jobwatch-fixes.json';
$fixes = is_file($fixFile) ? (json_decode((string) @file_get_contents($fixFile), true) ?: []) : [];
$fixes += ['fixed' => [], 'dead' => [], 'auto' => [], 'v' => 1];
/* v2: heals recorded before the non-empty rule were unreliable. Drop them
   once and let the prober rebuild the map under the stricter rule. */
if ((int) $fixes['v'] < 2) { $fixes['fixed'] = []; $fixes['dead'] = []; $fixes['v'] = 2; }
foreach ($fixes['fixed'] as $slug => $ats) {
    if (isset($companies[$slug])) $companies[$slug] = $ats;
}
/* Slugs harvested from ATS links seen in the long-tail feeds (small
   companies nobody hand-listed) join the watchlist automatically. */
foreach ($fixes['auto'] as $slug => $ats) {
    if (!isset($companies[$slug])) $companies[$slug] = $ats;
}

/* ---- Build one URL per company ---- */
$requests = []; // slug => [ats, url]
foreach ($companies as $slug => $ats) {
    $url = match ($ats) {
        'greenhouse' => "https://boards-api.greenhouse.io/v1/boards/{$slug}/jobs",
        'lever' => "https://api.lever.co/v0/postings/{$slug}?mode=json",
        'ashby' => "https://api.ashbyhq.com/posting-api/job-board/{$slug}",
        'smartrecruiters' => "https://api.smartrecruiters.com/v1/companies/{$slug}/postings?limit=100",
        default => null,
    };
    if ($url) $requests[$slug] = [$ats, $url];
}

/* ---- Fetch in parallel batches ---- */
function fetch_all(array $requests, int $batchSize = 60): array
{
    $results = [];
    $chunks = array_chunk($requests, $batchSize, true);
    foreach ($chunks as $chunk) {
        if (defined('POLL_DEADLINE') && microtime(true) > POLL_DEADLINE) {
            foreach ($chunk as $slug => $_) $results[$slug] = null; // out of time: count as unreachable
            continue;
        }
        $mh = curl_multi_init();
        $handles = [];
        foreach ($chunk as $slug => [$ats, $url]) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 3,
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_TIMEOUT => 6,
                CURLOPT_USERAGENT => 'billykulpa.com job watch (billy@billykulpa.com)',
                CURLOPT_HTTPHEADER => ['Accept: application/json'],
            ]);
            curl_multi_add_handle($mh, $ch);
            $handles[$slug] = $ch;
        }
        do {
            $status = curl_multi_exec($mh, $active);
            if ($active) curl_multi_select($mh, 0.2);
        } while ($active && $status === CURLM_OK);
        foreach ($handles as $slug => $ch) {
            $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $body = curl_multi_getcontent($ch);
            $results[$slug] = ($code === 200 && is_string($body) && $body !== '') ? $body : null;
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        curl_multi_close($mh);
    }
    return $results;
}

/* ---- Long-tail aggregators: live feeds spanning thousands of small
        companies, no slug enumeration needed. ---- */
$aggregators = [
    'remotive' => 'https://remotive.com/api/remote-jobs?search=creative',
    'remoteok' => 'https://remoteok.com/api',
    'jobicy' => 'https://jobicy.com/api/v2/remote-jobs?count=50&tag=creative',
    'jobicy-marketing' => 'https://jobicy.com/api/v2/remote-jobs?count=50&tag=marketing',
    'wwr-design' => 'https://weworkremotely.com/categories/remote-design-jobs.rss',
    'wwr-management' => 'https://weworkremotely.com/categories/remote-management-and-finance-jobs.rss',
    'himalayas' => 'https://himalayas.app/jobs/api?limit=200',
    'workingnomads' => 'https://www.workingnomads.com/api/exposed_jobs/',
];
foreach ($aggregators as $name => $url) {
    $requests['agg:' . $name] = ['aggregator:' . $name, $url];
}

$responses = fetch_all($requests);

/* ---- Parse per ATS ---- */
$jobs = [];
$errors = [];

foreach ($requests as $slug => [$ats, $url]) {
    $raw = $responses[$slug] ?? null;
    if ($raw === null) { $errors[] = "{$slug} ({$ats})"; continue; }

    /* Long-tail aggregators get their own parsers. */
    if (str_starts_with($ats, 'aggregator:')) {
        foreach (parse_aggregator(substr($ats, 11), $raw) as $f) {
            $f['ats'] = 'aggregator';
            $jobs[] = $f;
        }
        continue;
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) { $errors[] = "{$slug} ({$ats})"; continue; }

    foreach (parse_board($ats, $slug, $data) as $f) {
        $f['company'] = $slug;
        $f['ats'] = $ats;
        $jobs[] = $f;
    }
}

/* ---- Hacker News "Who is hiring": the monthly startup hiring thread.
        Two-step: find the current thread, then pull its top-level comments.
        This is the small-company channel Billy asked for. ---- */
foreach ((microtime(true) < POLL_DEADLINE - 25 ? fetch_hn_jobs() : []) as $f) {
    $f['ats'] = 'aggregator';
    $jobs[] = $f;
}

/* ---- Tier every match: bar (clears Billy's title bar on its own) or
        flag (art director / creative manager / lead: a look, not a file). */
foreach ($jobs as &$j) {
    $t = $j['title'] ?? '';
    $j['tier'] = (preg_match(BAR_RX, $t) && !preg_match('/associate|assistant|junior|intern|freelance|contract|operations|production|producer|project|program/i', $t)) ? 'bar' : 'flag';
}
unset($j);

/* ---- Harvest ATS slugs from every feed hit (URL and, for HN, the comment
        body). A small company that posts once anywhere is polled forever. */
$harvested = [];
foreach ($jobs as $j) {
    if (($j['ats'] ?? '') !== 'aggregator') continue;
    foreach (harvest_slugs(($j['url'] ?? '') . ' ' . ($j['_text'] ?? '')) as $slug => $ats) {
        if (!isset($companies[$slug]) && !isset($fixes['auto'][$slug])) {
            $fixes['auto'][$slug] = $ats;
            $harvested[$slug] = $ats;
        }
    }
}
foreach ($jobs as &$j) unset($j['_text']);
unset($j);
if ($harvested) {
    if (!is_dir($cacheDir)) @mkdir($cacheDir);
    @file_put_contents($fixFile, json_encode($fixes, JSON_PRETTY_PRINT), LOCK_EX);
}

/* ---- Probe failed slugs against the other ATS providers. A wrong
        watchlist guess ("netflix is on Lever" when it moved) heals
        itself: the working provider gets cached and used from then on.
        Slugs that probe dead twice become prune candidates and stop
        being probed. Capped per run to keep response times sane. ---- */
const PROBE_SLUGS_PER_RUN = 40;
$allAts = ['greenhouse', 'lever', 'ashby', 'smartrecruiters'];
$probeSlugs = [];
/* If most of the run failed, the network (not the slugs) is the problem:
   skip probing so transient outages don't poison the dead counts. */
$networkOk = count($errors) < count($requests) * 0.6;
foreach (($networkOk && microtime(true) < POLL_DEADLINE - 30) ? $errors : [] as $err) {
    $slug = explode(' ', $err)[0];
    if (isset($companies[$slug]) && (int) ($fixes['dead'][$slug] ?? 0) < 2) {
        $probeSlugs[] = $slug;
        if (count($probeSlugs) >= PROBE_SLUGS_PER_RUN) break;
    }
}

if ($probeSlugs) {
    $probeReqs = [];
    foreach ($probeSlugs as $slug) {
        foreach ($allAts as $ats) {
            if ($ats === $companies[$slug]) continue; // already failed there
            $url = match ($ats) {
                'greenhouse' => "https://boards-api.greenhouse.io/v1/boards/{$slug}/jobs",
                'lever' => "https://api.lever.co/v0/postings/{$slug}?mode=json",
                'ashby' => "https://api.ashbyhq.com/posting-api/job-board/{$slug}",
                'smartrecruiters' => "https://api.smartrecruiters.com/v1/companies/{$slug}/postings?limit=100",
            };
            $probeReqs["{$slug}|{$ats}"] = [$ats, $url];
        }
    }
    $probeResponses = fetch_all($probeReqs);

    $healed = [];
    foreach ($probeResponses as $key => $raw) {
        if ($raw === null) continue;
        [$slug, $ats] = explode('|', $key);
        if (isset($healed[$slug])) continue;
        $data = json_decode($raw, true);
        /* A heal must find a NON-EMPTY board: several ATS APIs answer an
           unknown slug with 200 and an empty list, and accepting that
           silently pointed half the watchlist at nothing (Aug 17). A real
           company with zero openings just stays un-healed, harmlessly. */
        $valid = match ($ats) {
            'greenhouse', 'ashby' => !empty($data['jobs']) && is_array($data['jobs']),
            'lever' => is_array($data) && isset($data[0]),
            'smartrecruiters' => !empty($data['content']) && is_array($data['content']),
        };
        if ($valid) {
            $healed[$slug] = $ats;
            /* The healed board's matches count this run, not next. */
            foreach (parse_board($ats, $slug, $data) as $f) {
                $f['company'] = $slug;
                $f['ats'] = $ats;
                $jobs[] = $f;
            }
        }
    }

    foreach ($probeSlugs as $slug) {
        if (isset($healed[$slug])) {
            $fixes['fixed'][$slug] = $healed[$slug];
            unset($fixes['dead'][$slug]);
        } else {
            $fixes['dead'][$slug] = (int) ($fixes['dead'][$slug] ?? 0) + 1;
        }
    }
    if (!is_dir($cacheDir)) @mkdir($cacheDir);
    @file_put_contents($fixFile, json_encode($fixes, JSON_PRETTY_PRINT), LOCK_EX);
}

$pruneCandidates = array_keys(array_filter($fixes['dead'], fn($n) => $n >= 2));

/* ---- Auto-file into the job tracker. The server already knows which
        matches are live, bar-tier, and remote-US, so it writes them into
        the applications table as "found" rows itself: no sandbox, no
        proxy, nothing for Billy to copy. Rows it filed carry a "[auto]"
        note prefix; when one of those postings later disappears from a
        board that was reachable this run, the row is retired to
        "abandoned" with a closed date, so dead links stop reaching him.
        Everything here is best-effort: the poll never fails because of it. ---- */
$autoFiled = [];
$autoRetired = [];
try {
    require_once APP_DIR . '/db.php';
    $pdo = db();
    $rows = $pdo->query('SELECT id, company, role, url, status, notes FROM applications')->fetchAll();
    $norm = fn(string $x) => preg_replace('/[^a-z0-9]/', '', strtolower($x));
    $trackedUrls = [];
    $trackedCos = [];
    foreach ($rows as $r) {
        if ($r['url'] !== '') $trackedUrls[$r['url']] = true;
        $trackedCos[$norm($r['company'])] = true;
    }
    $notUsRx = '/canada|\buk\b|united kingdom|europe|emea|latam|brazil|argentina|mexico|colombia|chile|india|australia|\bau\b|singapore|london|philippines|germany|poland|ireland|spain|portugal|netherlands|france|toronto|vancouver|montreal/i';
    /* Remote-US means: says remote/anywhere, or is nothing but a country
       ("United States", "US", "USA"). A city list is an office, even when
       it ends in "USA". */
    $isRemoteUs = function (string $loc) use ($notUsRx): bool {
        if (preg_match($notUsRx, $loc)) return false;
        if (preg_match('/remote|anywhere|distributed|work from home|wfh/i', $loc)) return true;
        return (bool) preg_match('/^\s*(united states( of america)?|usa?|us-based|us only)\s*$/i', $loc);
    };
    $skipTitleRx = '/experiential|\bevent|trade ?show|contract|temporary|freelance|intern/i';
    $avoidCos = ['jackmortonworldwide'];
    $liveUrls = [];
    $reachable = [];
    foreach ($requests as $slug => [$ats, $u]) {
        if (!str_starts_with($ats, 'aggregator') && ($responses[$slug] ?? null) !== null) $reachable[$slug] = true;
    }
    foreach ($jobs as $j) {
        $url = (string) ($j['url'] ?? '');
        if ($url !== '') $liveUrls[$url] = true;
        if (($j['tier'] ?? '') !== 'bar') continue;
        $loc = (string) ($j['location'] ?? '');
        if (!$isRemoteUs($loc)) continue;
        if (preg_match($skipTitleRx, (string) $j['title'])) continue;
        $co = (string) ($j['company'] ?? '');
        if ($co === '' || in_array(strtolower($co), $avoidCos, true)) continue;
        if ($url === '' || isset($trackedUrls[$url]) || isset($trackedCos[$norm($co)])) continue;
        $pretty = ($j['ats'] === 'aggregator') ? $co : ucwords(str_replace(['-', '_'], ' ', $co));
        $note = '[auto] Filed by jobwatch ' . date('Y-m-d') . ' from ' . $j['ats'] . '; live on the board at filing. Location: ' . $loc . '.';
        $stmt = $pdo->prepare('INSERT INTO applications (company, role, comp, remote, url, status, applied_on, notes, letter)
                               VALUES (?,?,?,?,?, "found", NULL, ?, NULL)');
        $stmt->execute([mb_substr($pretty, 0, 190), mb_substr((string) $j['title'], 0, 190),
                        mb_substr((string) ($j['salary'] ?? ''), 0, 190), mb_substr($loc, 0, 190), mb_substr($url, 0, 500), $note]);
        $trackedUrls[$url] = true;
        $trackedCos[$norm($co)] = true;
        $autoFiled[] = ['company' => $pretty, 'title' => $j['title'], 'url' => $url];
    }
    /* Retire auto rows whose posting vanished from a board we reached this run,
       and auto rows whose stored location fails the remote-US rule (a city
       list that slipped through an earlier version of the rule). */
    $rows = $pdo->query('SELECT id, company, role, url, status, notes, remote FROM applications')->fetchAll();
    foreach ($rows as $r) {
        if ($r['status'] !== 'found' || !str_starts_with((string) $r['notes'], '[auto]')) continue;
        if (!$isRemoteUs((string) $r['remote'])) {
            $stmt = $pdo->prepare('UPDATE applications SET status = "abandoned", notes = CONCAT(notes, ?) WHERE id = ?');
            $stmt->execute([' Retired ' . date('Y-m-d') . ': location is not remote-US.', $r['id']]);
            $autoRetired[] = ['company' => $r['company'], 'title' => $r['role'], 'reason' => 'not remote-US'];
            continue;
        }
        if (isset($liveUrls[$r['url']])) continue;
        if (!preg_match('/from (greenhouse|lever|ashby|smartrecruiters)/', (string) $r['notes'])) continue; // aggregator rows: feeds rotate, don't retire
        $slug = null;
        foreach (array_keys($companies) as $c) {
            $u = strtolower($r['url']);
            if (str_contains($u, '/' . $c . '/') || str_contains($u, '//' . $c . '.') || str_contains($u, '.' . $c . '.') || str_contains($u, '/' . $c . '?')) { $slug = $c; break; }
        }
        if ($slug === null || empty($reachable[$slug])) continue;
        $stmt = $pdo->prepare('UPDATE applications SET status = "abandoned", notes = CONCAT(notes, ?) WHERE id = ?');
        $stmt->execute([' Closed: gone from the board ' . date('Y-m-d') . '.', $r['id']]);
        $autoRetired[] = ['company' => $r['company'], 'title' => $r['role']];
    }
} catch (Throwable $e) {
    // Tracker unavailable or schema mismatch: the poll still answers.
}

usort($jobs, fn($a, $b) => [$a['tier'] !== 'bar', $b['posted']] <=> [$b['tier'] !== 'bar', $a['posted']]);

$out = json_encode([
    'generated_at' => date('c'),
    'companies_checked' => count($requests),
    'poll_seconds' => (int) (200 - (POLL_DEADLINE - microtime(true))),
    'poll_truncated' => microtime(true) > POLL_DEADLINE,
    'companies_unreachable' => $errors,
    'slug_fixes' => $fixes['fixed'],
    'prune_candidates' => $pruneCandidates,
    'harvested_this_run' => $harvested,
    'auto_filed' => $autoFiled,
    'auto_retired' => $autoRetired,
    'auto_watchlist' => $fixes['auto'],
    'matches' => $jobs,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

if (!is_dir($cacheDir)) @mkdir($cacheDir);
@file_put_contents($cacheFile, $out, LOCK_EX);
echo $out;

/**
 * Parse one company board's decoded JSON into the common match shape
 * (title, location, url, posted), filtered by TITLE_RX.
 */
function parse_board(string $ats, string $slug, array $data): array
{
    $found = [];
    if ($ats === 'greenhouse') {
        foreach ($data['jobs'] ?? [] as $j) {
            if (preg_match(TITLE_RX, $j['title'] ?? '')) {
                $found[] = [
                    'title' => $j['title'],
                    'location' => $j['location']['name'] ?? '',
                    'url' => $j['absolute_url'] ?? '',
                    'posted' => substr($j['updated_at'] ?? '', 0, 10),
                ];
            }
        }
    } elseif ($ats === 'lever') {
        foreach ($data as $j) {
            if (is_array($j) && preg_match(TITLE_RX, $j['text'] ?? '')) {
                $found[] = [
                    'title' => $j['text'],
                    'location' => $j['categories']['location'] ?? '',
                    'url' => $j['hostedUrl'] ?? '',
                    'posted' => isset($j['createdAt']) ? date('Y-m-d', intdiv((int) $j['createdAt'], 1000)) : '',
                ];
            }
        }
    } elseif ($ats === 'ashby') {
        foreach ($data['jobs'] ?? [] as $j) {
            if (preg_match(TITLE_RX, $j['title'] ?? '')) {
                $found[] = [
                    'title' => $j['title'],
                    'location' => $j['location'] ?? '',
                    'url' => $j['jobUrl'] ?? '',
                    'posted' => substr($j['publishedAt'] ?? '', 0, 10),
                ];
            }
        }
    } elseif ($ats === 'smartrecruiters') {
        foreach ($data['content'] ?? [] as $j) {
            if (preg_match(TITLE_RX, $j['name'] ?? '')) {
                $found[] = [
                    'title' => $j['name'],
                    'location' => trim(($j['location']['city'] ?? '') . ', ' . ($j['location']['country'] ?? ''), ', '),
                    'url' => 'https://jobs.smartrecruiters.com/' . rawurlencode($j['company']['identifier'] ?? $slug) . '/' . ($j['id'] ?? ''),
                    'posted' => substr($j['releasedDate'] ?? '', 0, 10),
                ];
            }
        }
    }
    return $found;
}

/**
 * Pull ATS slugs out of any text containing Greenhouse / Lever / Ashby /
 * SmartRecruiters links. Returns slug => ats.
 */
function harvest_slugs(string $text): array
{
    $out = [];
    $pats = [
        'greenhouse' => '~(?:job-boards|boards)\.greenhouse\.io/([a-z0-9]+)~i',
        'lever' => '~jobs\.lever\.co/([a-z0-9]+)~i',
        'ashby' => '~jobs\.ashbyhq\.com/([a-z0-9]+)~i',
        'smartrecruiters' => '~jobs\.smartrecruiters\.com/([a-z0-9]+)~i',
    ];
    foreach ($pats as $ats => $rx) {
        if (preg_match_all($rx, $text, $m)) {
            foreach ($m[1] as $slug) {
                $slug = strtolower($slug);
                if (!in_array($slug, ['jobs', 'embed', 'v1', 'api'], true)) $out[$slug] = $ats;
            }
        }
    }
    return $out;
}

/**
 * Hacker News "Ask HN: Who is hiring?" — find the newest thread, read its
 * top-level comments, keep the ones whose header line matches TITLE_RX.
 * Header convention: "Company | Role | Location | REMOTE | comp".
 */
function fetch_hn_jobs(): array
{
    $get = function (string $url): ?array {
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_TIMEOUT => 10, CURLOPT_USERAGENT => 'billykulpa.com job watch (billy@billykulpa.com)']);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        return ($code === 200 && is_string($body)) ? (json_decode($body, true) ?: null) : null;
    };
    $threads = $get('https://hn.algolia.com/api/v1/search_by_date?query=%22who%20is%20hiring%22&tags=story,author_whoishiring&hitsPerPage=5');
    $story = null;
    foreach ($threads['hits'] ?? [] as $h) {
        if (stripos($h['title'] ?? '', 'who is hiring') !== false) { $story = $h; break; }
    }
    if (!$story) return [];
    $id = (int) $story['objectID'];
    $comments = $get("https://hn.algolia.com/api/v1/search_by_date?tags=comment,story_{$id}&hitsPerPage=1000");
    $out = [];
    foreach ($comments['hits'] ?? [] as $c) {
        if ((int) ($c['parent_id'] ?? 0) !== $id) continue; // top-level only
        $html = (string) ($c['comment_text'] ?? '');
        $text = html_entity_decode(strip_tags(preg_replace('~<p>~i', "\n", $html)), ENT_QUOTES | ENT_HTML5);
        $head = trim(strtok($text, "\n") ?: '');
        if ($head === '' || !preg_match(TITLE_RX, $head)) continue;
        $parts = array_map('trim', explode('|', $head));
        $out[] = [
            'title' => $head,
            'company' => $parts[0] ?? '',
            'location' => stripos($head, 'remote') !== false ? 'Remote (see post)' : 'see post',
            'url' => 'https://news.ycombinator.com/item?id=' . $c['objectID'],
            'posted' => substr((string) ($c['created_at'] ?? ''), 0, 10),
            'salary' => '',
            '_text' => $html, // for slug harvesting; stripped before output
        ];
    }
    return $out;
}

/**
 * Parse an aggregator feed (JSON or RSS) into the common match shape.
 * Aggregators cover the long tail: thousands of small companies that
 * would never be on a hand-built watchlist.
 */
function parse_aggregator(string $name, string $raw): array
{
    $out = [];
    if ($name === 'remotive') {
        $data = json_decode($raw, true);
        foreach ($data['jobs'] ?? [] as $j) {
            if (!preg_match(TITLE_RX, $j['title'] ?? '')) continue;
            $out[] = [
                'title' => $j['title'],
                'company' => $j['company_name'] ?? '',
                'location' => $j['candidate_required_location'] ?? '',
                'url' => $j['url'] ?? '',
                'posted' => substr($j['publication_date'] ?? '', 0, 10),
                'salary' => $j['salary'] ?? '',
            ];
        }
    } elseif ($name === 'remoteok') {
        $data = json_decode($raw, true);
        foreach (is_array($data) ? $data : [] as $j) {
            if (!is_array($j) || !preg_match(TITLE_RX, $j['position'] ?? '')) continue;
            $out[] = [
                'title' => $j['position'],
                'company' => $j['company'] ?? '',
                'location' => $j['location'] ?? 'Remote',
                'url' => $j['url'] ?? '',
                'posted' => substr($j['date'] ?? '', 0, 10),
                'salary' => ((int) ($j['salary_min'] ?? 0) > 0)
                    ? '$' . number_format((int) $j['salary_min']) . '-$' . number_format((int) ($j['salary_max'] ?? 0)) : '',
            ];
        }
    } elseif (str_starts_with($name, 'jobicy')) {
        $data = json_decode($raw, true);
        foreach ($data['jobs'] ?? [] as $j) {
            if (!preg_match(TITLE_RX, $j['jobTitle'] ?? '')) continue;
            $out[] = [
                'title' => $j['jobTitle'],
                'company' => $j['companyName'] ?? '',
                'location' => $j['jobGeo'] ?? 'Remote',
                'url' => $j['url'] ?? '',
                'posted' => substr($j['pubDate'] ?? '', 0, 10),
                'salary' => ((int) ($j['annualSalaryMin'] ?? 0) > 0)
                    ? '$' . number_format((int) $j['annualSalaryMin']) . '-$' . number_format((int) ($j['annualSalaryMax'] ?? 0)) : '',
            ];
        }
    } elseif ($name === 'himalayas') {
        $data = json_decode($raw, true);
        foreach ($data['jobs'] ?? [] as $j) {
            if (!preg_match(TITLE_RX, $j['title'] ?? '')) continue;
            $loc = $j['locationRestrictions'] ?? [];
            $out[] = [
                'title' => $j['title'],
                'company' => $j['companyName'] ?? '',
                'location' => is_array($loc) && $loc ? 'Remote: ' . implode(', ', $loc) : 'Remote',
                'url' => $j['applicationLink'] ?? ($j['guid'] ?? ''),
                'posted' => isset($j['pubDate']) ? date('Y-m-d', (int) $j['pubDate']) : '',
                'salary' => ((int) ($j['minSalary'] ?? 0) > 0)
                    ? '$' . number_format((int) $j['minSalary']) . '-$' . number_format((int) ($j['maxSalary'] ?? 0)) : '',
            ];
        }
    } elseif ($name === 'workingnomads') {
        $data = json_decode($raw, true);
        foreach (is_array($data) ? $data : [] as $j) {
            if (!is_array($j) || !preg_match(TITLE_RX, $j['title'] ?? '')) continue;
            $out[] = [
                'title' => $j['title'],
                'company' => $j['company_name'] ?? '',
                'location' => $j['location'] ?? 'Remote',
                'url' => $j['url'] ?? '',
                'posted' => substr((string) ($j['pub_date'] ?? ''), 0, 10),
                'salary' => '',
            ];
        }
    } elseif (str_starts_with($name, 'wwr')) {
        // RSS: <item><title>Company: Job Title</title><link/><pubDate/>
        $xml = @simplexml_load_string($raw);
        if ($xml !== false) {
            foreach ($xml->channel->item ?? [] as $item) {
                $t = (string) $item->title;
                if (!preg_match(TITLE_RX, $t)) continue;
                $parts = explode(':', $t, 2);
                $out[] = [
                    'title' => trim($parts[1] ?? $t),
                    'company' => trim($parts[0] ?? ''),
                    'location' => 'Remote',
                    'url' => (string) $item->link,
                    'posted' => date('Y-m-d', strtotime((string) $item->pubDate) ?: time()),
                    'salary' => '',
                ];
            }
        }
    }
    return $out;
}
