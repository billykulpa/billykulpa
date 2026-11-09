<?php
/**
 * ssffl.billykulpa.com — front controller.
 * The subdomain's docroot is public_html/ssffl. It runs the same league site
 * as mfl.billykulpa.com (app/mfl), with SSFFL's settings and database.
 */

declare(strict_types=1);

/* Hostinger: app/ sits one level up, in public_html. Locally: two levels up. */
define('APP_DIR', is_dir(__DIR__ . '/../app') ? __DIR__ . '/../app' : __DIR__ . '/../../app');

require APP_DIR . '/db.php';
require APP_DIR . '/helpers.php';
require APP_DIR . '/mfl/site.php';
mfl_use_league('ssffl');
$GLOBALS['league_site'] = 'ssffl';

$path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/', '/');

/* The shared stylesheet, scripts and fonts live once, in the MFL folder next
   door, which this docroot can't reach by URL. They're served from here, with
   the same long caching as static files; mfl_asset() versions every URL. */
if (str_starts_with($path, 'assets/')) {
    mfl_serve_shared_asset(substr($path, 7));
    exit;
}

/* The folder is also reachable as billykulpa.com/ssffl. Keep one public address. */
$host = strtolower(explode(':', $_SERVER['HTTP_HOST'] ?? '')[0]);
if (in_array($host, ['billykulpa.com', 'www.billykulpa.com'], true)) {
    header('Location: ' . mfl_league()['base_url'] . '/' . preg_replace('#^ssffl/?#', '', $path), true, 301);
    exit;
}

/* The league's own admin, signed in with its own accounts. */
if ($path === 'admin' || str_starts_with($path, 'admin/')) {
    require APP_DIR . '/mfl/admin.php';
    mfl_site_admin_route($path);
    exit;
}

header('X-Robots-Tag: noindex, nofollow');
mfl_site_route($path);
