<?php /** @var int $season */ /** @var array $seasons */ /** @var array $trades */ ?>
<section class="hero hero--tight">
  <p class="mono-label"><?= count($trades) ?> <?= count($trades) === 1 ? 'trade' : 'trades' ?> in <?= $season ?></p>
  <h1>Trades</h1>
</section>
<nav class="era-tabs" aria-label="Seasons">
  <?php foreach ($seasons as $s): ?><a href="/trades?season=<?= (int) $s ?>"<?= (int) $s === $season ? ' aria-current="page"' : '' ?>><?= (int) $s ?></a><?php endforeach; ?>
</nav>

<?php if (!$trades): ?><p>No trades in <?= $season ?>.</p><?php endif; ?>

<?php foreach ($trades as $t): ?>
<section class="trade">
  <h2 class="trade-title">
    <span><?= implode(' <span class="trade-swap">⇄</span> ', array_map(fn($s) => mfl_team_link($s['franchise_id'], $s['team']['team_name'] ?? 'Team'), $t['sides'])) ?></span>
    <span class="mono-label"><?= esc(central_time($t['completed_at'], 'M j, Y')) ?></span>
  </h2>
  <div class="table-scroll">
    <table class="trade-table">
      <thead><tr><th scope="col">Received</th><th scope="col" class="num">Starts</th><th scope="col" class="num">Points</th><th scope="col" class="num">Record</th></tr></thead>
      <?php foreach ($t['sides'] as $side): ?>
      <tbody>
        <tr class="trade-side">
          <th scope="colgroup" colspan="4">
            <?= esc($side['team']['team_name'] ?? '') ?>
            <span class="row-note"><?= esc(mfl_owner_label($side['team']['owner_name'] ?? null, $side['team']['real_name'] ?? null)) ?></span>
          </th>
        </tr>
        <?php if (!$side['assets']): ?><tr><td colspan="4" class="row-note">Nothing received.</td></tr><?php endif; ?>
        <?php foreach ($side['assets'] as $a): $isPick = !empty($a['is_pick']); ?>
        <tr>
          <td class="wrap">
            <span class="team"><?= esc($a['name']) ?></span><?php if (!$isPick): ?> <span class="row-note"><?= esc($a['position']) ?></span><?php endif; ?>
            <?php if ($a['note'] !== ''): ?><div class="row-note"><?= esc($a['note']) ?></div><?php endif; ?>
            <div class="trade-status trade-status--<?= esc($a['status']['state']) ?>"><?= esc($a['status']['label']) ?></div>
            <?php if ($a['status']['path']): ?><div class="row-note"><?= esc(implode(' → ', $a['status']['path'])) ?></div><?php endif; ?>
          </td>
          <?php if ($isPick): ?><td class="num">—</td><td class="num">—</td><td class="num">—</td>
          <?php else: ?>
          <td class="num"><?= (int) $a['starts'] ?></td>
          <td class="num"><?= number_format($a['points'], 2) ?></td>
          <td class="num"><?= $a['weeks'] ? esc(mfl_record($a)) : '—' ?></td>
          <?php endif; ?>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <?php endforeach; ?>
    </table>
  </div>
</section>
<?php endforeach; ?>

<?php if ($trades): ?>
<p class="note records-note">Starts, points and record cover the player's time on the team that received him, from the first week he was on its roster. Record is the team's record in those weeks, whether or not he started. Games count the regular season and title-path playoffs, and the date is when the trade took effect.</p>
<?php endif; ?>
