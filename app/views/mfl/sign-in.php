<?php /** @var bool $sent */ /** @var bool $failed */ /** @var ?array $owner */ ?>
<section class="hero hero--tight">
  <p class="mono-label">Owners</p>
  <h1>Sign in to vote</h1>
</section>
<section class="sign-in">
  <?php if ($owner): ?>
  <p>You're signed in as <?= esc(mfl_owner_label($owner['display_name'], $owner['real_name'])) ?>. <a class="link" href="/polls">Go to the polls</a></p>
  <?php elseif ($sent): ?>
  <p><b>Check your email.</b> If that address belongs to an MFL owner, a sign-in link is on its way. It works for <?= MFL_LINK_MINUTES ?> minutes.</p>
  <p class="note">Nothing yet? Check your spam folder, or ask the commissioner which email is on file.</p>
  <?php else: ?>
  <?php if ($failed): ?><p class="sign-in-error">That link has expired. Enter your email for a new one.</p><?php endif; ?>
  <p>Enter the email you use for the league, and we'll send you a link. No password needed.</p>
  <form method="post" action="/sign-in">
    <label class="mono-label" for="email">Email</label>
    <input type="email" id="email" name="email" required autocomplete="email">
    <button class="poll-vote" type="submit">Email me a link</button>
  </form>
  <?php endif; ?>
</section>
