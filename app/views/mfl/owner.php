<?php /** @var array $owner */ /** @var array $tenures */ /** @var array $summary */ /** @var array $honors */ /** @var array $seasons */ /** @var array $headToHead */
$showTeam = true; ?>
<section class="hero hero--tight">
  <p class="mono-label">Owner</p>
  <h1><?= esc(mfl_owner_label($owner['display_name'], $owner['real_name'])) ?></h1>
  <p class="byline">
    <?php foreach ($tenures as $i => $t): ?><?= $i ? ' · ' : '' ?><?= mfl_team_link((int) $t['franchise_id'], $t['franchise_name']) ?> <span class="mono-label"><?= esc(mfl_tenure_span($t)) ?></span><?php endforeach; ?>
  </p>
</section>

<section>
  <?php require __DIR__ . '/_stat-cards.php'; ?>
  <p class="note">Only games played while this owner held the team. Record and points per game are regular season; playoff record counts games on the title path.</p>
</section>

<?php if ($seasons): ?>
<section>
  <h2>Seasons</h2>
  <?php require __DIR__ . '/_seasons-table.php'; ?>
</section>
<?php endif; ?>

<?php require __DIR__ . '/_head-to-head.php'; ?>
