<?php
// Router for the PHP built-in server, SSFFL subdomain (mimics public/ssffl/.htaccess).
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$file = __DIR__ . '/public/ssffl' . $path;
if ($path !== '/' && file_exists($file) && !is_dir($file)) return false;
$_SERVER['SCRIPT_NAME'] = '/index.php';
require __DIR__ . '/public/ssffl/index.php';
