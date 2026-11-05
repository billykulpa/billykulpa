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
<section class="trade" id="trade-<?= esc($t['id']) ?>">
  <h2 class="trade-title">
    <span><?= implode(' <span class="trade-swap">⇄</span> ', array_map(fn($s) => mfl_team_link($s['franchise_id'], $s['team']['team_name'] ?? 'Team'), $t['sides'])) ?></span>
    <span class="mono-label"><?= esc(central_time($t['completed_at'], 'M j, Y')) ?> · <?= esc($t['week_label']) ?></span>
  </h2>
  <div class="table-scroll">
    <table class="trade-table">
      <thead>
        <tr class="trade-groups">
          <th scope="col" class="sticky" rowspan="2">Received</th>
          <th scope="colgroup" colspan="5" class="group-start">Season of trade</th>
          <th scope="colgroup" colspan="5" class="group-start">All-time</th>
        </tr>
        <tr>
          <?php foreach ([0, 1] as $group): ?>
          <th scope="col" class="num group-start">Total points</th><th scope="col" class="num">On roster</th><th scope="col" class="num">As starter</th><th scope="col" class="num">Starts</th><th scope="col" class="num">Record</th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <?php foreach ($t['sides'] as $side): ?>
      <tbody>
        <tr class="trade-side">
          <th scope="colgroup" colspan="11">
            <span class="trade-side-label">
              <?= esc($side['team']['team_name'] ?? '') ?>
              <span class="row-note"><?= esc(mfl_owner_label($side['team']['owner_name'] ?? null, $side['team']['real_name'] ?? null)) ?></span>
            </span>
          </th>
        </tr>
        <?php if (!$side['assets']): ?><tr><td colspan="11" class="row-note">Nothing received.</td></tr><?php endif; ?>
        <?php foreach ($side['assets'] as $a): $isPick = !empty($a['is_pick']); ?>
        <tr>
          <td class="wrap sticky">
            <span class="team"><?= $isPick ? esc($a['name']) : mfl_player_link($a['player_id'], $a['name']) ?></span><?php if (!$isPick): ?> <span class="row-note"><?= esc($a['position']) ?></span><?php endif; ?>
            <?php if ($a['note'] !== ''): ?><div class="row-note"><?= esc($a['note']) ?></div><?php endif; ?>
            <div class="trade-status trade-status--<?= esc($a['status']['state']) ?>"><?= esc($a['status']['label']) ?></div>
            <?php if ($a['status']['path']): ?><div class="row-note"><?= implode(' → ', array_map(fn($step) => $step['href']
              ? '<a class="link" href="' . esc($step['href']) . '">' . esc($step['text']) . '</a>' : esc($step['text']), $a['status']['path'])) ?></div><?php endif; ?>
          </td>
          <?php foreach (['season', 'all'] as $set): $n = $a[$set]; ?>
          <?php if (!$n['weeks']): ?>
          <td class="num group-start">—</td><td class="num">—</td><td class="num">—</td><td class="num">—</td><td class="num">—</td>
          <?php else: ?>
          <td class="num group-start"><?= number_format($n['total_points'], 2) ?></td>
          <td class="num"><?= number_format($n['roster_points'], 2) ?></td>
          <td class="num"><?= number_format($n['points'], 2) ?></td>
          <td class="num"><?= (int) $n['starts'] ?></td>
          <td class="num"><?= esc(mfl_record($n)) ?></td>
          <?php endif; ?>
          <?php endforeach; ?>
        </tr>
        <?php endforeach; ?>
        <?php if (count($side['assets']) > 1): ?>
        <tr class="trade-total">
          <td class="sticky">Team total</td>
          <?php foreach (['season', 'all'] as $set): $n = $side[$set]; ?>
          <td class="num group-start"><?= number_format($n['total_points'], 2) ?></td>
          <td class="num"><?= number_format($n['roster_points'], 2) ?></td>
          <td class="num"><?= number_format($n['points'], 2) ?></td>
          <td class="num"><?= (int) $n['starts'] ?></td>
          <td class="num">—</td>
          <?php endforeach; ?>
        </tr>
        <?php endif; ?>
      </tbody>
      <?php endforeach; ?>
    </table>
  </div>
</section>
<?php endforeach; ?>

<?php if ($trades): ?>
<p class="note records-note">Season of trade covers the season the trade happened. All-time keeps counting for as long as this team holds the player, so a dynasty trade can change hands in the numbers years later. Total points is everything he scored in those seasons, for any team or as a free agent. On roster counts his time on this team, bench included, and As starter counts only its starting lineup. Those two, plus Starts and Record, run from the first week he was on the team's roster and count the regular season and title-path playoffs. Record is the team's record in those weeks, whether or not he started. The date is when the trade took effect.</p>
<?php endif; ?>
