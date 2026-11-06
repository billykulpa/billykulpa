<?php /** @var array $tableRows */ /** @var array $f */ /** @var bool $showTeam */ /** @var bool $byPlayer */ ?>
<div class="table-scroll">
  <table class="pickups">
    <thead><tr>
      <th scope="col" class="num">#</th>
      <?= mfl_pickup_th($f, 'name', 'Player', 'sticky') ?>
      <th scope="col">Pos</th>
      <?php if ($showTeam): ?><th scope="col">Picked up by</th><?php endif; ?>
      <?= $byPlayer ? mfl_pickup_th($f, 'pickups', 'Pickups', 'num') : mfl_pickup_th($f, 'added_on', 'Added') ?>
      <?= mfl_pickup_th($f, 'starts', 'Started', 'num') ?>
      <?= mfl_pickup_th($f, 'total_points', 'Total points', 'num') ?>
      <?= mfl_pickup_th($f, 'roster_points', 'On roster', 'num') ?>
      <?= mfl_pickup_th($f, 'points', 'As starter', 'num') ?>
      <?= mfl_pickup_th($f, 'per_start', 'Per start', 'num') ?>
    </tr></thead>
    <tbody>
    <?php foreach ($tableRows as $i => $r): ?>
      <tr>
        <td class="num"><?= $i + 1 ?></td>
        <td class="wrap sticky">
          <span class="team"><?= mfl_player_link($r['player_id'], $r['name']) ?></span>
          <?php if (!$byPlayer): ?><div class="row-note"><?= esc(mfl_pickup_note($r)) ?></div><?php endif; ?>
        </td>
        <td><?= esc($r['position']) ?></td>
        <?php if ($showTeam): ?>
        <td class="wrap">
          <?php if ($byPlayer): ?><?= implode(', ', array_map(fn($id, $name) => mfl_team_link((int) $id, $name), array_keys($r['teams']), $r['teams'])) ?>
          <?php else: ?><?= mfl_team_link($r['franchise_id'], $r['team_name']) ?><?php endif; ?>
        </td>
        <?php endif; ?>
        <?php if ($byPlayer): ?><td class="num"><?= (int) $r['pickups'] ?></td>
        <?php else: ?><td><?= esc($r['added_label']) ?></td><?php endif; ?>
        <td class="num"><?= (int) $r['starts'] ?></td>
        <td class="num"><?= number_format($r['total_points'], 2) ?></td>
        <td class="num"><?= number_format($r['roster_points'], 2) ?></td>
        <td class="num"><?= number_format($r['points'], 2) ?></td>
        <td class="num"><?= $r['starts'] ? number_format($r['per_start'], 1) : '—' ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
