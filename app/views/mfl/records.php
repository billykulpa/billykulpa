<?php /** @var array $records */
$points = fn($g) => number_format($g['points'], 2);
$margin = fn($g) => number_format($g['points'] - (float) $g['opponent_points'], 2);
$streakSpan = fn($s) => $s['from']['season'] . ' W' . $s['from']['week'] . ' – ' . $s['to']['season'] . ' W' . $s['to']['week'] . ($s['active'] ? ' · Active' : ''); ?>
<section class="hero hero--tight">
  <p class="mono-label">All-time</p>
  <h1>Records</h1>
</section>
<div class="record-grid">
  <?php
  foreach ([['Highest scores', $records['high_scores'], $points], ['Lowest scores', $records['low_scores'], $points],
            ['Biggest wins', $records['blowouts'], $margin], ['Closest wins', $records['close_games'], $margin]] as [$heading, $list, $value]) {
      require __DIR__ . '/_game-record.php';
  }
  foreach ([['Best seasons', $records['most_wins'], fn($s) => mfl_record($s)],
            ['Most points in a season', $records['most_points'], fn($s) => number_format($s['pf'], 2)],
            ['Fewest points in a season', $records['fewest_points'], fn($s) => number_format($s['pf'], 2)],
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
<p class="note records-note">Games count from the regular season and the title path of the playoffs. Season records cover finished seasons. Streaks run across seasons.</p>
