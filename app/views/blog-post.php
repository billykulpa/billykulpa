<?php /** @var array $pg */ /** @var array $post */ ?>

<section class="hero hero--inner">
  <div class="wrap">
    <p class="mono-label hero-kicker"><?= esc(nice_date($post['published_at'])) ?></p>
    <h1 class="hero-h1"><?= esc($post['title']) ?></h1>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <article class="prose prose--post">
      <?= $post['body_html'] /* generated server-side from markdown; inline HTML is escaped */ ?>
    </article>
    <p class="post-back"><a href="/notes">&larr; All notes</a></p>
  </div>
</section>
