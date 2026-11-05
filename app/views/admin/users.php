<?php /** @var array $accounts */ /** @var string $error */ /** @var string $ok */ ?>
<h1>Accounts</h1>
<?php if ($error): ?><div class="a-flash a-flash--err"><?= esc($error) ?></div><?php endif; ?>
<?php if ($ok): ?><div class="a-flash a-flash--ok"><?= esc($ok) ?></div><?php endif; ?>

<table>
  <thead><tr><th>Name</th><th>Email</th><th>Type</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($accounts as $a): ?>
    <tr>
      <td><?= esc($a['display_name'] ?: '—') ?></td>
      <td><?= esc($a['email']) ?></td>
      <td><span class="a-pill a-pill--draft"><?= $a['role'] === 'admin' ? 'Admin' : 'MFL Member' ?></span></td>
      <td class="a-row-actions">
        <?php if ($a['role'] === 'mfl_member'): ?>
        <form method="post" action="/admin/users"><?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
          <button class="a-btn a-btn--danger a-btn--quiet" name="action" value="reset" type="submit">Reset password</button>
          <button class="a-btn a-btn--danger" name="action" value="remove" type="submit"
                  onclick="return confirm('Remove this account?')">Remove</button>
        </form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<h2 class="a-section-head">Add an MFL Member</h2>
<p class="a-hint">MFL Members can only open the MFL tab and change their own password. You'll get a temporary password to send them.</p>
<form method="post" action="/admin/users"><?= csrf_field() ?>
  <input type="hidden" name="action" value="add">
  <div class="a-grid-2">
    <div>
      <label class="a-label" for="display_name">Display name</label>
      <input type="text" id="display_name" name="display_name" required maxlength="80">
    </div>
    <div>
      <label class="a-label" for="email">Email</label>
      <input type="email" id="email" name="email" required>
    </div>
  </div>
  <button class="a-btn" type="submit">Create account</button>
</form>
