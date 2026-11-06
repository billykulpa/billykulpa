<?php /** @var array $poll */ /** @var string $from */
$leader = max(array_map('intval', array_column($poll['answers'], 'votes')) ?: [0]); ?>
<article class="poll" id="poll-<?= (int) $poll['id'] ?>">
  <h3 class="poll-question"><?= esc($poll['question']) ?></h3>
  <?php if (trim((string) $poll['description']) !== ''): ?><div class="writeup poll-description"><?= mfl_writeup_html($poll['description']) ?></div><?php endif; ?>
  <?php if ($poll['show_results']): ?>
  <ul class="poll-results">
    <?php foreach ($poll['answers'] as $a):
      $votes = (int) $a['votes'];
      $percent = mfl_poll_percent($votes, (int) $poll['total']);
      $mine = (int) $a['id'] === $poll['my_answer'];
      // One bar gets the accent: your pick, or the leader if you didn't vote.
      $lit = $poll['my_answer'] !== null ? $mine : ($votes > 0 && $votes === $leader); ?>
    <li class="poll-result">
      <div class="poll-line">
        <span class="poll-label"><?= esc($a['label']) ?><?php if ($mine): ?> <span class="poll-mine">✓ Your pick</span><?php endif; ?></span>
        <span class="poll-percent"><?= $percent ?>%</span>
      </div>
      <div class="poll-track"><div class="poll-bar<?= $lit ? ' is-lit' : '' ?>" style="width: <?= $percent ?>%"></div></div>
    </li>
    <?php endforeach; ?>
  </ul>
  <?php else: ?>
  <form method="post" action="/polls/vote">
    <input type="hidden" name="poll" value="<?= (int) $poll['id'] ?>">
    <input type="hidden" name="from" value="<?= esc($from) ?>">
    <fieldset class="poll-options">
      <legend class="poll-legend"><?= esc($poll['question']) ?></legend>
      <?php foreach ($poll['answers'] as $a): ?>
      <label class="poll-option"><input type="radio" name="answer" value="<?= (int) $a['id'] ?>" required> <span><?= esc($a['label']) ?></span></label>
      <?php endforeach; ?>
    </fieldset>
    <button class="poll-vote" type="submit">Vote</button>
  </form>
  <?php endif; ?>
  <p class="poll-meta mono-label">
    <?php if ($poll['show_results']): ?><?= (int) $poll['total'] ?> <?= (int) $poll['total'] === 1 ? 'vote' : 'votes' ?> · <?php endif; ?>
    <?= $poll['open'] ? 'Closes ' : 'Closed ' ?><?= esc(central_time($poll['expires_at'], 'M j, g:i a')) ?> CT
  </p>
</article>
