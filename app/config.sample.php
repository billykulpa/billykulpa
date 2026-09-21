<?php
/**
 * Template for app/config.php. Copy it, fill in real values, and keep the
 * copy out of git.
 *
 * On production (Hostinger) config.php lives at ~/app/config.php, outside
 * public_html. deploy.yml excludes it from the app/ sync, so a push will
 * neither overwrite it NOR restore it. It is the only file on the server
 * that exists nowhere else — back it up somewhere you would actually find
 * it, because losing it takes the whole site down, not just one endpoint.
 *
 * This file IS in the repo, and the repo is PUBLIC. Everything below is a
 * dummy, a Cloudflare test key, or a local-dev default. Never paste a real
 * credential into this file.
 *
 * When you change the shape of config.php, change it here too. This is the
 * only written record of what belongs in that file.
 */

return [

    // ---- Database ------------------------------------------------------
    'db' => [
        'host'    => '127.0.0.1',
        'port'    => 3306,        // MAMP local dev: 8889
        'name'    => 'billykulpa',
        'user'    => 'bk',        // MAMP local dev: root
        'pass'    => 'bkpass',    // MAMP local dev: root
        'charset' => 'utf8mb4',
    ],

    // ---- Site ----------------------------------------------------------
    // base_url is the canonical host, used in <title> fallbacks and
    // structured data. Apex, no www — www should 301 here so the two hosts
    // never both answer 200.
    'site' => [
        'name'     => 'Billy Kulpa',
        'base_url' => 'https://billykulpa.com',
    ],

    // ---- Admin ---------------------------------------------------------
    // Session cookie name for the admin.
    'session_name' => 'bk_admin',

    // ---- API keys ------------------------------------------------------
    // Two SEPARATE keys, one per endpoint. They are NOT interchangeable.
    //
    //   jobtracker_key  ->  public/api/jobtracker.php   (read/write tracker rows)
    //   jobwatch_key    ->  public/api/jobwatch.php     (one call fans out to
    //                                                    ~363 ATS board polls)
    //
    // Each is read as config()['<name>'] ?? '' and compared with hash_equals,
    // so a missing or wrong value makes the endpoint return 404 instead of
    // failing loudly. That is deliberate — it keeps the endpoints invisible —
    // but it also means a bad key is indistinguishable from a dead endpoint
    // from the outside, and the site itself keeps working either way.
    //
    // Pasting one value over the other took both endpoints down for five days
    // in September 2026. Change one at a time and re-test BOTH afterward.
    //
    // Omit a key entirely to leave that endpoint disabled. Use any long
    // random string; never commit the real values.
    // 'jobtracker_key' => 'change-me',
    // 'jobwatch_key'   => 'change-me-as-well',

    // ---- Contact form --------------------------------------------------
    'contact' => [
        'to'        => 'billy@billykulpa.com',
        // 'log' writes to logs/mail.log (local dev); 'mail' uses PHP mail()
        // in production. PHP mail() on shared hosting is the least reliable
        // delivery path — switch to authenticated SMTP if the host offers it.
        'mail_mode' => 'log',
    ],

    // ---- Cloudflare Turnstile (spam protection on /contact) ------------
    // These are Cloudflare's official ALWAYS-PASS test keys. Swap in real
    // keys from the Cloudflare dashboard (free) in config.php only — real
    // Turnstile keys must never appear in this file.
    'turnstile' => [
        'site_key'   => '1x00000000000000000000AA',
        'secret_key' => '1x0000000000000000000000000000000AA',
    ],

    // ---- Optional ------------------------------------------------------
    // Override where the Restreak case study pulls its live numbers from.
    // Defaults to https://restreak.com/api/stats.php. Useful for local dev
    // against a local Restreak instance.
    // 'restreak_stats_url' => 'http://127.0.0.1:8095/api/stats.php',

    // Salt for the daily visitor digest in the traffic log. Falls back to the
    // DB password, which is fine — neither value ever leaves the server.
    // 'visit_salt' => 'change-me-too',
];
