<?php /** @var array $pg */ ?>
<?php /* DRAFT COPY — adapted from Billy's launch notes; give it a voice pass. */ ?>

<section class="hero hero--inner">
  <div class="wrap">
    <p class="mono-label hero-kicker">01 / Work / Reading Quest</p>
    <h1 class="hero-h1"><?= esc($pg['h1']) ?></h1>
    <p class="hero-lede"><?= ($pg['lede'] ?? '') !== '' ? md_inline($pg['lede'])
      : 'A screen-time deal with my son, shipped as a product: read today&rsquo;s chapter, pass a quiz, unlock the iPad. He&rsquo;s five books in.' ?></p>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <dl class="case-meta">
      <div><dt class="mono-label">Client</dt><dd>Jude, my youngest</dd></div>
      <div><dt class="mono-label">Role</dt><dd>Design, development, quiz writing, parenting</dd></div>
      <div><dt class="mono-label">Users</dt><dd>One reader, two subscribed parents</dd></div>
      <div><dt class="mono-label">Status</dt><dd>Live since summer 2026, at a private address only our family uses</dd></div>
    </dl>

    <div class="prose-grid">
      <div class="prose">
        <h2>The problem arrived with summer</h2>
        <p>When school let out, the structure went with it, and my wife and I
        watched our youngest disappear into video games and the iPad for
        hours a day. We were a little alarmed. So we made him a deal, and the
        deal became a brief: you want screen time? Read a book to unlock it.</p>
        <p>I know an app is a counterintuitive tool for limiting screens.
        But the app isn&rsquo;t the point; the book is. The screen&rsquo;s job
        is to hand out the assignment, check the reading actually happened,
        and get out of the way. The long-term goal was never rules for their
        own sake. It&rsquo;s to help him fall in love with reading.</p>

        <h2>How it works</h2>
        <p>Every day the app deals one reading: a chapter or two, sized to
        about thirty minutes. When he&rsquo;s done, he takes a ten-question
        quiz on what he read. Seven out of ten unlocks his screens for the
        day, and a push alert lands on our phones the moment he passes.
        (It started at eight out of ten. I lowered it. Difficulty tuning is
        part of the job, even when the player is your son.)</p>

        <figure class="case-fig">
          <img src="/assets/img/reading-quest/rq-todays-reading.webp" alt="The daily reading assignment screen: Reading 1 of The Last Kids on Earth and the Midnight Blade" width="1187" height="1008" loading="lazy">
          <figcaption class="mono-label">The daily deal: one reading, then the quiz.</figcaption>
        </figure>

        <figure class="case-fig">
          <img src="/assets/img/reading-quest/rq-quiz.webp" alt="The ten-question quiz screen with the line: Answer all 10. Get 7 right to unlock your screens." width="1039" height="1148" loading="lazy">
          <figcaption class="mono-label">The stakes, stated plainly: get 7 right to unlock your screens.</figcaption>
        </figure>

        <p>The first book was Harry Potter and the Sorcerer&rsquo;s Stone.
        He&rsquo;d seen the movie and already owned the series, and I wanted
        the first book to be one he&rsquo;d actually enjoy, because the whole
        system fails if the reading feels like homework. He finished it in
        seventeen readings and asked what was next.</p>

        <h2>Design decisions</h2>
        <p>It looks like a storybook on purpose: a night sky, gold small
        caps, one warm parchment card floating in the middle. Every day gets
        a chapter title treated like the start of an adventure, progress
        chips turn gold as readings pass, and finishing a book earns a star
        and a proper celebration screen. The microcopy stays kind
        (&ldquo;No pressure to read every day. Pick it up whenever you want
        to.&rdquo;) because the app is a deal between us, not a homework
        portal.</p>

        <figure class="case-fig">
          <img src="/assets/img/reading-quest/rq-book-complete.webp" alt="The celebration screen: You finished The Last Kids on Earth and the Cosmic Beyond!" width="1139" height="1064" loading="lazy">
          <figcaption class="mono-label">Finishing a book is an event, with the next one queued right there.</figcaption>
        </figure>

        <figure class="case-fig">
          <img src="/assets/img/reading-quest/rq-bookshelf.webp" alt="The bookshelf screen: 5 books finished, 41 readings passed" width="1049" height="1148" loading="lazy">
          <figcaption class="mono-label">The bookshelf keeps every pass forever. Nothing is lost when he switches books.</figcaption>
        </figure>

        <h2>The parent panel</h2>
        <p>Behind a separate password, my wife and I get the operations
        side: a library where any book can be made active or reset, a
        reading queue that starts the next book automatically when one is
        finished, a send-him-back-to-a-chapter tool for retakes, and a
        history of every pass with its score. It&rsquo;s the same system
        thinking I use at work (templates, governance, quality gates)
        applied to an audience of three.</p>

        <div class="fig-row">
          <figure class="case-fig">
            <img src="/assets/img/reading-quest/rq-reading-queue.webp" alt="The reading queue: What comes next, with reorderable books" width="1253" height="952" loading="lazy">
            <figcaption class="mono-label">The queue. When a book ends, the next begins.</figcaption>
          </figure>
          <figure class="case-fig">
            <img src="/assets/img/reading-quest/rq-history.webp" alt="The history screen: recent passes with dates and scores" width="1258" height="952" loading="lazy">
            <figcaption class="mono-label">Every pass, dated and scored.</figcaption>
          </figure>
        </div>

        <h2>Where it stands</h2>
        <p>In his first summer he finished five books and passed
        forty-one readings, with quiz scores living in the 7-to-9 range,
        which is right where a well-tuned game should sit. Harry Potter
        fell on July 11. The Last Kids on Earth series fell next, book
        after book, and he&rsquo;s now on the sixth one. When I announced
        this project, I promised to report back if it backfired. It
        hasn&rsquo;t. He asks what&rsquo;s next.</p>
      </div>
      <aside class="prose-aside">
        <div class="fact-stack">
          <div><dt class="mono-label">The deal</dt><dd>One daily reading (~30 minutes), a 10-question quiz, 7/10 to unlock screen time</dd></div>
          <div><dt class="mono-label">First summer</dt><dd>5 books finished, 41 readings passed, quiz scores steady at 7&ndash;9 out of 10</dd></div>
          <div><dt class="mono-label">Features</dt><dd>Daily assignments, server-checked quizzes, push alerts to parents, bookshelf history, parent panel with queue, retakes, and passwords</dd></div>
          <div><dt class="mono-label">Built with</dt><dd>An assist from Claude, directed by a dad with a spec</dd></div>
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
