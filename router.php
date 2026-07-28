<?php
// Router for the PHP built-in server (mimics .htaccess)
// rawurldecode: some migrated asset filenames contain spaces (%20 in the URL);
// Apache decodes these natively, so the dev router must too.
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$file = __DIR__ . '/public' . $path;
if ($path !== '/' && file_exists($file) && !is_dir($file)) return false;
$_SERVER['SCRIPT_NAME'] = '/index.php';
require __DIR__ . '/public/index.php';
