<?php /** @var string $heading */ /** @var array $list */ /** @var callable $value */ ?>
<section class="record-block">
  <h2><?= esc($heading) ?></h2>
  <ol class="record-list">
    <?php foreach ($list as $s): ?>
    <li>
      <span class="record-value"><?= esc($value($s)) ?></span>
      <span class="record-who"><?= mfl_team_link((int) $s['franchise_id'], $s['team_name']) ?>
        <?php if (isset($s['season'])): ?><span class="mono-label"><?= (int) $s['season'] ?></span><?php endif; ?></span>
    </li>
    <?php endforeach; ?>
  </ol>
</section>
