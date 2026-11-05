<?php /** @var string $content */ /** @var ?string $updated */ /** @var ?string $title */ $nav ??= ''; ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title><?= isset($title) ? esc($title) . ' — ' : '' ?>Midwest Football League</title>
  <link rel="preload" href="/assets/fonts/archivo-latin-normal.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="stylesheet" href="<?= esc(mfl_asset('mfl.css')) ?>">
</head>
<body>
  <a class="skip" href="#main">Skip to content</a>
  <header class="site-header">
    <div class="wrap header-grid">
      <a class="wordmark" href="/">MFL<span class="wordmark-rule">—</span><span class="wordmark-name">Midwest Football League</span></a>
      <nav class="site-nav" aria-label="Primary">
        <a href="/" <?= $nav === 'home' ? 'aria-current="page"' : '' ?>>Home</a>
        <a href="/rankings" <?= $nav === 'rankings' ? 'aria-current="page"' : '' ?>>Power Rankings</a>
        <a href="/polls" <?= $nav === 'polls' ? 'aria-current="page"' : '' ?>>Polls</a>
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
  <?php if (!empty($script)): ?><script src="<?= esc(mfl_asset('mfl.js')) ?>" defer></script><?php endif; ?>
</body>
</html>
