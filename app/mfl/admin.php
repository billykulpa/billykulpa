<?php
/**
 * The MFL tab: /admin/mfl/...
 * Open to admins and MFL Members. Every route here sets its own gate.
 */

require_once __DIR__ . '/data.php';

function mfl_admin_route(string $route): void
{
    switch ($route) {

        case 'mfl':
            require_login(ROLES_ALL);
            $flash = $_SESSION['mfl_flash'] ?? null;
            unset($_SESSION['mfl_flash']);
            render_admin('mfl/index', [
                'flash'      => $flash,
                'updated'    => mfl_last_updated(),
                'season'     => mfl_latest_season(),
                'seasons'    => (int) db()->query('SELECT COUNT(*) FROM mfl_seasons')->fetchColumn(),
                'matchups'   => (int) db()->query('SELECT COUNT(*) FROM mfl_matchups')->fetchColumn(),
                'picks'      => (int) db()->query('SELECT COUNT(*) FROM mfl_draft_picks')->fetchColumn(),
                'franchises' => mfl_franchises(),
                'syncs'      => db()->query('SELECT l.*, u.display_name, u.email FROM mfl_sync_log l LEFT JOIN users u ON u.id = l.user_id ORDER BY l.id DESC LIMIT 8')->fetchAll(),
                'title'      => 'MFL',
            ]);
            break;

        case 'mfl/sync':
            $user = require_login(ROLES_ALL);
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                verify_csrf();
                require_once __DIR__ . '/sync.php';
                set_time_limit(120);
                // Release the session so other CMS tabs don't hang while Sleeper answers.
                session_write_close();
                $result = mfl_sync('manual', (int) $user['id']);
                session_start();
                $_SESSION['mfl_flash'] = $result;
            }
            header('Location: /admin/mfl');
            break;

        default:
            http_response_code(404);
            exit('Not found.');
    }
}
