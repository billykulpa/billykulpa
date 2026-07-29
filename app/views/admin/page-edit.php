<?php /** @var array $pg */ /** @var bool $saved */ ?>
<h1>Edit: <?= esc($pg['label']) ?></h1>
<p class="a-sub">/<?= esc($pg['slug']) ?></p>
<?php if ($saved): ?><div class="a-flash a-flash--ok">Saved.</div><?php endif; ?>
<form method="post" action="/admin/pages/edit?id=<?= (int) $pg['id'] ?>">
  <?= csrf_field() ?>

  <h2 class="a-section-head">On the page</h2>
  <p class="a-hint">What visitors read. These render in the page itself.</p>

  <label class="a-label" for="h1">Headline (H1)</label>
  <input type="text" id="h1" name="h1" value="<?= esc($pg['h1']) ?>" maxlength="255">

  <label class="a-label" for="lede">Lede (the opening text block under the headline)</label>
  <textarea id="lede" name="lede" maxlength="1000"><?= esc($pg['lede'] ?? '') ?></textarea>
  <p class="a-hint">Used on pages with an intro paragraph (currently the home hero). Inline markdown works: [links](https://example.com), **bold**, *italics*. Leave blank to fall back to the built-in copy.</p>

  <h2 class="a-section-head">Search and social</h2>
  <p class="a-hint">What Google and link previews show. Invisible on the page itself.</p>

  <label class="a-label" for="meta_title">Meta title <span class="a-count" data-count-for="meta_title"></span></label>
  <input type="text" id="meta_title" name="meta_title" value="<?= esc($pg['meta_title']) ?>" maxlength="255">
  <p class="a-hint">Browser tab and search-result title. Aim for &le; 60 characters.</p>

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
