<?php /** @var string $error */ /** @var string $ok */ ?>
<div class="a-auth">
  <h1>Portrait</h1>
  <?php if ($error): ?><div class="a-flash a-flash--err"><?= esc($error) ?></div><?php endif; ?>
  <?php if ($ok): ?><div class="a-flash a-flash--ok"><?= esc($ok) ?></div><?php endif; ?>

  <p class="a-sub">This photo runs on the <a href="/about" target="_blank" rel="noopener" style="text-decoration: underline">About page</a>.
  Whatever you upload gets center-cropped to a square, so lead with your face in the middle.
  JPEG, PNG, WEBP, or GIF, up to 15&nbsp;MB.</p>

  <figure class="a-portrait-preview">
    <div class="a-portrait-frame">
      <img src="<?= esc(portrait_url()) ?>" alt="Current portrait">
    </div>
    <figcaption class="a-sub">Current portrait</figcaption>
  </figure>

  <form method="post" action="/admin/portrait" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <label class="a-label" for="portrait">New portrait</label>
    <input type="file" id="portrait" name="portrait" accept="image/jpeg,image/png,image/webp,image/gif" required>
    <button class="a-btn" type="submit">Upload portrait</button>
  </form>
</div>
