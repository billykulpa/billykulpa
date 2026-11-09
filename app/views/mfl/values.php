<?php /** @var ?array $owner */ /** @var array $f */ /** @var ?array $run */ /** @var array $rows */ /** @var array $picks */ /** @var array $names */
$season = (int) ($run['season'] ?? date('Y'));
$yearLabels = [];
for ($k = 0; $k < MFL_VALUE_YEARS; $k++) $yearLabels[] = $k === 0 ? $season . ' rest' : (string) ($season + $k);
$num = fn($v) => number_format((float) $v, 0); ?>
<section class="hero hero--tight">
  <p class="mono-label"><a class="link" href="/trades">Trades</a><?php if ($run): ?> · Values updated <?= esc(central_time($run['computed_at'], 'M j, g:i a')) ?> CT<?php endif; ?></p>
  <h1>Trade Values</h1>
</section>

<?php if (!$owner): ?>
<div class="voter-strip">
  <span>League owners can sign in to see trade values.</span>
  <a class="poll-vote poll-vote--small" href="/sign-in?next=/trades/values">Sign in</a>
</div>
<?php elseif (!$run): ?>
<p>Values haven't been worked out yet. They update with the daily sync.</p>
<?php else: ?>

<div class="values-intro">
  <p><b>Model</b>: points above a replacement starter, this season and the next four, from each player's current scoring, how players at the position age, and how long they last. Nearer seasons count more. Picks are worth what their draft slot has produced in the past.</p>
  <p><b>Market</b>: what dynasty managers are paying in real trades, from FantasyCalc (12 teams, 1 QB, PPR). <a class="link" href="/trades/values/how">How trade values are calculated</a><?php if (!$run['market']): ?> <i>Today's market prices didn't load, so player market values may be missing.</i><?php endif; ?></p>
</div>

<nav class="era-tabs" aria-label="What to show" data-swap>
  <a href="<?= esc(mfl_values_url($f, ['view' => 'players'])) ?>"<?= $f['view'] === 'players' ? ' aria-current="page"' : '' ?>>Players</a>
  <a href="<?= esc(mfl_values_url($f, ['view' => 'picks'])) ?>"<?= $f['view'] === 'picks' ? ' aria-current="page"' : '' ?>>Draft picks</a>
</nav>

<?php if ($f['view'] === 'players'): ?>
<form class="filters" method="get" action="/trades/values" data-autosubmit>
  <input type="hidden" name="sort" value="<?= esc($f['sort']) ?>">
  <input type="hidden" name="dir" value="<?= esc($f['dir']) ?>">
  <fieldset class="pill-group">
    <legend class="mono-label">Position</legend>
    <?php foreach (MFL_VALUE_POSITIONS as $p): ?>
    <label class="pill"><input type="checkbox" name="pos[]" value="<?= esc($p) ?>"<?= in_array($p, $f['pos'], true) ? ' checked' : '' ?>><span><?= esc($p) ?></span></label>
    <?php endforeach; ?>
  </fieldset>
  <div class="filter-row">
    <div>
      <label class="mono-label" for="values-team">Team</label>
      <select id="values-team" name="team">
        <option value="">All rostered players</option>
        <?php foreach ($names as $id => $name): ?><option value="<?= (int) $id ?>"<?= $f['team'] === (string) $id ? ' selected' : '' ?>><?= esc($name) ?></option><?php endforeach; ?>
        <option value="fa"<?= $f['team'] === 'fa' ? ' selected' : '' ?>>Free agents</option>
      </select>
    </div>
    <button class="poll-vote poll-vote--small" type="submit">Apply</button>
  </div>
</form>

<section>
  <div class="table-scroll">
    <table class="pickups values-table">
      <thead>
        <tr class="trade-groups">
          <th scope="col" class="sticky" colspan="2"></th>
          <th scope="colgroup" colspan="3"></th>
          <th scope="colgroup" colspan="3" class="group-start">Value</th>
          <th scope="colgroup" colspan="<?= MFL_VALUE_YEARS ?>" class="group-start">Model: points above replacement</th>
        </tr>
        <tr>
          <th scope="col" class="num">#</th>
          <?= mfl_values_th($f, 'name', 'Player', 'sticky') ?>
          <th scope="col">Pos</th>
          <?= mfl_values_th($f, 'age', 'Age') ?>
          <th scope="col">MFL team</th>
          <?= mfl_values_th($f, 'model', 'Model', 'num group-start') ?>
          <?= mfl_values_th($f, 'market', 'Market') ?>
          <?= mfl_values_th($f, 'lean', 'Lean', '') ?>
          <?php foreach ($yearLabels as $k => $label): ?><?= mfl_values_th($f, "s$k", $label, 'num' . ($k === 0 ? ' group-start' : '')) ?><?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $i => $r): $lean = $r['lean_label']; ?>
        <tr>
          <td class="num"><?= $i + 1 ?></td>
          <td class="wrap sticky"><span class="team"><?= mfl_player_link($r['player_id'], $r['name']) ?></span>
            <span class="row-note"><?= esc($r['nfl_team'] ?: 'No NFL team') ?><?= $r['injury'] !== '' ? ' · ' . esc($r['injury']) : '' ?> · <?= number_format((float) $r['ppg'], 1) ?> pts/gm</span></td>
          <td><?= esc($r['position']) ?></td>
          <td class="num"><?= $r['age'] !== null ? number_format($r['age'], 1) : '—' ?></td>
          <td><?= $r['holder'] ? mfl_team_link($r['holder'], $names[$r['holder']] ?? '') : '<span class="row-note">Free agent</span>' ?></td>
          <td class="num group-start"><b><?= $num($r['model_value']) ?></b><?php if ($r['model_rank']): ?> <span class="row-note">#<?= $r['model_rank'] ?></span><?php endif; ?></td>
          <td class="num"><?= $r['market_value'] !== null ? $num($r['market_value']) . ' <span class="row-note">#' . $r['market_rank'] . '</span>' : '<span class="row-note">—</span>' ?></td>
          <td><?php if ($lean !== ''): ?><span class="lean lean--<?= str_starts_with($lean, 'Model') ? 'model' : 'market' ?>"><?= esc($lean) ?></span><?php endif; ?></td>
          <?php foreach ($r['par'] as $k => $v): ?><td class="num<?= $k === 0 ? ' group-start' : '' ?>"><?= $num($v) ?></td><?php endforeach; ?>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if (!$rows): ?><p>No players match.</p><?php endif; ?>
  <p class="note"><b>Lean</b> shows how many spots higher one view ranks a player than the other, when they disagree by a lot. Model +20 means the model ranks him 20 spots above the market: a possible buy. Market +20 is a possible sell.</p>
</section>

<?php else:
usort($picks, fn($a, $b) => [$a['season'], $a['round'], $a['expected_slot']] <=> [$b['season'], $b['round'], $b['expected_slot']]); ?>
<section>
  <div class="table-scroll">
    <table class="pickups values-table">
      <thead><tr>
        <th scope="col" class="sticky">Pick</th>
        <th scope="col">Likely slot</th>
        <th scope="col">Owner</th>
        <th scope="col" class="num">Model</th>
        <th scope="col" class="num">Market</th>
        <th scope="col">Taken near this slot before <span class="row-note">(pts above replacement, first 4 years)</span></th>
      </tr></thead>
      <tbody>
      <?php foreach ($picks as $p): ?>
        <tr>
          <td class="wrap sticky"><span class="team"><?= (int) $p['season'] ?> round <?= (int) $p['round'] ?></span>
            <span class="row-note"><?= esc($names[$p['original']] ?? '') ?>'s</span></td>
          <td><?= esc(mfl_pick_range($p)) ?></td>
          <td><?= mfl_team_link($p['owner'], $names[$p['owner']] ?? '') ?></td>
          <td class="num"><b><?= $num($p['model_value']) ?></b></td>
          <td class="num"><?= $p['market_value'] !== null ? $num($p['market_value']) : '<span class="row-note">—</span>' ?></td>
          <td class="wrap"><span class="row-note"><?= esc(implode(', ', array_map(fn($e) => "{$e['name']} ({$e['season']}, {$e['par']})", mfl_pick_examples($p)))) ?></span></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <p class="note">The next draft's order is estimated from this season's record against every team, every week, worst team first. Later drafts could land anywhere, so they're valued as an average pick in the round.</p>
</section>
<?php endif; ?>

<p class="values-how"><a class="link" href="/trades/values/how">How trade values are calculated</a></p>
<?php endif; ?>
