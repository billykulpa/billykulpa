<?php /** @var array $pg */ ?>
<?php /* DRAFT COPY — written from Billy's notes; give it a voice pass. */ ?>

<section class="hero hero--inner">
  <div class="wrap">
    <p class="mono-label hero-kicker">01 / Work / FMA</p>
    <h1 class="hero-h1"><?= esc($pg['h1']) ?></h1>
    <p class="hero-lede">Around two million emails a year, every brand in the
    family, a two-designer team, and a template system that lets the whole
    organization ship email without breaking anything.</p>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <dl class="case-meta">
      <div><dt class="mono-label">Role</dt><dd>Design systems, templates, training, governance</dd></div>
      <div><dt class="mono-label">Platform</dt><dd>Omeda</dd></div>
      <div><dt class="mono-label">Volume</dt><dd>~2,000,000 sends a year</dd></div>
      <div><dt class="mono-label">Ongoing</dt><dd>2025&ndash;present</dd></div>
    </dl>

    <div class="prose-grid">
      <div class="prose">
        <h2>A new platform, a fresh start</h2>
        <p>When FMA moved its email program to Omeda, the easy path was to
        port the old emails over and keep going. Instead, I treated the
        migration as a chance to rebuild the entire library the right way:
        every recurring email, for every brand, rebuilt as a proper template
        with the design decisions locked in and the editable parts left
        open. The first email our audience saw from the new platform was the
        warm-up notice below, which was also the first proof of the new
        system.</p>

        <figure class="case-fig">
          <a class="fig-link" href="/assets/img/email/warmup-1-top.webp">
            <img src="/assets/img/email/warmup-1-top.webp" alt="The FMA warm-up email announcing the platform switch, with the FMA masthead" width="1152" height="1012" loading="lazy">
          </a>
          <figcaption class="mono-label">The warm-up email: the platform-switch notice, and the first template out the door.</figcaption>
        </figure>

        <h2>A template for every brand</h2>
        <p>The library now covers more than twenty templates: newsletters,
        promos, renewals, showcases, and event emails across FMA,
        The Fabricator, SparkForce, and the rest of the family. Each one
        speaks its own brand&rsquo;s dialect (SparkForce&rsquo;s scaffolding
        motif, The Fabricator&rsquo;s rust and photography, Fabrinomics in
        plum and gold) while the underlying skeleton stays consistent, the
        same way the social system works.</p>

        <figure class="case-fig">
          <a class="fig-link" href="/assets/img/email/email-templates-trio.webp">
            <img src="/assets/img/email/email-templates-trio.webp" alt="Three templates from the library: SparkForce Top Five, The Fabricator Pub Promo, and Fabrinomics: three different brand systems on one skeleton" width="3654" height="1200" loading="lazy">
          </a>
          <figcaption class="mono-label">Three of the templates: SparkForce Top Five, Pub Promo, Fabrinomics.</figcaption>
        </figure>

        <h2>Built to be operated by anyone</h2>
        <p>The templates are designed for the people who use them, not the
        person who made them. Image slots, headline patterns, and buttons are
        pre-built and waiting; the marketing team drops in content and
        ships. The best trick in the system: where an email varies week to
        week (the SparkForce Top Five is sometimes a Top Three), every
        variation is pre-built into the template, and the editor simply
        deletes what the week doesn&rsquo;t need. Nobody ever has to fake a
        &ldquo;4&rdquo; out of a &ldquo;5.&rdquo; Even the sponsored-content
        slot is a designed module, so ad placements can&rsquo;t knock the
        layout over.</p>

        <div class="gallery-scroll">
          <div class="gallery-track">
            <figure class="case-fig">
              <a class="fig-link" href="/assets/img/email/fabricator-1-masthead.webp">
                <img src="/assets/img/email/fabricator-1-masthead.webp" alt="The Fabricator Newsletter template in the editor: masthead and hero image slot" width="1152" height="1012" loading="lazy">
              </a>
              <figcaption class="mono-label">The Fabricator Newsletter, in the editor: masthead and hero slot.</figcaption>
            </figure>
            <figure class="case-fig">
              <a class="fig-link" href="/assets/img/email/fabricator-2-article.webp">
                <img src="/assets/img/email/fabricator-2-article.webp" alt="The article module: overline, headline, body, image slot, and button, all pre-built" width="1152" height="1012" loading="lazy">
              </a>
              <figcaption class="mono-label">The article module: every piece pre-built.</figcaption>
            </figure>
            <figure class="case-fig">
              <a class="fig-link" href="/assets/img/email/fabricator-3-podcast.webp">
                <img src="/assets/img/email/fabricator-3-podcast.webp" alt="The FMA Podcast Network section of the newsletter template" width="1152" height="1012" loading="lazy">
              </a>
              <figcaption class="mono-label">The podcast section.</figcaption>
            </figure>
            <figure class="case-fig">
              <a class="fig-link" href="/assets/img/email/fabricator-4-sponsored.webp">
                <img src="/assets/img/email/fabricator-4-sponsored.webp" alt="The sponsored-content module, designed into the template" width="1152" height="1012" loading="lazy">
              </a>
              <figcaption class="mono-label">Sponsored content: a designed module, not an afterthought.</figcaption>
            </figure>
            <figure class="case-fig">
              <a class="fig-link" href="/assets/img/email/fabricator-5-footer.webp">
                <img src="/assets/img/email/fabricator-5-footer.webp" alt="The footer: the full FMA brand-family lockup, social links, and the compliance block" width="1152" height="1012" loading="lazy">
              </a>
              <figcaption class="mono-label">The footer: brand family, socials, compliance.</figcaption>
            </figure>
          </div>
        </div>

        <p>Every template is responsive, and the breakpoints are part of the
        system: the masthead, modules, and footer all restack for a phone
        without anyone thinking about it.</p>

        <div class="fig-row">
          <figure class="case-fig">
            <img src="/assets/img/email/fabricator-mobile-top.webp" alt="The Fabricator Newsletter at mobile width: the masthead restacked for a 320-pixel screen" width="616" height="950" loading="lazy">
            <figcaption class="mono-label">Mobile, top.</figcaption>
          </figure>
          <figure class="case-fig">
            <img src="/assets/img/email/fabricator-mobile-footer.webp" alt="The newsletter footer at mobile width" width="616" height="950" loading="lazy">
            <figcaption class="mono-label">Mobile, footer.</figcaption>
          </figure>
        </div>

        <h2>The rules that keep it safe</h2>
        <p>At two million sends a year, governance is a design problem. The
        rule I train hardest: never reuse last week&rsquo;s email; always
        start fresh from the template. It sounds like extra work; it&rsquo;s
        actually the safety system. A copied email carries everything with
        it: an expired sponsorship, a licensed image whose term ended, a
        retired brand mark, an outdated compliance line. Starting from the
        template means every send inherits the current, correct version of
        all of it, including the CASL and GDPR language and the
        brand-family footer, which are baked in where nobody can lose
        them.</p>

        <figure class="case-fig">
          <a class="fig-link" href="/assets/img/email/warmup-3-footer.webp">
            <img src="/assets/img/email/warmup-3-footer.webp" alt="The email footer with the FMA brand-family lockup and the CASL and GDPR compliance block" width="1152" height="1012" loading="lazy">
          </a>
          <figcaption class="mono-label">Compliance and the brand family, baked into every footer.</figcaption>
        </figure>

        <p>Alongside the build-out, I trained the marketing staff on the
        platform and the system: how to work the templates, and why the
        rules exist. The measure of success is that email at this volume is
        boring, in the best sense: on brand, on time, and nothing slips
        through.</p>
      </div>
      <aside class="prose-aside">
        <div class="fact-stack">
          <div><dt class="mono-label">The system</dt><dd>20+ templates across the brand family, pre-built variations, designed ad slots</dd></div>
          <div><dt class="mono-label">The rule</dt><dd>Always start fresh from the template; copied emails carry expired rights, retired marks, and stale compliance with them</dd></div>
          <div><dt class="mono-label">The audience</dt><dd>~2,000,000 sends a year across member companies and the wider industry</dd></div>
          <div><dt class="mono-label">See also</dt><dd><a href="/work/fma-social">FMA Social Brand Management</a>, the same brand family, in the feed</dd></div>
        </div>
      </aside>
    </div>
  </div>
</section>

<section class="section section--cta">
  <div class="wrap">
    <h2 class="cta-h2">More work</h2>
    <p><a class="btn" href="/work">Back to all work &rarr;</a></p>
  </div>
</section>
