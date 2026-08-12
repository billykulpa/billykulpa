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

        /* ------------------------ Portrait upload ------------------------- */
        // Saves to assets/uploads/portrait.webp — a path the deploy workflow
        // excludes, so an uploaded portrait survives every push. The About
        // page falls back to the committed photo until an upload exists.
        case $route === 'portrait':
            require_login();
            $error = $ok = '';
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                verify_csrf();
                [$ok, $error] = save_portrait($_FILES['portrait'] ?? null);
            }
            render_admin('portrait', ['error' => $error, 'ok' => $ok, 'title' => 'Portrait']);
            break;

        /* --------------------------- Dashboard ---------------------------- */
        case $route === '':
            $user = require_login();
            $pageCount = (int) db()->query("SELECT COUNT(*) FROM pages WHERE slug NOT LIKE 'work/%'")->fetchColumn();
            $caseCount = (int) db()->query("SELECT COUNT(*) FROM pages WHERE slug LIKE 'work/%'")->fetchColumn();
            $postCount = (int) db()->query('SELECT COUNT(*) FROM posts')->fetchColumn();
            $drafts    = (int) db()->query("SELECT COUNT(*) FROM posts WHERE status='draft'")->fetchColumn();
            render_admin('dashboard', compact('user', 'pageCount', 'caseCount', 'postCount', 'drafts') + ['title' => 'Dashboard']);
            break;

        /* ----------------------------- Pages ------------------------------ */
        case $route === 'pages':
            require_login();
            $pages = db()->query("SELECT * FROM pages WHERE slug NOT LIKE 'work/%' ORDER BY id")->fetchAll();
            render_admin('pages', ['pages' => $pages, 'title' => 'Pages', 'heading' => 'Pages']);
            break;

        // Case studies are pages rows under work/, surfaced as their own
        // content type in the admin. Same editor, separate shelf.
        case $route === 'case-studies':
            require_login();
            $pages = db()->query("SELECT * FROM pages WHERE slug LIKE 'work/%' ORDER BY id")->fetchAll();
            render_admin('pages', ['pages' => $pages, 'title' => 'Case Studies', 'heading' => 'Case Studies']);
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
                $bodyMd = trim($_POST['body_md'] ?? '');
                $stmt = db()->prepare(
                    'UPDATE pages SET h1 = ?, lede = ?, body_md = ?, body_html = ?, meta_title = ?, meta_description = ? WHERE id = ?'
                );
                $stmt->execute([
                    trim($_POST['h1'] ?? ''),
                    trim($_POST['lede'] ?? ''),
                    $bodyMd,
                    $bodyMd === '' ? '' : markdown_to_html($bodyMd),
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

        /* ------------------------- Job tracker ----------------------------- */
        // Private to the admin: the job-search pipeline. Nothing here is
        // linked from, or visible on, the public site.
        case $route === 'jobs':
            require_login();
            $apps = db()->query("SELECT * FROM applications ORDER BY FIELD(status,'offer','interview','callback','applied','found','denied','abandoned'), COALESCE(applied_on, created_at) DESC")->fetchAll();
            render_admin('jobs', ['apps' => $apps, 'title' => 'Job tracker']);
            break;

        case $route === 'jobs/edit':
        case $route === 'jobs/new':
            require_login();
            $app = ['id' => 0, 'company' => '', 'role' => '', 'comp' => '', 'remote' => '',
                    'url' => '', 'status' => 'found', 'applied_on' => null, 'notes' => ''];
            if ($route === 'jobs/edit') {
                $stmt = db()->prepare('SELECT * FROM applications WHERE id = ?');
                $stmt->execute([(int) ($_GET['id'] ?? 0)]);
                $app = $stmt->fetch();
                if (!$app) { http_response_code(404); exit('Not found.'); }
            }
            $saved = false;
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                verify_csrf();
                $fields = [
                    trim($_POST['company'] ?? ''), trim($_POST['role'] ?? ''),
                    trim($_POST['comp'] ?? ''), trim($_POST['remote'] ?? ''),
                    trim($_POST['url'] ?? ''),
                    in_array($_POST['status'] ?? '', ['found','applied','callback','interview','offer','denied','abandoned'], true) ? $_POST['status'] : 'found',
                    ($_POST['applied_on'] ?? '') !== '' ? $_POST['applied_on'] : null,
                    trim($_POST['notes'] ?? ''),
                ];
                if ($app['id']) {
                    $stmt = db()->prepare('UPDATE applications SET company=?, role=?, comp=?, remote=?, url=?, status=?, applied_on=?, notes=? WHERE id=?');
                    $stmt->execute([...$fields, $app['id']]);
                } else {
                    $stmt = db()->prepare('INSERT INTO applications (company, role, comp, remote, url, status, applied_on, notes) VALUES (?,?,?,?,?,?,?,?)');
                    $stmt->execute($fields);
                    $app['id'] = (int) db()->lastInsertId();
                }
                $stmt = db()->prepare('SELECT * FROM applications WHERE id = ?');
                $stmt->execute([$app['id']]);
                $app = $stmt->fetch();
                $saved = true;
            }
            render_admin('job-edit', ['app' => $app, 'saved' => $saved,
                'title' => $app['id'] ? 'Edit application' : 'New application']);
            break;

        case $route === 'jobs/delete':
            require_login();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                verify_csrf();
                $stmt = db()->prepare('DELETE FROM applications WHERE id = ?');
                $stmt->execute([(int) ($_POST['id'] ?? 0)]);
            }
            header('Location: /admin/jobs');
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

/**
 * Validate an uploaded image, center-crop it square, and save it as the
 * About portrait. Returns [okMessage, errorMessage] — exactly one is ''.
 *
 * The CSS already forces a square frame with object-fit: cover, so the
 * server-side crop is belt and braces: it keeps the stored file small and
 * square even if it's ever used somewhere without that CSS.
 */
function save_portrait(?array $file): array
{
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['', 'Choose an image file first.'];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['', 'The upload failed (code ' . (int) $file['error'] . '). Try again, or try a smaller file.'];
    }
    if ($file['size'] > 15 * 1024 * 1024) {
        return ['', 'That file is over 15 MB. Export a smaller version and try again.'];
    }

    $info = @getimagesize($file['tmp_name']);
    $allowed = [IMAGETYPE_JPEG => 1, IMAGETYPE_PNG => 1, IMAGETYPE_WEBP => 1, IMAGETYPE_GIF => 1];
    if (!$info || !isset($allowed[$info[2]])) {
        return ['', 'That doesn\'t look like an image. JPEG, PNG, WEBP, or GIF, please.'];
    }

    $src = @imagecreatefromstring((string) file_get_contents($file['tmp_name']));
    if (!$src) {
        return ['', 'Couldn\'t read that image. Try re-exporting it as a JPEG or PNG.'];
    }

    // Phone JPEGs often carry their rotation in EXIF, which GD ignores —
    // honor it so portraits don't arrive sideways.
    if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
        $exif = @exif_read_data($file['tmp_name']);
        $src = match ((int) ($exif['Orientation'] ?? 1)) {
            3       => imagerotate($src, 180, 0),
            6       => imagerotate($src, -90, 0),
            8       => imagerotate($src, 90, 0),
            default => $src,
        };
    }

    // Center-crop to a square, capped at 1600px.
    $w = imagesx($src);
    $h = imagesy($src);
    $side = min($w, $h);
    $out = (int) min($side, 1600);
    $dst = imagecreatetruecolor($out, $out);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    imagecopyresampled(
        $dst, $src,
        0, 0,
        (int) (($w - $side) / 2), (int) (($h - $side) / 2),
        $out, $out, $side, $side
    );
    imagedestroy($src);

    $dir = public_dir() . '/assets/uploads';
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
        imagedestroy($dst);
        return ['', 'Couldn\'t create the uploads folder on the server.'];
    }

    // Prefer WEBP; fall back to JPEG if this PHP build lacks webp support.
    if (function_exists('imagewebp')) {
        $saved = imagewebp($dst, $dir . '/portrait.webp', 85);
        if ($saved) @unlink($dir . '/portrait.jpg'); // don't let an old jpg shadow anything
    } else {
        imagealphablending($dst, true); // flatten alpha for jpeg
        $saved = imagejpeg($dst, $dir . '/portrait.jpg', 85);
        if ($saved) @unlink($dir . '/portrait.webp');
    }
    imagedestroy($dst);

    if (!$saved) {
        return ['', 'The server couldn\'t save the image. Check that assets/uploads is writable.'];
    }
    return ["Portrait updated. It's live on the About page now ({$out}\u{00d7}{$out}).", ''];
}
