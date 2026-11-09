<?php
$me = current_user();
$here = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '', '/');
$base = trim(mfl_admin_base(), '/');
$tabs = [$base => 'Overview', "$base/rankings" => 'Power Rankings', "$base/polls" => 'Polls', "$base/awards" => 'Awards'];
if ($me['role'] === 'admin') $tabs["$base/owners"] = 'Owners';
?>
<nav class="a-subnav" aria-label="<?= esc(mfl_league()['short']) ?>">
  <?php foreach ($tabs as $url => $label):
    // A tab stays lit on the screens below it, like the rankings editor.
    $current = $here === $url || ($url !== $base && str_starts_with($here, "$url/")); ?>
  <a href="/<?= esc($url) ?>"<?= $current ? ' aria-current="page"' : '' ?>><?= esc($label) ?></a>
  <?php endforeach; ?>
</nav>
