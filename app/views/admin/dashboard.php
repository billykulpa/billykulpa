<?php /** @var array $user */ /** @var int $pageCount */ /** @var int $caseCount */ /** @var int $postCount */ /** @var int $drafts */ ?>
<h1>Dashboard</h1>

<h2 class="a-section-head a-first">Site</h2>
<div class="a-cards">
  <a class="a-card" href="/admin/pages"><b><?= $pageCount ?></b><span>Editable pages</span></a>
  <a class="a-card" href="/admin/case-studies"><b><?= $caseCount ?></b><span>Case studies</span></a>
</div>

<h2 class="a-section-head">Notes</h2>
<div class="a-cards">
  <a class="a-card" href="/admin/posts"><b><?= $postCount ?></b><span>Posts</span></a>
  <a class="a-card" href="/admin/posts"><b><?= $drafts ?></b><span>Drafts</span></a>
</div>

<p class="a-sub">Signed in as <?= esc($user['email']) ?>. Edit page headlines and meta under
<a href="/admin/pages" style="text-decoration: underline">Pages</a>; case-study heroes under
<a href="/admin/case-studies" style="text-decoration: underline">Case Studies</a>; write under
<a href="/admin/posts" style="text-decoration: underline">Posts</a>.</p>
