<?php /** @var array $pg */ ?>
<?php /* DRAFT COPY — Billy: this is written from your origin story and numbers;
        give it a voice pass. Structure and images are final-ready. */ ?>
<?php $rs = restreak_stats(); ?>

<section class="hero hero--inner">
  <div class="wrap">
    <p class="mono-label hero-kicker">01 / Work / Restreak</p>
    <h1 class="hero-h1"><?= esc($pg['h1']) ?></h1>
    <p class="hero-lede">A daily sports trivia game: brand, product design, and
    front-end engineering, shipped end-to-end by one person.
    Live at <a href="https://restreak.com" target="_blank" rel="noopener">restreak.com</a>.</p>
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
        <p>Before it was called Restreak, the game started as a group text.
        For a few years, my friends and I played a game with no name: someone
        would screenshot a roster from Basketball-Reference with the team and
        season cropped out, drop it in the group text, and whoever got closest
        on the team, the year, and the win total picked the next one.
        Restreak.com <em>is</em> that game, built properly: one shared puzzle
        per calendar day, a score out of 10, and a streak to protect.</p>

        <h2>Design decisions</h2>
        <p>The stat table is the whole interface, so it had to read like the
        reference material my friends and I were accustomed to: dense,
        utilitarian, Basketball-Reference-legible. Everything else stays out of its way:
        a near-black canvas, one orange accent, and a type system that lets
        thirty columns of numbers breathe. Light and dark themes share one
        variable scale, and the whole game works one-handed on a phone,
        where the roster scrolls like a box score.</p>

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
        <p>Restreak is hand-built PHP, MySQL, and vanilla JavaScript with no
        frameworks and no build step, running on ordinary shared hosting.
        The parts I sweated: puzzles are pre-generated and keyed to calendar
        dates so every player worldwide gets the same roster; all grading
        happens server-side and the answers never reach the client; a
        &plusmn;1-day window keeps clients from requesting future puzzles; and
        streak logic survives timezones by trusting each device's local
        calendar. Since launch it has grown to four sports (NBA, NFL, MLB,
        NHL), private leagues with invite codes, accounts with email
        verification, and public profiles, all through plain SQL
        migrations.</p>
        <p>Restreak was built with an assist from Claude, and I'd rather say so
        than let you wonder. Here's how that actually works: I wrote the product
        spec first (the scoring rules, the data model, the anti-cheat date
        window, the era-correct franchise logic) and treated the AI as a fast
        pair of hands, reading every diff before it shipped. The decisions that
        make Restreak <em>Restreak</em> (what the game is, how the table reads,
        what not to build) don't come from autocomplete. Directing tools well
        is a skill I'd put on the table, not under it.</p>
        <p>I also build in public: every release, and every revert, lives in
        the <a href="https://restreak.com/changelog" target="_blank" rel="noopener">public
        changelog</a>. Anyone can see the whole life of the product, including
        the parts that didn't work the first time.</p>
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
        Small, steady, and sticky: exactly what a daily game is supposed
        to be.</p>
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
