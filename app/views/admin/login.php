<?php /** @var string $error */ ?>
<div class="a-auth">
  <h1>Sign in</h1>
  <?php if ($error): ?><div class="a-flash a-flash--err"><?= esc($error) ?></div><?php endif; ?>
  <form method="post" action="/admin/login">
    <?= csrf_field() ?>
    <label class="a-label" for="email">Email</label>
    <input type="email" id="email" name="email" required autocomplete="username">
    <label class="a-label" for="password">Password</label>
    <input type="password" id="password" name="password" required autocomplete="current-password">
    <button class="a-btn" type="submit">Sign in</button>
  </form>
</div>
