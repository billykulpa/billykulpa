<?php /** @var array $pg */ ?>
<?php /* DRAFT COPY — Billy: this is written from your origin story and numbers;
        give it a voice pass. Structure and images are final-ready. */ ?>
<?php $rs = restreak_stats(); ?>

<section class="hero hero--inner">
  <div class="wrap">
    <p class="mono-label hero-kicker">01 / Work / Restreak</p>
    <h1 class="hero-h1"><?= esc($pg['h1']) ?></h1>
    <p class="hero-lede">A daily sports trivia game. I did the brand, the
    product design and the front-end engineering, and shipped the whole thing
    myself. Live at restreak.com.</p>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <dl class="case-meta">
      <div><dt class="mono-label">Role</dt><dd>Designer &amp; developer (solo)</dd></div>
      <div><dt class="mono-label">Scope</dt><dd>Identity, product UX, front-end, game logic, data</dd></div>
      <div><dt class="mono-label">Stack</dt><dd>PHP 8, MySQL, vanilla JS (no frameworks)</dd></div>
      <div><dt class="mono-label">Status</dt><dd>Live since June 2026</dd></div>
    </dl>

    <figure class="case-fig case-fig--hero">
      <img src="/assets/img/restreak/restreak-home-light.webp" alt="Restreak's daily NBA game: a dense stat table for a mystery roster, in the light theme" width="2880" height="1800">
      <figcaption class="mono-label">The daily game: one mystery roster, three questions.</figcaption>
    </figure>

    <div class="prose-grid">
      <div class="prose">
        <h2>Origins</h2>
        <p>Before it was called Restreak, the game started as a group text. My
        friends and I played it for a few years with no name on it. Someone
        would screenshot a roster from Basketball-Reference, crop out the team
        and the season, and drop it in the thread. Whoever got closest on the
        team, the year and the win total picked the next one. Restreak.com is
        that game, built properly. One shared puzzle per calendar day, a score
        out of 10, a streak to protect.</p>

        <h2>Design decisions</h2>
        <p>The stat table is the whole interface. It had to read like the
        reference material we were already used to, which means dense,
        utilitarian, and legible the way Basketball-Reference is legible.
        Everything else stays out of its way. Near-black canvas, one orange
        accent, and a type system that lets thirty columns of numbers
        breathe. Light and dark share one variable scale. The whole game
        works one-handed on a phone, where the roster scrolls like a box
        score.</p>

        <figure class="case-fig">
          <img src="/assets/img/restreak/restreak-home-light.webp" alt="The same daily game in the light theme" width="2880" height="1800" loading="lazy">
          <figcaption class="mono-label">One variable scale, two themes.</figcaption>
        </figure>

        <figure class="case-fig">
          <img src="/assets/img/restreak/restreak-results-desktop.webp" alt="The results panel: per-question marks, partial credit, score out of 10, and a countdown to the next roster" width="2880" height="2958" loading="lazy">
          <figcaption class="mono-label">Grading with partial credit. Close counts, but exact pays.</figcaption>
        </figure>

        <div class="fig-row">
          <figure class="case-fig">
            <img src="/assets/img/restreak/restreak-mobile-home.webp" alt="Restreak on mobile" width="780" height="1688" loading="lazy">
            <figcaption class="mono-label">Mobile: the roster scrolls like a box score.</figcaption>
          </figure>
          <figure class="case-fig">
            <img src="/assets/img/restreak/restreak-mobile-drawer.webp" alt="The mobile slide-out navigation drawer" width="780" height="1688" loading="lazy">
            <figcaption class="mono-label">The slide-out drawer.</figcaption>
          </figure>
        </div>

        <figure class="case-fig">
          <!-- NOTE: this screenshot shows SIMULATED demo data (seeded users),
               not real players. Replace with a live capture from restreak.com
               when convenient: save over
               /assets/img/restreak/restreak-leaderboard-view.webp. -->
          <img src="/assets/img/restreak/restreak-leaderboard-view.webp" alt="The leaderboard: a medal podium above a 21-deep ranked list" width="2880" height="1800" loading="lazy">
          <figcaption class="mono-label">The leaderboard: 21 deep, podium up top.</figcaption>
        </figure>

        <h2>Engineering</h2>
        <p>Restreak is hand-built PHP, MySQL and vanilla JavaScript. No
        frameworks, no build step, running on ordinary shared hosting. The
        parts I sweated over were the ones that break quietly. Puzzles are
        pre-generated and keyed to calendar dates, so every player in the
        world gets the same roster. Grading happens server-side and the
        answers never reach the client. A &plusmn;1-day window stops anyone
        requesting tomorrow&rsquo;s puzzle early. Streak logic survives
        timezones by trusting each device&rsquo;s local calendar. Since launch
        it has grown to four sports (NBA, NFL, MLB, NHL), private leagues with
        invite codes, accounts with email verification, and public profiles,
        all through plain SQL migrations.</p>
        <p>Restreak was built with an assist from Claude, and I&rsquo;d rather
        say so than let you wonder. I wrote the product spec first. The
        scoring rules, the data model, the anti-cheat date window, the
        era-correct franchise logic. Then I treated the AI as a fast pair of
        hands and read every diff before it shipped. What the game is, how the
        table reads, what not to build: none of that comes from autocomplete.
        Directing these tools well is a skill, and I&rsquo;ll claim it.</p>
        <p>I also build in public. Every release and every revert lives in the
        changelog. Anyone can read the whole life of the product, including
        the parts that did not work the first time.</p>
        <p><!-- PLACEHOLDER: optionally add a paragraph on the data pipeline
        (the Python importers, rate-limited scraping, resume-on-failure) or
        an honest war story (the mojibake incident is a good one). --></p>

        <h2>Where it stands</h2>
        <p>Restreak launched in June 2026 and has been played
        <strong><?= number_format($rs['total_plays']) ?> times</strong>. It has
        <strong><?= number_format($rs['registered_players']) ?> registered
        players</strong>, with <?= number_format($rs['active_players_30d']) ?> of
        them active in the last 30 days, and the longest streak so far is
        <strong><?= number_format($rs['longest_streak']) ?> days</strong>.
        That is a small audience, and it keeps showing up. For a daily game
        that is the only number that matters.</p>
        <p class="mono-label"><?= $rs['live']
            ? "These numbers are live, pulled from Restreak's database."
            : 'Numbers captured July 2026. They compound daily.' ?></p>
      </div>
      <aside class="prose-aside">
        <div class="fact-stack">
          <div><p class="mono-label">A full play, start to finish</p></div>
          <figure class="case-fig">
            <img src="/assets/img/restreak/restreak-results-mobile.webp" alt="A complete played round on mobile in the light theme, from roster to results" width="780" height="4670" loading="lazy">
          </figure>
          <div><p class="mono-label" style="margin-top: 12px">By the numbers</p></div>
          <div><dt class="mono-label">Launched</dt><dd>June 2026</dd></div>
          <div><dt class="mono-label">Registered players</dt><dd><?= number_format($rs['registered_players']) ?> (<?= number_format($rs['active_players_30d']) ?> active in the last 30 days)</dd></div>
          <div><dt class="mono-label">Total plays</dt><dd><?= number_format($rs['total_plays']) ?></dd></div>
          <div><dt class="mono-label">Longest streak</dt><dd><?= number_format($rs['longest_streak']) ?> days</dd></div>
        </div>
      </aside>
    </div>
  </div>
</section>

<section class="section section--cta">
  <div class="wrap">
    <h2 class="cta-h2">Play today's roster at restreak.com</h2>
    <p><a class="btn" href="https://restreak.com" target="_blank" rel="noopener">Play Restreak &rarr;</a></p>
  </div>
</section>
