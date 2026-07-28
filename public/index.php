<?php
/**
 * billykulpa.com — front controller.
 * All requests route through here (see .htaccess).
 */

declare(strict_types=1);

require __DIR__ . '/../app/db.php';
require __DIR__ . '/../app/helpers.php';

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
$path = trim($path, '/');

/* ----------------------------- Admin routes ----------------------------- */

if ($path === 'admin' || str_starts_with($path, 'admin/')) {
    require __DIR__ . '/../app/admin.php';
    admin_route(substr($path, 5) ? trim(substr($path, 6), '/') : '');
    exit;
}

/* ----------------------------- Public routes ---------------------------- */

switch (true) {

    case $path === '':
        $pg = page('');
        $posts = db()->query(
            "SELECT slug, title, meta_description, published_at
             FROM posts WHERE status = 'published'
             ORDER BY published_at DESC LIMIT 3"
        )->fetchAll();
        render('home', ['pg' => $pg, 'posts' => $posts, 'nav' => 'home']);
        break;

    case $path === 'contact':
        require __DIR__ . '/../app/contact.php';
        contact_route();
        break;

    case $path === 'about':
        render('about', ['pg' => page('about'), 'nav' => 'about']);
        break;

    case $path === 'work':
        render('work-index', ['pg' => page('work'), 'nav' => 'work']);
        break;

    // Migrated portfolio archive: /work/{slug} (was /portfolio/{slug}).
    case (bool) preg_match('#^work/([a-z0-9-]+)$#', $path, $wm)
        && $path !== 'work/restreak'
        && ($archive = json_decode(file_get_contents(__DIR__ . '/../app/work-archive.json'), true))
        && isset($archive[$wm[1]]):
        $a = $archive[$wm[1]];
        render('work-archive-' . $wm[1], [
            'pg' => [
                'h1' => $a['title'],
                'meta_title' => $a['meta_title'] . ' — Billy Kulpa',
                'meta_description' => $a['meta_description'],
            ],
            'nav' => 'work',
        ]);
        break;

    // Old URLs 301 to the new home for each piece.
    case (bool) preg_match('#^portfolio/([a-z0-9-]+)$#', $path, $pm):
        header('Location: /work/' . $pm[1], true, 301);
        break;
    case $path === 'portfolio':
        header('Location: /work', true, 301);
        break;
    case $path === 'blog':
        header('Location: /notes', true, 301);
        break;
    case (bool) preg_match('#^blog/([a-z0-9-]+)$#', $path, $bm):
        header('Location: /notes/' . $bm[1], true, 301);
        break;

    case $path === 'resume':
        render('resume', [
            'pg' => [
                'h1' => 'Resume',
                'meta_title' => "Billy Kulpa's Resume",
                'meta_description' => 'The resume of Billy Kulpa, a creative director, designer, developer, and musician in Roscoe, Illinois',
            ],
            'noindex' => true, // linked in the nav, but kept out of search by request
            'nav' => 'resume',
        ]);
        break;

    case $path === 'work/fma-email':
        render('work-fma-email', [
            'pg' => [
                'h1' => 'Email Design at Scale',
                'meta_title' => 'Email Design at Scale — Billy Kulpa',
                'meta_description' => 'Rebuilding a national association\'s email program on a new platform: a template system, staff training, and the governance rules behind two million sends a year.',
            ],
            'nav' => 'work',
        ]);
        break;

    case $path === 'work/fma-social':
        render('work-fma-social', [
            'pg' => [
                'h1' => 'FMA Social Brand Management',
                'meta_title' => 'FMA Social Brand Management — Billy Kulpa',
                'meta_description' => 'Managing one parent brand and two overlapping subbrands — FMA, The Fabricator, and SparkForce — across a constant cadence of social content.',
            ],
            'nav' => 'work',
        ]);
        break;

    case $path === 'work/supporting-local-music':
        render('work-supporting-local-music', [
            'pg' => [
                'h1' => 'Supporting Local Music',
                'meta_title' => 'Supporting Local Music — Billy Kulpa',
                'meta_description' => 'Concert posters, cassette packaging, and other work from the Rockford, Illinois music scene.',
            ],
            'nav' => 'work',
        ]);
        break;

    case $path === 'work/restreak':
        render('work-restreak', [
            'pg' => [
                'h1' => 'Restreak',
                'meta_title' => 'Restreak — daily sports trivia — Billy Kulpa',
                'meta_description' => 'Case study: designing, building, and shipping Restreak, a daily sports trivia game — product design, brand, and front-end engineering by one person.',
            ],
            'nav' => 'work',
        ]);
        break;

    case $path === 'notes':
        $posts = db()->query(
            "SELECT slug, title, meta_description, published_at
             FROM posts WHERE status = 'published'
             ORDER BY published_at DESC"
        )->fetchAll();
        render('blog-index', ['pg' => page('notes'), 'posts' => $posts, 'nav' => 'notes']);
        break;

    case (bool) preg_match('#^notes/([a-z0-9-]+)$#', $path, $m):
        $stmt = db()->prepare("SELECT * FROM posts WHERE slug = ? AND status = 'published'");
        $stmt->execute([$m[1]]);
        $post = $stmt->fetch();
        if (!$post) { http_response_code(404); render('404', ['pg' => ['h1' => 'Not found', 'meta_title' => 'Not found', 'meta_description' => ''], 'nav' => '']); break; }
        render('blog-post', [
            'pg' => [
                'h1' => $post['title'],
                'meta_title' => $post['meta_title'] ?: $post['title'] . ' — Billy Kulpa',
                'meta_description' => $post['meta_description'],
            ],
            'post' => $post,
            'nav' => 'notes',
        ]);
        break;

    default:
        http_response_code(404);
        render('404', ['pg' => ['h1' => 'Page not found', 'meta_title' => 'Not found — Billy Kulpa', 'meta_description' => ''], 'nav' => '']);
}
