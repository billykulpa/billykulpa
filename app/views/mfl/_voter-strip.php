<?php /** @var array $polls The polls shown on this page. */
$owner = mfl_current_owner();
$canVote = (bool) array_filter($polls, fn ($p) => !$p['show_results']);
if ($owner): ?>
<form class="voter-strip" method="post" action="/sign-out">
  <span>Voting as <b><?= esc(mfl_owner_label($owner['display_name'], $owner['real_name'])) ?></b></span>
  <button class="sort-button" type="submit">Sign out</button>
</form>
<?php elseif ($canVote): ?>
<div class="voter-strip">
  <span>League owners can sign in to vote.</span>
  <a class="poll-vote poll-vote--small" href="/sign-in">Sign in</a>
</div>
<?php endif; ?>
