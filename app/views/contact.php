<?php /** @var array $pg */ /** @var string $sent */ /** @var string $error */ /** @var array $old */ ?>

<section class="hero hero--inner">
  <div class="wrap">
    <p class="mono-label hero-kicker">04 / Contact</p>
    <h1 class="hero-h1"><?= esc($pg['h1'] ?: 'Hello there.') ?></h1>
    <p class="hero-lede">Thank you for visiting. If you have questions, need help
    with a project, or would like to get in touch, fill out the form below.
    I&rsquo;ll get back to you as soon as possible.</p>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <?php if ($sent): ?>
      <div class="form-notice form-notice--ok">Sent. Thanks for reaching out; I&rsquo;ll reply soon.</div>
    <?php elseif ($error): ?>
      <div class="form-notice form-notice--err"><?= esc($error) ?></div>
    <?php endif; ?>

    <?php if (!$sent): ?>
    <form method="post" action="/contact" class="contact-form" autocomplete="on">
      <div class="contact-grid">
        <div>
          <label for="c-name">Name</label>
          <input type="text" id="c-name" name="name" required maxlength="100" value="<?= esc($old['name'] ?? '') ?>">
        </div>
        <div>
          <label for="c-email">Email</label>
          <input type="email" id="c-email" name="email" required maxlength="190" value="<?= esc($old['email'] ?? '') ?>">
        </div>
      </div>
      <label for="c-message">Message</label>
      <textarea id="c-message" name="message" required maxlength="5000"><?= esc($old['message'] ?? '') ?></textarea>

      <!-- Honeypot: humans never see it; bots can't resist it. -->
      <div class="cf-website" aria-hidden="true">
        <label for="c-website">Website</label>
        <input type="text" id="c-website" name="website" tabindex="-1" autocomplete="off">
      </div>

      <div class="contact-turnstile">
        <div class="cf-turnstile" data-sitekey="<?= esc(config()['turnstile']['site_key']) ?>"></div>
      </div>

      <button class="btn" type="submit">Say hello</button>
    </form>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    <?php endif; ?>
  </div>
</section>
