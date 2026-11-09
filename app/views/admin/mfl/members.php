<?php /** @var array $accounts */ /** @var string $error */ /** @var string $ok */ /** @var array $me */ ?>
<?php require __DIR__ . '/_subnav.php'; ?>
<h1>Members</h1>
<p class="a-hint">Who can sign in to this league's admin. League admins run everything here, including these accounts. Members write power rankings and polls and comment on awards.</p>
<?php if ($error): ?><div class="a-flash a-flash--err"><?= esc($error) ?></div><?php endif; ?>
<?php if ($ok): ?><div class="a-flash a-flash--ok"><?= esc($ok) ?></div><?php endif; ?>

<table>
  <thead><tr><th>Name</th><th>Email</th><th>Type</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($accounts as $a): $self = (int) $a['id'] === (int) $me['id']; ?>
    <tr>
      <td><?= esc($a['display_name'] ?: '—') ?><?= $self ? ' <span class="a-hint">(you)</span>' : '' ?></td>
      <td><?= esc($a['email']) ?></td>
      <td><span class="a-pill a-pill--draft"><?= $a['role'] === 'admin' ? 'League admin' : 'Member' ?></span></td>
      <td class="a-row-actions">
        <?php if (!$self): ?>
        <form method="post" action="<?= mfl_admin_base() ?>/members"><?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
          <input type="hidden" name="role" value="<?= $a['role'] === 'admin' ? 'member' : 'admin' ?>">
          <button class="a-btn a-btn--quiet" name="action" value="role" type="submit"><?= $a['role'] === 'admin' ? 'Make member' : 'Make league admin' ?></button>
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

<h2 class="a-section-head">Add an account</h2>
<p class="a-hint">You'll get a temporary password to send them. They can change it under Password.</p>
<form method="post" action="<?= mfl_admin_base() ?>/members"><?= csrf_field() ?>
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
  <label class="a-label" for="role">Type</label>
  <select id="role" name="role">
    <option value="member">Member</option>
    <option value="admin">League admin</option>
  </select>
  <button class="a-btn" type="submit">Create account</button>
</form>
