<?php /** @var ?array $flash */ /** @var array $form */
$action = '/admin/mfl/polls/' . ($form['id'] ? 'edit?id=' . (int) $form['id'] : 'new');
$locked = $form['votes'] > 0; ?>
<?php require __DIR__ . '/_subnav.php'; ?>
<h1><?= $form['id'] ? 'Edit poll' : 'New poll' ?></h1>
<?php if ($form['id']): ?>
<p class="a-sub">
  <span class="a-pill a-pill--<?= $form['state'] === 'open' ? 'published' : 'draft' ?>"><?= esc($form['state']) ?></span>
  By <?= esc($form['author']) ?> · <?= (int) $form['votes'] ?> <?= $form['votes'] === 1 ? 'vote' : 'votes' ?>
  <?php if ($form['status'] === 'published'): ?>· <a href="<?= esc(MFL_BASE_URL) ?>/polls#poll-<?= (int) $form['id'] ?>" target="_blank" rel="noopener" style="text-decoration:underline">View live &nearr;</a><?php endif; ?>
</p>
<?php endif; ?>
<?php if ($flash): ?><div class="a-flash a-flash--<?= $flash['status'] === 'ok' ? 'ok' : 'err' ?>"><?= esc($flash['summary']) ?></div><?php endif; ?>

<form method="post" action="<?= esc($action) ?>"><?= csrf_field() ?>
  <label class="a-label" for="question">Title</label>
  <input type="text" id="question" name="question" required maxlength="200" value="<?= esc($form['question']) ?>"
         placeholder="Best Waiver Wire Pickup">

  <label class="a-label" for="expires_at">Closes (Central time)</label>
  <input type="datetime-local" id="expires_at" name="expires_at" required value="<?= esc($form['expires']) ?>">

  <p class="a-label" id="answers-label">Answers</p>
  <?php if ($locked): ?>
  <p class="a-hint">Answers lock once a poll has votes, because rewording one would change what people voted for.</p>
  <ol class="a-poll-locked">
    <?php foreach ($form['answers'] as $a): ?><li><?= esc($a) ?></li><?php endforeach; ?>
  </ol>
  <?php else: ?>
  <div class="a-poll-answers" id="a-poll-answers" role="group" aria-labelledby="answers-label">
    <?php foreach ($form['answers'] as $i => $a): ?>
    <div class="a-poll-answer">
      <input type="text" name="answers[]" maxlength="200" value="<?= esc($a) ?>" aria-label="Answer <?= $i + 1 ?>">
      <button type="button" class="a-rank-move a-poll-remove" aria-label="Remove answer">×</button>
    </div>
    <?php endforeach; ?>
  </div>
  <button type="button" class="a-link-btn" id="a-poll-add">Add another answer</button>
  <?php endif; ?>

  <div class="a-rank-actions">
    <?php if ($form['status'] === 'published'): ?>
    <button class="a-btn" type="submit" name="action" value="save">Save changes</button>
    <?php if ($form['state'] === 'open'): ?><button class="a-btn a-btn--ghost" type="submit" name="action" value="close">Close poll now</button><?php endif; ?>
    <button class="a-btn a-btn--ghost" type="submit" name="action" value="unpublish">Unpublish</button>
    <?php else: ?>
    <button class="a-btn a-btn--ghost" type="submit" name="action" value="save">Save draft</button>
    <button class="a-btn" type="submit" name="action" value="publish">Publish poll</button>
    <?php endif; ?>
  </div>
</form>

<?php if ($form['id'] && $form['status'] === 'draft'): ?>
<form method="post" action="/admin/mfl/polls/delete" class="a-rank-delete"><?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= (int) $form['id'] ?>">
  <button class="a-btn a-btn--danger" type="submit" onclick="return confirm('Delete this poll and its votes?')">Delete poll</button>
</form>
<?php endif; ?>
