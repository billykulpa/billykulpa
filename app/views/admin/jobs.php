<?php /** @var array $apps */
$statusColors = [
  'offer' => 'a-pill--published', 'interview' => 'a-pill--published',
  'callback' => 'a-pill--published', 'applied' => 'a-pill--draft',
  'found' => 'a-pill--draft', 'denied' => 'a-pill--denied', 'abandoned' => 'a-pill--denied',
];
?>
<div class="a-toolbar">
  <h1>Job tracker</h1>
  <a class="a-btn" href="/admin/jobs/new">Add application</a>
</div>

<?php if (!$apps): ?>
<p class="a-sub">Nothing tracked yet. Add the first one.</p>
<?php else: ?>
<table>
  <thead>
    <tr><th>Company</th><th>Role</th><th>Comp</th><th>Status</th><th>Applied</th><th></th></tr>
  </thead>
  <tbody>
    <?php foreach ($apps as $a): ?>
    <tr>
      <td><a class="a-rowlink" href="/admin/jobs/edit?id=<?= (int) $a['id'] ?>"><?= esc($a['company']) ?></a></td>
      <td><?= esc($a['role']) ?><?= $a['remote'] !== '' ? ' <span class="a-count">· ' . esc($a['remote']) . '</span>' : '' ?></td>
      <td><?= esc($a['comp']) ?></td>
      <td><span class="a-pill <?= $statusColors[$a['status']] ?? 'a-pill--draft' ?>"><?= esc($a['status']) ?></span></td>
      <td><?= esc(nice_date($a['applied_on'])) ?></td>
      <td><?php if ($a['url'] !== ''): ?><a class="a-count" href="<?= esc($a['url']) ?>" target="_blank" rel="noopener">Posting &nearr;</a><?php endif; ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>
