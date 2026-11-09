<?php /** @var ?array $season */ /** @var array $standings */ /** @var array $champions */ /** @var ?array $latest */ /** @var array $latestRows */ /** @var array $polls */ /** @var array $pickups */ ?>
<?php $league = mfl_league(); ?>
<section class="hero<?= $league['logo'] ? ' hero--logo' : '' ?>">
  <?php if ($league['logo']): ?><img class="hero-logo" src="<?= esc(mfl_asset('logo.svg')) ?>" alt="" width="300" height="234"><?php endif; ?>
  <div>
    <p class="mono-label"><?= $season ? (int) $season['num_teams'] . ' teams · ' : '' ?>Dynasty · Est. <?= (int) $league['founded'] ?></p>
    <h1><?= esc($league['name']) ?></h1>
  </div>
</section>

<?php if ($polls): ?>
<section>
  <h2>Polls <a class="link h2-link" href="/polls">All polls</a></h2>
  <?php if (!mfl_current_owner()) require __DIR__ . '/_voter-strip.php'; ?>
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
      <span class="team"><?= mfl_team_link((int) $e['franchise_id'], $e['team_name']) ?></span>
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
        <tr><th scope="col" class="num">#</th><th scope="col" class="sticky">Team</th><th scope="col">Owner</th>
            <th scope="col" class="num">Record</th><th scope="col" class="num">PF</th><th scope="col" class="num">PA</th></tr>
      </thead>
      <tbody>
      <?php foreach ($standings as $i => $t): ?>
        <tr<?= $i + 1 === (int) $season['playoff_teams'] ? ' class="cutline"' : '' ?>>
          <td class="num"><?= $i + 1 ?></td>
          <td class="team"><?= mfl_team_link((int) $t['franchise_id'], $t['team_name']) ?></td>
          <td><?= mfl_owner_link($t['owner_name'], $t['real_name']) ?></td>
          <td class="num"><?= (int) $t['wins'] ?>–<?= (int) $t['losses'] ?><?= (int) $t['ties'] ? '–' . (int) $t['ties'] : '' ?></td>
          <td class="num"><?= number_format((float) $t['points_for'], 2) ?></td>
          <td class="num"><?= number_format((float) $t['points_against'], 2) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <p class="note">The blue rule marks the playoff line. <a class="link" href="/standings">Full standings</a></p>
</section>
<?php else: ?>
<p>League data hasn't been imported yet.</p>
<?php endif; ?>

<?php if ($pickups): ?>
<section>
  <h2>Best pickups <a class="link h2-link" href="/pickups">Full table</a></h2>
  <ol class="top-ranks">
    <?php foreach ($pickups as $i => $p): ?>
    <li>
      <span class="top-rank-no"><?= $i + 1 ?></span>
      <span class="team"><?= mfl_player_link($p['player_id'], $p['name']) ?> <span class="row-note">· <?= esc($p['team_name']) ?></span></span>
      <span class="move"><?= number_format($p['points'], 2) ?></span>
    </li>
    <?php endforeach; ?>
  </ol>
</section>
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
