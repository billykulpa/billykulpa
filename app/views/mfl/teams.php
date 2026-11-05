<?php /** @var array $teams */ /** @var string $sort */ /** @var string $dir */
$th = fn($key, $label, $class = 'num') => mfl_sort_th('/teams', $sort, $dir, $key, $label, $class); ?>
<section class="hero hero--tight">
  <p class="mono-label">All-time</p>
  <h1>Teams</h1>
</section>
<section>
  <div class="table-scroll">
    <table class="teams-table">
      <thead><tr>
        <?= $th('name', 'Team', '') ?>
        <?= $th('owner_name', 'Owner', 'wide-only') ?>
        <?= $th('seasons', 'Seasons', 'num wide-only') ?>
        <?= $th('wins', 'Record') ?>
        <?= $th('win_pct', 'Win %') ?>
        <?= $th('pf', 'PF') ?>
        <?= $th('pa', 'PA', 'num wide-only') ?>
        <?= $th('playoffs', 'Playoffs', 'num wide-only') ?>
        <?= $th('titles', 'Titles') ?>
      </tr></thead>
      <tbody>
      <?php foreach ($teams as $t): ?>
        <tr>
          <td class="team"><?= mfl_team_link((int) $t['id'], $t['name']) ?></td>
          <td class="wide-only"><?= mfl_owner_link($t['owner_name'], $t['real_name']) ?></td>
          <td class="num wide-only"><?= (int) $t['seasons'] ?></td>
          <td class="num"><?= esc(mfl_record($t)) ?></td>
          <td class="num"><?= esc(mfl_pct($t['win_pct'])) ?></td>
          <td class="num"><?= number_format($t['pf'], 2) ?></td>
          <td class="num wide-only"><?= number_format($t['pa'], 2) ?></td>
          <td class="num wide-only"><?= (int) $t['playoffs'] ?></td>
          <td class="num"><?= (int) $t['titles'] ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <p class="note">Click a heading to sort. Seasons include the one in progress. Records and points are regular season. A franchise keeps its history through owner changes; each owner's page shows only their own run.</p>
</section>
