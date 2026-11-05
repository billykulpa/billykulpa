<?php /** @var array $summary */ /** @var array $honors */ ?>
<dl class="stat-cards">
  <div><dt class="mono-label">Record</dt><dd><?= esc(mfl_record($summary)) ?></dd></div>
  <div><dt class="mono-label">Win %</dt><dd><?= esc(mfl_pct($summary['win_pct'])) ?></dd></div>
  <div><dt class="mono-label">Titles</dt><dd><?= (int) $honors['titles'] ?></dd></div>
  <div><dt class="mono-label">Playoff trips</dt><dd><?= (int) $honors['playoffs'] ?></dd></div>
  <div><dt class="mono-label">Playoff record</dt><dd><?= (int) $summary['playoff_wins'] ?>–<?= (int) $summary['playoff_losses'] ?></dd></div>
  <div><dt class="mono-label">Points per game</dt><dd><?= number_format($summary['average'], 2) ?></dd></div>
  <div><dt class="mono-label">Best game</dt><dd><?= $summary['high'] !== null ? number_format($summary['high'], 2) : '—' ?></dd></div>
  <div><dt class="mono-label">Worst game</dt><dd><?= $summary['low'] !== null ? number_format($summary['low'], 2) : '—' ?></dd></div>
</dl>
