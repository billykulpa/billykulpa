<?php
$me = current_user();
$here = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '', '/');
$tabs = ['/admin/mfl' => 'Overview', '/admin/mfl/rankings' => 'Power Rankings'];
if ($me['role'] === 'admin') $tabs['/admin/mfl/owners'] = 'Owners';
?>
<nav class="a-subnav" aria-label="MFL">
  <?php foreach ($tabs as $url => $label):
    // The rankings tab stays lit inside the editor, which sits below it.
    $current = $here === ltrim($url, '/') || ($url === '/admin/mfl/rankings' && str_starts_with($here, 'admin/mfl/rankings/')); ?>
  <a href="<?= esc($url) ?>"<?= $current ? ' aria-current="page"' : '' ?>><?= esc($label) ?></a>
  <?php endforeach; ?>
</nav>
