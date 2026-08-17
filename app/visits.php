<?php
/**
 * First-party visit log — the "did a recruiter actually look?" ledger.
 *
 * Logs path, referrer, and user agent for public page views. No IP address,
 * no cookies, no client-side JavaScript, so there's nothing to disclose and
 * nothing for PageSpeed to count. Visits are classed at write time:
 *   0 = human, 1 = bot, 2 = self (the admin session cookie is present, so
 *   it's almost certainly Billy browsing his own site).
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
 * can't follow anyone across days. The traffic page counts people, not hits.
 *
 * Rows older than 90 days are pruned opportunistically (~1% of requests).
 * Every failure mode is swallowed: if the visits table doesn't exist yet
 * (code deploys before SQL runs), the public site must not care.
 */

declare(strict_types=1);

const VISIT_BOT_RX = '/bot|crawl|spider|slurp|bingpreview|facebookexternalhit|headless|python|curl|wget|monitor|uptime|scan|fetch|feed|lighthouse|pagespeed|pingdom|ahrefs|semrush|dataprovider|gpt|claude|perplexity|anthropic|openai|go-http-client|okhttp|java\/|axios|node|libwww|http\.rb|scrapy|httpclient|ruby|perl|php\/|dart|postman|insomnia|bytespider|amazonbot|applebot|meta-externalagent|yandex|baidu|duckduck|petal|seznam|censys|shodan|zgrab|masscan|nmap|nikto|sqlmap|wpscan|expanse|internet-measurement|researchscan|mozilla\/4\.0|msie [1-9]\./i';

/* Paths that only scanners ask for. Even when one of these 200s (it won't),
   nobody typing it is a recruiter. */
const VISIT_PROBE_RX = '~\.(php|env|git|aws|sql|bak|old|zip|tar|gz|xml|txt|yml|yaml|json|ini|log|asp|aspx|jsp|cgi)$|wp-|wordpress|xmlrpc|phpmyadmin|/admin|/vendor/|/\.|cgi-bin|/config|/backup|/console|/actuator|/telescope|/debug~i';

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

    $who = 0; // human
    if ($ua === ''
        || preg_match(VISIT_BOT_RX, $ua)
        || preg_match(VISIT_PROBE_RX, '/' . $path)
        || $status >= 400
        || $lang === ''
        || ($accept !== '' && !str_contains($accept, 'text/html') && !str_contains($accept, '*/*'))
    ) {
        $who = 1; // bot
    } elseif (!empty($_COOKIE[config()['session_name'] ?? ''])) {
        $who = 2; // self: admin session cookie present
    }

    /* Daily-rotating visitor digest. Salt = site secret + date, so the same
       person on the same device is one visitor per day and nothing more. */
    $cfg = config();
    $salt = (string) ($cfg['visit_salt'] ?? ($cfg['db']['pass'] ?? 'bk')) . gmdate('Y-m-d');
    $vhash = substr(hash('sha256', $salt . ($_SERVER['REMOTE_ADDR'] ?? '') . $ua), 0, 16);

    try {
        try {
            $stmt = db()->prepare('INSERT INTO visits (path, referrer, ua, who, vhash) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute(['/' . $path, $referrer, $ua, $who, $vhash]);
        } catch (PDOException $e) {
            // vhash column not migrated yet: log without it rather than lose the row.
            $stmt = db()->prepare('INSERT INTO visits (path, referrer, ua, who) VALUES (?, ?, ?, ?)');
            $stmt->execute(['/' . $path, $referrer, $ua, $who]);
        }

        if (random_int(1, 100) === 1) {
            db()->exec('DELETE FROM visits WHERE created_at < NOW() - INTERVAL 90 DAY');
        }
    } catch (Throwable $e) {
        // Table missing or DB hiccup: never let analytics break the site.
    }
}
