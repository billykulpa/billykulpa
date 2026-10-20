<?php /** @var array $pg */ ?>
<?php /* DRAFT COPY — written from Billy's notes; give it a voice pass. */ ?>

<section class="hero hero--inner">
  <div class="wrap">
    <p class="mono-label hero-kicker">01 / Work / FMA</p>
    <h1 class="hero-h1"><?= esc($pg['h1']) ?></h1>
    <p class="hero-lede">Around two million emails a year, across every brand
    in the family. A two-designer team builds the templates. Everyone else
    uses them, and nothing breaks.</p>
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
        <h2>Moving to Omeda</h2>
        <p>When FMA moved its email program to Omeda, we could have ported the
        old emails over and kept going. I rebuilt the library instead. Every
        recurring email, for every brand, became a real template, with the
        design decisions locked down and only the parts that should change
        left editable. The warm-up notice below went out first. It was also
        the first test of whether the system held.</p>

        <figure class="case-fig">
          <a class="fig-link" href="/assets/img/email/warmup-1-top.webp">
            <img src="/assets/img/email/warmup-1-top.webp" alt="The FMA warm-up email announcing the platform switch, with the FMA masthead" width="1152" height="1012" loading="lazy">
          </a>
          <figcaption class="mono-label">The warm-up email: the platform-switch notice, and the first template out the door.</figcaption>
        </figure>

        <h2>A template for every brand</h2>
        <p>There are more than twenty templates now. Newsletters, promos,
        renewals, showcases, event mail, across FMA, The Fabricator,
        SparkForce and the rest of the family. Each one speaks its own
        brand&rsquo;s dialect. SparkForce has the scaffolding motif, The
        Fabricator runs on rust and photography, Fabrinomics is plum and
        gold. Underneath, the skeleton is the same every time, the same way
        the social system works.</p>

        <figure class="case-fig">
          <a class="fig-link" href="/assets/img/email/email-templates-trio.webp">
            <img src="/assets/img/email/email-templates-trio.webp" alt="Three templates from the library: SparkForce Top Five, The Fabricator Pub Promo, and Fabrinomics: three different brand systems on one skeleton" width="3654" height="1200" loading="lazy">
          </a>
          <figcaption class="mono-label">Three of the templates: SparkForce Top Five, Pub Promo, Fabrinomics.</figcaption>
        </figure>

        <h2>Built to be operated by anyone</h2>
        <p>Image slots, headline patterns and buttons are already built and
        waiting. The marketing team drops in content and ships. Where an
        email varies week to week, every variation is pre-built and the
        editor deletes what that week does not need. The SparkForce Top Five
        is sometimes a Top Three. Nobody ever has to fake a &ldquo;4&rdquo;
        out of a &ldquo;5.&rdquo; The sponsored-content slot is a designed
        module too, so an ad placement cannot knock the layout over.</p>

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

        <p>Every template is responsive, and the breakpoints are built into the
        system. Masthead, modules, footer, all of it restacks for a phone
        without anyone having to think about it.</p>

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
        <p>At two million sends a year, governance is part of the design job.
        The rule I train hardest is never to reuse last week&rsquo;s email.
        Always start fresh from the template. People hear that as extra work.
        It is the safety system. A copied email brings everything along with
        it, including an expired sponsorship, a licensed image whose term ran
        out, a retired brand mark, a compliance line that has since changed.
        Start from the template and every send inherits the current version
        of all of it, CASL and GDPR language and the brand-family footer
        included, baked in where nobody can lose them.</p>

        <figure class="case-fig">
          <a class="fig-link" href="/assets/img/email/warmup-3-footer.webp">
            <img src="/assets/img/email/warmup-3-footer.webp" alt="The email footer with the FMA brand-family lockup and the CASL and GDPR compliance block" width="1152" height="1012" loading="lazy">
          </a>
          <figcaption class="mono-label">Compliance and the brand family, baked into every footer.</figcaption>
        </figure>

        <p>I also trained the marketing staff on the platform and the system,
        both how to work the templates and why the rules exist. Success here
        looks boring. On brand, on time, nothing slipping through, week after
        week.</p>
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
