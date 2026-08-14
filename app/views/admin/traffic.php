<?php /** @var array $days */ /** @var array $recent */ /** @var array $referrers */ /** @var bool $tableMissing */ ?>
<div class="a-toolbar">
  <h1>Traffic</h1>
</div>

<?php if ($tableMissing): ?>
<p class="a-sub">The visits table doesn't exist yet. Run <code>add-visit-log.sql</code> in phpMyAdmin and this page will light up.</p>
<?php else: ?>

<p class="a-sub">First-party log: no cookies, no IP addresses, no client scripts.
A human visit with a job-board or corporate referrer means someone is actually looking.
Times are US&nbsp;Central.</p>

<h2 class="a-traffic-h">Last 14 days</h2>
<?php if (!$days): ?>
<p class="a-sub">No visits logged yet.</p>
<?php else: ?>
<div class="a-table-scroll">
<table class="a-traffic-table">
  <thead><tr><th>Day</th><th>Humans</th><th>Bots</th><th>You</th></tr></thead>
  <tbody>
    <?php foreach ($days as $d): ?>
    <tr>
      <td><?= esc(nice_date($d['d'])) ?></td>
      <td><strong><?= (int) $d['humans'] ?></strong></td>
      <td class="a-count"><?= (int) $d['bots'] ?></td>
      <td class="a-count"><?= (int) $d['self'] ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php endif; ?>

<h2 class="a-traffic-h">Top outside referrers (30 days)</h2>
<?php if (!$referrers): ?>
<p class="a-sub">No outside referrers yet. Direct visits and same-site clicks don't count here.</p>
<?php else: ?>
<div class="a-table-scroll">
<table class="a-traffic-table">
  <thead><tr><th>Referrer</th><th>Visits</th></tr></thead>
  <tbody>
    <?php foreach ($referrers as $r): ?>
    <tr>
      <td class="a-traffic-ref"><a class="a-count" href="<?= esc($r['referrer']) ?>" target="_blank" rel="noopener noreferrer"><?= esc($r['referrer']) ?></a></td>
      <td><strong><?= (int) $r['n'] ?></strong></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php endif; ?>

<h2 class="a-traffic-h">Recent human visits</h2>
<?php if (!$recent): ?>
<p class="a-sub">Nothing yet.</p>
<?php else: ?>
<div class="a-table-scroll">
<table class="a-traffic-table">
  <thead><tr><th>When</th><th>Page</th><th>Came from</th></tr></thead>
  <tbody>
    <?php foreach ($recent as $v): ?>
    <tr>
      <td class="a-count" style="white-space:nowrap"><?= esc(central_time($v['created_at'])) ?></td>
      <td><?= esc($v['path']) ?></td>
      <td class="a-traffic-ref a-count"><?= $v['referrer'] !== '' ? esc($v['referrer']) : '—' ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php endif; ?>

<?php endif; ?>
