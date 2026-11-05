<?php /** @var int $season */ /** @var array $seasons */ /** @var ?int $team */ /** @var array $rounds */ /** @var array $teams */ ?>
<section class="hero hero--tight">
  <p class="mono-label">Draft history</p>
  <h1><?= $season ?> Draft</h1>
</section>
<nav class="era-tabs" aria-label="Draft years">
  <?php foreach ($seasons as $s): ?><a href="/drafts/<?= (int) $s ?>"<?= (int) $s === $season ? ' aria-current="page"' : '' ?>><?= (int) $s ?></a><?php endforeach; ?>
</nav>
<form class="trend-controls" method="get" action="/drafts/<?= $season ?>">
  <label class="mono-label" for="draft-team">Team</label>
  <select id="draft-team" name="team">
    <option value="">All teams</option>
    <?php foreach ($teams as $t): ?><option value="<?= (int) $t['franchise_id'] ?>"<?= $team === (int) $t['franchise_id'] ? ' selected' : '' ?>><?= esc($t['team_name']) ?></option><?php endforeach; ?>
  </select>
  <button class="poll-vote poll-vote--small" type="submit">Show</button>
</form>
<?php if (!$rounds): ?><p>No picks for that team in this draft.</p><?php endif; ?>
<?php foreach ($rounds as $round => $picks): ?>
<section>
  <h2>Round <?= (int) $round ?></h2>
  <div class="table-scroll">
    <table>
      <thead><tr><th scope="col">Pick</th><th scope="col" class="sticky">Player</th><th scope="col">Pos</th><th scope="col">NFL</th><th scope="col">Team</th></tr></thead>
      <tbody>
      <?php foreach ($picks as $p): ?>
        <tr>
          <td><?= esc(mfl_pick_label($p)) ?></td>
          <td class="team"><?= esc($p['player_name']) ?></td>
          <td><?= esc($p['position']) ?></td>
          <td><?= esc($p['nfl_team']) ?></td>
          <td><?= mfl_team_link((int) $p['franchise_id'], (string) $p['team_name']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<?php endforeach; ?>
