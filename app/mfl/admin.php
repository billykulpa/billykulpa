<?php
/**
 * The MFL tab: /admin/mfl/...
 * Open to admins and MFL Members. Every route here sets its own gate.
 */

require_once __DIR__ . '/data.php';
require_once __DIR__ . '/rankings.php';
require_once __DIR__ . '/polls.php';
require_once __DIR__ . '/email.php';

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
                    // The rankings are live either way, so a failed email is
                    // only a warning, and the editor offers a retry.
                    if ($action === 'publish' && !empty($_POST['email_league']) && !$ranking['emailed_at']) {
                        $fresh = mfl_ranking_for_edit((int) $ranking['id'], $user);
                        $mailError = mfl_email_league($fresh);
                        if ($mailError === null) $_SESSION['mfl_flash']['summary'] = 'Published and emailed to the league.';
                        else $_SESSION['mfl_flash']['warning'] = "The rankings are published, but the email didn't send. $mailError";
                    }
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
                'emailReady' => mfl_email_ready(),
                'recipients' => count(mfl_league_recipients()),
                'myEmail' => $user['email'],
                'title'   => 'Edit rankings',
            ]);
            break;

        // 'test' goes only to the person asking. 'league' and 'again' go to
        // every owner, and only for published rankings.
        case 'mfl/rankings/email':
            $user = require_login(ROLES_ALL);
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /admin/mfl/rankings'); exit; }
            verify_csrf();
            $ranking = mfl_ranking_for_edit((int) ($_POST['id'] ?? 0), $user);
            $mode = $_POST['mode'] ?? '';
            if ($mode === 'test') {
                $message = mfl_ranking_message($ranking);
                $message['subject'] = 'Test: ' . $message['subject'];
                $error = mfl_send_emails([$user['email']], $message, 'mfl-test/' . (int) $ranking['id'] . '/' . time());
                $done = 'Test sent to ' . $user['email'] . '.';
            } elseif (in_array($mode, ['league', 'again'], true) && $ranking['status'] === 'published'
                      && ($mode === 'again' || !$ranking['emailed_at'])) {
                $error = mfl_email_league($ranking, $mode === 'again');
                $done = 'Emailed to the league.';
            } else {
                $error = 'That email was not sent.';
                $done = '';
            }
            $_SESSION['mfl_flash'] = $error === null ? ['status' => 'ok', 'summary' => $done] : ['status' => 'error', 'summary' => $error];
            header('Location: /admin/mfl/rankings/edit?id=' . (int) $ranking['id']);
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

        // Best Pickups moved to the public site.
        case 'mfl/pickups':
            header('Location: ' . MFL_BASE_URL . '/pickups');
            break;

        case 'mfl/awards':
            require_login(ROLES_ALL);
            foreach (['history', 'reports', 'trades', 'players', 'awards'] as $file) require_once __DIR__ . "/$file.php";
            $seasons = array_reverse(array_keys(mfl_seasons()));
            $year = in_array((int) ($_GET['season'] ?? 0), $seasons, true) ? (int) $_GET['season'] : ($seasons[0] ?? 0);
            $flash = $_SESSION['mfl_flash'] ?? null;
            unset($_SESSION['mfl_flash']);
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                verify_csrf();
                $save = db()->prepare('INSERT INTO mfl_award_notes (season, award_key, note_md, updated_at) VALUES (?, ?, ?, ?)
                                       ON DUPLICATE KEY UPDATE note_md = VALUES(note_md), updated_at = VALUES(updated_at)');
                $clear = db()->prepare('DELETE FROM mfl_award_notes WHERE season = ? AND award_key = ?');
                foreach (mfl_awards($year) as $award) {
                    $note = mb_substr(trim((string) ($_POST['note'][$award['key']] ?? '')), 0, 2000);
                    if ($note === '') $clear->execute([$year, $award['key']]);
                    else $save->execute([$year, $award['key'], $note, date('Y-m-d H:i:s')]);
                }
                $_SESSION['mfl_flash'] = ['status' => 'ok', 'summary' => 'Saved.'];
                header("Location: /admin/mfl/awards?season=$year");
                exit;
            }
            render_admin('mfl/awards', ['flash' => $flash, 'awards' => mfl_awards($year), 'notes' => mfl_award_notes($year),
                'season' => $year, 'seasons' => $seasons, 'title' => 'Awards']);
            break;

        // Real names go on public pages, so entering them stays with the admin.
        case 'mfl/owners':
            require_login();
            $saved = false;
            $bad = [];
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                verify_csrf();
                $saveName = db()->prepare('UPDATE mfl_owners SET real_name = ? WHERE id = ?');
                $saveEmail = db()->prepare('UPDATE mfl_owners SET email = ? WHERE id = ?');
                foreach ((array) ($_POST['real_name'] ?? []) as $id => $name) {
                    $saveName->execute([mb_substr(trim((string) $name), 0, 120), (string) $id]);
                    $email = trim((string) ($_POST['email'][$id] ?? ''));
                    // A bad address leaves the old one in place and gets reported.
                    if ($email !== '' && (mb_strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL))) {
                        $bad[] = $email;
                        continue;
                    }
                    $saveEmail->execute([$email, (string) $id]);
                }
                $saved = true;
            }
            render_admin('mfl/owners', ['saved' => $saved, 'bad' => $bad, 'franchises' => mfl_franchises(),
                'emails' => db()->query('SELECT id, email FROM mfl_owners')->fetchAll(PDO::FETCH_KEY_PAIR), 'title' => 'Owners']);
            break;

        // The editors ping this to keep a long writing session alive, to notice
        // a lost sign-in right away, and to collect a fresh token before saving.
        case 'mfl/keepalive':
            require_login(ROLES_ALL);
            header('Content-Type: application/json');
            header('Cache-Control: no-store');
            echo json_encode(['csrf' => csrf_token()]);
            break;

        default:
            http_response_code(404);
            exit('Not found.');
    }
}
