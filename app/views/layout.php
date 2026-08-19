<?php /** @var array $pg */ /** @var string $content */ /** @var string $nav */
// Admin-only quick links (rendered near </body>): the session must be probed
// HERE, before any output, or session_start() fails silently. Only probe when
// the admin cookie already exists so ordinary visitors never get a session.
$qa_user = null;
if (!empty($_COOKIE[config()['session_name']])) { $qa_user = current_user(); }
?>
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
  <?php /* Fonts are self-hosted (see @font-face at the top of main.css) so
           nothing render-blocks on a third party. Preload the two files
           almost every page paints with. */ ?>
  <link rel="preload" href="/assets/fonts/archivo-latin-wdth-normal.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="preload" href="/assets/fonts/ibm-plex-mono-latin-500-normal.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="stylesheet" href="/assets/css/main.css?v=<?= @filemtime(public_dir() . '/assets/css/main.css') ?: 0 ?>">
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
        <a href="/resume" <?= $nav === 'resume' ? 'aria-current="page"' : '' ?>><span class="nav-idx">04</span>Resume</a>
        <a href="/contact" class="nav-contact" <?= $nav === 'contact' ? 'aria-current="page"' : '' ?>><span class="nav-idx">05</span>Contact</a>
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

  <?php if ($qa_user): ?>
  <div class="admin-quick">
    <a class="admin-quick-btn" href="/admin">Admin</a>
    <form method="post" action="/admin/logout"><?= csrf_field() ?><button class="admin-quick-btn" type="submit">Sign out</button></form>
  </div>
  <?php endif; ?>
  <script src="/assets/js/main.js?v=<?= @filemtime(public_dir() . '/assets/js/main.js') ?: 0 ?>"></script>
  <script src="/assets/js/lightbox.js?v=<?= @filemtime(public_dir() . '/assets/js/lightbox.js') ?: 0 ?>" defer></script>
  <?php // Visit-log beacon: marks this visit "verified" (browsers run it, scrapers don't). No payload. See app/visits.php. ?>
  <script>navigator.sendBeacon&&navigator.sendBeacon('/api/ping.php');</script>
</body>
</html>
