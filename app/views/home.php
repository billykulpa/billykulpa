<?php /** @var array $pg */ /** @var array $posts */ ?>

<section class="hero">
  <div class="wrap">
    <p class="mono-label hero-kicker">Creative direction / Brand systems / Front-end code</p>
    <h1 class="hero-h1"><?= esc($pg['h1']) ?></h1>
    <div class="hero-grid">
      <p class="hero-lede"><?= ($pg['lede'] ?? '') !== '' ? md_inline($pg['lede'])
        : 'Art direction is the job. Design, code, video, and copy are how I do it. More than twenty years across design and development; currently leading creative for a national trade association and building products on the side.' ?></p>
      <div class="hero-side">
        <!-- The exploded site: browser / page / grid. Pure SVG; motion is
             three CSS transforms (see "Motion" in main.css). -->
        <svg class="xpl" viewBox="0 64 316 158" aria-hidden="true">
        <g class="guides">
        <line class="guide" x1="130.0" y1="-1.0" x2="130.0" y2="97.0"/>
        <line class="guide" x1="233.9" y1="59.0" x2="233.9" y2="157.0"/>
        <line class="guide" x1="26.1" y1="59.0" x2="26.1" y2="157.0"/>
        <line class="guide" x1="130.0" y1="119.0" x2="130.0" y2="217.0"/>
        </g>
        <g class="layer l-grid">
        <polygon class="face" fill="#fff" points="233.9,151.0 130.0,211.0 130.0,216.0 233.9,156.0"/>
        <polygon class="face" fill="#fff" points="26.1,151.0 130.0,211.0 130.0,216.0 26.1,156.0"/>
        <polygon class="face" fill="#fff" points="130.0,91.0 233.9,151.0 130.0,211.0 26.1,151.0"/>
        <polyline class="wf soft" points="150.8,109.0 57.3,163.0"/>
        <polyline class="wf soft" points="176.8,124.0 83.2,178.0"/>
        <polyline class="wf soft" points="202.7,139.0 109.2,193.0"/>
        <polyline class="wf" points="83.2,168.0 83.2,154.0 107.5,154.0"/>
        <polyline class="wf" points="100.6,178.0 124.8,178.0 124.8,164.0"/>
        <polyline class="wf" points="93.6,178.0 114.4,154.0"/>
        <text class="lbl" x="240.7" y="154.0">the grid</text>
        </g>
        <g class="layer l-page">
        <polygon class="face" fill="#fff" points="233.9,151.0 130.0,211.0 130.0,216.0 233.9,156.0"/>
        <polygon class="face" fill="#fff" points="26.1,151.0 130.0,211.0 130.0,216.0 26.1,156.0"/>
        <polygon class="face" fill="#fff" points="130.0,91.0 233.9,151.0 130.0,211.0 26.1,151.0"/>
        <polygon class="wf" points="119.6,105.0 202.7,153.0 192.4,159.0 109.2,111.0"/>
        <polyline class="wf" points="117.9,110.0 135.2,120.0"/>
        <polyline class="wf" points="169.8,140.0 194.1,154.0"/>
        <polyline class="wf" points="102.3,117.0 154.2,147.0"/>
        <polyline class="wf" points="94.5,121.5 132.6,143.5"/>
        <polygon class="wf" points="86.7,126.0 121.3,146.0 88.4,165.0 53.8,145.0"/>
        <polyline class="wf soft" points="86.7,126.0 88.4,165.0"/>
        <polyline class="wf soft" points="121.3,146.0 53.8,145.0"/>
        <polyline class="wf" points="126.5,153.0 162.9,174.0"/>
        <polyline class="wf" points="118.7,157.5 155.1,178.5"/>
        <polyline class="wf" points="110.9,162.0 147.3,183.0"/>
        <polygon class="wf accent" points="104.0,166.0 128.3,180.0 117.9,186.0 93.6,172.0"/>
        <polyline class="wf" points="43.4,151.0 123.1,197.0"/>
        <text class="lbl" x="240.7" y="154.0">the content</text>
        </g>
        <g class="layer l-chrome">
        <polygon class="face" fill="#fff" points="233.9,151.0 130.0,211.0 130.0,216.0 233.9,156.0"/>
        <polygon class="face" fill="#fff" points="26.1,151.0 130.0,211.0 130.0,216.0 26.1,156.0"/>
        <polygon class="glass" points="130.0,91.0 233.9,151.0 130.0,211.0 26.1,151.0"/>
        <polygon class="face" fill="#fff" points="130.0,91.0 233.9,151.0 220.1,159.0 116.1,99.0"/>
        <circle class="dot d-r" cx="130.0" cy="99.0" r="2"/>
        <circle class="dot d-y" cx="136.9" cy="103.0" r="2"/>
        <circle class="dot d-g" cx="143.9" cy="107.0" r="2"/>
        <polyline class="wf" points="152.5,112.0 220.1,151.0"/>
        <text class="lbl" x="240.7" y="154.0">the browser</text>
        <polyline class="charge" points="130.0,91.0 233.9,151.0 130.0,211.0 26.1,151.0 130.0,91.0" pathLength="100"/>
        </g>
        </svg>
        <dl class="hero-facts">
          <div><dt class="mono-label">Currently</dt><dd>Senior Manager, Creative &middot; FMA</dd></div>
          <div><dt class="mono-label">Based</dt><dd>Roscoe, Illinois</dd></div>
        </dl>
      </div>
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
          <img src="/assets/img/restreak/restreak-card.webp"
               srcset="/assets/img/restreak/restreak-card-900w.webp 900w, /assets/img/restreak/restreak-card-1800w.webp 1800w, /assets/img/restreak/restreak-card.webp 5760w"
               sizes="(min-width: 1180px) 1084px, 100vw"
               alt="Restreak key art: the wordmark across a four-color banner over black-and-white sports photos and a stat table" width="5760" height="2200" loading="lazy">
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
      <a class="work-card" href="/work/cleo-website-and-brand-development">
        <div class="work-card-media">
          <img src="/assets/img/cleo-website-home-sample-960x600.webp"
               srcset="/assets/img/cleo-website-home-sample-540x338.webp 540w, /assets/img/cleo-website-home-sample-960x600.webp 960w, /assets/img/cleo-website-home-sample-1920x1200.webp 1920w"
               sizes="(min-width: 1180px) 526px, (min-width: 681px) 50vw, 100vw" alt="The redesigned Cleo website hero" width="1920" height="1200" loading="lazy">
        </div>
        <div class="work-card-body">
          <h3>Cleo Website and Brand Development</h3>
          <p>A B2B software brand rebuilt from the logo up by a one-person creative team: identity, website, video, and print.</p>
          <p class="mono-label">Identity &middot; Web &middot; Video</p>
        </div>
      </a>
      <a class="work-card" href="/work/supporting-local-music">
        <div class="work-card-media">
          <img src="/assets/img/halloween-2026/facebook.webp"
               srcset="/assets/img/halloween-2026/facebook-800w.webp 800w, /assets/img/halloween-2026/facebook-1400w.webp 1400w, /assets/img/halloween-2026/facebook.webp 3840w"
               sizes="(min-width: 1180px) 526px, (min-width: 681px) 50vw, 100vw"
               alt="The Halloween cover-show poster art: a hooded skull figure beside a sunken cabin in cold blue-green woods" width="3840" height="2010" loading="lazy">
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
    <p><a class="btn btn--surge" href="mailto:billy@billykulpa.com">billy@billykulpa.com<svg viewBox="0 0 100 40" preserveAspectRatio="none" aria-hidden="true"><rect x="0.5" y="0.5" width="99" height="39" pathLength="100"/></svg></a></p>
  </div>
</section>
