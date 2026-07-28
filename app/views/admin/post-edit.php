<?php /** @var array $post */ /** @var bool $saved */ /** @var string $error */ ?>
<h1><?= $post['id'] ? 'Edit post' : 'New post' ?></h1>
<?php if ($saved): ?><div class="a-flash a-flash--ok">Saved.
  <?php if ($post['status'] === 'published'): ?>
    <a href="/notes/<?= esc($post['slug']) ?>" target="_blank" rel="noopener" style="text-decoration:underline">View live &nearr;</a>
  <?php endif; ?></div><?php endif; ?>
<?php if ($error): ?><div class="a-flash a-flash--err"><?= esc($error) ?></div><?php endif; ?>

<form method="post" action="/admin/posts/<?= $post['id'] ? 'edit?id=' . (int) $post['id'] : 'new' ?>">
  <?= csrf_field() ?>

  <label class="a-label" for="title">Title</label>
  <input type="text" id="title" name="title" value="<?= esc($post['title']) ?>" required maxlength="255">

  <div class="a-grid-2">
    <div>
      <label class="a-label" for="slug">Slug</label>
      <input type="text" id="slug" name="slug" value="<?= esc($post['slug']) ?>" maxlength="190" placeholder="left blank = generated from title">
    </div>
    <div>
      <label class="a-label" for="status">Status</label>
      <select id="status" name="status">
        <option value="draft" <?= $post['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
        <option value="published" <?= $post['status'] === 'published' ? 'selected' : '' ?>>Published</option>
      </select>
    </div>
  </div>

  <label class="a-label" for="body_md">Body (Markdown)</label>
  <textarea class="a-body" id="body_md" name="body_md" spellcheck="true"><?= esc($post['body_md']) ?></textarea>
  <p class="a-hint"># heading &middot; **bold** &middot; *italic* &middot; [link](url) &middot; ![image](src) &middot; - list &middot; &gt; quote &middot; ``` code fence</p>

  <div class="a-grid-2">
    <div>
      <label class="a-label" for="meta_title">Meta title</label>
      <input type="text" id="meta_title" name="meta_title" value="<?= esc($post['meta_title']) ?>" maxlength="255" placeholder="left blank = post title">
    </div>
    <div>
      <label class="a-label" for="meta_description">Meta description</label>
      <textarea id="meta_description" name="meta_description" maxlength="500" style="min-height:80px"><?= esc($post['meta_description']) ?></textarea>
    </div>
  </div>

  <button class="a-btn" type="submit">Save</button>
</form>
