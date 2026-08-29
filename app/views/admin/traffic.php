<?php /** @var array $days @var array $entries @var array $paths @var array $refs @var array $vias @var array $viaApps @var array $recent @var bool $isMe @var bool $v2 @var bool $tableMissing @var bool $meOk */
$fmtDur = function (int $s): string {
  if ($s < 60) return $s . 's';
  return intdiv($s, 60) . 'm ' . ($s % 60) . 's';
};
/* Inline icons: one stroke weight, square corners, drawn in the page ink. */
$icoBeacon = '<svg class="a-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="butt" stroke-linejoin="miter" aria-label="verified" role="img">'
  . '<path d="M9.3 9 V5.9 L12 3.6 L14.7 5.9 V9"/>'
  . '<path d="M8.2 9 H15.8"/>'
  . '<path d="M9.9 9 L7.7 20 M14.1 9 L16.3 20"/>'
  . '<path d="M6.3 20 H17.7"/>'
  . '<path d="M12 20 V16.3"/>'
  . '<path d="M5.6 5.8 L3.2 4.5 M5.6 10.6 L3.2 11.9 M18.4 5.8 L20.8 4.5 M18.4 10.6 L20.8 11.9"/>'
  . '</svg>';
$icoPhone = '<svg class="a-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="butt" stroke-linejoin="miter" aria-label="mobile" role="img">'
  . '<rect x="7" y="3" width="10" height="18"/>'
  . '<path d="M10.2 17.6 H13.8"/>'
  . '</svg>';
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
<p class="a-sub">Each application gets its own link, <code>billykulpa.com?via=tag</code>, on the resume and in the form; the tag is recorded on the application (its Referral tag field), so two roles at one company stay tellable-apart. When a link is opened, it shows up here and on the application's row in the tracker.</p>
<?php if (!$vias): ?>
<p class="a-sub">No tagged links opened yet.</p>
<?php else: ?>
<div class="a-table-scroll">
<table class="a-traffic-table">
  <thead><tr><th class="c-key">Tag</th><th class="c-num">Sessions</th><th class="c-num">Verified</th><th class="c-when">Last seen</th><th class="c-text">Pages read</th></tr></thead>
  <tbody>
    <?php foreach ($vias as $v): ?>
    <tr>
      <td class="c-key"><strong><?= esc($v['via']) ?></strong>
        <?php foreach (($viaApps ?? [])[$v['via']] ?? [] as $applabel): ?>
        <span class="a-via-app"><?= esc($applabel) ?></span>
        <?php endforeach; ?></td>
      <td class="c-num"><strong><?= (int) $v['sessions'] ?></strong></td>
      <td class="c-num a-count"><?= (int) $v['verified'] ?></td>
      <td class="c-when a-count"><?= esc(central_time($v['last'])) ?></td>
      <td class="c-text a-traffic-ref"><?= esc(implode(', ', array_keys($v['pages']))) ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php endif; ?>

<h2 class="a-traffic-h">Recent sessions (last three days)</h2>
<?php if (!$recent): ?>
<p class="a-sub">No human sessions today, yesterday, or the day before.</p>
<?php else: ?>
<div class="a-table-scroll">
<table class="a-traffic-table">
  <thead><tr><th class="c-when">When</th><th class="c-text">Pages</th><th class="c-num">Time</th><th class="c-key">Came from</th><th class="c-num"></th></tr></thead>
  <tbody>
    <?php foreach ($recent as $s): ?>
    <tr>
      <td class="c-when a-count"><?= esc(central_time($s['start'])) ?></td>
      <td class="c-text a-traffic-ref"><?= esc(implode(' → ', $s['pages'])) ?></td>
      <td class="c-num a-count"><?= $s['seconds'] > 0 ? esc($fmtDur($s['seconds'])) : '—' ?></td>
      <td class="c-key a-traffic-ref"><?= $s['via'] !== '' ? '<strong>via ' . esc($s['via']) . '</strong>' : ($s['referrer'] !== '' ? esc(preg_replace('/^(www|m|l|lm)\./', '', parse_url($s['referrer'], PHP_URL_HOST) ?: $s['referrer'])) : '<span class="a-count">direct</span>') ?></td>
      <td class="c-num a-count c-icons"><?= $s['verified'] ? $icoBeacon : '' ?><?= $s['mobile'] ? $icoPhone : '' ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>
<p class="a-sub" style="margin-top:12px"><?= $icoBeacon ?> verified by the beacon &middot; <?= $icoPhone ?> mobile</p>
<?php endif; ?>

<h2 class="a-traffic-h">Last 14 days</h2>
<?php if (!$days): ?>
<p class="a-sub">No sessions logged yet.</p>
<?php else: ?>
<div class="a-table-scroll">
<table class="a-traffic-table">
  <thead><tr><th class="c-key">Day</th><th class="c-num">Sessions</th><th class="c-num">Verified</th><th class="c-num">Pages / sess.</th><th class="c-num">Mobile</th><th class="c-num">Bots</th><th class="c-num">You</th></tr></thead>
  <tbody>
    <?php foreach ($days as $d): ?>
    <tr>
      <td class="c-key"><?= esc(nice_date($d['d'])) ?></td>
      <td class="c-num"><strong><?= (int) $d['sessions'] ?></strong></td>
      <td class="c-num a-count"><?= (int) $d['verified'] ?></td>
      <td class="c-num a-count"><?= $d['sessions'] ? number_format($d['pages'] / $d['sessions'], 1) : '—' ?></td>
      <td class="c-num a-count"><?= (int) $d['mobile'] ?></td>
      <td class="c-num a-count"><?= (int) $d['bots'] ?></td>
      <td class="c-num a-count"><?= (int) $d['self'] ?></td>
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
    <div class="a-table-scroll"><table class="a-traffic-table a-traffic-table--narrow">
      <tbody>
        <?php foreach ($entries as $p => $n): ?>
        <tr><td class="c-text a-traffic-ref"><?= esc($p) ?></td><td class="c-num"><strong><?= (int) $n ?></strong></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table></div>
    <?php endif; ?>
  </div>
  <div>
    <h2 class="a-traffic-h">Outside referrers (30 days)</h2>
    <?php if (!$refs): ?><p class="a-sub">No outside referrers yet.</p><?php else: ?>
    <div class="a-table-scroll"><table class="a-traffic-table a-traffic-table--narrow">
      <tbody>
        <?php foreach ($refs as $h => $n): ?>
        <tr><td class="c-text a-traffic-ref"><?= esc($h) ?></td><td class="c-num"><strong><?= (int) $n ?></strong></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table></div>
    <?php endif; ?>
  </div>
</div>

<h2 class="a-traffic-h">Paths through the site (30 days, 2+ pages)</h2>
<?php if (!$paths): ?><p class="a-sub">No multi-page sessions yet.</p><?php else: ?>
<div class="a-table-scroll">
<table class="a-traffic-table">
  <tbody>
    <?php foreach ($paths as $p => $n): ?>
    <tr><td class="c-text a-traffic-ref"><?= esc($p) ?></td><td class="c-num"><strong><?= (int) $n ?></strong></td></tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php endif; ?>

<?php endif; ?>
