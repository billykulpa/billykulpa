<?php /** @var ?array $flash */ /** @var array $awards */ /** @var array $notes */ /** @var int $season */ /** @var array $seasons */ ?>
<h1><?= esc(mfl_award_title($season)) ?></h1>
<?php require __DIR__ . '/_subnav.php'; ?>
<?php if ($flash): ?><div class="a-flash a-flash--<?= $flash['status'] === 'ok' ? 'ok' : 'err' ?>"><?= esc($flash['summary']) ?></div><?php endif; ?>
<p class="a-hint">Winners come from the league data and update on their own. Add a comment to any award. Formatting: **bold**, *italic*, [link](https://…).</p>
<?php if (count($seasons) > 1): ?>
<p class="a-hint">Season: <?php foreach ($seasons as $s): ?><a class="a-link-btn" href="/admin/mfl/awards?season=<?= (int) $s ?>"><?= (int) $s ?></a> <?php endforeach; ?></p>
<?php endif; ?>

<form method="post" action="/admin/mfl/awards?season=<?= $season ?>" data-autosave="awards-<?= $season ?>"<?= ($flash['status'] ?? '') === 'ok' ? ' data-saved' : '' ?>><?= csrf_field() ?>
  <?php foreach ($awards as $a): ?>
  <div class="a-award">
    <label class="a-label" for="note-<?= esc($a['key']) ?>"><?= esc($a['title']) ?></label>
    <p class="a-hint"><?= strip_tags(implode(' & ', $a['winners'])) ?> · <?= esc($a['value']) ?></p>
    <textarea id="note-<?= esc($a['key']) ?>" name="note[<?= esc($a['key']) ?>]" maxlength="2000" placeholder="Comment (optional)"><?= esc($notes[$a['key']] ?? '') ?></textarea>
  </div>
  <?php endforeach; ?>
  <button class="a-btn" type="submit">Save comments</button>
</form>
