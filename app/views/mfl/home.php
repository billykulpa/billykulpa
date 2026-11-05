<?php /** @var ?array $season */ /** @var array $standings */ /** @var array $champions */ ?>
<section class="hero">
  <p class="mono-label"><?= $season ? (int) $season['num_teams'] . ' teams · Dynasty' : 'Dynasty' ?></p>
  <h1>Midwest Football League</h1>
</section>

<?php if ($season): ?>
<section>
  <h2>
    <?= (int) $season['season'] ?> standings
    <?php if ($season['status'] !== 'complete' && (int) $season['last_scored_week'] > 0): ?>
    <span class="mono-label">Through week <?= (int) $season['last_scored_week'] ?></span>
    <?php endif; ?>
  </h2>
  <div class="table-scroll">
    <table>
      <thead>
        <tr><th scope="col" class="num">#</th><th scope="col">Team</th><th scope="col" class="wide-only">Owner</th>
            <th scope="col" class="num">Record</th><th scope="col" class="num">PF</th><th scope="col" class="num wide-only">PA</th></tr>
      </thead>
      <tbody>
      <?php foreach ($standings as $i => $t): ?>
        <tr<?= $i + 1 === (int) $season['playoff_teams'] ? ' class="cutline"' : '' ?>>
          <td class="num"><?= $i + 1 ?></td>
          <td class="team"><?= esc($t['team_name']) ?></td>
          <td class="wide-only"><?= esc($t['owner_name'] ?? 'Vacant') ?></td>
          <td class="num"><?= (int) $t['wins'] ?>–<?= (int) $t['losses'] ?><?= (int) $t['ties'] ? '–' . (int) $t['ties'] : '' ?></td>
          <td class="num"><?= number_format((float) $t['points_for'], 2) ?></td>
          <td class="num wide-only"><?= number_format((float) $t['points_against'], 2) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <p class="note">The rule under <?= (int) $season['playoff_teams'] ?>th place marks the playoff line.</p>
</section>
<?php else: ?>
<p>League data hasn't been imported yet.</p>
<?php endif; ?>

<?php if ($champions): ?>
<section>
  <h2>Champions</h2>
  <ul class="champions">
    <?php foreach ($champions as $c): ?>
    <li><span class="mono-label"><?= (int) $c['season'] ?></span> <b><?= esc($c['team_name']) ?></b> <span class="owner"><?= esc($c['owner_name'] ?? '') ?></span></li>
    <?php endforeach; ?>
  </ul>
</section>
<?php endif; ?>
