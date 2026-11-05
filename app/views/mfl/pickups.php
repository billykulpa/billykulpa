<?php /** @var array $rows */ /** @var array $f */ /** @var array $seasons */ /** @var array $teams */
$byPlayer = $f['view'] === 'players';
$changed = mfl_pickup_url($f) !== mfl_pickup_url(['season' => $f['season'], 'view' => $f['view'], 'pos' => MFL_PICKUP_DEFAULT_POSITIONS, 'from' => '', 'to' => '', 'team' => 0, 'type' => '', 'sort' => 'points', 'dir' => 'desc']); ?>
<section class="hero hero--tight">
  <p class="mono-label"><?= (int) $f['season'] ?></p>
  <h1>Best Pickups</h1>
</section>

<nav class="era-tabs" aria-label="How to group the table">
  <a href="<?= esc(mfl_pickup_url($f, ['view' => 'pickups'])) ?>"<?= !$byPlayer ? ' aria-current="page"' : '' ?>>Each pickup</a>
  <a href="<?= esc(mfl_pickup_url($f, ['view' => 'players'])) ?>"<?= $byPlayer ? ' aria-current="page"' : '' ?>>By player</a>
</nav>

<form class="filters" method="get" action="/pickups" data-autosubmit>
  <?php if ($byPlayer): ?><input type="hidden" name="view" value="players"><?php endif; ?>
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
      <select id="f-season" name="season"><?php foreach ($seasons as $s): ?><option value="<?= (int) $s ?>"<?= (int) $s === $f['season'] ? ' selected' : '' ?>><?= (int) $s ?></option><?php endforeach; ?></select></div>
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
    <button class="poll-vote poll-vote--small" type="submit">Apply</button>
    <?php if ($changed): ?><a class="link filter-clear" href="<?= esc(mfl_pickup_url(['season' => $f['season'], 'view' => $f['view'], 'pos' => MFL_PICKUP_DEFAULT_POSITIONS, 'from' => '', 'to' => '', 'team' => 0, 'type' => '', 'sort' => 'points', 'dir' => 'desc'])) ?>">Clear</a><?php endif; ?>
  </div>
</form>

<?php if ($rows): ?>
<section>
  <div class="table-scroll">
    <table class="pickups">
      <thead><tr>
        <th scope="col" class="num">#</th>
        <?= mfl_pickup_th($f, 'name', 'Player') ?>
        <th scope="col">Pos</th>
        <th scope="col" class="wide-only">Picked up by</th>
        <?= $byPlayer ? mfl_pickup_th($f, 'pickups', 'Pickups', 'num wide-only') : mfl_pickup_th($f, 'added_on', 'Added', 'wide-only') ?>
        <?= mfl_pickup_th($f, 'starts', 'Started', 'num wide-only') ?>
        <?= mfl_pickup_th($f, 'points', 'Points', 'num') ?>
        <?= mfl_pickup_th($f, 'per_start', 'Per start', 'num') ?>
      </tr></thead>
      <tbody>
      <?php foreach ($rows as $i => $r): ?>
        <tr>
          <td class="num"><?= $i + 1 ?></td>
          <td class="wrap">
            <span class="team"><?= esc($r['name']) ?></span>
            <?php if (!$byPlayer): ?><div class="row-note"><?= esc(mfl_pickup_note($r)) ?></div><?php endif; ?>
            <div class="row-note narrow-only">
              <?= $byPlayer ? esc(implode(', ', $r['teams'])) . ' · ' . (int) $r['pickups'] . ' ' . ((int) $r['pickups'] === 1 ? 'pickup' : 'pickups') : esc($r['team_name']) . ' · ' . esc(central_time($r['added_at'], 'M j')) ?>
              · <?= (int) $r['starts'] ?> started
            </div>
          </td>
          <td><?= esc($r['position']) ?></td>
          <td class="wide-only wrap">
            <?php if ($byPlayer): ?><?= implode(', ', array_map(fn($id, $name) => mfl_team_link((int) $id, $name), array_keys($r['teams']), $r['teams'])) ?>
            <?php else: ?><?= mfl_team_link($r['franchise_id'], $r['team_name']) ?><?php endif; ?>
          </td>
          <?php if ($byPlayer): ?><td class="num wide-only"><?= (int) $r['pickups'] ?></td>
          <?php else: ?><td class="wide-only"><?= esc(central_time($r['added_at'], 'M j')) ?></td><?php endif; ?>
          <td class="num wide-only"><?= (int) $r['starts'] ?></td>
          <td class="num"><?= number_format($r['points'], 2) ?></td>
          <td class="num"><?= number_format($r['per_start'], 1) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <p class="note">Regular-season points in the starting lineup of the team that made the pickup, from the week of the add. Bench points don't count, and pickups that never started aren't listed. Dates filter on when the player was added. <?= $byPlayer ? 'By player adds up every pickup of that player, across teams.' : 'A player dropped and picked up again gets a row for each pickup, and no game counts twice.' ?></p>
</section>
<?php else: ?>
<p>No pickups match those filters.</p>
<?php endif; ?>
