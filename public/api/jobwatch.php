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
const TITLE_RX = '/creative\s+director|director[,]?\s+(of\s+)?creative|executive\s+creative|group\s+creative|head\s+of\s+(creative|brand|design|content|marketing\s+creative)|vp[,]?\s+(of\s+)?(creative|brand)|creative\s+(lead|manager)|brand\s+(director|lead)|design\s+director|art\s+director/i';
const BAR_RX = '/creative\s+director|director[,]?\s+(of\s+)?creative|executive\s+creative|group\s+creative|head\s+of\s+(creative|brand)|vp[,]?\s+(of\s+)?(creative|brand)/i';

header('Content-Type: application/json; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow');

if (($_GET['k'] ?? '') !== JOBWATCH_KEY) {
    http_response_code(404);
    echo json_encode(['error' => 'not found']);
    exit;
}

$cacheDir = APP_DIR . '/cache';
$cacheFile = $cacheDir . '/jobwatch.json';
if (empty($_GET['fresh']) && is_file($cacheFile) && (time() - filemtime($cacheFile)) < CACHE_TTL) {
    readfile($cacheFile);
    exit;
}

@set_time_limit(300);
/* Callers on short fetch timeouts (the scheduled run's proxy) often hang up
   before a fresh poll finishes. Keep going anyway: the cache still gets
   written, and their next cache-served request picks up the fresh data. */
ignore_user_abort(true);
$companies = require APP_DIR . '/jobwatch-companies.php';

/* ---- Self-healing slug fixes: previously discovered ATS corrections
        override the hand-written watchlist, and slugs that repeatedly
        probe dead get skipped and reported as prune candidates. ---- */
$fixFile = APP_DIR . '/cache/jobwatch-fixes.json';
$fixes = is_file($fixFile) ? (json_decode((string) @file_get_contents($fixFile), true) ?: []) : [];
$fixes += ['fixed' => [], 'dead' => [], 'auto' => []];
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
function fetch_all(array $requests, int $batchSize = 40): array
{
    $results = [];
    $chunks = array_chunk($requests, $batchSize, true);
    foreach ($chunks as $chunk) {
        $mh = curl_multi_init();
        $handles = [];
        foreach ($chunk as $slug => [$ats, $url]) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 3,
                CURLOPT_CONNECTTIMEOUT => 4,
                CURLOPT_TIMEOUT => 8,
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
foreach (fetch_hn_jobs() as $f) {
    $f['ats'] = 'aggregator';
    $jobs[] = $f;
}

/* ---- Tier every match: bar (clears Billy's title bar on its own) or
        flag (art director / creative manager / lead: a look, not a file). */
foreach ($jobs as &$j) {
    $t = $j['title'] ?? '';
    $j['tier'] = (preg_match(BAR_RX, $t) && !preg_match('/associate|assistant|junior|intern|freelance|contract/i', $t)) ? 'bar' : 'flag';
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
foreach ($networkOk ? $errors : [] as $err) {
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
        $valid = match ($ats) {
            'greenhouse', 'ashby' => is_array($data['jobs'] ?? null),
            'lever' => is_array($data) && ($data === [] || isset($data[0])), // list check, PHP 8.0 safe
            'smartrecruiters' => is_array($data['content'] ?? null),
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

usort($jobs, fn($a, $b) => [$a['tier'] !== 'bar', $b['posted']] <=> [$b['tier'] !== 'bar', $a['posted']]);

$out = json_encode([
    'generated_at' => date('c'),
    'companies_checked' => count($requests),
    'companies_unreachable' => $errors,
    'slug_fixes' => $fixes['fixed'],
    'prune_candidates' => $pruneCandidates,
    'harvested_this_run' => $harvested,
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
