<?php

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $c   = config()['db'];
        $port = $c['port'] ?? 3306;   // MAMP's MySQL runs on 8889
        $dsn = "mysql:host={$c['host']};port={$port};dbname={$c['name']};charset={$c['charset']}";
        $pdo = new PDO($dsn, $c['user'], $c['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            /* Pin the connection collation to the one the tables use.
               Hostinger's MariaDB defaults the utf8mb4 connection to
               general_ci while schema.sql builds unicode_ci tables; any
               literal-vs-parameter comparison (e.g. the published_at IF in
               the post editor) then dies with "illegal mix of collations".
               One collation everywhere makes the class of bug impossible. */
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci',
        ]);
    }
    return $pdo;
}

function config(): array
{
    static $config = null;
    if ($config === null) {
        $config = require __DIR__ . '/config.php';
    }
    return $config;
}
