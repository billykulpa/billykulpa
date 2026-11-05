<?php /** @var array $ranking */ /** @var array $rows */ /** @var ?array $prev */ /** @var ?array $next */ ?>
<section class="hero hero--tight">
  <p class="mono-label">Power Rankings · <?= (int) $ranking['season'] ?> · <?= esc(mfl_week_label((int) $ranking['week'])) ?></p>
  <h1><?= esc(mfl_ranking_title($ranking)) ?></h1>
  <p class="byline">By <?= esc($ranking['author_name']) ?> · <?= esc(central_time($ranking['published_at'], 'M j, Y')) ?></p>
  <?php if (trim((string) $ranking['intro_md']) !== ''): ?><div class="writeup intro"><?= mfl_writeup_html($ranking['intro_md']) ?></div><?php endif; ?>
</section>

<ol class="rank-list">
  <?php foreach ($rows as $e): ?>
  <li class="rank-row">
    <div class="rank-no"><?= (int) $e['rank_no'] ?></div>
    <div class="rank-body">
      <div class="rank-head">
        <div>
          <h2 class="rank-team"><?= mfl_team_link((int) $e['franchise_id'], $e['team_name']) ?></h2>
          <p class="rank-owner"><?= mfl_owner_link($e['owner_name'], $e['real_name']) ?> · <?= esc($e['record']) ?></p>
        </div>
        <div class="rank-trend">
          <?= mfl_sparkline($e['trail'], count($rows)) ?>
          <?= mfl_movement($e['delta']) ?>
        </div>
      </div>
      <?php if (trim((string) $e['writeup_md']) !== ''): ?><div class="writeup"><?= mfl_writeup_html($e['writeup_md']) ?></div><?php endif; ?>
    </div>
  </li>
  <?php endforeach; ?>
</ol>

<nav class="pager" aria-label="Other rankings">
  <span><?php if ($prev): ?><a class="link" href="<?= esc(mfl_ranking_url($prev)) ?>">← <?= esc(mfl_week_label((int) $prev['week'])) ?></a><?php endif; ?></span>
  <a class="link" href="/rankings">All rankings</a>
  <span><?php if ($next): ?><a class="link" href="<?= esc(mfl_ranking_url($next)) ?>"><?= esc(mfl_week_label((int) $next['week'])) ?> →</a><?php endif; ?></span>
</nav>
