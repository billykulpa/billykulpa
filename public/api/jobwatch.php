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

@set_time_limit(120);
$companies = require APP_DIR . '/jobwatch-companies.php';

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
            $data = ($code === 200 && $body !== false) ? json_decode($body, true) : null;
            $results[$slug] = is_array($data) ? $data : null;
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        curl_multi_close($mh);
    }
    return $results;
}

$responses = fetch_all($requests);

/* ---- Parse per ATS ---- */
$jobs = [];
$errors = [];

foreach ($requests as $slug => [$ats, $url]) {
    $data = $responses[$slug] ?? null;
    if ($data === null) { $errors[] = "{$slug} ({$ats})"; continue; }

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

    foreach ($found as $f) {
        $f['company'] = $slug;
        $f['ats'] = $ats;
        $jobs[] = $f;
    }
}

usort($jobs, fn($a, $b) => strcmp($b['posted'], $a['posted']));

$out = json_encode([
    'generated_at' => date('c'),
    'companies_checked' => count($requests),
    'companies_unreachable' => $errors,
    'matches' => $jobs,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

if (!is_dir($cacheDir)) @mkdir($cacheDir);
@file_put_contents($cacheFile, $out, LOCK_EX);
echo $out;
