<?php /** @var array $records */ /** @var array $allTime */ /** @var string $sort */ /** @var string $dir */
$points = fn($g) => number_format($g['points'], 2);
$margin = fn($g) => number_format($g['points'] - (float) $g['opponent_points'], 2);
$streakSpan = fn($s) => $s['from']['season'] . ' W' . $s['from']['week'] . ' – ' . $s['to']['season'] . ' W' . $s['to']['week'] . ($s['active'] ? ' · Active' : ''); ?>
<section class="hero hero--tight">
  <p class="mono-label">All-time</p>
  <h1>Records</h1>
</section>
<section id="all-time">
  <h2>All-time standings</h2>
  <div class="table-scroll">
    <table>
      <thead><tr>
        <th scope="col" class="num">#</th>
        <?= mfl_sort_th('/records#all-time', $sort, $dir, 'name', 'Team', 'sticky') ?>
        <?= mfl_sort_th('/records#all-time', $sort, $dir, 'wins', 'W') ?>
        <?= mfl_sort_th('/records#all-time', $sort, $dir, 'losses', 'L') ?>
        <?= mfl_sort_th('/records#all-time', $sort, $dir, 'ties', 'T', 'num') ?>
        <?= mfl_sort_th('/records#all-time', $sort, $dir, 'win_pct', 'Win %') ?>
        <?= mfl_sort_th('/records#all-time', $sort, $dir, 'pf', 'PF') ?>
        <?= mfl_sort_th('/records#all-time', $sort, $dir, 'pa', 'PA', 'num') ?>
      </tr></thead>
      <tbody>
      <?php foreach ($allTime as $i => $t): ?>
        <tr>
          <td class="num"><?= $i + 1 ?></td>
          <td class="team"><?= mfl_team_link((int) $t['id'], $t['name']) ?></td>
          <td class="num"><?= (int) $t['wins'] ?></td>
          <td class="num"><?= (int) $t['losses'] ?></td>
          <td class="num"><?= (int) $t['ties'] ?></td>
          <td class="num"><?= esc(mfl_pct($t['win_pct'])) ?></td>
          <td class="num"><?= number_format($t['pf'], 2) ?></td>
          <td class="num"><?= number_format($t['pa'], 2) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <p class="note">Regular season, including the season in progress. Click a heading to sort.</p>
</section>

<div class="record-grid">
  <?php
  $pf = fn($s) => number_format($s['pf'], 2);
  $pa = fn($s) => number_format($s['pa'], 2);
  foreach ([['Most points scored, all-time', $records['career_pf_most'], $pf], ['Fewest points scored, all-time', $records['career_pf_least'], $pf],
            ['Most points against, all-time', $records['career_pa_most'], $pa], ['Fewest points against, all-time', $records['career_pa_least'], $pa]] as [$heading, $list, $value]) {
      require __DIR__ . '/_season-record.php';
  }
  foreach ([['Highest scores', $records['high_scores'], $points], ['Lowest scores', $records['low_scores'], $points],
            ['Biggest wins', $records['blowouts'], $margin], ['Closest wins', $records['close_games'], $margin]] as [$heading, $list, $value]) {
      require __DIR__ . '/_game-record.php';
  }
  foreach ([['Best seasons', $records['most_wins'], fn($s) => mfl_record($s)],
            ['Most points in a season', $records['most_points'], fn($s) => number_format($s['pf'], 2)],
            ['Fewest points in a season', $records['fewest_points'], fn($s) => number_format($s['pf'], 2)],
            ['Most points against in a season', $records['most_against'], $pa],
            ['Fewest points against in a season', $records['fewest_against'], $pa],
            ['Most roster moves', $records['moves'], fn($s) => (string) $s['moves']]] as [$heading, $list, $value]) {
      require __DIR__ . '/_season-record.php';
  }
  foreach ([['Longest winning streaks', $records['win_streaks']], ['Longest losing streaks', $records['loss_streaks']]] as [$heading, $list]): ?>
  <section class="record-block">
    <h2><?= esc($heading) ?></h2>
    <ol class="record-list">
      <?php foreach ($list as $s): ?>
      <li>
        <span class="record-value"><?= (int) $s['length'] ?></span>
        <span class="record-who"><?= mfl_team_link($s['franchise_id'], $s['team_name']) ?> <span class="mono-label"><?= esc($streakSpan($s)) ?></span></span>
      </li>
      <?php endforeach; ?>
    </ol>
  </section>
  <?php endforeach; ?>
</div>
<p class="note records-note">All-time points are regular season and include the season in progress. Game records count the regular season and the title path of the playoffs. Season records cover finished seasons. Streaks run across seasons.</p>
