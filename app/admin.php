<?php
/**
 * Admin routes: /admin/...
 * Login, page meta editing (H1 / meta title / meta description), blog CRUD.
 */

function admin_route(string $route): void
{
    start_session();

    switch (true) {

        /* ------------------------- First-run setup ------------------------ */
        // Works only while the users table is empty; after that it 404s.
        case $route === 'setup':
            $count = (int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn();
            if ($count > 0) { http_response_code(404); exit('Not found.'); }
            $error = '';
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                verify_csrf();
                $email = trim($_POST['email'] ?? '');
                $pass  = $_POST['password'] ?? '';
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = 'Enter a valid email address.';
                } elseif (strlen($pass) < 12) {
                    $error = 'Use at least 12 characters for the password.';
                } else {
                    $stmt = db()->prepare('INSERT INTO users (email, password_hash) VALUES (?, ?)');
                    $stmt->execute([$email, password_hash($pass, PASSWORD_DEFAULT)]);
                    $_SESSION['user_id'] = (int) db()->lastInsertId();
                    header('Location: /admin');
                    exit;
                }
            }
            render_admin('setup', ['error' => $error, 'title' => 'Set up admin']);
            break;

        /* ------------------------------ Auth ------------------------------ */
        case $route === 'login':
            if (current_user()) { header('Location: /admin'); exit; }
            $error = '';
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                verify_csrf();
                $stmt = db()->prepare('SELECT * FROM users WHERE email = ?');
                $stmt->execute([trim($_POST['email'] ?? '')]);
                $user = $stmt->fetch();
                if ($user && password_verify($_POST['password'] ?? '', $user['password_hash'])) {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = (int) $user['id'];
                    header('Location: /admin');
                    exit;
                }
                sleep(2); // flat cost per failed attempt — cheap, effective brute-force friction
                $error = 'Wrong email or password.';
            }
            render_admin('login', ['error' => $error, 'title' => 'Sign in']);
            break;

        case $route === 'logout':
            verify_csrf();
            session_destroy();
            header('Location: /admin/login');
            break;

        /* ------------------------- Change password ------------------------ */
        // Requires an authenticated session AND the current password, plus the
        // CSRF token every admin POST carries. A failed current-password check
        // pays the same 2-second cost as a failed login.
        case $route === 'password':
            $user = require_login();
            $error = $ok = '';
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                verify_csrf();
                $current = $_POST['current_password'] ?? '';
                $new     = $_POST['new_password'] ?? '';
                $confirm = $_POST['confirm_password'] ?? '';
                if (!password_verify($current, $user['password_hash'])) {
                    sleep(2);
                    $error = 'Current password is incorrect.';
                } elseif (strlen($new) < 12) {
                    $error = 'Use at least 12 characters for the new password.';
                } elseif ($new !== $confirm) {
                    $error = 'New passwords do not match.';
                } else {
                    $stmt = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
                    $stmt->execute([password_hash($new, PASSWORD_DEFAULT), $user['id']]);
                    session_regenerate_id(true);
                    $ok = 'Password updated.';
                }
            }
            render_admin('password', ['error' => $error, 'ok' => $ok, 'title' => 'Change password']);
            break;

        /* --------------------------- Dashboard ---------------------------- */
        case $route === '':
            $user = require_login();
            $pageCount = (int) db()->query('SELECT COUNT(*) FROM pages')->fetchColumn();
            $postCount = (int) db()->query('SELECT COUNT(*) FROM posts')->fetchColumn();
            $drafts    = (int) db()->query("SELECT COUNT(*) FROM posts WHERE status='draft'")->fetchColumn();
            render_admin('dashboard', compact('user', 'pageCount', 'postCount', 'drafts') + ['title' => 'Dashboard']);
            break;

        /* ----------------------------- Pages ------------------------------ */
        case $route === 'pages':
            require_login();
            $pages = db()->query('SELECT * FROM pages ORDER BY id')->fetchAll();
            render_admin('pages', ['pages' => $pages, 'title' => 'Pages']);
            break;

        case $route === 'pages/edit':
            require_login();
            $id = (int) ($_GET['id'] ?? 0);
            $stmt = db()->prepare('SELECT * FROM pages WHERE id = ?');
            $stmt->execute([$id]);
            $pg = $stmt->fetch();
            if (!$pg) { http_response_code(404); exit('Page not found.'); }
            $saved = false;
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                verify_csrf();
                $stmt = db()->prepare(
                    'UPDATE pages SET h1 = ?, meta_title = ?, meta_description = ? WHERE id = ?'
                );
                $stmt->execute([
                    trim($_POST['h1'] ?? ''),
                    trim($_POST['meta_title'] ?? ''),
                    trim($_POST['meta_description'] ?? ''),
                    $id,
                ]);
                $stmt = db()->prepare('SELECT * FROM pages WHERE id = ?');
                $stmt->execute([$id]);
                $pg = $stmt->fetch();
                $saved = true;
            }
            render_admin('page-edit', ['pg' => $pg, 'saved' => $saved, 'title' => 'Edit: ' . $pg['label']]);
            break;

        /* ----------------------------- Posts ------------------------------ */
        case $route === 'posts':
            require_login();
            $posts = db()->query('SELECT id, slug, title, status, published_at, updated_at FROM posts ORDER BY COALESCE(published_at, created_at) DESC')->fetchAll();
            render_admin('posts', ['posts' => $posts, 'title' => 'Posts']);
            break;

        case $route === 'posts/new':
        case $route === 'posts/edit':
            require_login();
            $post = [
                'id' => 0, 'slug' => '', 'title' => '', 'meta_title' => '',
                'meta_description' => '', 'body_md' => '', 'status' => 'draft',
            ];
            if ($route === 'posts/edit') {
                $stmt = db()->prepare('SELECT * FROM posts WHERE id = ?');
                $stmt->execute([(int) ($_GET['id'] ?? 0)]);
                $post = $stmt->fetch();
                if (!$post) { http_response_code(404); exit('Post not found.'); }
            }
            $saved = false;
            $error = '';
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                verify_csrf();
                $title = trim($_POST['title'] ?? '');
                $slug  = slugify(trim($_POST['slug'] ?? '') ?: $title);
                $md    = $_POST['body_md'] ?? '';
                $status = ($_POST['status'] ?? 'draft') === 'published' ? 'published' : 'draft';
                if ($title === '' || $slug === '') {
                    $error = 'A title is required.';
                } else {
                    $html = markdown_to_html($md);
                    try {
                        if ($post['id']) {
                            $stmt = db()->prepare(
                                'UPDATE posts SET slug=?, title=?, meta_title=?, meta_description=?,
                                 body_md=?, body_html=?, status=?,
                                 published_at = IF(? = "published" AND published_at IS NULL, NOW(), published_at)
                                 WHERE id=?'
                            );
                            $stmt->execute([
                                $slug, $title,
                                trim($_POST['meta_title'] ?? ''), trim($_POST['meta_description'] ?? ''),
                                $md, $html, $status, $status, $post['id'],
                            ]);
                        } else {
                            $stmt = db()->prepare(
                                'INSERT INTO posts (slug, title, meta_title, meta_description, body_md, body_html, status, published_at)
                                 VALUES (?,?,?,?,?,?,?, IF(? = "published", NOW(), NULL))'
                            );
                            $stmt->execute([
                                $slug, $title,
                                trim($_POST['meta_title'] ?? ''), trim($_POST['meta_description'] ?? ''),
                                $md, $html, $status, $status,
                            ]);
                            $post['id'] = (int) db()->lastInsertId();
                        }
                        $stmt = db()->prepare('SELECT * FROM posts WHERE id = ?');
                        $stmt->execute([$post['id']]);
                        $post = $stmt->fetch();
                        $saved = true;
                    } catch (PDOException $e) {
                        $error = str_contains($e->getMessage(), 'Duplicate')
                            ? 'That slug is already in use — pick another.'
                            : 'Could not save the post.';
                        $post = array_merge($post, [
                            'slug' => $slug, 'title' => $title, 'body_md' => $md, 'status' => $status,
                            'meta_title' => trim($_POST['meta_title'] ?? ''),
                            'meta_description' => trim($_POST['meta_description'] ?? ''),
                        ]);
                    }
                }
            }
            render_admin('post-edit', ['post' => $post, 'saved' => $saved, 'error' => $error,
                'title' => $post['id'] ? 'Edit post' : 'New post']);
            break;

        case $route === 'posts/delete':
            require_login();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                verify_csrf();
                $stmt = db()->prepare('DELETE FROM posts WHERE id = ?');
                $stmt->execute([(int) ($_POST['id'] ?? 0)]);
            }
            header('Location: /admin/posts');
            break;

        default:
            http_response_code(404);
            exit('Not found.');
    }
}
