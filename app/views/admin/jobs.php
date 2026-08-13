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
<div class="a-table-scroll">
<table class="a-jobs-table">
  <thead>
    <tr><th>Company</th><th>Role</th><th>Comp</th><th>Status</th><th>Applied</th><th></th></tr>
  </thead>
  <tbody>
    <?php foreach ($apps as $a): ?>
    <tr>
      <td class="a-job-company"><a class="a-rowlink" href="/admin/jobs/edit?id=<?= (int) $a['id'] ?>"><?= esc($a['company']) ?></a>
        <span class="a-job-status-inline"><span class="a-pill <?= $statusColors[$a['status']] ?? 'a-pill--draft' ?>"><?= esc($a['status']) ?></span></span></td>
      <td class="a-job-role"><?= esc($a['role']) ?><?= $a['remote'] !== '' ? ' <span class="a-count">· ' . esc($a['remote']) . '</span>' : '' ?></td>
      <td data-label="Comp"><?= esc($a['comp']) ?></td>
      <td class="a-job-status" data-label="Status"><span class="a-pill <?= $statusColors[$a['status']] ?? 'a-pill--draft' ?>"><?= esc($a['status']) ?></span></td>
      <td data-label="Applied"><?= esc(nice_date($a['applied_on'])) ?></td>
      <td class="a-job-link"><?php if ($a['url'] !== ''): ?><a class="a-count" href="<?= esc($a['url']) ?>" target="_blank" rel="noopener">Posting &nearr;</a><?php endif; ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php endif; ?>
