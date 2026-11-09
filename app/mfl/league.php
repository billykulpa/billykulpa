<?php
/**
 * The leagues this code runs. One codebase serves every league site; each
 * league keeps its own copy of the mfl_ tables. ("mfl_" is the engine's name
 * now, not just the Midwest Football League's.)
 *
 * Each league names a block under config()['league_db'] for a database of its
 * own, which keeps one league's rows from ever showing on another's site. CMS
 * accounts (the users table) stay in the main database for all. A league with
 * 'db' => null would use the main database; MFL did until November 2026.
 *
 * Scoring, lineup, team count and playoff format are read from Sleeper, so
 * they need no entry here.
 */

const MFL_LEAGUES = [
    'mfl' => [
        'name'       => 'Midwest Football League',
        'short'      => 'MFL',
        'base_url'   => 'https://mfl.billykulpa.com',
        'founded'    => 2025,
        // logo.svg, logo-email.png, logo-share.png and the icons in public/mfl/assets.
        'logo'       => true,
        // Where the league chain starts on a fresh database. After the first
        // sync the newest stored league takes over.
        'seed_league_id' => '1312119304191238144',
        // A Sleeper user in the league (Billy), whose league list is how next
        // season's renewed league is found.
        'tracker_user_id' => '1175642773426860032',
        'db'         => 'mfl',
        'model'      => 'model.json',
        // Bench depth value is off for MFL, so its trade values stay exactly
        // as they were before SSFFL. See mfl_depth().
        'depth_value' => false,
        // Rookie drafts don't follow the NFL draft: each position's place in a
        // class is ordered by a * (NFL overall pick) + b. Fit to Sleeper's
        // 2021-25 rookie rankings for 1QB leagues (rank agreement 0.68 on NFL
        // order alone, 0.86 with these): quarterbacks much later, running
        // backs much earlier, tight ends a bit later. Wide receivers are 1, 0.
        'rookie_order' => ['QB' => [3, 24], 'RB' => [0.5, 0], 'TE' => [1.5, 0]],
        // How the trade values FAQ describes that order.
        // The season MVP award's name on the Awards page.
        'mvp_award'  => 'The Golden Cob',
        'rookie_order_note' => 'adjusted the way 1-QB rookie drafts go: quarterbacks much later, running backs much earlier, tight ends a little later. That order matches Sleeper\'s 2021-25 rookie rankings closely.',
    ],
    'ssffl' => [
        'name'       => 'Super Soft Fantasy Football League',
        'short'      => 'SSFFL',
        'base_url'   => 'https://ssffl.billykulpa.com',
        'founded'    => 2022,
        // No logo yet: the site shows the name as a text wordmark. To add one,
        // put the logo files in public/ssffl/assets and set this to true.
        'logo'       => false,
        // MFL's sender comes from config.php's mfl_email block.
        'email_from' => 'SSFFL Power Rankings <rankings@billykulpa.com>',
        'seed_league_id' => '1312122983090749440',
        'tracker_user_id' => '1175642773426860032',
        'db'         => 'ssffl',
        'model'      => 'model-ssffl.json',
        // Ten 10-man benches plus taxi squads leave almost nothing on waivers,
        // so a backup who fills in for byes and injuries is worth real points.
        'depth_value' => true,
        // Fit to SSFFL's own 2023-26 rookie drafts, 159 picks (rank agreement
        // 0.86, against 0.81 for MFL's numbers and 0.78 for NFL order alone;
        // every leave-one-draft-out fit landed on the same numbers).
        // Superflex: quarterbacks go about where the NFL takes them, running
        // backs well before, tight ends after.
        'rookie_order' => ['QB' => [1.5, 12], 'RB' => [0.8, -17], 'TE' => [2.3, -20]],
        'mvp_award'  => 'The Golden Roll',   // softest league, softest trophy
        'rookie_order_note' => 'adjusted the way this league\'s own rookie drafts have gone since 2023: quarterbacks about where the NFL takes them, running backs well before that, tight ends after. That order matches the league\'s 2023-26 drafts closely.',
    ],
];

/** Pick the league for this request or CLI run. Call before anything reads league data. */
function mfl_use_league(string $key): void
{
    if (!isset(MFL_LEAGUES[$key])) throw new InvalidArgumentException("No league '$key'.");
    $GLOBALS['mfl_league_key'] = $key;
}

/** The active league's settings, with its key. MFL unless a caller chose another. */
function mfl_league(): array
{
    $key = $GLOBALS['mfl_league_key'] ?? 'mfl';
    return ['key' => $key] + MFL_LEAGUES[$key];
}

/** Where the league's CMS lives on its own site. */
function mfl_admin_base(): string
{
    return '/admin';
}

/** Prefix for editor autosave keys, so two leagues' drafts with the same id can't restore into each other. */
function mfl_autosave_prefix(): string
{
    $key = mfl_league()['key'];
    return $key === 'mfl' ? '' : "$key-";   // MFL's keys stay as they were, so no saved draft is lost
}

/** "--league=ssffl" from a CLI script's arguments, or 'mfl'. */
function mfl_cli_league(array $argv): string
{
    foreach ($argv as $arg) if (str_starts_with($arg, '--league=')) return substr($arg, 9);
    return 'mfl';
}

/** The connection that holds the active league's mfl_ tables. */
function league_db(): PDO
{
    static $pdos = [];
    $l = mfl_league();
    if ($l['db'] === null) return db();
    if (!isset($pdos[$l['key']])) {
        $c = config()['league_db'][$l['db']] ?? null;
        if (!$c) throw new RuntimeException("config.php has no league_db['{$l['db']}'] block.");
        $pdos[$l['key']] = pdo_connect($c);
    }
    return $pdos[$l['key']];
}
