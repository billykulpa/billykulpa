<?php /** @var string $error */ ?>
<div class="a-auth">
  <h1>First-run setup</h1>
  <p>Create the one admin account. This page disappears after it's used.</p>
  <?php if ($error): ?><div class="a-flash a-flash--err"><?= esc($error) ?></div><?php endif; ?>
  <form method="post" action="/admin/setup">
    <?= csrf_field() ?>
    <label class="a-label" for="email">Email</label>
    <input type="email" id="email" name="email" required autocomplete="username">
    <label class="a-label" for="password">Password</label>
    <input type="password" id="password" name="password" required minlength="12" autocomplete="new-password">
    <p class="a-hint">12+ characters. A password manager phrase works well.</p>
    <button class="a-btn" type="submit">Create account</button>
  </form>
</div>
