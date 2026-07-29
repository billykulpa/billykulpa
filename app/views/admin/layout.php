<?php /** @var string $content */ /** @var string $title */ $u = current_user(); ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title><?= esc($title ?? 'Admin') ?> — billykulpa.com</title>
  <link href="https://fonts.googleapis.com/css2?family=Archivo:wght@400;500;600;800&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/admin.css?v=<?= @filemtime(public_dir() . '/assets/css/admin.css') ?: 0 ?>">
</head>
<body>
  <header class="a-header">
    <a class="a-wordmark" href="/admin">BK<span>/admin</span></a>
    <?php if ($u): ?>
    <nav class="a-nav">
      <a href="/admin/pages">Pages</a>
      <a href="/admin/case-studies">Case Studies</a>
      <a href="/admin/posts">Posts</a>
      <a href="/admin/portrait">Portrait</a>
      <a href="/admin/password">Password</a>
      <a href="/" target="_blank" rel="noopener">View site &nearr;</a>
      <form method="post" action="/admin/logout" class="a-logout"><?= csrf_field() ?>
        <button type="submit">Sign out</button>
      </form>
    </nav>
    <?php endif; ?>
  </header>
  <main class="a-main">
    <?= $content ?>
  </main>
</body>
</html>
