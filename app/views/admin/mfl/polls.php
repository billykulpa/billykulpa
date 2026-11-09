<?php /** @var ?array $flash */ /** @var array $user */ /** @var array $polls */ ?>
<h1>Polls</h1>
<?php require __DIR__ . '/_subnav.php'; ?>
<?php if ($flash): ?><div class="a-flash a-flash--<?= $flash['status'] === 'ok' ? 'ok' : 'err' ?>"><?= esc($flash['summary']) ?></div><?php endif; ?>

<div class="a-toolbar">
  <span></span>
  <a class="a-btn" href="<?= mfl_admin_base() ?>/polls/new">New poll</a>
</div>

<?php if ($polls): ?>
<div class="a-table-scroll"><table>
  <thead><tr><th>Poll</th><th>Author</th><th>Status</th><th>Votes</th><th>Closes (CT)</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($polls as $p):
    $canEdit = $user['role'] === 'admin' || (int) $p['author_user_id'] === (int) $user['id'];
    $state = mfl_poll_state($p); ?>
    <tr>
      <td><?php if ($canEdit): ?><a class="a-rowlink" href="<?= mfl_admin_base() ?>/polls/edit?id=<?= (int) $p['id'] ?>"><?= esc($p['question']) ?></a>
        <?php else: ?><?= esc($p['question']) ?><?php endif; ?></td>
      <td><?= esc($p['author_name']) ?></td>
      <td><span class="a-pill a-pill--<?= $state === 'open' ? 'published' : 'draft' ?>"><?= esc($state) ?></span></td>
      <td><?= (int) $p['votes'] ?></td>
      <td><?= esc(central_time($p['expires_at'], 'M j, g:i a')) ?></td>
      <td class="a-row-actions">
        <form method="post" action="<?= mfl_admin_base() ?>/polls/clone"><?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
          <button class="a-link-btn" type="submit">Clone</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php else: ?>
<p class="a-hint">No polls yet.</p>
<?php endif; ?>
