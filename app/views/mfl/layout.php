<?php /** @var string $content */ /** @var ?string $updated */ /** @var ?string $title */ $nav ??= '';
// Same pattern as the main site: probe the session before any output, and only
// when the cookie exists, so ordinary visitors never get a session.
$signed_in = !empty($_COOKIE[session_cookie_name()]) ? current_user() : null;
$cms = config()['site']['base_url'] . '/admin'; ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title><?= isset($title) ? esc($title) . ' — ' : '' ?>Midwest Football League</title>
  <link rel="icon" type="image/png" href="/assets/favicon-64.png">
  <link rel="apple-touch-icon" href="/assets/apple-touch-icon.png">
  <meta property="og:title" content="<?= isset($title) ? esc($title) . ' — ' : '' ?>Midwest Football League">
  <meta property="og:image" content="<?= esc(MFL_BASE_URL) ?>/assets/logo-share.png">
  <link rel="preload" href="/assets/fonts/archivo-latin-normal.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="stylesheet" href="<?= esc(mfl_asset('mfl.css')) ?>">
</head>
<body>
  <a class="skip" href="#main">Skip to content</a>
  <header class="site-header">
    <div class="wrap header-grid">
      <a class="wordmark" href="/" aria-label="Midwest Football League home"<?= $nav === 'home' ? ' aria-current="page"' : '' ?>><img class="wordmark-logo" src="<?= esc(mfl_asset('logo.svg')) ?>" alt="" width="64" height="50"><span class="wordmark-name">Midwest Football League</span></a>
      <nav class="site-nav" aria-label="Primary">
        <a href="/rankings" <?= $nav === 'rankings' ? 'aria-current="page"' : '' ?>>Power Rankings</a>
        <a href="/polls" <?= $nav === 'polls' ? 'aria-current="page"' : '' ?>>Polls</a>
        <a href="/standings" <?= $nav === 'standings' ? 'aria-current="page"' : '' ?>>Standings</a>
        <a href="/teams" <?= $nav === 'teams' ? 'aria-current="page"' : '' ?>>Teams</a>
        <a href="/drafts" <?= $nav === 'drafts' ? 'aria-current="page"' : '' ?>>Drafts</a>
        <a href="/players" <?= $nav === 'players' ? 'aria-current="page"' : '' ?>>Players</a>
        <a href="/pickups" <?= $nav === 'pickups' ? 'aria-current="page"' : '' ?>>Pickups</a>
        <a href="/trades" <?= $nav === 'trades' ? 'aria-current="page"' : '' ?>>Trades</a>
        <a href="/records" <?= $nav === 'records' ? 'aria-current="page"' : '' ?>>Records</a>
        <a href="/awards" <?= $nav === 'awards' ? 'aria-current="page"' : '' ?>>Awards</a>
      </nav>
      <p class="updated mono-label">
        <?php if ($updated): ?>Last updated <time datetime="<?= esc(date('c', strtotime($updated))) ?>"><?= esc(central_time($updated, 'M j, g:i a')) ?> CT</time>
        <?php else: ?>Not synced yet<?php endif; ?>
      </p>
    </div>
  </header>
  <main id="main" class="wrap"><?= $content ?></main>
  <footer class="site-footer">
    <div class="wrap"><p class="mono-label">League data from Sleeper</p></div>
  </footer>
  <?php if ($signed_in): ?>
  <div class="admin-quick">
    <a class="admin-quick-btn" href="<?= esc($cms) ?>/mfl">Admin</a>
    <form method="post" action="<?= esc($cms) ?>/logout"><?= csrf_field() ?><button class="admin-quick-btn" type="submit">Sign out</button></form>
  </div>
  <?php endif; ?>
  <?php if (!empty($script)): ?><script src="<?= esc(mfl_asset('mfl.js')) ?>" defer></script><?php endif; ?>
</body>
</html>
