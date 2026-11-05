<?php /** @var string $error */ /** @var string $next */ ?>
<div class="a-auth">
  <h1>Sign in</h1>
  <?php if ($error): ?><div class="a-flash a-flash--err"><?= esc($error) ?></div><?php endif; ?>
  <form method="post" action="/admin/login">
    <?= csrf_field() ?>
    <?php if ($next !== ''): ?><input type="hidden" name="next" value="<?= esc($next) ?>"><?php endif; ?>
    <label class="a-label" for="email">Email</label>
    <input type="email" id="email" name="email" required autocomplete="username">
    <label class="a-label" for="password">Password</label>
    <input type="password" id="password" name="password" required autocomplete="current-password">
    <button class="a-btn" type="submit">Sign in</button>
  </form>
</div>
