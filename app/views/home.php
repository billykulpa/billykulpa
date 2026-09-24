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
        <svg class="xpl" viewBox="0 24 316 198" aria-hidden="true">
        <defs>
        <mask id="bk-rainmask" maskUnits="userSpaceOnUse" x="-260" y="-260" width="900" height="900">
        <rect fill="#fff" x="0" y="24" width="316" height="198"/>
        <g class="layer l-grid">
        <polygon fill="#000" points="130.0,85.0 244.3,151.0 244.3,162.0 130.0,228.0 15.7,162.0 15.7,151.0"/>
        <rect fill="#000" x="234.7" y="145.0" width="86" height="15"/>
        </g>
        <g class="layer l-page">
        <polygon fill="#000" points="130.0,85.0 244.3,151.0 244.3,162.0 130.0,228.0 15.7,162.0 15.7,151.0"/>
        <rect fill="#000" x="234.7" y="145.0" width="86" height="15"/>
        </g>
        <g class="layer l-chrome">
        <polygon fill="#000" points="130.0,85.0 244.3,151.0 244.3,162.0 130.0,228.0 15.7,162.0 15.7,151.0"/>
        <rect fill="#000" x="234.7" y="145.0" width="86" height="15"/>
        </g>
        </mask>
        </defs>
        <g class="rain" mask="url(#bk-rainmask)">
        <line class="streak len-xs" x1="-228.9" y1="152.8" x2="359.9" y2="-187.2" pathLength="100"/>
        <line class="speck per-20" x1="-236.7" y1="157.3" x2="352.2" y2="-182.7" pathLength="100"/>
        <line class="streak len-s" x1="-219.9" y1="168.4" x2="368.9" y2="-171.6" pathLength="100"/>
        <line class="speck per-25" x1="-182.7" y1="146.9" x2="406.2" y2="-193.1" pathLength="100"/>
        <line class="streak len-m" x1="-219.4" y1="169.2" x2="369.4" y2="-170.8" pathLength="100"/>
        <line class="streak len-xs" x1="-209.4" y1="186.6" x2="379.4" y2="-153.4" pathLength="100"/>
        <line class="speck per-20" x1="-178.1" y1="168.5" x2="410.8" y2="-171.5" pathLength="100"/>
        <line class="streak len-l" x1="-208.4" y1="188.3" x2="380.4" y2="-151.7" pathLength="100"/>
        <line class="speck per-25" x1="-216.2" y1="192.8" x2="372.7" y2="-147.2" pathLength="100"/>
        <line class="streak len-s" x1="-197.9" y1="206.5" x2="390.9" y2="-133.5" pathLength="100"/>
        <line class="streak len-xs" x1="-188.9" y1="222.1" x2="399.9" y2="-117.9" pathLength="100"/>
        <line class="speck per-20" x1="-196.7" y1="226.6" x2="392.2" y2="-113.4" pathLength="100"/>
        <line class="streak len-m" x1="-188.4" y1="222.9" x2="400.4" y2="-117.1" pathLength="100"/>
        <line class="speck per-20" x1="-139.4" y1="194.6" x2="449.5" y2="-145.4" pathLength="100"/>
        <line class="streak len-xl" x1="-178.4" y1="240.3" x2="410.4" y2="-99.7" pathLength="100"/>
        <line class="speck per-50" x1="-186.2" y1="244.8" x2="402.7" y2="-95.2" pathLength="100"/>
        <line class="streak len-s" x1="-177.9" y1="241.1" x2="410.9" y2="-98.9" pathLength="100"/>
        <line class="streak len-xs" x1="-168.4" y1="257.6" x2="420.4" y2="-82.4" pathLength="100"/>
        <line class="speck per-20" x1="-137.1" y1="239.5" x2="451.8" y2="-100.5" pathLength="100"/>
        <line class="streak len-l" x1="-159.4" y1="273.2" x2="429.4" y2="-66.8" pathLength="100"/>
        <line class="speck per-25" x1="-167.2" y1="277.7" x2="421.7" y2="-62.3" pathLength="100"/>
        <line class="streak len-m" x1="-158.9" y1="274.0" x2="429.9" y2="-66.0" pathLength="100"/>
        <line class="streak len-s" x1="-148.9" y1="291.3" x2="439.9" y2="-48.7" pathLength="100"/>
        <line class="speck per-25" x1="-111.7" y1="269.8" x2="477.2" y2="-70.2" pathLength="100"/>
        <line class="streak len-xs" x1="-148.9" y1="291.3" x2="439.9" y2="-48.7" pathLength="100"/>
        <line class="speck per-20" x1="-156.7" y1="295.8" x2="432.2" y2="-44.2" pathLength="100"/>
        <line class="streak len-l" x1="-135.9" y1="313.9" x2="452.9" y2="-26.1" pathLength="100"/>
        <line class="speck per-25" x1="-63.4" y1="272.0" x2="525.5" y2="-68.0" pathLength="100"/>
        <line class="streak len-s" x1="-133.9" y1="317.3" x2="454.9" y2="-22.7" pathLength="100"/>
        <line class="streak len-l" x1="-125.4" y1="332.1" x2="463.4" y2="-7.9" pathLength="100"/>
        <line class="speck per-25" x1="-133.2" y1="336.6" x2="455.7" y2="-3.4" pathLength="100"/>
        <line class="streak len-xs" x1="-116.4" y1="347.6" x2="472.4" y2="7.6" pathLength="100"/>
        <line class="speck per-20" x1="-85.1" y1="329.5" x2="503.8" y2="-10.5" pathLength="100"/>
        <line class="streak len-xl" x1="-117.4" y1="345.9" x2="471.4" y2="5.9" pathLength="100"/>
        <line class="streak len-m" x1="-107.9" y1="362.4" x2="480.9" y2="22.4" pathLength="100"/>
        <line class="speck per-20" x1="-115.7" y1="366.9" x2="473.2" y2="26.9" pathLength="100"/>
        <line class="streak len-s" x1="-106.4" y1="365.0" x2="482.4" y2="25.0" pathLength="100"/>
        <line class="speck per-25" x1="-69.2" y1="343.5" x2="519.7" y2="3.5" pathLength="100"/>
        <line class="streak len-xs" x1="-94.4" y1="385.7" x2="494.4" y2="45.7" pathLength="100"/>
        <line class="speck per-20" x1="-102.2" y1="390.2" x2="486.7" y2="50.2" pathLength="100"/>
        <line class="streak len-l" x1="-90.9" y1="391.8" x2="497.9" y2="51.8" pathLength="100"/>
        <line class="streak len-m" x1="-86.4" y1="399.6" x2="502.4" y2="59.6" pathLength="100"/>
        <line class="speck per-20" x1="-37.4" y1="371.3" x2="551.5" y2="31.3" pathLength="100"/>
        <line class="streak len-xs" x1="-74.9" y1="419.5" x2="513.9" y2="79.5" pathLength="100"/>
        <line class="speck per-20" x1="-82.7" y1="424.0" x2="506.2" y2="84.0" pathLength="100"/>
        <line class="streak len-s" x1="-75.9" y1="417.8" x2="512.9" y2="77.8" pathLength="100"/>
        <line class="speck per-25" x1="-38.7" y1="396.3" x2="550.2" y2="56.3" pathLength="100"/>
        <line class="streak len-m" x1="-64.9" y1="436.8" x2="523.9" y2="96.8" pathLength="100"/>
        </g>
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
