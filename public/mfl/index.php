<?php
/**
 * mfl.billykulpa.com — front controller.
 * The subdomain's docroot is public_html/mfl, so it needs its own entry point
 * and its own assets; nothing above this folder is reachable by URL from it.
 */

declare(strict_types=1);

/* Hostinger: app/ sits one level up, in public_html. Locally: two levels up. */
define('APP_DIR', is_dir(__DIR__ . '/../app') ? __DIR__ . '/../app' : __DIR__ . '/../../app');

require APP_DIR . '/db.php';
require APP_DIR . '/helpers.php';
require APP_DIR . '/mfl/site.php';

$path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/', '/');

/* The folder is also reachable as billykulpa.com/mfl. Keep one public address. */
$host = strtolower(explode(':', $_SERVER['HTTP_HOST'] ?? '')[0]);
if (in_array($host, ['billykulpa.com', 'www.billykulpa.com'], true)) {
    header('Location: ' . MFL_BASE_URL . '/' . preg_replace('#^mfl/?#', '', $path), true, 301);
    exit;
}

/* The CMS and its session cookie live on the main host only. */
if ($path === 'admin' || str_starts_with($path, 'admin/')) {
    header('Location: ' . config()['site']['base_url'] . '/' . $path);
    exit;
}

header('X-Robots-Tag: noindex, nofollow');
mfl_site_route($path);
