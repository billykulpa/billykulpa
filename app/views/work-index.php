<?php /** @var array $pg */ ?>

<section class="hero hero--inner">
  <div class="wrap">
    <p class="mono-label hero-kicker">01 / Work</p>
    <h1 class="hero-h1"><?= esc($pg['h1']) ?></h1>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="work-grid">
      <a class="work-card work-card--lead" href="/work/restreak">
        <div class="work-card-media work-card-media--restreak">
          <img src="/assets/img/restreak/restreak-home-light.webp" alt="Restreak's daily game screen" width="2880" height="1800" loading="lazy">
        </div>
        <div class="work-card-body">
          <h3>Restreak</h3>
          <p>A daily sports trivia game, designed, built, and shipped by one person.</p>
          <p class="mono-label">Product design &middot; Front-end &middot; Identity</p>
        </div>
      </a>
      <a class="work-card" href="/work/fma-social">
        <div class="work-card-media">
          <img src="/assets/img/fma-social/fma-three-brands.webp" alt="The same social template across the FMA, Fabricator, and SparkForce brands" width="2328" height="760" loading="lazy">
        </div>
        <div class="work-card-body">
          <h3>FMA Social Brand Management</h3>
          <p>One parent brand, two subbrands, and a system that stays fresh without breaking its own rules.</p>
          <p class="mono-label">Brand systems &middot; Social &middot; Messaging</p>
        </div>
      </a>
      <a class="work-card" href="/work/alaw-rebrand">
        <div class="work-card-media">
          <img src="/assets/img/portfolio-alaw-2024-cover.svg" alt="The ALAW 2024 rebrand cover" width="688" height="387" loading="lazy">
        </div>
        <div class="work-card-body">
          <h3>The ALAW Rebrand</h3>
          <p>Custom letterforms on a 100-point grid, carried through three seasons of print and digital.</p>
          <p class="mono-label">Identity &middot; Print &middot; Campaigns</p>
        </div>
      </a>
      <a class="work-card" href="/work/fma-email">
        <div class="work-card-media">
          <img src="/assets/img/email/email-templates-trio.webp" alt="Three email templates from the FMA library: SparkForce Top Five, Pub Promo, and Fabrinomics" width="3654" height="1200" loading="lazy">
        </div>
        <div class="work-card-body">
          <h3>Email Design at Scale</h3>
          <p>A template system behind a national association's entire email program.</p>
          <p class="mono-label">Design systems &middot; Training &middot; Governance</p>
        </div>
      </a>
      <a class="work-card" href="/work/supporting-local-music">
        <div class="work-card-media">
          <img src="/assets/img/joie-de-vivre/jdv-facebook.webp" alt="The Joie de Vivre poster art" width="3840" height="2010" loading="lazy">
        </div>
        <div class="work-card-body">
          <h3>Local music scene work</h3>
          <p>Gig posters, cassette packaging, and a hand-drawn logotype from the Rockford music scene.</p>
          <p class="mono-label">Poster &middot; Packaging &middot; Illustration</p>
        </div>
      </a>
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="section-head">
      <h2><span class="nav-idx">02</span>Archive</h2>
      <p class="mono-label">Brand, web, and video work from earlier roles and freelance clients</p>
    </div>
    <?php
    $archive = json_decode(file_get_contents(__DIR__ . '/../work-archive.json'), true);
    unset($archive['alaw-rebrand']); // featured above — keep it out of the archive list
    uasort($archive, fn($a, $b) => strcmp($b['completed'], $a['completed'])); // newest first
    ?>
    <div class="notes-list archive-list">
      <?php foreach ($archive as $slug => $a): ?>
      <a class="note-row" href="/work/<?= esc($slug) ?>">
        <span class="note-date mono-label"><?= esc($a['client'] ?: '—') ?></span>
        <span class="note-date mono-label"><?= esc($a['completed'] ?: '—') ?></span>
        <span class="note-title"><?= esc($a['title']) ?></span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
