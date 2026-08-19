<?php /** @var array $apps @var string $show @var array $counts @var array $opens */
$show = $show ?? 'active';
$counts = $counts ?? [];
$filters = ['found' => 'Found', 'active' => 'Active', 'closed' => 'Closed', 'all' => 'All'];
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

<nav class="a-filters" aria-label="Filter applications">
  <?php foreach ($filters as $key => $label): ?>
  <a class="a-filter<?= $show === $key ? ' is-on' : '' ?>"
     href="/admin/jobs<?= $key === 'active' ? '' : '?show=' . $key ?>"
     <?= $show === $key ? 'aria-current="page"' : '' ?>><?= $label ?>
    <span class="a-filter-n"><?= (int) ($counts[$key] ?? 0) ?></span></a>
  <?php endforeach; ?>
</nav>

<?php if (!$apps): ?>
<p class="a-sub"><?= match (true) {
  $show === 'found' => 'Nothing waiting to be applied for. Inbox zero.',
  $show === 'closed' => 'Nothing closed yet.',
  $show === 'active' && ($counts['all'] ?? 0) > 0 => 'Nothing active right now.',
  default => 'Nothing tracked yet. Add the first one.',
} ?></p>
<?php else: ?>
<div class="a-jobs-list">
  <?php foreach ($apps as $a): ?>
  <div class="a-job-row">
    <div class="a-job-main">
      <p class="a-job-top">
        <a class="a-job-title" href="/admin/jobs/edit?id=<?= (int) $a['id'] ?>"><?= esc($a['role']) ?></a>
        <span class="a-job-co"><?= esc($a['company']) ?></span>
      </p>
      <p class="a-job-meta">
        <span class="a-pill <?= $statusColors[$a['status']] ?? 'a-pill--draft' ?>"><?= esc($a['status']) ?></span>
        <?php if (str_starts_with((string) ($a['notes'] ?? ''), '[auto]')): ?><span class="a-pill a-pill--auto" title="Filed automatically by jobwatch from the live ATS board">auto</span><?php endif; ?>
        <?php if ($a['comp'] !== ''): ?><span><?= esc($a['comp']) ?></span><?php endif; ?>
        <?php if ($a['remote'] !== ''): ?><span><?= esc($a['remote']) ?></span><?php endif; ?>
        <?php if ($a['status'] === 'found' && trim((string) ($a['letter'] ?? '')) !== ''): ?><span class="a-job-letter-flag">Letter ready</span><?php endif; ?>
        <?php $o = ($opens ?? [])[slugify($a['company'])] ?? null; if ($o && $o['n'] > 0): ?><span class="a-job-opens" title="Tagged link opened; last <?= esc(central_time($o['last'])) ?>">Link opened <?= (int) $o['n'] ?>&times;</span><?php endif; ?>
      </p>
      <?php if ($a['applied_on']): ?><p class="a-job-applied">Applied <?= esc(nice_date($a['applied_on'])) ?></p><?php endif; ?>
    </div>
    <?php if ($a['url'] !== ''): ?>
    <a class="a-job-go" href="<?= esc($a['url']) ?>" target="_blank" rel="noopener">Posting&nbsp;&nearr;</a>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
