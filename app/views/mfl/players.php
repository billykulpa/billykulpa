<?php /** @var array $rows */ /** @var array $f */ /** @var array $cards */ /** @var array $seasons */ /** @var array $names */ /** @var array $holders */
$metric = $f['counted'] ? 'starter' : 'total';
$shown = $f['all'] ? $rows : array_slice($rows, 0, MFL_PLAYER_LIST_LIMIT); ?>
<section class="hero hero--tight">
  <p class="mono-label">All-time</p>
  <h1>Players</h1>
</section>

<nav class="era-tabs" aria-label="Which points to count" data-swap>
  <a href="<?= esc(mfl_players_url($f, ['counted' => false, 'sort' => 'total', 'dir' => 'desc'])) ?>"<?= !$f['counted'] ? ' aria-current="page"' : '' ?>>All points</a>
  <a href="<?= esc(mfl_players_url($f, ['counted' => true, 'sort' => 'starter', 'dir' => 'desc'])) ?>"<?= $f['counted'] ? ' aria-current="page"' : '' ?>>Points that counted</a>
</nav>

<?php if ($cards): ?>
<section class="player-cards" aria-label="Leaders">
  <?php foreach ($cards as $label => $p): ?>
  <a class="player-card" href="/players/<?= esc(rawurlencode($p['id'])) ?>">
    <span class="mono-label"><?= esc($label) ?></span>
    <b><?= number_format($p[$metric], 2) ?></b>
    <span class="player-card-name"><?= esc($p['name']) ?></span>
  </a>
  <?php endforeach; ?>
</section>
<?php endif; ?>

<form class="filters" method="get" action="/players" data-autosubmit>
  <?php if ($f['counted']): ?><input type="hidden" name="counted" value="1"><?php endif; ?>
  <?php if ($f['all']): ?><input type="hidden" name="all" value="1"><?php endif; ?>
  <input type="hidden" name="sort" value="<?= esc($f['sort']) ?>">
  <input type="hidden" name="dir" value="<?= esc($f['dir']) ?>">
  <fieldset class="pill-group">
    <legend class="mono-label">Position</legend>
    <?php foreach (MFL_PLAYER_POSITIONS as $p): ?>
    <label class="pill"><input type="checkbox" name="pos[]" value="<?= esc($p) ?>"<?= in_array($p, $f['pos'], true) ? ' checked' : '' ?>><span><?= esc($p) ?></span></label>
    <?php endforeach; ?>
    <button class="poll-vote poll-vote--small" type="submit">Apply</button>
  </fieldset>
</form>

<section>
  <div class="table-scroll">
    <table class="pickups">
      <thead><tr>
        <th scope="col" class="num">#</th>
        <?= mfl_players_th($f, 'name', 'Player', 'sticky') ?>
        <th scope="col">Pos</th>
        <th scope="col">Now on</th>
        <?php if ($f['counted']): ?>
        <?= mfl_players_th($f, 'starter', 'As starter') ?>
        <?= mfl_players_th($f, 'starts', 'Starts') ?>
        <?php else: ?>
        <?= mfl_players_th($f, 'total', 'Total points') ?>
        <?= mfl_players_th($f, 'starter', 'As starter') ?>
        <?= mfl_players_th($f, 'bench', 'Bench') ?>
        <?= mfl_players_th($f, 'free_agent', 'Free agent') ?>
        <?php endif; ?>
        <?php foreach ($seasons as $y): ?><?= mfl_players_th($f, "s$y", (string) $y) ?><?php endforeach; ?>
      </tr></thead>
      <tbody>
      <?php foreach ($shown as $i => $p): $fid = $holders[$p['id']] ?? null; ?>
        <tr>
          <td class="num"><?= $i + 1 ?></td>
          <td class="wrap sticky"><span class="team"><?= mfl_player_link($p['id'], $p['name']) ?></span></td>
          <td><?= esc($p['position']) ?></td>
          <td><?= $fid ? mfl_team_link($fid, $names[$fid] ?? '') : '<span class="row-note">Free agent</span>' ?></td>
          <?php if ($f['counted']): ?>
          <td class="num"><?= number_format($p['starter'], 2) ?></td>
          <td class="num"><?= (int) $p['starts'] ?></td>
          <?php else: ?>
          <td class="num"><?= number_format($p['total'], 2) ?></td>
          <td class="num"><?= number_format($p['starter'], 2) ?></td>
          <td class="num"><?= number_format($p['bench'], 2) ?></td>
          <td class="num"><?= number_format($p['free_agent'], 2) ?></td>
          <?php endif; ?>
          <?php foreach ($seasons as $y): ?><td class="num"><?= number_format($p['seasons'][$y][$metric] ?? 0, 2) ?></td><?php endforeach; ?>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if (!$f['all'] && count($rows) > MFL_PLAYER_LIST_LIMIT): ?>
  <p class="note">Showing the first <?= MFL_PLAYER_LIST_LIMIT ?> of <?= number_format(count($rows)) ?> players. <a class="link" href="<?= esc(mfl_players_url($f, ['all' => true])) ?>">Show all</a></p>
  <?php endif; ?>
  <p class="note"><?= $f['counted'] ? 'Points that counted are points in a starting lineup, in the regular season or on the title path of the playoffs. Season columns show the same.' : 'Total points is everything a player scored under league scoring since 2025. As starter is points that counted, in the regular season or on the title path of the playoffs. Bench is points on a roster that didn\'t count, including consolation games. Free agent is weeks on no MFL roster.' ?> Current seasons are still in progress.</p>
</section>
