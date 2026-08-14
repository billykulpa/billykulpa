<?php
// Copy this file to config.php and fill in your values.
// config.php is ignored by the deploy zip on purpose — never commit real credentials.

return [
    // Optional: override where the Restreak case study pulls its live numbers
    // from (defaults to https://restreak.com/api/stats.php). Useful for local
    // dev against a local Restreak instance.
    // 'restreak_stats_url' => 'http://127.0.0.1:8095/api/stats.php',

    'db' => [
        'host'    => '127.0.0.1',
        'port'    => 3306,        // MAMP local dev: 8889
        'name'    => 'billykulpa',
        'user'    => 'bk',        // MAMP local dev: root
        'pass'    => 'bkpass',    // MAMP local dev: root
        'charset' => 'utf8mb4',
    ],

    // Used in <title> fallbacks and structured data.
    'site' => [
        'name'     => 'Billy Kulpa',
        'base_url' => 'https://billykulpa.com',
    ],

    // Session cookie name for the admin.
    'session_name' => 'bk_admin',

    // Write key for /api/jobtracker.php (the morning job-search run files
    // its finds through it). Any long random string; leave it out to keep
    // the endpoint disabled. Never commit the real value.
    // 'jobtracker_key' => 'change-me',

    // Contact form.
    'contact' => [
        'to'        => 'billy@billykulpa.com',
        // 'log' writes to logs/mail.log (local dev); 'mail' uses PHP mail().
        'mail_mode' => 'log',
    ],

    // Cloudflare Turnstile (spam protection on /contact).
    // These are Cloudflare's official ALWAYS-PASS test keys — swap in real
    // keys from the Cloudflare dashboard (free) before launch.
    'turnstile' => [
        'site_key'   => '1x00000000000000000000AA',
        'secret_key' => '1x0000000000000000000000000000000AA',
    ],
];
