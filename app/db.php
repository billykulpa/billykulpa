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
