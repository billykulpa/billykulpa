<?php /** @var ?array $flash */ /** @var ?string $updated */ /** @var ?array $season */
/** @var int $seasons */ /** @var int $matchups */ /** @var int $picks */
/** @var array $franchises */ /** @var array $syncs */ ?>
<h1>Midwest Football League</h1>
<?php require __DIR__ . '/_subnav.php'; ?>

<?php if ($flash): ?>
<div class="a-flash a-flash--<?= $flash['status'] === 'ok' ? 'ok' : 'err' ?>"><?= esc($flash['summary']) ?></div>
<?php endif; ?>

<div class="a-toolbar">
  <span class="a-updated">
    <?= $updated ? 'Last updated ' . esc(central_time($updated, 'M j, Y, g:i a')) . ' CT' : 'Not synced yet.' ?>
  </span>
  <form method="post" action="/admin/mfl/sync"><?= csrf_field() ?>
    <button class="a-btn" type="submit">Sync now</button>
  </form>
</div>

<div class="a-cards">
  <div class="a-card"><b><?= $seasons ?></b><span>Seasons</span></div>
  <div class="a-card"><b><?= $matchups ?></b><span>Team-weeks</span></div>
  <div class="a-card"><b><?= $picks ?></b><span>Draft picks</span></div>
</div>

<?php if ($franchises): ?>
<h2 class="a-section-head a-first">Franchises</h2>
<table>
  <thead><tr><th>Team</th><th>Owner</th><th>Since</th></tr></thead>
  <tbody>
  <?php foreach ($franchises as $f): ?>
    <tr>
      <td><?= esc($f['name']) ?></td>
      <td><?= esc(mfl_owner_label($f['owner_name'], $f['real_name'])) ?></td>
      <td><?= $f['start_season'] ? (int) $f['start_season'] . ((int) $f['start_week'] > 1 ? ', week ' . (int) $f['start_week'] : '') : '—' ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>

<?php if ($syncs): ?>
<h2 class="a-section-head">Sync history</h2>
<table>
  <thead><tr><th>When (CT)</th><th>Source</th><th>Result</th></tr></thead>
  <tbody>
  <?php foreach ($syncs as $s): ?>
    <tr>
      <td><?= esc(central_time($s['started_at'])) ?></td>
      <td><?= $s['trigger_source'] === 'cron' ? 'Daily' : esc($s['display_name'] ?: ($s['email'] ?? 'Sync now')) ?></td>
      <td><?= esc($s['summary'] ?: ucfirst($s['status'])) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>
