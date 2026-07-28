<?php /** @var array $pg */ /** @var bool $saved */ ?>
<h1>Edit: <?= esc($pg['label']) ?></h1>
<p class="a-sub">/<?= esc($pg['slug']) ?></p>
<?php if ($saved): ?><div class="a-flash a-flash--ok">Saved.</div><?php endif; ?>
<form method="post" action="/admin/pages/edit?id=<?= (int) $pg['id'] ?>">
  <?= csrf_field() ?>

  <label class="a-label" for="h1">H1 (the on-page headline)</label>
  <input type="text" id="h1" name="h1" value="<?= esc($pg['h1']) ?>" maxlength="255">

  <label class="a-label" for="meta_title">Meta title <span class="a-count" data-count-for="meta_title"></span></label>
  <input type="text" id="meta_title" name="meta_title" value="<?= esc($pg['meta_title']) ?>" maxlength="255">
  <p class="a-hint">Shown in the browser tab and search results. Aim for &le; 60 characters.</p>

  <label class="a-label" for="meta_description">Meta description <span class="a-count" data-count-for="meta_description"></span></label>
  <textarea id="meta_description" name="meta_description" maxlength="500"><?= esc($pg['meta_description']) ?></textarea>
  <p class="a-hint">Search-result snippet. Aim for 140&ndash;160 characters.</p>

  <button class="a-btn" type="submit">Save</button>
</form>
<script>
document.querySelectorAll('[data-count-for]').forEach(function (el) {
  var input = document.getElementById(el.dataset.countFor);
  var update = function () { el.textContent = '(' + input.value.length + ' chars)'; };
  input.addEventListener('input', update); update();
});
</script>
