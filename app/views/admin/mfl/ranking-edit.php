<?php /** @var ?array $flash */ /** @var array $ranking */ /** @var array $rows */ /** @var array $revisions */
$published = $ranking['status'] === 'published'; ?>
<?php require __DIR__ . '/_subnav.php'; ?>
<h1><?= esc(mfl_ranking_title($ranking)) ?></h1>
<p class="a-sub">
  <span class="a-pill a-pill--<?= esc($ranking['status']) ?>"><?= esc($ranking['status']) ?></span>
  <?= (int) $ranking['season'] ?> · By <?= esc($ranking['author_name']) ?>
  <?php if ($published): ?>· <a href="<?= esc(MFL_BASE_URL . mfl_ranking_url($ranking)) ?>" target="_blank" rel="noopener" style="text-decoration:underline">View live &nearr;</a><?php endif; ?>
</p>
<?php if ($flash): ?><div class="a-flash a-flash--<?= $flash['status'] === 'ok' ? 'ok' : 'err' ?>"><?= esc($flash['summary']) ?></div><?php endif; ?>

<form method="post" action="/admin/mfl/rankings/edit?id=<?= (int) $ranking['id'] ?>" id="a-ranking-form"><?= csrf_field() ?>
  <div class="a-grid-2">
    <div>
      <label class="a-label" for="title">Title (optional)</label>
      <input type="text" id="title" name="title" maxlength="160" value="<?= esc($ranking['title']) ?>"
             placeholder="<?= esc(mfl_week_label((int) $ranking['week'])) ?> Power Rankings">
    </div>
    <div>
      <label class="a-label" for="week">Week</label>
      <select id="week" name="week">
        <?php for ($w = 0; $w <= MFL_FINAL_WEEK; $w++): ?>
        <option value="<?= $w ?>" <?= $w === (int) $ranking['week'] ? 'selected' : '' ?>><?= esc(mfl_week_label($w)) ?></option>
        <?php endfor; ?>
      </select>
    </div>
  </div>

  <label class="a-label" for="intro_md">Intro (optional)</label>
  <textarea id="intro_md" name="intro_md" maxlength="20000"><?= esc($ranking['intro_md'] ?? '') ?></textarea>

  <div class="a-rank-head">
    <h2 class="a-label">Teams</h2>
    <button type="button" class="a-link-btn" id="a-rank-compact" aria-pressed="false">Hide write-ups</button>
  </div>
  <p class="a-hint">Drag the handle, or use the arrows. Write-ups are optional. Formatting: **bold**, *italic*, [link](https://…), and a blank line for a new paragraph.</p>
  <p class="a-visually-hidden" id="a-rank-status" aria-live="polite"></p>

  <ol class="a-rank-list" id="a-rank-list">
  <?php foreach ($rows as $i => $t): $fid = (int) $t['franchise_id']; ?>
    <li class="a-rank-item">
      <input type="hidden" name="order[]" value="<?= $fid ?>">
      <div class="a-rank-bar">
        <span class="a-rank-handle" aria-hidden="true" title="Drag to reorder">⠿</span>
        <span class="a-rank-no"><?= $i + 1 ?></span>
        <span class="a-rank-team"><?= esc($t['current_name'] ?? $t['team_name']) ?></span>
        <span class="a-rank-meta"><?= esc($t['record']) ?><?= $t['last_rank'] ? ' · Last: ' . (int) $t['last_rank'] : '' ?></span>
        <span class="a-rank-moves">
          <button type="button" class="a-rank-move" data-dir="-1" aria-label="Move <?= esc($t['team_name']) ?> up">↑</button>
          <button type="button" class="a-rank-move" data-dir="1" aria-label="Move <?= esc($t['team_name']) ?> down">↓</button>
        </span>
      </div>
      <label class="a-visually-hidden" for="writeup-<?= $fid ?>">Write-up for <?= esc($t['team_name']) ?></label>
      <textarea class="a-rank-writeup" id="writeup-<?= $fid ?>" name="writeup[<?= $fid ?>]" maxlength="20000"
                placeholder="Write-up"><?= esc($t['writeup_md'] ?? '') ?></textarea>
    </li>
  <?php endforeach; ?>
  </ol>

  <div class="a-rank-actions">
    <?php if ($published): ?>
    <button class="a-btn" type="submit" name="action" value="save">Save changes</button>
    <button class="a-btn a-btn--ghost" type="submit" name="action" value="unpublish">Unpublish</button>
    <?php else: ?>
    <button class="a-btn a-btn--ghost" type="submit" name="action" value="save">Save draft</button>
    <button class="a-btn" type="submit" name="action" value="publish">Publish rankings</button>
    <?php endif; ?>
  </div>
</form>

<?php if (!$published): ?>
<form method="post" action="/admin/mfl/rankings/delete" class="a-rank-delete"><?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= (int) $ranking['id'] ?>">
  <button class="a-btn a-btn--danger" type="submit" onclick="return confirm('Delete this draft?')">Delete draft</button>
</form>
<?php endif; ?>

<?php if ($revisions): ?>
<h2 class="a-section-head">Save history</h2>
<p class="a-hint">Every change is kept. Loading a version fills the form above; nothing changes until you save.</p>
<table class="a-history">
  <thead><tr><th>When (CT)</th><th>Who</th><th>Action</th><th>Changes</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($revisions as $i => $v): ?>
    <tr>
      <td><?= esc(central_time($v['created_at'], 'M j, g:i a')) ?></td>
      <td><?= esc($v['user_name']) ?></td>
      <td><?= esc(mfl_revision_label($v['action'])) ?></td>
      <td><?= esc($v['summary'] ?: '—') ?></td>
      <td class="a-row-actions">
        <?php if ($i === 0): ?><span class="a-hint">Current</span>
        <?php else: ?><a class="a-link-btn" href="/admin/mfl/rankings/edit?id=<?= (int) $ranking['id'] ?>&amp;revision=<?= (int) $v['id'] ?>">Load</a><?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>
