<?php /** @var array $accounts */ ?>
<h1>Accounts</h1>
<p class="a-hint">Sign-ins for this site's CMS. Each league keeps its own accounts: manage them under Members in the
  <a class="a-link-btn" href="<?= esc(MFL_BASE_URL) ?>/admin/members">MFL admin</a> or the
  <a class="a-link-btn" href="https://ssffl.billykulpa.com/admin/members">SSFFL admin</a>.</p>

<table>
  <thead><tr><th>Name</th><th>Email</th><th>Type</th></tr></thead>
  <tbody>
  <?php foreach ($accounts as $a): ?>
    <tr>
      <td><?= esc($a['display_name'] ?: '—') ?></td>
      <td><?= esc($a['email']) ?></td>
      <td><span class="a-pill a-pill--draft"><?= $a['role'] === 'admin' ? 'Admin' : 'Old MFL Member (moved to the MFL admin)' ?></span></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
