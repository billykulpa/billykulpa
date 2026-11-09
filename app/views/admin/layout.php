<?php /** @var string $content */ /** @var string $title */ $u = current_user();
// On a league site this is that league's admin; on billykulpa.com, the site CMS.
$league = league_site() ? mfl_league() : null; ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title><?= esc($title ?? 'Admin') ?> — <?= $league ? esc($league['short']) . ' admin' : 'billykulpa.com' ?></title>
  <link rel="stylesheet" href="/assets/css/admin.css?v=<?= @filemtime(public_dir() . '/assets/css/admin.css') ?: 0 ?>">
</head>
<body<?= $league ? ' data-keepalive="/admin/keepalive"' : '' ?>>
  <header class="a-header">
    <a class="a-wordmark" href="/admin"><?= $league ? esc($league['short']) : 'BK' ?><span>/admin</span></a>
    <?php if ($u && $league): ?>
    <button type="button" class="a-nav-toggle" id="a-nav-toggle"
            aria-label="Open menu" aria-expanded="false" aria-controls="a-nav">
      <span class="a-nav-toggle-bars" aria-hidden="true"></span>
    </button>
    <nav class="a-nav" id="a-nav">
      <a href="/admin">League</a>
      <a href="/admin/password">Password</a>
      <a href="/">View site</a>
      <form method="post" action="/admin/logout" class="a-logout"><?= csrf_field() ?>
        <button type="submit">Sign out</button>
      </form>
    </nav>
    <div class="a-nav-overlay" id="a-nav-overlay" hidden></div>
    <?php elseif ($u): ?>
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
      <a href="<?= esc(MFL_BASE_URL) ?>/admin">MFL</a>
      <a href="https://ssffl.billykulpa.com/admin">SSFFL</a>
      <a href="/admin/users">Accounts</a>
      <a href="/admin/password">Password</a>
      <a href="/">View site</a>
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
