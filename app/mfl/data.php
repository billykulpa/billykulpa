<?php
/** Read queries shared by the league site and the CMS tab. */

require_once __DIR__ . '/league.php';

/** The latest Sleeper sync or published rankings or poll change. NULL before the first sync. */
function mfl_last_updated(): ?string
{
    $sync = league_db()->query("SELECT MAX(finished_at) FROM mfl_sync_log WHERE status = 'ok'")->fetchColumn();
    $rank = league_db()->query("SELECT MAX(public_updated_at) FROM mfl_rankings WHERE status = 'published'")->fetchColumn();
    $poll = league_db()->query("SELECT MAX(public_updated_at) FROM mfl_polls WHERE status = 'published'")->fetchColumn();
    return max((string) $sync, (string) $rank, (string) $poll) ?: null;
}

/** "Username (First Last)", or just the username until a real name is entered. */
function mfl_owner_label(?string $username, ?string $realName): string
{
    if (!$username) return 'Vacant';
    return $realName ? "$username ($realName)" : $username;
}

function mfl_latest_season(): ?array
{
    return league_db()->query('SELECT * FROM mfl_seasons ORDER BY season DESC LIMIT 1')->fetch() ?: null;
}

/** Regular-season table: a tie counts as half a win, points for breaks ties. */
function mfl_standings(int $season): array
{
    $stmt = league_db()->prepare(
        'SELECT ts.*, o.display_name AS owner_name, o.real_name
           FROM mfl_team_seasons ts
           LEFT JOIN mfl_owners o ON o.id = ts.owner_id
          WHERE ts.season = ?
          ORDER BY (ts.wins + ts.ties / 2) DESC, ts.points_for DESC'
    );
    $stmt->execute([$season]);
    return $stmt->fetchAll();
}

/** Finished seasons, newest first, with the champion as named that year. */
function mfl_champions(): array
{
    return league_db()->query(
        'SELECT s.season, ts.team_name, o.display_name AS owner_name, o.real_name
           FROM mfl_seasons s
           JOIN mfl_team_seasons ts ON ts.season = s.season AND ts.franchise_id = s.champion_franchise_id
           LEFT JOIN mfl_owners o ON o.id = ts.owner_id
          ORDER BY s.season DESC'
    )->fetchAll();
}

/** Current owner of every franchise, with the season they took over. */
function mfl_franchises(): array
{
    return league_db()->query(
        'SELECT f.id, f.name, o.id AS owner_id, o.display_name AS owner_name, o.real_name, t.start_season, t.start_week
           FROM mfl_franchises f
           LEFT JOIN mfl_tenures t ON t.franchise_id = f.id AND t.end_season IS NULL
           LEFT JOIN mfl_owners o ON o.id = t.owner_id
          ORDER BY f.name'
    )->fetchAll();
}

/**
 * The week a transaction belongs to. Sleeper files everything before the
 * season under week 1, so the league calendar sorts those out: before that
 * season's draft is Offseason, from the draft to Labor Day is Preseason. With
 * no draft date on file yet, everything before Labor Day is Offseason.
 */
function mfl_transaction_week(int $season, int $week, string $at): string
{
    static $drafts = null;
    $drafts ??= league_db()->query('SELECT season, MIN(started_at) FROM mfl_drafts GROUP BY season')->fetchAll(PDO::FETCH_KEY_PAIR);

    if (central_time($at, 'Y-m-d') >= date('Y-m-d', strtotime("first monday of september $season"))) return "Week $week";
    $draft = $drafts[$season] ?? null;
    return $draft && $at >= $draft ? 'Preseason' : 'Offseason';
}

/**
 * What each player scored in a season: [player_id => points]. By default that's
 * every scored week; $regularOnly stops at the league's playoffs.
 */
function mfl_player_totals(int $season, bool $regularOnly = false): array
{
    static $cache = [];
    $key = "$season-" . (int) $regularOnly;
    if (isset($cache[$key])) return $cache[$key];
    $stmt = league_db()->prepare(
        'SELECT w.player_id, SUM(w.points) FROM mfl_player_weeks w
           JOIN mfl_seasons s ON s.season = w.season
          WHERE w.season = ? AND w.week <= s.last_scored_week AND (? = 0 OR w.week < s.playoff_week_start)
          GROUP BY w.player_id'
    );
    $stmt->execute([$season, (int) $regularOnly]);
    return $cache[$key] = array_map('floatval', $stmt->fetchAll(PDO::FETCH_KEY_PAIR));
}
