<?php /** @var array $pg */ /** @var array $posts */ ?>

<section class="hero hero--inner">
  <div class="wrap">
    <p class="mono-label hero-kicker">02 / Notes</p>
    <h1 class="hero-h1"><?= esc($pg['h1']) ?></h1>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <?php if (!$posts): ?>
      <p class="prose">Nothing here yet. First note coming soon.</p>
    <?php else: ?>
    <div class="notes-list">
      <?php foreach ($posts as $p): ?>
      <a class="note-row" href="/notes/<?= esc($p['slug']) ?>">
        <span class="note-date mono-label"><?= esc(nice_date($p['published_at'])) ?></span>
        <span class="note-title"><?= esc($p['title']) ?></span>
        <?php if ($p['meta_description']): ?>
        <span class="note-desc"><?= esc($p['meta_description']) ?></span>
        <?php endif; ?>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>
