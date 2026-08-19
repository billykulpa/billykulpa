<?php /** @var array $days @var array $entries @var array $paths @var array $refs @var array $vias @var array $recent @var bool $isMe @var bool $v2 @var bool $tableMissing @var bool $meOk */
$fmtDur = function (int $s): string {
  if ($s < 60) return $s . 's';
  return intdiv($s, 60) . 'm ' . ($s % 60) . 's';
};
?>
<div class="a-toolbar">
  <h1>Traffic</h1>
  <?php if (!$isMe): ?>
  <form method="post" action="/admin/traffic">
    <?= csrf_field() ?><input type="hidden" name="me" value="1">
    <button class="a-btn a-btn--ghost" type="submit" title="Sets a one-year cookie in this browser so it is never counted as a visitor">This device is me</button>
  </form>
  <?php else: ?>
  <span class="a-count">This device is excluded</span>
  <?php endif; ?>
</div>

<?php if ($meOk): ?><div class="a-flash a-flash--ok">This browser is now excluded from the counts.</div><?php endif; ?>

<?php if ($tableMissing): ?>
<p class="a-sub">The visits table doesn't exist yet. Run <code>add-visit-log.sql</code>, then <code>add-visit-vhash.sql</code> and <code>add-visit-v2.sql</code>, in phpMyAdmin.</p>
<?php else: ?>

<?php if (!$v2): ?><div class="a-flash a-flash--err">Tagged links and verified sessions need <code>add-visit-v2.sql</code> run once in phpMyAdmin. Everything else here works now.</div><?php endif; ?>

<p class="a-sub">Sessions, not hits: one visitor's page views within 30 minutes count once.
<strong>Verified</strong> sessions ran the page's beacon, which scrapers don't; treat Sessions as the ceiling and Verified as the floor.
Bots and your own devices are excluded from every number except their own columns. Times are US&nbsp;Central.</p>

<h2 class="a-traffic-h">Applications: who opened the link</h2>
<p class="a-sub">Each application gets its own link, <code>billykulpa.com/?via=company</code>, on the resume and in the form. When it's opened, it shows up here and on the application's row in the tracker.</p>
<?php if (!$vias): ?>
<p class="a-sub">No tagged links opened yet.</p>
<?php else: ?>
<div class="a-table-scroll">
<table class="a-traffic-table">
  <thead><tr><th>Tag</th><th>Sessions</th><th>Verified</th><th>Last seen</th><th>Pages read</th></tr></thead>
  <tbody>
    <?php foreach ($vias as $v): ?>
    <tr>
      <td><strong><?= esc($v['via']) ?></strong></td>
      <td><?= (int) $v['sessions'] ?></td>
      <td class="a-count"><?= (int) $v['verified'] ?></td>
      <td class="a-count"><?= esc(central_time($v['last'])) ?></td>
      <td class="a-traffic-ref"><?= esc(implode(', ', array_keys($v['pages']))) ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php endif; ?>

<h2 class="a-traffic-h">Last 14 days</h2>
<?php if (!$days): ?>
<p class="a-sub">No sessions logged yet.</p>
<?php else: ?>
<div class="a-table-scroll">
<table class="a-traffic-table">
  <thead><tr><th>Day</th><th>Sessions</th><th>Verified</th><th>Pages / session</th><th>Mobile</th><th>Bots</th><th>You</th></tr></thead>
  <tbody>
    <?php foreach ($days as $d): ?>
    <tr>
      <td><?= esc(nice_date($d['d'])) ?></td>
      <td><strong><?= (int) $d['sessions'] ?></strong></td>
      <td class="a-count"><?= (int) $d['verified'] ?></td>
      <td class="a-count"><?= $d['sessions'] ? number_format($d['pages'] / $d['sessions'], 1) : '—' ?></td>
      <td class="a-count"><?= (int) $d['mobile'] ?></td>
      <td class="a-count"><?= (int) $d['bots'] ?></td>
      <td class="a-count"><?= (int) $d['self'] ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php endif; ?>

<div class="a-grid-2">
  <div>
    <h2 class="a-traffic-h">Where sessions start (30 days)</h2>
    <?php if (!$entries): ?><p class="a-sub">Nothing yet.</p><?php else: ?>
    <table class="a-traffic-table">
      <tbody>
        <?php foreach ($entries as $p => $n): ?>
        <tr><td class="a-traffic-ref"><?= esc($p) ?></td><td><strong><?= (int) $n ?></strong></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
  <div>
    <h2 class="a-traffic-h">Outside referrers (30 days)</h2>
    <?php if (!$refs): ?><p class="a-sub">No outside referrers yet.</p><?php else: ?>
    <table class="a-traffic-table">
      <tbody>
        <?php foreach ($refs as $h => $n): ?>
        <tr><td class="a-traffic-ref"><?= esc($h) ?></td><td><strong><?= (int) $n ?></strong></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<h2 class="a-traffic-h">Paths through the site (30 days, 2+ pages)</h2>
<?php if (!$paths): ?><p class="a-sub">No multi-page sessions yet.</p><?php else: ?>
<div class="a-table-scroll">
<table class="a-traffic-table">
  <tbody>
    <?php foreach ($paths as $p => $n): ?>
    <tr><td class="a-traffic-ref"><?= esc($p) ?></td><td><strong><?= (int) $n ?></strong></td></tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php endif; ?>

<h2 class="a-traffic-h">Recent sessions</h2>
<?php if (!$recent): ?>
<p class="a-sub">Nothing yet.</p>
<?php else: ?>
<div class="a-table-scroll">
<table class="a-traffic-table">
  <thead><tr><th>When</th><th>Pages</th><th>Time</th><th>Came from</th><th></th></tr></thead>
  <tbody>
    <?php foreach ($recent as $s): ?>
    <tr>
      <td class="a-count"><?= esc(central_time($s['start'])) ?></td>
      <td class="a-traffic-ref"><?= esc(implode(' → ', $s['pages'])) ?></td>
      <td class="a-count"><?= $s['count'] > 1 ? esc($fmtDur($s['seconds'])) : '—' ?></td>
      <td class="a-traffic-ref"><?= $s['via'] !== '' ? '<strong>via ' . esc($s['via']) . '</strong>' : ($s['referrer'] !== '' ? esc(preg_replace('/^(www|m|l|lm)\./', '', parse_url($s['referrer'], PHP_URL_HOST) ?: $s['referrer'])) : '<span class="a-count">direct</span>') ?></td>
      <td class="a-count"><?= $s['verified'] ? '✓' : '' ?><?= $s['mobile'] ? ' ☎' : '' ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>
<p class="a-sub" style="margin-top:12px">✓ verified by the beacon &middot; ☎ mobile</p>
<?php endif; ?>

<?php endif; ?>
