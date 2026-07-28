<?php /** @var array $pg */ /** @var string $content */ /** @var string $nav */ ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= esc($pg['meta_title'] ?: config()['site']['name']) ?></title>
  <?php if (!empty($pg['meta_description'])): ?>
  <meta name="description" content="<?= esc($pg['meta_description']) ?>">
  <?php endif; ?>
  <?php if (!empty($noindex)): ?>
  <meta name="robots" content="noindex, nofollow">
  <?php endif; ?>
  <link rel="apple-touch-icon" sizes="180x180" href="/assets/favicons/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="/assets/favicons/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="/assets/favicons/favicon-16x16.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Archivo:ital,wdth,wght@0,62..125,100..900;1,62..125,100..900&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/main.css">
  <?php // Google Analytics — skipped on local dev so test traffic stays out of the numbers.
        $ga_host = $_SERVER['HTTP_HOST'] ?? '';
        if (!str_starts_with($ga_host, '127.0.0.1') && !str_starts_with($ga_host, 'localhost')): ?>
  <!-- Google tag (gtag.js) -->
  <script async src="https://www.googletagmanager.com/gtag/js?id=G-5BPH0BCRWF"></script>
  <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', 'G-5BPH0BCRWF');
  </script>
  <?php endif; ?>
</head>
<body>
  <a class="skip" href="#main">Skip to content</a>

  <header class="site-header">
    <div class="wrap header-grid">
      <a class="wordmark" href="/">BK<span class="wordmark-rule">—</span><span class="wordmark-name">Billy&nbsp;Kulpa</span></a>
      <!-- Restreak nav pattern: one nav element, inline on desktop,
           slide-in drawer on mobile (see main.css + main.js). -->
      <button type="button" class="nav-toggle" id="nav-toggle"
              aria-label="Open menu" aria-expanded="false" aria-controls="site-nav">
        <span class="nav-toggle-bars" aria-hidden="true"></span>
      </button>
      <?php require __DIR__ . '/social-links.php'; ?>
      <nav class="site-nav" id="site-nav" aria-label="Primary" tabindex="-1">
        <a href="/" class="nav-home" <?= $nav === 'home' ? 'aria-current="page"' : '' ?>><span class="nav-idx">00</span>Home</a>
        <a href="/work"  <?= $nav === 'work'  ? 'aria-current="page"' : '' ?>><span class="nav-idx">01</span>Work</a>
        <a href="/notes" <?= $nav === 'notes' ? 'aria-current="page"' : '' ?>><span class="nav-idx">02</span>Notes</a>
        <a href="/about" <?= $nav === 'about' ? 'aria-current="page"' : '' ?>><span class="nav-idx">03</span>About</a>
        <a href="/contact" class="nav-contact" <?= $nav === 'contact' ? 'aria-current="page"' : '' ?>>Contact</a>
        <span class="nav-social" aria-label="Social profiles">
          <?php foreach ($social_links as $s): ?>
          <a class="social-icon" href="<?= esc($s['url']) ?>" target="_blank" rel="noopener"
             title="Find Billy Kulpa on <?= esc($s['name']) ?>"><?= $s['svg'] ?><span class="social-name"><?= esc($s['name']) ?></span></a>
          <?php endforeach; ?>
        </span>
      </nav>
    </div>
    <div class="nav-overlay" id="nav-overlay" hidden></div>
  </header>

  <main id="main">
    <?= $content ?>
  </main>

  <footer class="site-footer">
    <div class="wrap footer-grid">
      <div>
        <p class="mono-label">Billy Kulpa</p>
        <p class="footer-line">Creative direction &middot; brand systems &middot; front-end code.</p>
      </div>
      <div>
        <p class="mono-label">Elsewhere</p>
        <p class="footer-line footer-social">
          <?php foreach ($social_links as $i => $s): ?><a href="<?= esc($s['url']) ?>" target="_blank" rel="noopener"><?= esc($s['name']) ?></a><?= $i < count($social_links) - 1 ? ' / ' : '' ?><?php endforeach; ?>
          / <a href="mailto:billy@billykulpa.com">Email</a>
        </p>
      </div>
      <div class="footer-colophon">
        <p class="mono-label">Colophon</p>
        <p class="footer-line">Designed by me, built with Claude. PHP, MySQL, vanilla JS. No frameworks, no page builders.</p>
      </div>
    </div>
  </footer>

  <script src="/assets/js/main.js"></script>
  <script src="/assets/js/lightbox.js" defer></script>
</body>
</html>
