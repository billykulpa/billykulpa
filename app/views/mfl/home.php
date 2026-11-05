<?php /** @var ?array $season */ /** @var array $standings */ /** @var array $champions */ /** @var ?array $latest */ /** @var array $latestRows */ /** @var array $polls */ ?>
<section class="hero">
  <p class="mono-label"><?= $season ? (int) $season['num_teams'] . ' teams · Dynasty · Est. 2025' : 'Dynasty · Est. 2025' ?></p>
  <h1>Midwest Football League</h1>
</section>

<?php if ($polls): ?>
<section>
  <h2>Polls <a class="link h2-link" href="/polls">All polls</a></h2>
  <div class="poll-grid">
    <?php foreach ($polls as $poll): $from = 'home'; require __DIR__ . '/_poll.php'; endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php if ($latest): ?>
<section>
  <h2>
    <?= esc(mfl_ranking_title($latest)) ?>
    <span class="mono-label">By <?= esc($latest['author_name']) ?></span>
  </h2>
  <ol class="top-ranks">
    <?php foreach (array_slice($latestRows, 0, 5) as $e): ?>
    <li>
      <span class="top-rank-no"><?= (int) $e['rank_no'] ?></span>
      <span class="team"><?= esc($e['team_name']) ?></span>
      <?= mfl_movement($e['delta']) ?>
    </li>
    <?php endforeach; ?>
  </ol>
  <p class="note"><a class="link" href="<?= esc(mfl_ranking_url($latest)) ?>">Read the full rankings</a></p>
</section>
<?php endif; ?>

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
          <td class="wide-only"><?= esc(mfl_owner_label($t['owner_name'], $t['real_name'])) ?></td>
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
    <li><span class="mono-label"><?= (int) $c['season'] ?></span> <b><?= esc($c['team_name']) ?></b> <span class="owner"><?= esc(mfl_owner_label($c['owner_name'], $c['real_name'])) ?></span></li>
    <?php endforeach; ?>
  </ul>
</section>
<?php endif; ?>
