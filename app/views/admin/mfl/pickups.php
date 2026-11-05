<?php /** @var array $rows */ /** @var array $seasons */ /** @var int $season */ /** @var string $position */ /** @var bool $afterWeekOne */ /** @var string $sort */ /** @var ?string $updated */ ?>
<h1>Best Pickups</h1>
<?php require __DIR__ . '/_subnav.php'; ?>

<form method="get" action="/admin/mfl/pickups" class="a-inline-fields a-report-filters">
  <div>
    <label class="a-label" for="season">Season</label>
    <select id="season" name="season">
      <?php foreach ($seasons as $s): ?><option value="<?= (int) $s ?>"<?= (int) $s === $season ? ' selected' : '' ?>><?= (int) $s ?></option><?php endforeach; ?>
    </select>
  </div>
  <div>
    <label class="a-label" for="position">Position</label>
    <select id="position" name="position">
      <?php foreach (MFL_PICKUP_POSITIONS as $value => $label): ?><option value="<?= esc($value) ?>"<?= $value === $position ? ' selected' : '' ?>><?= esc($label) ?></option><?php endforeach; ?>
    </select>
  </div>
  <div>
    <label class="a-label" for="sort">Sort by</label>
    <select id="sort" name="sort">
      <?php foreach (MFL_PICKUP_SORTS as $value => $label): ?><option value="<?= esc($value) ?>"<?= $value === $sort ? ' selected' : '' ?>><?= esc($label) ?></option><?php endforeach; ?>
    </select>
  </div>
  <label class="a-check"><input type="checkbox" name="after_week_1" value="1"<?= $afterWeekOne ? ' checked' : '' ?>> Added after week 1</label>
  <button class="a-btn" type="submit">Update</button>
</form>

<p class="a-hint">Regular-season points in the starting lineup of the team that added the player, from the week of the add. Bench points don't count. Adds that were never started aren't listed. <?= $updated ? 'Data from ' . esc(central_time($updated, 'M j, g:i a')) . ' CT.' : '' ?></p>

<?php if ($rows): ?>
<div class="a-table-scroll">
  <table class="a-report">
    <thead><tr><th class="a-num">#</th><th>Player</th><th>Pos</th><th>Picked up by</th><th>Added</th><th class="a-num">Started</th><th class="a-num">Points</th><th class="a-num">Per start</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $i => $r): ?>
      <tr>
        <td class="a-num"><?= $i + 1 ?></td>
        <td>
          <b><?= esc($r['name']) ?></b>
          <div class="a-hint"><?= $r['type'] === 'waiver' ? 'Waiver claim' : 'Free agent' ?><?= $r['on_roster'] ? '' : ' · No longer on this roster' ?></div>
        </td>
        <td><?= esc($r['position']) ?></td>
        <td><?= esc($r['team_name']) ?></td>
        <td><?= esc(central_time($r['added_at'], 'M j')) ?></td>
        <td class="a-num"><?= (int) $r['starts'] ?></td>
        <td class="a-num"><?= number_format($r['points'], 2) ?></td>
        <td class="a-num"><?= number_format($r['per_start'], 1) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php else: ?>
<p class="a-hint">No pickups match those filters.</p>
<?php endif; ?>
