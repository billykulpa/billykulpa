<?php /** @var array $pg */ /** @var array $posts */ ?>

<section class="hero">
  <div class="wrap">
    <p class="mono-label hero-kicker">Creative direction / Brand systems / Front-end code</p>
    <h1 class="hero-h1"><?= esc($pg['h1']) ?></h1>
    <div class="hero-grid">
      <p class="hero-lede"><?= ($pg['lede'] ?? '') !== '' ? md_inline($pg['lede'])
        : 'Art direction is the job. Design, code, video, and copy are how I do it. More than twenty years across design and development; currently leading creative for a national trade association and building products on the side.' ?></p>
      <dl class="hero-facts">
        <div><dt class="mono-label">Currently</dt><dd>Senior Manager, Creative &middot; FMA</dd></div>
        <div><dt class="mono-label">Based</dt><dd>Roscoe, Illinois</dd></div>
      </dl>
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="section-head">
      <h2><span class="nav-idx">01</span>Selected work</h2>
      <a class="section-more" href="/work">All work &rarr;</a>
    </div>
    <div class="work-grid">
      <a class="work-card work-card--lead" href="/work/restreak">
        <div class="work-card-media work-card-media--restreak">
          <img src="/assets/img/restreak/restreak-home-dark.webp"
               srcset="/assets/img/restreak/restreak-home-dark-900w.webp 900w, /assets/img/restreak/restreak-home-dark-1800w.webp 1800w, /assets/img/restreak/restreak-home-dark.webp 2880w"
               sizes="(min-width: 1180px) 1084px, 100vw"
               alt="Restreak's daily game screen" width="2880" height="1800" loading="lazy">
        </div>
        <div class="work-card-body">
          <h3>Restreak</h3>
          <p>A daily sports trivia game, designed, built, and shipped by one person: brand, product, and code.</p>
          <p class="mono-label">Product design &middot; Front-end &middot; Identity</p>
        </div>
      </a>
      <a class="work-card" href="/work/fma-social">
        <div class="work-card-media">
          <img src="/assets/img/fma-social/fma-three-brands.webp"
               srcset="/assets/img/fma-social/fma-three-brands-800w.webp 800w, /assets/img/fma-social/fma-three-brands-1400w.webp 1400w, /assets/img/fma-social/fma-three-brands.webp 2328w"
               sizes="(min-width: 1180px) 526px, (min-width: 681px) 50vw, 100vw"
               alt="The same social template across the FMA, Fabricator, and SparkForce brands" width="2328" height="760" loading="lazy">
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
          <img src="/assets/img/email/email-templates-trio.webp"
               srcset="/assets/img/email/email-templates-trio-800w.webp 800w, /assets/img/email/email-templates-trio-1400w.webp 1400w, /assets/img/email/email-templates-trio.webp 3654w"
               sizes="(min-width: 1180px) 526px, (min-width: 681px) 50vw, 100vw"
               alt="Three email templates from the FMA library: SparkForce Top Five, Pub Promo, and Fabrinomics" width="3654" height="1200" loading="lazy">
        </div>
        <div class="work-card-body">
          <h3>Email Design at Scale</h3>
          <p>A template system that lets a two-designer team ship a national association's entire email program.</p>
          <p class="mono-label">Design systems &middot; Training &middot; Governance</p>
        </div>
      </a>
      <a class="work-card" href="/work/supporting-local-music">
        <div class="work-card-media">
          <img src="/assets/img/joie-de-vivre/jdv-facebook.webp"
               srcset="/assets/img/joie-de-vivre/jdv-facebook-800w.webp 800w, /assets/img/joie-de-vivre/jdv-facebook-1400w.webp 1400w, /assets/img/joie-de-vivre/jdv-facebook.webp 3840w"
               sizes="(min-width: 1180px) 526px, (min-width: 681px) 50vw, 100vw"
               alt="The Joie de Vivre poster art" width="3840" height="2010" loading="lazy">
        </div>
        <div class="work-card-body">
          <h3>Supporting Local Music</h3>
          <p>Gig posters, cassette packaging, and a hand-drawn logotype from the Rockford music scene.</p>
          <p class="mono-label">Poster &middot; Packaging &middot; Illustration</p>
        </div>
      </a>
    </div>
  </div>
</section>

<?php if ($posts): ?>
<section class="section">
  <div class="wrap">
    <div class="section-head">
      <h2><span class="nav-idx">02</span>Notes</h2>
      <a class="section-more" href="/notes">All notes &rarr;</a>
    </div>
    <div class="notes-list">
      <?php foreach ($posts as $p): ?>
      <a class="note-row" href="/notes/<?= esc($p['slug']) ?>">
        <span class="note-date mono-label"><?= esc(nice_date($p['published_at'])) ?></span>
        <span class="note-title"><?= esc($p['title']) ?></span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section section--cta">
  <div class="wrap">
    <h2 class="cta-h2">Looking for a creative leader who can<br>run the meeting <em>and</em> read the diff?</h2>
    <p><a class="btn" href="mailto:billy@billykulpa.com">billy@billykulpa.com</a></p>
  </div>
</section>
