<?php $me = current_user(); ?>
<nav class="a-subnav" aria-label="MFL">
  <a href="/admin/mfl">Overview</a>
  <a href="/admin/mfl/rankings">Power Rankings</a>
  <?php if ($me['role'] === 'admin'): ?><a href="/admin/mfl/owners">Owners</a><?php endif; ?>
</nav>
