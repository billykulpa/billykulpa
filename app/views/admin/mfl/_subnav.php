<?php
$me = current_user();
$here = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '', '/');
$tabs = ['admin/mfl' => 'Overview', 'admin/mfl/rankings' => 'Power Rankings', 'admin/mfl/polls' => 'Polls', 'admin/mfl/pickups' => 'Best Pickups'];
if ($me['role'] === 'admin') $tabs['admin/mfl/owners'] = 'Owners';
?>
<nav class="a-subnav" aria-label="MFL">
  <?php foreach ($tabs as $url => $label):
    // A tab stays lit on the screens below it, like the rankings editor.
    $current = $here === $url || ($url !== 'admin/mfl' && str_starts_with($here, "$url/")); ?>
  <a href="/<?= esc($url) ?>"<?= $current ? ' aria-current="page"' : '' ?>><?= esc($label) ?></a>
  <?php endforeach; ?>
</nav>
