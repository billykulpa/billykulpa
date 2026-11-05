<?php /** @var array $season */ /** @var array $seasons */ /** @var array $rows */ /** @var array $rounds */
$year = (int) $season['season']; ?>
<section class="hero hero--tight">
  <p class="mono-label"><?= $season['status'] === 'complete' ? 'Final' : 'Through week ' . (int) $season['last_scored_week'] ?></p>
  <h1><?= $year ?> Standings</h1>
</section>
<nav class="era-tabs" aria-label="Seasons">
  <?php foreach ($seasons as $s): ?><a href="/standings/<?= (int) $s ?>"<?= (int) $s === $year ? ' aria-current="page"' : '' ?>><?= (int) $s ?></a><?php endforeach; ?>
</nav>
<section>
  <div class="table-scroll">
    <table>
      <thead><tr><th scope="col" class="num">#</th><th scope="col">Team</th><th scope="col" class="wide-only">Owner</th><th scope="col" class="num">Record</th>
        <th scope="col" class="num">PF</th><th scope="col" class="num wide-only">PA</th><th scope="col" class="num wide-only">Finish</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $i => $t): ?>
        <tr<?= $i + 1 === (int) $season['playoff_teams'] ? ' class="cutline"' : '' ?>>
          <td class="num"><?= $i + 1 ?></td>
          <td class="team"><?= mfl_team_link((int) $t['franchise_id'], $t['team_name']) ?></td>
          <td class="wide-only"><?= mfl_owner_link($t['owner_name'], $t['real_name']) ?></td>
          <td class="num"><?= (int) $t['wins'] ?>–<?= (int) $t['losses'] ?><?= (int) $t['ties'] ? '–' . (int) $t['ties'] : '' ?></td>
          <td class="num"><?= number_format((float) $t['points_for'], 2) ?></td>
          <td class="num wide-only"><?= number_format((float) $t['points_against'], 2) ?></td>
          <td class="num wide-only"><?= esc($t['finish']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <p class="note">Ordered by regular-season record, then points for. The blue rule marks the <?= (int) $season['playoff_teams'] ?>-team playoff line.</p>
</section>

<?php if ($rounds): ?>
<section>
  <h2>Playoffs</h2>
  <?php foreach ($rounds as $name => $games): ?>
  <h3 class="sub-head"><?= esc($name) ?></h3>
  <ul class="plain-list">
    <?php foreach ($games as $g):
      $won = $g['result'] === 'W'; ?>
    <li class="game-line">
      <span class="<?= $won ? 'game-winner' : '' ?>"><?= mfl_team_link($g['franchise_id'], $g['team_name']) ?> <?= number_format($g['points'], 2) ?></span>
      <span class="mono-label">vs</span>
      <span class="<?= !$won && $g['result'] ? 'game-winner' : '' ?>"><?= mfl_team_link((int) $g['opponent_franchise_id'], (string) $g['opponent_name']) ?> <?= number_format((float) $g['opponent_points'], 2) ?></span>
    </li>
    <?php endforeach; ?>
  </ul>
  <?php endforeach; ?>
</section>
<?php endif; ?>
