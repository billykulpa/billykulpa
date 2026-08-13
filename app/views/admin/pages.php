<?php /** @var array $pages */ ?>
<h1><?= esc($heading ?? 'Pages') ?></h1>
<div class="a-table-scroll">
<table>
  <thead><tr><th>Page</th><th>H1</th><th>Meta title</th><th>Updated</th></tr></thead>
  <tbody>
    <?php foreach ($pages as $p): ?>
    <tr>
      <td><a class="a-rowlink" href="/admin/pages/edit?id=<?= (int) $p['id'] ?>"><?= esc($p['label']) ?></a></td>
      <td><?= esc(mb_strimwidth($p['h1'], 0, 44, '…')) ?></td>
      <td><?= esc(mb_strimwidth($p['meta_title'], 0, 44, '…')) ?></td>
      <td class="a-count"><?= esc(nice_date($p['updated_at'])) ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>
