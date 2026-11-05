<?php /** @var array $team */ /** @var array $tenures */ /** @var ?array $era */ /** @var array $summary */ /** @var array $honors */
/** @var array $seasons */ /** @var array $headToHead */ /** @var array $picks */ /** @var array $roster */ /** @var array $future */
/** @var array $moves */ /** @var array $trail */ /** @var int $teamCount */
$id = (int) $team['id']; $showTeam = false; ?>
<section class="hero hero--tight">
  <p class="mono-label">Franchise</p>
  <h1><?= esc($team['name']) ?></h1>
  <p class="byline">Owner: <?= mfl_owner_link($team['owner_name'], $team['real_name']) ?></p>
</section>

<?php if (count($tenures) > 1): ?>
<nav class="era-tabs" aria-label="Franchise history by owner">
  <a href="/teams/<?= $id ?>"<?= !$era ? ' aria-current="page"' : '' ?>>All owners</a>
  <?php foreach ($tenures as $t): ?>
  <a href="/teams/<?= $id ?>?era=<?= (int) $t['id'] ?>"<?= $era && (int) $era['id'] === (int) $t['id'] ? ' aria-current="page"' : '' ?>><?= esc($t['owner_name']) ?> <span class="mono-label"><?= esc(mfl_tenure_span($t)) ?></span></a>
  <?php endforeach; ?>
</nav>
<?php endif; ?>

<section>
  <?php require __DIR__ . '/_stat-cards.php'; ?>
  <p class="note">Record and points per game are regular season. Playoff record counts games on the title path.<?php if (!$era): ?> <?= (int) $moves['moves'] ?> roster moves and <?= (int) $moves['trades'] ?> trades on record.<?php endif; ?></p>
</section>

<?php if ($seasons): ?>
<section>
  <h2>Seasons</h2>
  <?php require __DIR__ . '/_seasons-table.php'; ?>
</section>
<?php endif; ?>

<section>
  <h2>Ownership</h2>
  <ul class="plain-list">
    <?php foreach ($tenures as $t): ?>
    <li><?= mfl_owner_link($t['owner_name'], $t['real_name']) ?> <span class="mono-label"><?= esc(mfl_tenure_span($t)) ?></span></li>
    <?php endforeach; ?>
  </ul>
</section>

<?php if (count($trail) > 0): ?>
<section>
  <h2>Power rankings <a class="link h2-link" href="/rankings">Archive</a></h2>
  <p class="rank-summary">
    Latest <b><?= (int) end($trail) ?></b> · Best <b><?= min($trail) ?></b> · Worst <b><?= max($trail) ?></b>
    <?= mfl_sparkline($trail, $teamCount) ?>
  </p>
</section>
<?php endif; ?>

<?php require __DIR__ . '/_head-to-head.php'; ?>

<?php if ($roster && !$era || ($era && $era['end_season'] === null)): ?>
<section>
  <h2>Current roster</h2>
  <div class="roster">
    <?php foreach ($roster as $position => $players): ?>
    <div>
      <h3 class="mono-label"><?= esc($position) ?></h3>
      <ul class="plain-list">
        <?php foreach ($players as $p): ?><li><?= esc($p['full_name'] ?: $p['id']) ?><?php if ($p['nfl_team'] && $p['position'] !== 'DEF'): ?> <span class="mono-label"><?= esc($p['nfl_team']) ?></span><?php endif; ?></li><?php endforeach; ?>
      </ul>
    </div>
    <?php endforeach; ?>
  </div>
  <?php if ($future['owned'] || $future['traded']): ?>
  <h3 class="sub-head">Future picks on the move</h3>
  <ul class="plain-list">
    <?php foreach ($future['owned'] as $line): ?><li>Holds <?= esc($line) ?></li><?php endforeach; ?>
    <?php foreach ($future['traded'] as $line): ?><li>Traded <?= esc($line) ?></li><?php endforeach; ?>
  </ul>
  <?php endif; ?>
</section>
<?php endif; ?>

<?php if ($picks): ?>
<section>
  <h2>Draft history</h2>
  <?php $groups = []; foreach ($picks as $p) $groups[$p['season']][] = $p; $first = true; ?>
  <?php foreach ($groups as $season => $list): ?>
  <details class="draft-group"<?= $first ? ' open' : '' ?>>
    <summary><?= (int) $season ?> <span class="mono-label"><?= count($list) ?> picks</span></summary>
    <div class="table-scroll">
      <table>
        <thead><tr><th scope="col">Pick</th><th scope="col">Player</th><th scope="col">Pos</th><th scope="col" class="wide-only">NFL team</th></tr></thead>
        <tbody>
        <?php foreach ($list as $p): ?>
          <tr><td><?= esc(mfl_pick_label($p)) ?></td><td class="team"><?= esc($p['player_name']) ?></td><td><?= esc($p['position']) ?></td><td class="wide-only"><?= esc($p['nfl_team']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </details>
  <?php $first = false; endforeach; ?>
</section>
<?php endif; ?>
