<?php
/** Read queries shared by the league site and the CMS tab. */

/** The latest Sleeper sync or published rankings change. NULL before the first sync. */
function mfl_last_updated(): ?string
{
    $sync = db()->query("SELECT MAX(finished_at) FROM mfl_sync_log WHERE status = 'ok'")->fetchColumn();
    $rank = db()->query("SELECT MAX(public_updated_at) FROM mfl_rankings WHERE status = 'published'")->fetchColumn();
    return max((string) $sync, (string) $rank) ?: null;
}

/** "Username (First Last)", or just the username until a real name is entered. */
function mfl_owner_label(?string $username, ?string $realName): string
{
    if (!$username) return 'Vacant';
    return $realName ? "$username ($realName)" : $username;
}

function mfl_latest_season(): ?array
{
    return db()->query('SELECT * FROM mfl_seasons ORDER BY season DESC LIMIT 1')->fetch() ?: null;
}

/** Regular-season table: a tie counts as half a win, points for breaks ties. */
function mfl_standings(int $season): array
{
    $stmt = db()->prepare(
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
    return db()->query(
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
    return db()->query(
        'SELECT f.id, f.name, o.id AS owner_id, o.display_name AS owner_name, o.real_name, t.start_season, t.start_week
           FROM mfl_franchises f
           LEFT JOIN mfl_tenures t ON t.franchise_id = f.id AND t.end_season IS NULL
           LEFT JOIN mfl_owners o ON o.id = t.owner_id
          ORDER BY f.name'
    )->fetchAll();
}
