<?php
/**
 * Cron entry point for the Sleeper sync. See README-DEPLOY.md.
 *   php app/mfl/sync-cli.php          current season, plus anything new
 *   php app/mfl/sync-cli.php --full   re-import finished seasons too
 *   php app/mfl/sync-cli.php --values recompute trade values even if done today
 */

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

define('APP_DIR', dirname(__DIR__));
require APP_DIR . '/db.php';
require APP_DIR . '/helpers.php';
require __DIR__ . '/sync.php';

$result = mfl_sync('cron', null, in_array('--full', $argv, true), in_array('--values', $argv, true));
echo $result['status'], ': ', $result['summary'], PHP_EOL;
exit($result['status'] === 'error' ? 1 : 0);
