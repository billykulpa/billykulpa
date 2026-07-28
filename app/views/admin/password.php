<?php /** @var string $error */ /** @var string $ok */ ?>
<div class="a-auth">
  <h1>Change password</h1>
  <?php if ($error): ?><div class="a-flash a-flash--err"><?= esc($error) ?></div><?php endif; ?>
  <?php if ($ok): ?><div class="a-flash a-flash--ok"><?= esc($ok) ?></div><?php endif; ?>
  <form method="post" action="/admin/password">
    <?= csrf_field() ?>
    <label class="a-label" for="current_password">Current password</label>
    <input type="password" id="current_password" name="current_password" required autocomplete="current-password">
    <label class="a-label" for="new_password">New password (12+ characters)</label>
    <input type="password" id="new_password" name="new_password" required minlength="12" autocomplete="new-password">
    <label class="a-label" for="confirm_password">Confirm new password</label>
    <input type="password" id="confirm_password" name="confirm_password" required minlength="12" autocomplete="new-password">
    <button class="a-btn" type="submit">Update password</button>
  </form>
</div>
