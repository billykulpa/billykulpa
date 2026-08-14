<?php
/**
 * First-party visit log — the "did a recruiter actually look?" ledger.
 *
 * Logs path, referrer, and user agent for public page views. No IP address,
 * no cookies, no client-side JavaScript, so there's nothing to disclose and
 * nothing for PageSpeed to count. Visits are classed at write time:
 *   0 = human, 1 = bot (user-agent heuristic), 2 = self (the admin session
 *   cookie is present, so it's almost certainly Billy browsing his own site).
 * Rows older than 90 days are pruned opportunistically (~1% of requests).
 *
 * Every failure mode is swallowed: if the visits table doesn't exist yet
 * (code deploys before SQL runs), the public site must not care.
 */

declare(strict_types=1);

const VISIT_BOT_RX = '/bot|crawl|spider|slurp|bingpreview|facebookexternalhit|headless|python-requests|curl|wget|monitor|uptime|scan|fetch|feed|lighthouse|pagespeed|pingdom|ahrefs|semrush|dataprovider|gpt|claude|perplexity|anthropic|openai/i';

function log_visit(string $path): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') return;

    $ua = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
    $referrer = substr((string) ($_SERVER['HTTP_REFERER'] ?? ''), 0, 255);

    $who = 0; // human
    if ($ua === '' || preg_match(VISIT_BOT_RX, $ua)) {
        $who = 1; // bot
    } elseif (!empty($_COOKIE[config()['session_name'] ?? ''])) {
        $who = 2; // self: admin session cookie present
    }

    try {
        $stmt = db()->prepare('INSERT INTO visits (path, referrer, ua, who) VALUES (?, ?, ?, ?)');
        $stmt->execute(['/' . $path, $referrer, $ua, $who]);

        if (random_int(1, 100) === 1) {
            db()->exec('DELETE FROM visits WHERE created_at < NOW() - INTERVAL 90 DAY');
        }
    } catch (Throwable $e) {
        // Table missing or DB hiccup: never let analytics break the site.
    }
}
