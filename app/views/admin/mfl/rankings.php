<?php /** @var ?array $flash */ /** @var array $user */ /** @var array $rankings */ /** @var array $seasons */ /** @var int $nextWeek */ ?>
<h1>Power Rankings</h1>
<?php require __DIR__ . '/_subnav.php'; ?>
<?php if ($flash): ?><div class="a-flash a-flash--<?= $flash['status'] === 'ok' ? 'ok' : 'err' ?>"><?= esc($flash['summary']) ?></div><?php endif; ?>

<form method="post" action="/admin/mfl/rankings/new" class="a-inline-fields"><?= csrf_field() ?>
  <div>
    <label class="a-label" for="season">Season</label>
    <select id="season" name="season">
      <?php foreach ($seasons as $s): ?><option value="<?= (int) $s ?>"><?= (int) $s ?></option><?php endforeach; ?>
    </select>
  </div>
  <div>
    <label class="a-label" for="week">Week</label>
    <select id="week" name="week">
      <?php for ($w = 0; $w <= MFL_FINAL_WEEK; $w++): ?>
      <option value="<?= $w ?>" <?= $w === $nextWeek ? 'selected' : '' ?>><?= esc(mfl_week_label($w)) ?></option>
      <?php endfor; ?>
    </select>
  </div>
  <button class="a-btn" type="submit">New rankings</button>
</form>

<?php if ($rankings): ?>
<table class="a-rank-table">
  <thead><tr><th>Rankings</th><th>Author</th><th>Status</th><th>Published (CT)</th></tr></thead>
  <tbody>
  <?php foreach ($rankings as $r):
    $canEdit = $user['role'] === 'admin' || (int) $r['author_user_id'] === (int) $user['id']; ?>
    <tr>
      <td>
        <?php if ($canEdit): ?><a class="a-rowlink" href="/admin/mfl/rankings/edit?id=<?= (int) $r['id'] ?>"><?= esc(mfl_ranking_title($r)) ?></a>
        <?php else: ?><?= esc(mfl_ranking_title($r)) ?><?php endif; ?>
        <div class="a-hint"><?= (int) $r['season'] ?> · <?= esc(mfl_week_label((int) $r['week'])) ?></div>
      </td>
      <td><?= esc($r['author_name']) ?></td>
      <td><span class="a-pill a-pill--<?= esc($r['status']) ?>"><?= esc($r['status']) ?></span></td>
      <td><?= $r['published_at'] ? esc(central_time($r['published_at'], 'M j, Y')) : '—' ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php else: ?>
<p class="a-hint">No rankings yet.</p>
<?php endif; ?>
