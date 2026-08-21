<?php
/**
 * Beacon endpoint for the visit log. The public layout fires one request
 * here after the page loads (navigator.sendBeacon). Browsers do; scrapers
 * don't. It marks the caller's visit rows from the last 30 minutes as
 * verified. When the visitor leaves the page, a second beacon arrives
 * with ?t=<seconds on page>, recorded as the current page view's dwell
 * time. Nothing is returned either way.
 */
declare(strict_types=1);
define('APP_DIR', is_dir(__DIR__ . '/../../app') ? __DIR__ . '/../../app' : __DIR__ . '/../app');
require APP_DIR . '/db.php';
require APP_DIR . '/helpers.php';
require APP_DIR . '/visits.php';
http_response_code(204);
header('Cache-Control: no-store');
if (!visit_is_self()) {
    if (isset($_GET['t'])) visit_dwell((int) $_GET['t']);
    else visit_verify();
}
