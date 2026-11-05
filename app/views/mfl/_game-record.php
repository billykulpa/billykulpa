<?php /** @var string $heading */ /** @var array $list */ /** @var callable $value */ ?>
<section class="record-block">
  <h2><?= esc($heading) ?></h2>
  <ol class="record-list">
    <?php foreach ($list as $g): ?>
    <li>
      <span class="record-value"><?= esc($value($g)) ?></span>
      <span class="record-who">
        <?= mfl_team_link($g['franchise_id'], $g['team_name']) ?>
        <?php if ($g['opponent_franchise_id'] ?? null): ?><span class="record-opp">vs <?= esc((string) $g['opponent_name']) ?></span><?php endif; ?>
        <span class="mono-label"><?= esc(mfl_game_label($g)) ?></span>
      </span>
    </li>
    <?php endforeach; ?>
  </ol>
</section>
