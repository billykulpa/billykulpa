<?php /** @var array $p */ /** @var array $names */ /** @var ?int $holder */ /** @var array $timeline */ /** @var ?string $updated */ ?>
<section class="hero hero--tight">
  <p class="mono-label"><?= esc($p['position']) ?><?= $p['nfl_team'] && $p['position'] !== 'DEF' ? ' · ' . esc($p['nfl_team']) : '' ?></p>
  <h1><?= esc($p['name']) ?></h1>
  <p class="player-status">
    <?php if ($holder): ?><span class="trade-status trade-status--here">On <?= mfl_team_link($holder, $names[$holder] ?? '') ?></span>
    <?php else: ?><span class="trade-status trade-status--gone">Free agent</span><?php endif; ?>
    <span class="row-note">As of <?= esc(central_time($updated, 'M j, g:i a')) ?> CT</span>
  </p>
  <p class="byline"><a class="link" href="https://sleeper.com/nfl/players/<?= esc(rawurlencode($p['id'])) ?>" target="_blank" rel="noopener">View on Sleeper</a></p>
</section>

<section>
  <dl class="stat-cards">
    <div><dt class="mono-label">Total points</dt><dd><?= number_format($p['total'], 2) ?></dd></div>
    <div><dt class="mono-label">As starter</dt><dd><?= number_format($p['starter'], 2) ?></dd></div>
    <div><dt class="mono-label">Bench</dt><dd><?= number_format($p['bench'], 2) ?></dd></div>
    <div><dt class="mono-label">Free agent</dt><dd><?= number_format($p['free_agent'], 2) ?></dd></div>
  </dl>
</section>

<?php if ($p['teams']): ?>
<section>
  <h2>By team</h2>
  <div class="table-scroll">
    <table>
      <thead><tr><th scope="col" class="sticky">Team</th><th scope="col" class="num">Total</th><th scope="col" class="num">As starter</th><th scope="col" class="num">Bench</th><th scope="col" class="num">Starts</th><th scope="col" class="num">Weeks</th></tr></thead>
      <tbody>
      <?php uasort($p['teams'], fn($a, $b) => $b['total'] <=> $a['total']); foreach ($p['teams'] as $fid => $t): ?>
        <tr>
          <td class="team"><?= mfl_team_link((int) $fid, $names[$fid] ?? '') ?></td>
          <td class="num"><?= number_format($t['total'], 2) ?></td>
          <td class="num"><?= number_format($t['starter'], 2) ?></td>
          <td class="num"><?= number_format($t['bench'], 2) ?></td>
          <td class="num"><?= (int) $t['starts'] ?></td>
          <td class="num"><?= (int) $t['weeks'] ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if ($p['free_agent'] != 0): ?>
        <tr><td class="team">Free agent</td><td class="num"><?= number_format($p['free_agent'], 2) ?></td><td class="num">—</td><td class="num">—</td><td class="num">—</td><td class="num">—</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>
<?php endif; ?>

<section>
  <h2>By season</h2>
  <div class="table-scroll">
    <table>
      <thead><tr><th scope="col" class="sticky">Season</th><th scope="col" class="num">Total</th><th scope="col" class="num">As starter</th><th scope="col" class="num">Bench</th><th scope="col" class="num">Free agent</th><th scope="col" class="num">Starts</th></tr></thead>
      <tbody>
      <?php krsort($p['seasons']); foreach ($p['seasons'] as $y => $s): ?>
        <tr>
          <td class="team"><?= (int) $y ?></td>
          <td class="num"><?= number_format($s['total'], 2) ?></td>
          <td class="num"><?= number_format($s['starter'], 2) ?></td>
          <td class="num"><?= number_format($s['bench'], 2) ?></td>
          <td class="num"><?= number_format($s['free_agent'], 2) ?></td>
          <td class="num"><?= (int) $s['starts'] ?></td>
        </tr>
      <?php endforeach; ?>
        <tr class="trade-total">
          <td class="sticky">All-time</td>
          <td class="num"><?= number_format($p['total'], 2) ?></td>
          <td class="num"><?= number_format($p['starter'], 2) ?></td>
          <td class="num"><?= number_format($p['bench'], 2) ?></td>
          <td class="num"><?= number_format($p['free_agent'], 2) ?></td>
          <td class="num"><?= (int) $p['starts'] ?></td>
        </tr>
      </tbody>
    </table>
  </div>
</section>

<?php if ($timeline): ?>
<section>
  <h2><?= esc(mfl_league()['short']) ?> history</h2>
  <ul class="plain-list">
    <?php foreach ($timeline as $e): ?>
    <li><?= $e['href'] ? '<a class="link" href="' . esc($e['href']) . '">' . esc($e['text']) . '</a>' : esc($e['text']) ?></li>
    <?php endforeach; ?>
  </ul>
</section>
<?php endif; ?>
<p class="note records-note">As starter is points that counted, in the regular season or on the title path of the playoffs. Bench is points on a roster that didn't count, including consolation games.</p>
