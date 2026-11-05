<?php /** @var bool $saved */ /** @var array $franchises */ ?>
<h1>Owners</h1>
<?php require __DIR__ . '/_subnav.php'; ?>
<?php if ($saved): ?><div class="a-flash a-flash--ok">Saved.</div><?php endif; ?>
<p class="a-hint">Sleeper doesn't share real names. Names entered here appear on the public site as Username (First Last); blank ones show the username alone.</p>
<form method="post" action="/admin/mfl/owners"><?= csrf_field() ?>
  <table>
    <thead><tr><th>Team</th><th>Username</th><th>First and last name</th></tr></thead>
    <tbody>
    <?php foreach ($franchises as $f): if (!$f['owner_id']) continue; ?>
      <tr>
        <td><?= esc($f['name']) ?></td>
        <td><?= esc($f['owner_name']) ?></td>
        <td><input type="text" name="real_name[<?= esc($f['owner_id']) ?>]" value="<?= esc($f['real_name']) ?>" maxlength="120"
                   aria-label="Real name for <?= esc($f['owner_name']) ?>"></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <button class="a-btn" type="submit">Save names</button>
</form>
