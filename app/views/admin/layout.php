<?php /** @var string $content */ /** @var string $title */ $u = current_user();
// "View site" follows the work: the league site from the MFL tab, and always for MFL Members.
$inMfl = str_starts_with(trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '', '/'), 'admin/mfl');
$siteUrl = $inMfl || ($u['role'] ?? '') !== 'admin' ? MFL_BASE_URL : '/'; ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title><?= esc($title ?? 'Admin') ?> — billykulpa.com</title>
  <link rel="stylesheet" href="/assets/css/admin.css?v=<?= @filemtime(public_dir() . '/assets/css/admin.css') ?: 0 ?>">
</head>
<body>
  <header class="a-header">
    <a class="a-wordmark" href="/admin">BK<span>/admin</span></a>
    <?php if ($u): ?>
    <button type="button" class="a-nav-toggle" id="a-nav-toggle"
            aria-label="Open menu" aria-expanded="false" aria-controls="a-nav">
      <span class="a-nav-toggle-bars" aria-hidden="true"></span>
    </button>
    <nav class="a-nav" id="a-nav">
      <?php if ($u['role'] === 'admin'): ?>
      <a href="/admin/pages">Pages</a>
      <a href="/admin/case-studies">Case Studies</a>
      <a href="/admin/notes">Notes</a>
      <a href="/admin/jobs">Jobs</a>
      <a href="/admin/traffic">Traffic</a>
      <a href="/admin/portrait">Portrait</a>
      <?php endif; ?>
      <a href="/admin/mfl">MFL</a>
      <?php if ($u['role'] === 'admin'): ?>
      <a href="/admin/users">Accounts</a>
      <?php endif; ?>
      <a href="/admin/password">Password</a>
      <a href="<?= esc($siteUrl) ?>">View site</a>
      <form method="post" action="/admin/logout" class="a-logout"><?= csrf_field() ?>
        <button type="submit">Sign out</button>
      </form>
    </nav>
    <div class="a-nav-overlay" id="a-nav-overlay" hidden></div>
    <?php endif; ?>
  </header>
  <main class="a-main">
    <?= $content ?>
  </main>
  <script src="/assets/js/admin.js?v=<?= @filemtime(public_dir() . '/assets/js/admin.js') ?: 0 ?>"></script>
</body>
</html>
