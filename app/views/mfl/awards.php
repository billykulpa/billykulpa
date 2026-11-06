<?php /** @var array $awards */ /** @var array $notes */ /** @var array $season */ /** @var array $seasons */
$year = (int) $season['season']; ?>
<section class="hero hero--tight">
  <p class="mono-label"><?= $year ?></p>
  <h1><?= esc(mfl_award_title($year)) ?></h1>
  <p class="award-through"><?= $season['status'] === 'complete' ? 'Final' : 'Through Week ' . (int) $season['last_scored_week'] ?></p>
</section>

<?php if (count($seasons) > 1): ?>
<nav class="era-tabs" aria-label="Seasons">
  <?php foreach ($seasons as $s): ?><a href="/awards?season=<?= (int) $s ?>"<?= (int) $s === $year ? ' aria-current="page"' : '' ?>><?= (int) $s ?></a><?php endforeach; ?>
</nav>
<?php endif; ?>

<?php if (!$awards): ?><p>No games have been played yet.</p><?php endif; ?>

<div class="award-grid">
  <?php foreach ($awards as $a): ?>
  <article class="award">
    <h2 class="award-title"><?= esc($a['title']) ?></h2>
    <p class="mono-label award-measures"><?= esc($a['measures']) ?></p>
    <p class="award-winner"><?= implode(' &amp; ', $a['winners']) ?></p>
    <p class="award-value"><?= esc($a['value']) ?></p>
    <?php if ($a['detail'] !== ''): ?><p class="row-note"><?= esc($a['detail']) ?></p><?php endif; ?>
    <?php if (trim((string) ($notes[$a['key']] ?? '')) !== ''): ?><div class="writeup award-note"><?= mfl_writeup_html($notes[$a['key']]) ?></div><?php endif; ?>
  </article>
  <?php endforeach; ?>
</div>

<?php if ($awards): ?>
<p class="note records-note">These update as the season goes. Points are regular season. Some awards are decided by the league instead: <a class="link" href="/polls">see the polls</a>.</p>
<?php endif; ?>
