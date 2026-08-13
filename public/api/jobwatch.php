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
const TITLE_RX = '/creative\s+director|director[,]?\s+creative|executive\s+creative|group\s+creative|head\s+of\s+(creative|brand|design)|vp[,]?\s+(of\s+)?(creative|brand)|creative\s+lead|brand\s+director|design\s+director/i';

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

@set_time_limit(180);
$companies = require APP_DIR . '/jobwatch-companies.php';

/* ---- Self-healing slug fixes: previously discovered ATS corrections
        override the hand-written watchlist, and slugs that repeatedly
        probe dead get skipped and reported as prune candidates. ---- */
$fixFile = APP_DIR . '/cache/jobwatch-fixes.json';
$fixes = is_file($fixFile) ? (json_decode((string) @file_get_contents($fixFile), true) ?: []) : [];
$fixes += ['fixed' => [], 'dead' => []];
foreach ($fixes['fixed'] as $slug => $ats) {
    if (isset($companies[$slug])) $companies[$slug] = $ats;
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
function fetch_all(array $requests, int $batchSize = 20): array
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
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => 10,
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
    'remotive' => 'https://remotive.com/api/remote-jobs?search=creative%20director',
    'remoteok' => 'https://remoteok.com/api',
    'jobicy' => 'https://jobicy.com/api/v2/remote-jobs?count=50&tag=creative',
    'wwr-design' => 'https://weworkremotely.com/categories/remote-design-jobs.rss',
    'wwr-management' => 'https://weworkremotely.com/categories/remote-management-and-finance-jobs.rss',
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

/* ---- Probe failed slugs against the other ATS providers. A wrong
        watchlist guess ("netflix is on Lever" when it moved) heals
        itself: the working provider gets cached and used from then on.
        Slugs that probe dead twice become prune candidates and stop
        being probed. Capped per run to keep response times sane. ---- */
const PROBE_SLUGS_PER_RUN = 20;
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

usort($jobs, fn($a, $b) => strcmp($b['posted'], $a['posted']));

$out = json_encode([
    'generated_at' => date('c'),
    'companies_checked' => count($requests),
    'companies_unreachable' => $errors,
    'slug_fixes' => $fixes['fixed'],
    'prune_candidates' => $pruneCandidates,
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
    } elseif ($name === 'jobicy') {
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
