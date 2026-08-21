<?php /** @var array $posts */ ?>
<div class="a-toolbar">
  <h1 style="margin:0">Notes</h1>
  <a class="a-btn" href="/admin/posts/new">New note</a>
</div>
<?php if (!$posts): ?>
  <p class="a-sub">No notes yet.</p>
<?php else: ?>
<div class="a-table-scroll">
<table>
  <thead><tr><th>Title</th><th>Status</th><th>Published</th><th></th></tr></thead>
  <tbody>
    <?php foreach ($posts as $p): ?>
    <tr>
      <td><a class="a-rowlink" href="/admin/posts/edit?id=<?= (int) $p['id'] ?>"><?= esc($p['title']) ?></a><br>
          <span class="a-count">/notes/<?= esc($p['slug']) ?></span></td>
      <td><span class="a-pill a-pill--<?= esc($p['status']) ?>"><?= esc($p['status']) ?></span></td>
      <td class="a-count"><?= esc(nice_date($p['published_at'])) ?></td>
      <td style="text-align:right">
        <form method="post" action="/admin/posts/delete" onsubmit="return confirm('Delete this note permanently?')">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
          <button class="a-btn a-btn--danger" type="submit">Delete</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php endif; ?>
