<?php /** @var array $app */ /** @var bool $saved */ ?>
<p class="a-back"><a href="/admin/jobs">&larr; Back to jobs</a></p>
<h1><?= $app['id'] ? 'Edit application' : 'New application' ?></h1>
<?php if ($saved): ?><div class="a-flash a-flash--ok">Saved.</div><?php endif; ?>

<form method="post" action="/admin/jobs/<?= $app['id'] ? 'edit?id=' . (int) $app['id'] : 'new' ?>">
  <?= csrf_field() ?>
  <div class="a-grid-2">
    <div>
      <label class="a-label" for="company">Company</label>
      <input type="text" id="company" name="company" required value="<?= esc($app['company']) ?>">
    </div>
    <div>
      <label class="a-label" for="role">Role</label>
      <input type="text" id="role" name="role" required value="<?= esc($app['role']) ?>">
    </div>
    <div>
      <label class="a-label" for="comp">Compensation</label>
      <input type="text" id="comp" name="comp" value="<?= esc($app['comp']) ?>">
    </div>
    <div>
      <label class="a-label" for="remote">Location / remote</label>
      <input type="text" id="remote" name="remote" value="<?= esc($app['remote']) ?>">
    </div>
    <div>
      <label class="a-label" for="status">Status</label>
      <select id="status" name="status">
        <?php foreach (['found','applied','callback','interview','offer','denied','abandoned'] as $s): ?>
        <option value="<?= $s ?>" <?= $app['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="a-label" for="applied_on">Applied on</label>
      <input type="date" id="applied_on" name="applied_on" value="<?= esc($app['applied_on'] ?? '') ?>">
    </div>
  </div>
  <label class="a-label" for="url">Posting URL</label>
  <input type="text" id="url" name="url" value="<?= esc($app['url']) ?>">
  <?php if ($app['id']): $tag = slugify($app['company']); $link = 'https://www.billykulpa.com/?via=' . $tag; ?>
  <div class="a-tagged">
    <p class="a-label" style="margin-top:18px">Your link for this application</p>
    <p class="a-tagged-row"><code id="tagged-link"><?= esc($link) ?></code> <button type="button" class="a-btn a-btn--ghost a-copy-mini" data-copy="#tagged-link">Copy</button></p>
    <p class="a-hint">Put this on the resume and in the form for <?= esc($app['company']) ?>. Opens show up here and on the Traffic page.
    <?php if (!empty($opens) && (int) $opens['n'] > 0): ?>
      <strong>Opened <?= (int) $opens['n'] ?>&times;</strong>, last <?= esc(central_time($opens['last'])) ?>; pages read: <?= esc((string) $opens['pages']) ?>.
    <?php else: ?>
      Not opened yet.
    <?php endif; ?></p>
  </div>
  <?php endif; ?>
  <label class="a-label" for="notes">Notes</label>
  <textarea id="notes" name="notes"><?= esc($app['notes'] ?? '') ?></textarea>
  <div class="a-letter-head">
    <label class="a-label" for="letter">Cover letter body</label>
    <button type="button" class="a-btn a-btn--ghost a-copy-letter" data-copy-target="letter" hidden>Copy letter</button>
  </div>
  <textarea id="letter" name="letter" class="a-letter" rows="14"
            placeholder="Paste or write the cover letter body here. The morning run fills this in for roles it files."><?= esc($app['letter'] ?? '') ?></textarea>
  <button class="a-btn" type="submit">Save</button>
</form>

<?php if ($app['id']): ?>
<form method="post" action="/admin/jobs/delete" style="margin-top: 20px"
      onsubmit="return confirm('Delete this application?');">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= (int) $app['id'] ?>">
  <button class="a-btn a-btn--danger" type="submit">Delete</button>
</form>
<?php endif; ?>
