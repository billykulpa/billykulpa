<?php
/**
 * The MFL tab: /admin/mfl/...
 * Open to admins and MFL Members. Every route here sets its own gate.
 */

require_once __DIR__ . '/data.php';
require_once __DIR__ . '/rankings.php';
require_once __DIR__ . '/polls.php';

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

        case 'mfl/rankings':
            $user = require_login(ROLES_ALL);
            $flash = $_SESSION['mfl_flash'] ?? null;
            unset($_SESSION['mfl_flash']);
            $season = mfl_latest_season();
            render_admin('mfl/rankings', [
                'flash'    => $flash,
                'user'     => $user,
                'rankings' => db()->query('SELECT * FROM mfl_rankings ORDER BY season DESC, week DESC')->fetchAll(),
                'seasons'  => db()->query('SELECT season FROM mfl_seasons ORDER BY season DESC')->fetchAll(PDO::FETCH_COLUMN),
                'nextWeek' => $season ? mfl_next_ranking_week($season) : 0,
                'title'    => 'Power Rankings',
            ]);
            break;

        case 'mfl/rankings/new':
            $user = require_login(ROLES_ALL);
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /admin/mfl/rankings'); exit; }
            verify_csrf();
            $season = (int) ($_POST['season'] ?? 0);
            $week   = (int) ($_POST['week'] ?? -1);
            $stmt = db()->prepare('SELECT COUNT(*) FROM mfl_seasons WHERE season = ?');
            $stmt->execute([$season]);
            if (!$stmt->fetchColumn() || $week < 0 || $week > MFL_FINAL_WEEK) {
                $_SESSION['mfl_flash'] = ['status' => 'error', 'summary' => 'Choose a season and week.'];
                header('Location: /admin/mfl/rankings');
                exit;
            }
            $id = mfl_ranking_create($season, $week, $user);
            if (!$id) {
                $_SESSION['mfl_flash'] = ['status' => 'error',
                    'summary' => mfl_week_label($week) . " of $season already has rankings. There's one set per week."];
                header('Location: /admin/mfl/rankings');
                exit;
            }
            header("Location: /admin/mfl/rankings/edit?id=$id");
            break;

        case 'mfl/rankings/edit':
            $user = require_login(ROLES_ALL);
            $ranking = mfl_ranking_for_edit((int) ($_GET['id'] ?? 0), $user);
            $flash = $_SESSION['mfl_flash'] ?? null;
            unset($_SESSION['mfl_flash']);
            $posted = null;
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                verify_csrf();
                $action = $_POST['action'] ?? 'save';
                $error = mfl_ranking_save($ranking, $_POST, $action, $user);
                if ($error === null) {
                    $_SESSION['mfl_flash'] = ['status' => 'ok', 'summary' => match ($action) {
                        'publish'   => 'Published.',
                        'unpublish' => 'Unpublished. It is a draft again.',
                        default     => 'Saved.',
                    }];
                    header('Location: /admin/mfl/rankings/edit?id=' . (int) $ranking['id']);
                    exit;
                }
                // Hand the typed text back, so a rejected save loses nothing.
                $flash = ['status' => 'error', 'summary' => $error];
                $posted = $_POST;
            } elseif (isset($_GET['revision'])) {
                // Loading a version only fills the form. Nothing changes until
                // someone saves, and that save becomes a new row in the log.
                $revision = mfl_ranking_revision((int) $ranking['id'], (int) $_GET['revision']);
                if ($revision) {
                    $posted = $revision['snapshot'];
                    $flash = ['status' => 'ok', 'summary' => 'Loaded the version from '
                        . central_time($revision['created_at'], 'M j, g:i a') . ' CT. Nothing changes until you save.'];
                }
            }
            render_admin('mfl/ranking-edit', [
                'flash'   => $flash,
                'ranking' => $posted ? array_merge($ranking, [
                    'week' => (int) ($posted['week'] ?? $ranking['week']),
                    'title' => (string) ($posted['title'] ?? ''),
                    'intro_md' => (string) ($posted['intro_md'] ?? ''),
                ]) : $ranking,
                'rows'    => mfl_editor_rows($ranking, $posted),
                'revisions' => mfl_ranking_revisions((int) $ranking['id']),
                'title'   => 'Edit rankings',
            ]);
            break;

        case 'mfl/rankings/delete':
            $user = require_login(ROLES_ALL);
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                verify_csrf();
                $ranking = mfl_ranking_for_edit((int) ($_POST['id'] ?? 0), $user);
                // A published set has to be unpublished first, so one click
                // can't remove something the whole league has read.
                if ($ranking['status'] === 'draft') {
                    db()->prepare('DELETE FROM mfl_rankings WHERE id = ?')->execute([$ranking['id']]);
                    $_SESSION['mfl_flash'] = ['status' => 'ok', 'summary' => 'Draft deleted.'];
                }
            }
            header('Location: /admin/mfl/rankings');
            break;

        case 'mfl/polls':
            $user = require_login(ROLES_ALL);
            $flash = $_SESSION['mfl_flash'] ?? null;
            unset($_SESSION['mfl_flash']);
            render_admin('mfl/polls', ['flash' => $flash, 'user' => $user, 'polls' => mfl_admin_polls(), 'title' => 'Polls']);
            break;

        case 'mfl/polls/new':
        case 'mfl/polls/edit':
            $user = require_login(ROLES_ALL);
            $poll = $route === 'mfl/polls/edit' ? mfl_poll_for_edit((int) ($_GET['id'] ?? 0), $user) : null;
            $flash = $_SESSION['mfl_flash'] ?? null;
            unset($_SESSION['mfl_flash']);
            $posted = null;
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                verify_csrf();
                $action = $_POST['action'] ?? 'save';
                $result = mfl_poll_save($poll, $_POST, $action, $user);
                if (is_int($result)) {
                    $_SESSION['mfl_flash'] = ['status' => 'ok', 'summary' => match ($action) {
                        'publish'   => 'Published.',
                        'unpublish' => 'Unpublished. It is a draft again, and its votes are kept.',
                        'close'     => 'Poll closed.',
                        default     => 'Saved.',
                    }];
                    header("Location: /admin/mfl/polls/edit?id=$result");
                    exit;
                }
                // Hand the typed text back, so a rejected save loses nothing.
                $flash = ['status' => 'error', 'summary' => $result];
                $posted = $_POST;
            }
            render_admin('mfl/poll-edit', [
                'flash' => $flash,
                'form'  => mfl_poll_form($poll, $posted),
                'title' => $poll ? 'Edit poll' : 'New poll',
            ]);
            break;

        case 'mfl/polls/delete':
            $user = require_login(ROLES_ALL);
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                verify_csrf();
                $poll = mfl_poll_for_edit((int) ($_POST['id'] ?? 0), $user);
                // Same rule as rankings: unpublish first, so one click can't
                // remove something the league has already seen.
                if ($poll['status'] === 'draft') {
                    db()->prepare('DELETE FROM mfl_polls WHERE id = ?')->execute([$poll['id']]);
                    $_SESSION['mfl_flash'] = ['status' => 'ok', 'summary' => 'Poll deleted.'];
                }
            }
            header('Location: /admin/mfl/polls');
            break;

        // Real names go on public pages, so entering them stays with the admin.
        case 'mfl/owners':
            require_login();
            $saved = false;
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                verify_csrf();
                $stmt = db()->prepare('UPDATE mfl_owners SET real_name = ? WHERE id = ?');
                foreach ((array) ($_POST['real_name'] ?? []) as $id => $name) {
                    $stmt->execute([mb_substr(trim((string) $name), 0, 120), (string) $id]);
                }
                $saved = true;
            }
            render_admin('mfl/owners', ['saved' => $saved, 'franchises' => mfl_franchises(), 'title' => 'Owners']);
            break;

        // The editor pings this so a long writing session can't outlive the
        // PHP session and lose its work to a rejected token on save.
        case 'mfl/keepalive':
            require_login(ROLES_ALL);
            http_response_code(204);
            break;

        default:
            http_response_code(404);
            exit('Not found.');
    }
}
