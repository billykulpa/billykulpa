<?php /** @var array $open */ /** @var array $closed */ $from = 'polls'; ?>
<section class="hero hero--tight">
  <p class="mono-label">League vote</p>
  <h1>Polls</h1>
</section>

<?php $owner = mfl_current_owner(); if ($owner): ?>
<form class="voter-strip" method="post" action="/sign-out">
  <span>Voting as <b><?= esc(mfl_owner_label($owner['display_name'], $owner['real_name'])) ?></b></span>
  <button class="sort-button" type="submit">Sign out</button>
</form>
<?php endif; ?>

<?php if (!$open && !$closed): ?>
<p>No polls yet.</p>
<?php endif; ?>

<?php if ($open): ?>
<section>
  <h2>Open</h2>
  <div class="poll-grid">
    <?php foreach ($open as $poll) require __DIR__ . '/_poll.php'; ?>
  </div>
</section>
<?php endif; ?>

<?php if ($closed): ?>
<section>
  <h2>Closed</h2>
  <div class="poll-grid">
    <?php foreach ($closed as $poll) require __DIR__ . '/_poll.php'; ?>
  </div>
</section>
<?php endif; ?>
