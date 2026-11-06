<?php /** @var array $open */ /** @var array $closed */ $from = 'polls'; ?>
<section class="hero hero--tight">
  <p class="mono-label">League vote</p>
  <h1>Polls</h1>
</section>

<?php $polls = array_merge($open, $closed); require __DIR__ . '/_voter-strip.php'; ?>

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
