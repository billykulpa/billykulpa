<?php /** @var array $rows */ /** @var array $owners */ /** @var array $f */ /** @var array $seasons */ /** @var array $teams */
$byPlayer = $f['view'] === 'players';
$byOwner = $f['view'] === 'owners';
$allTime = $f['season'] === 'all';
$teamNames = array_column(mfl_franchises(), 'name', 'id');
$changed = mfl_pickup_url($f) !== mfl_pickup_url(['season' => $f['season'], 'view' => $f['view'], 'pos' => MFL_PICKUP_DEFAULT_POSITIONS, 'from' => '', 'to' => '', 'team' => 0, 'type' => '', 'unstarted' => false, 'sort' => 'points', 'dir' => 'desc']); ?>
<section class="hero hero--tight">
  <p class="mono-label"><?= $allTime ? 'All-time' : (int) $f['season'] ?></p>
  <h1>Best Pickups</h1>
</section>

<nav class="era-tabs" aria-label="How to group the table" data-swap>
  <a href="<?= esc(mfl_pickup_url($f, ['view' => 'pickups'])) ?>"<?= $f['view'] === 'pickups' ? ' aria-current="page"' : '' ?>>Each pickup</a>
  <a href="<?= esc(mfl_pickup_url($f, ['view' => 'players'])) ?>"<?= $byPlayer ? ' aria-current="page"' : '' ?>>By player</a>
  <a href="<?= esc(mfl_pickup_url($f, ['view' => 'owners', 'season' => 'all'])) ?>"<?= $byOwner ? ' aria-current="page"' : '' ?>>By owner</a>
</nav>

<form class="filters" method="get" action="/pickups" data-autosubmit>
  <?php if ($f['view'] !== 'pickups'): ?><input type="hidden" name="view" value="<?= esc($f['view']) ?>"><?php endif; ?>
  <input type="hidden" name="sort" value="<?= esc($f['sort']) ?>">
  <input type="hidden" name="dir" value="<?= esc($f['dir']) ?>">
  <fieldset class="pill-group">
    <legend class="mono-label">Position</legend>
    <?php foreach (MFL_PICKUP_POSITIONS as $p): ?>
    <label class="pill"><input type="checkbox" name="pos[]" value="<?= esc($p) ?>"<?= in_array($p, $f['pos'], true) ? ' checked' : '' ?>><span><?= esc($p) ?></span></label>
    <?php endforeach; ?>
  </fieldset>
  <div class="filter-row">
    <div><label class="mono-label" for="f-season">Season</label>
      <select id="f-season" name="season"><option value="all"<?= $allTime ? ' selected' : '' ?>>All-Time</option><?php foreach ($seasons as $s): ?><option value="<?= (int) $s ?>"<?= (int) $s === $f['season'] ? ' selected' : '' ?>><?= (int) $s ?></option><?php endforeach; ?></select></div>
    <div><label class="mono-label" for="f-from">Added from</label><input type="date" id="f-from" name="from" value="<?= esc($f['from']) ?>"></div>
    <div><label class="mono-label" for="f-to">Added to</label><input type="date" id="f-to" name="to" value="<?= esc($f['to']) ?>"></div>
    <div><label class="mono-label" for="f-team">Team</label>
      <select id="f-team" name="team"><option value="">All teams</option><?php foreach ($teams as $t): ?><option value="<?= (int) $t['franchise_id'] ?>"<?= (int) $t['franchise_id'] === $f['team'] ? ' selected' : '' ?>><?= esc($t['team_name']) ?></option><?php endforeach; ?></select></div>
    <div><label class="mono-label" for="f-type">Type</label>
      <select id="f-type" name="type">
        <option value="">All adds</option>
        <option value="waiver"<?= $f['type'] === 'waiver' ? ' selected' : '' ?>>Waiver claims</option>
        <option value="free_agent"<?= $f['type'] === 'free_agent' ? ' selected' : '' ?>>Free agents</option>
      </select></div>
    <label class="filter-check"><input type="checkbox" name="unstarted" value="1"<?= $f['unstarted'] ? ' checked' : '' ?>> Include never started</label>
    <button class="poll-vote poll-vote--small" type="submit">Apply</button>
    <?php if ($changed): ?><a class="link filter-clear" href="<?= esc(mfl_pickup_url(['season' => $f['season'], 'view' => $f['view'], 'pos' => MFL_PICKUP_DEFAULT_POSITIONS, 'from' => '', 'to' => '', 'team' => 0, 'type' => '', 'unstarted' => false, 'sort' => 'points', 'dir' => 'desc'])) ?>">Clear</a><?php endif; ?>
  </div>
</form>

<?php if ($byOwner): ?>
  <?php foreach ($owners as $o): ?>
  <section class="owner-pickups">
    <h2 class="trade-title">
      <span><?= mfl_team_link((int) $o['franchise_id'], $teamNames[$o['franchise_id']] ?? '') ?></span>
      <span class="mono-label"><?= (int) $o['count'] ?> <?= (int) $o['count'] === 1 ? 'pickup' : 'pickups' ?> · <?= number_format($o['points'], 2) ?> as starter</span>
    </h2>
    <p class="row-note owner-line"><?= mfl_owner_link($o['owner_name'], $o['real_name']) ?></p>
    <?php $tableRows = $o['rows']; $showTeam = false; require __DIR__ . '/_pickups-table.php'; ?>
    <?php if ($o['count'] > count($o['rows'])): ?>
    <p class="note"><a class="link" href="<?= esc(mfl_pickup_url($f, ['view' => 'pickups', 'team' => (int) $o['franchise_id']])) ?>">See all <?= (int) $o['count'] ?></a></p>
    <?php endif; ?>
  </section>
  <?php endforeach; ?>
  <?php if (!$owners): ?><p>No pickups match those filters.</p><?php endif; ?>
<?php elseif ($rows): ?>
<section>
  <?php $tableRows = $rows; $showTeam = true; require __DIR__ . '/_pickups-table.php'; ?>
</section>
<?php else: ?>
<p>No pickups match those filters.</p>
<?php endif; ?>

<p class="note records-note">Regular season only. Total points is the player's whole season, on any team or as a free agent. On roster is what he scored for the team that picked him up, bench included, and As starter is only what it played. Both count from the week of the add. Dates filter on when the player was added.<?php if ($allTime): ?> All-time follows a pickup into later seasons for as long as the team keeps him, and Total points covers those seasons.<?php endif; ?> <?= $byPlayer ? 'By player adds up every pickup of that player, across teams.' : ($byOwner ? 'By owner shows each owner\'s top five, credited to whoever held the team when the player was added.' : 'A player dropped and picked up again gets a row for each pickup, and no game counts twice.') ?></p>
