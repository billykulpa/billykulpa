<?php /** @var array $history */ /** @var array $teams */ /** @var array $trend */ ?>
<section class="hero hero--tight">
  <p class="mono-label">Archive</p>
  <h1>Power Rankings</h1>
</section>

<?php if (!$history): ?>
<p>No rankings have been published yet.</p>
<?php else: ?>

<?php if (count($history) > 1): ?>
<section>
  <h2>Rank over time</h2>
  <div class="trend-controls">
    <label class="mono-label" for="trend-team">Highlight</label>
    <select id="trend-team">
      <?php foreach ($trend['teams'] as $i => $t): ?><option value="<?= $i ?>"><?= esc($t['name']) ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="trend" id="trend"></div>
  <script type="application/json" id="trend-data"><?= json_encode($trend, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?></script>
  <details class="trend-table">
    <summary class="mono-label">Show as a table</summary>
    <div class="table-scroll">
      <table>
        <thead><tr><th scope="col">Team</th>
          <?php foreach ($trend['sets'] as $s): ?><th scope="col" class="num" title="<?= esc($s['full']) ?>"><?= esc($s['short']) ?></th><?php endforeach; ?>
        </tr></thead>
        <tbody>
        <?php foreach ($trend['teams'] as $t): ?>
          <tr><td class="team"><?= esc($t['name']) ?></td>
            <?php foreach ($t['ranks'] as $rank): ?><td class="num"><?= $rank ?? '—' ?></td><?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </details>
</section>
<?php endif; ?>

<section>
  <h2>All-time <span class="mono-label"><?= count($history) ?> <?= count($history) === 1 ? 'ranking' : 'rankings' ?></span></h2>
  <div class="table-scroll">
    <table>
      <thead><tr><th scope="col">Team</th><th scope="col" class="num">Latest</th><th scope="col" class="num">Best</th>
        <th scope="col" class="num">Worst</th><th scope="col" class="num">Avg</th><th scope="col" class="num">Times #1</th></tr></thead>
      <tbody>
      <?php foreach ($teams as $t): ?>
        <tr>
          <td class="team"><?= mfl_team_link((int) $t['id'], $t['name']) ?></td>
          <td class="num"><?= $t['latest'] ?? '—' ?></td>
          <td class="num"><?= (int) $t['best'] ?></td>
          <td class="num"><?= (int) $t['worst'] ?></td>
          <td class="num"><?= number_format($t['average'], 1) ?></td>
          <td class="num"><?= (int) $t['first_place'] ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<section>
  <h2>Every ranking</h2>
  <ul class="archive">
    <?php foreach (array_reverse($history) as $r): ?>
    <li>
      <a class="archive-title" href="<?= esc(mfl_ranking_url($r)) ?>"><?= esc(mfl_ranking_title($r)) ?></a>
      <span class="mono-label"><?= (int) $r['season'] ?> · <?= esc($r['author_name']) ?> · <?= esc(central_time($r['published_at'], 'M j, Y')) ?></span>
    </li>
    <?php endforeach; ?>
  </ul>
</section>
<?php endif; ?>
