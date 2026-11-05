<?php /** @var ?array $flash */ /** @var array $user */ /** @var array $polls */ ?>
<h1>Polls</h1>
<?php require __DIR__ . '/_subnav.php'; ?>
<?php if ($flash): ?><div class="a-flash a-flash--<?= $flash['status'] === 'ok' ? 'ok' : 'err' ?>"><?= esc($flash['summary']) ?></div><?php endif; ?>

<div class="a-toolbar">
  <span></span>
  <a class="a-btn" href="/admin/mfl/polls/new">New poll</a>
</div>

<?php if ($polls): ?>
<table>
  <thead><tr><th>Poll</th><th>Author</th><th>Status</th><th>Votes</th><th>Closes (CT)</th></tr></thead>
  <tbody>
  <?php foreach ($polls as $p):
    $canEdit = $user['role'] === 'admin' || (int) $p['author_user_id'] === (int) $user['id'];
    $state = mfl_poll_state($p); ?>
    <tr>
      <td><?php if ($canEdit): ?><a class="a-rowlink" href="/admin/mfl/polls/edit?id=<?= (int) $p['id'] ?>"><?= esc($p['question']) ?></a>
        <?php else: ?><?= esc($p['question']) ?><?php endif; ?></td>
      <td><?= esc($p['author_name']) ?></td>
      <td><span class="a-pill a-pill--<?= $state === 'open' ? 'published' : 'draft' ?>"><?= esc($state) ?></span></td>
      <td><?= (int) $p['votes'] ?></td>
      <td><?= esc(central_time($p['expires_at'], 'M j, g:i a')) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php else: ?>
<p class="a-hint">No polls yet.</p>
<?php endif; ?>
