<?php
/**
 * First-party visit log — the "did a recruiter actually look?" ledger.
 *
 * Logs path, referrer, user agent, and an optional ?via= tag for public page
 * views. No IP address is stored, no cookie is set on visitors, and the
 * only client script is a one-line beacon (see below). Visits are classed
 * at write time:
 *   0 = human, 1 = bot, 2 = self (admin session cookie, or the long-lived
 *   "this device is me" cookie set from /admin/traffic).
 *
 * "Bot" is decided by more than the user-agent string, because most scrapers
 * and vulnerability scanners send a plain Chrome UA:
 *   - the request 404s (probes for /wp-login.php, /.env, /xmlrpc.php ...),
 *   - the UA is empty or matches a known crawler / HTTP-library pattern,
 *   - the request has no Accept-Language header, or an Accept header that
 *     doesn't want text/html (real browsers always send both).
 * Because the 404 check needs the final status, logging runs at shutdown.
 *
 * Distinct visitors: each row carries vhash, a 16-hex-char digest of
 * (daily salt + IP + UA). The IP is never stored, and the salt rotates each
 * day, so the hash can dedupe one visitor's page views within a day but
 * can't follow anyone across days. The traffic page groups rows into
 * sessions (same vhash, gaps under 30 minutes).
 *
 * Verified: the page fires a tiny beacon to /api/ping.php after load.
 * Scrapers don't run scripts; browsers do. The ping marks that visitor's
 * rows from the last 30 minutes verified=1. So "sessions" is the ceiling
 * and "verified sessions" is the floor; the truth sits between.
 *
 * via: a short tag from ?via=<tag> (e.g. the link on a resume sent to one
 * company), stored on the landing row and attributed to the whole session
 * at report time. This is how "did Render open the link?" gets answered.
 *
 * Rows older than 90 days are pruned opportunistically (~1% of requests).
 * Every failure mode is swallowed: if the visits table lacks a column yet
 * (code deploys before SQL runs), the public site must not care.
 */

declare(strict_types=1);

const VISIT_BOT_RX = '/bot|crawl|spider|slurp|bingpreview|facebookexternalhit|headless|python|curl|wget|monitor|uptime|scan|fetch|feed|lighthouse|pagespeed|pingdom|ahrefs|semrush|dataprovider|gpt|claude|perplexity|anthropic|openai|go-http-client|okhttp|java\/|axios|node|libwww|http\.rb|scrapy|httpclient|ruby|perl|php\/|dart|postman|insomnia|bytespider|amazonbot|applebot|meta-externalagent|yandex|baidu|duckduck|petal|seznam|censys|shodan|zgrab|masscan|nmap|nikto|sqlmap|wpscan|expanse|internet-measurement|researchscan|mozilla\/4\.0|msie [1-9]\./i';

/* Paths that only scanners ask for. Even when one of these 200s (it won't),
   nobody typing it is a recruiter. */
const VISIT_PROBE_RX = '~\.(php|env|git|aws|sql|bak|old|zip|tar|gz|xml|txt|yml|yaml|json|ini|log|asp|aspx|jsp|cgi)$|wp-|wordpress|xmlrpc|phpmyadmin|/admin|/vendor/|/\.|cgi-bin|/config|/backup|/console|/actuator|/telescope|/debug~i';

const VISIT_ME_COOKIE = 'bk_me';

/** The daily-rotating visitor digest for the current request. */
function visit_hash(): string
{
    $cfg = config();
    $salt = (string) ($cfg['visit_salt'] ?? ($cfg['db']['pass'] ?? 'bk')) . gmdate('Y-m-d');
    $ua = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
    return substr(hash('sha256', $salt . ($_SERVER['REMOTE_ADDR'] ?? '') . $ua), 0, 16);
}

function visit_is_self(): bool
{
    return !empty($_COOKIE[config()['session_name'] ?? '']) || !empty($_COOKIE[VISIT_ME_COOKIE]);
}

function log_visit(string $path): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') return;
    /* Defer until the response is decided so a 404 can count as a probe. */
    register_shutdown_function('log_visit_now', $path);
}

function log_visit_now(string $path): void
{
    $ua = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
    $referrer = substr((string) ($_SERVER['HTTP_REFERER'] ?? ''), 0, 255);
    $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');
    $lang = (string) ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '');
    $status = (int) http_response_code();
    $via = substr(preg_replace('/[^a-z0-9_-]/', '', strtolower((string) ($_GET['via'] ?? ''))), 0, 40);

    $who = 0; // human
    if ($ua === ''
        || preg_match(VISIT_BOT_RX, $ua)
        || preg_match(VISIT_PROBE_RX, '/' . $path)
        || $status >= 400
        || $lang === ''
        || ($accept !== '' && !str_contains($accept, 'text/html') && !str_contains($accept, '*/*'))
    ) {
        $who = 1; // bot
    } elseif (visit_is_self()) {
        $who = 2; // self
    }

    $vhash = visit_hash();

    try {
        try {
            $stmt = db()->prepare('INSERT INTO visits (path, referrer, ua, who, vhash, via) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute(['/' . $path, $referrer, $ua, $who, $vhash, $via]);
        } catch (PDOException $e) {
            // via column not migrated yet: log without it rather than lose the row.
            $stmt = db()->prepare('INSERT INTO visits (path, referrer, ua, who, vhash) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute(['/' . $path, $referrer, $ua, $who, $vhash]);
        }

        if (random_int(1, 100) === 1) {
            db()->exec('DELETE FROM visits WHERE created_at < NOW() - INTERVAL 90 DAY');
        }
    } catch (Throwable $e) {
        // Table missing or DB hiccup: never let analytics break the site.
    }
}

/**
 * Beacon: mark this visitor's recent rows verified. Called by /api/ping.php.
 * Tolerates the column not existing yet.
 */
function visit_verify(): void
{
    try {
        $stmt = db()->prepare('UPDATE visits SET verified = 1 WHERE vhash = ? AND created_at > NOW() - INTERVAL 30 MINUTE');
        $stmt->execute([visit_hash()]);
    } catch (Throwable $e) {
    }
}

/**
 * Leave beacon: record how long the visitor's current page stayed open.
 * Called by /api/ping.php?t=<seconds>. The browser may report several
 * times as the tab hides and returns, so keep the largest. Only the
 * visitor's newest row (their current page view) is touched.
 */
function visit_dwell(int $seconds): void
{
    $seconds = max(0, min($seconds, 7200)); // cap at 2h: parked tabs aren't reading
    if ($seconds === 0) return;
    try {
        $stmt = db()->prepare('UPDATE visits SET dwell = GREATEST(dwell, ?)
                               WHERE vhash = ? AND created_at > NOW() - INTERVAL 30 MINUTE
                               ORDER BY id DESC LIMIT 1');
        $stmt->execute([$seconds, visit_hash()]);
    } catch (Throwable $e) {
        // dwell column not migrated yet: ignore.
    }
}

/**
 * Group raw rows (ordered by created_at ASC) into sessions: same vhash,
 * gaps under 30 minutes. Returns sessions newest-first with: vhash, who,
 * start, end, seconds, pages (ordered paths), entry, referrer (first
 * external), via (first non-empty), verified (any), mobile (UA sniff).
 */
function visit_sessions(array $rows): array
{
    $open = [];   // vhash => session index
    $sessions = [];
    foreach ($rows as $r) {
        $h = $r['vhash'] ?: ('anon-' . $r['id']);
        $t = strtotime($r['created_at']);
        $idx = $open[$h] ?? null;
        if ($idx === null || $t - $sessions[$idx]['end_ts'] > 1800) {
            $sessions[] = [
                'vhash' => $h, 'who' => (int) $r['who'],
                'start' => $r['created_at'], 'end' => $r['created_at'],
                'start_ts' => $t, 'end_ts' => $t,
                'pages' => [], 'entry' => $r['path'],
                'referrer' => '', 'via' => '', 'verified' => 0, 'last_dwell' => 0,
                'n_human' => 0, 'n_bot' => 0,
                'mobile' => (bool) preg_match('/Mobile|Android|iPhone|iPad/i', (string) $r['ua']),
            ];
            $idx = array_key_last($sessions);
            $open[$h] = $idx;
        }
        $s = &$sessions[$idx];
        $s['pages'][] = $r['path'];
        $s['end'] = $r['created_at'];
        $s['end_ts'] = $t;
        $s['last_dwell'] = (int) ($r['dwell'] ?? 0); // newest row's time-on-page
        // Session class: self if any row is self; otherwise a majority vote.
        // A scanner that probes /login and /.env but also loads three real
        // pages used to count as human ("any human row wins"); now its probe
        // rows outvote the page hits. A real person who trips one 404 still
        // reads as human, because their real page views hold the majority.
        $w = (int) $r['who'];
        if ($w === 2 || $s['who'] === 2) $s['who'] = 2;
        else {
            if ($w === 0) $s['n_human']++; else $s['n_bot']++;
            $s['who'] = $s['n_bot'] > $s['n_human'] ? 1 : 0;
        }
        if ($s['referrer'] === '' && $r['referrer'] !== '' && !str_contains((string) $r['referrer'], 'billykulpa.com')) $s['referrer'] = (string) $r['referrer'];
        if ($s['via'] === '' && !empty($r['via'])) $s['via'] = (string) $r['via'];
        if (!empty($r['verified'])) $s['verified'] = 1;
        unset($s);
    }
    foreach ($sessions as &$s) {
        /* Gaps between page loads, plus the last page's reported dwell:
           a one-page visit read for five minutes now shows five minutes. */
        $s['seconds'] = max(0, $s['end_ts'] - $s['start_ts']) + $s['last_dwell'];
        $s['count'] = count($s['pages']);
    }
    unset($s);
    return array_reverse($sessions);
}
