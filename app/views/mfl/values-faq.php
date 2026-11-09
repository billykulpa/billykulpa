<?php /** @var ?array $run */
$model = mfl_model();
$repl = $run['replacement'] ?? null;
$aging = mfl_aging_table();
$ages = array_keys($aging['RB']);
$firstRound = [];
for ($slot = 1; $slot <= 12; $slot++) $firstRound[$slot] = array_sum($model['picks'][(string) $slot] ?? []);
$weights = array_map(fn($w) => round($w * 100) . '%', MFL_VALUE_WEIGHTS);
$season = (int) ($run['season'] ?? date('Y')); ?>
<section class="hero hero--tight">
  <p class="mono-label"><a class="link" href="/trades">Trades</a> · FAQ</p>
  <h1>How trade values are calculated</h1>
</section>

<div class="faq">
  <h2>What are the two values?</h2>
  <p>Every player and pick gets two numbers, worked out separately so you can see where they disagree.</p>
  <ul>
    <li><b>Model</b> is our own estimate: the fantasy points a player should score above a replacement starter, this season and the next four, under this league's scoring. A pick is worth what its draft slot has produced in the past.</li>
    <li><b>Market</b> is what dynasty managers are actually paying, from <a class="link" href="https://fantasycalc.com" rel="noopener">FantasyCalc</a>, which builds its values from real trades in leagues set up like ours: 12 teams, 1 QB, full PPR.</li>
  </ul>
  <p>The two use different scales, so compare a player's ranks, not the raw numbers. The Model number reads as points: 100 means about 100 points more than a replacement starter, spread over the next five seasons.</p>

  <h2>What is a "replacement starter"?</h2>
  <p>The best players who wouldn't start for anyone in a 12-team league with our lineup (QB, 2 RB, 2 WR, TE, FLEX). They're roughly who you could find on waivers. A player is only worth what he scores above that level, because that's what he adds to a lineup.</p>
  <?php if ($repl): ?>
  <p>Right now, replacement level is <b><?= number_format($repl['QB'], 1) ?></b> points per game at QB, <b><?= number_format($repl['RB'], 1) ?></b> at RB, <b><?= number_format($repl['WR'], 1) ?></b> at WR and <b><?= number_format($repl['TE'], 1) ?></b> at TE.</p>
  <?php endif; ?>

  <h2>Why are quarterbacks worth less than in other leagues?</h2>
  <p>We start one quarterback, so all 12 teams start a good one and the 13th-best is still decent. A quarterback has to score well above that bar to be worth much. Superflex leagues, where teams start two, value them far more.</p>

  <h2>Where does a player's value start?</h2>
  <p>With his scoring rate right now, a blend of three things:</p>
  <ul>
    <li>Sleeper's projections for the rest of the season (half the weight)</li>
    <li>His points per game this season (more weight the more games he's played)</li>
    <li>His points per game last season (a smaller share)</li>
  </ul>
  <p>The rest of this season uses Sleeper's week-by-week projections, so bye weeks and injuries count.</p>

  <h2>How does age factor in?</h2>
  <p>Each later season ages that scoring rate along the curve for his position, built from every NFL season since <?= (int) ($model['seasons'][0] ?? 2012) ?>. The model also expects fewer games each year, more so for older players, based on how often players at that age keep playing.</p>
  <p>Scoring per game by age, as a share of the position's peak:</p>
  <div class="table-scroll">
    <table class="pickups faq-table">
      <thead><tr><th scope="col" class="sticky">Age</th><?php foreach ($ages as $a): ?><th scope="col" class="num"><?= (int) $a ?></th><?php endforeach; ?></tr></thead>
      <tbody>
      <?php foreach ($aging as $pos => $row): $top = max($row); ?>
        <tr><th scope="row" class="sticky"><?= esc($pos) ?></th><?php foreach ($row as $v): ?><td class="num<?= $v === $top ? ' faq-peak' : '' ?>"><?= $v ?>%</td><?php endforeach; ?></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <h2>Doesn't that ignore upside?</h2>
  <p>No. Further-out seasons are treated as a range of outcomes, not one number, and the range widens each year. A young player just below starter level today still has a real chance of becoming one, and that chance is worth something.</p>

  <h2>How much does each season count?</h2>
  <p>Nearer seasons count more, because they're more certain and help you win sooner: the rest of <?= $season ?> counts <?= $weights[0] ?>, then <?= implode(', ', array_slice($weights, 1)) ?> for each season after.</p>

  <h2>How are draft picks valued?</h2>
  <p>By what that draft slot actually produced. For every rookie class from <?= esc(implode(' to ', $model['rookie_classes'] ?? [])) ?>, the model lines up the rookies in the order a dynasty rookie draft would take them, then adds up what each one scored above replacement in his first four seasons.</p>
  <p>Sleeper's rookie draft rankings only go back to about 2021, so classes are ordered by NFL draft position, adjusted the way 1-QB rookie drafts go: quarterbacks much later, running backs much earlier, tight ends a little later. That order matches Sleeper's 2021-25 rookie rankings closely.</p>
  <p>Average points above replacement over a rookie's first four seasons, by first-round slot:</p>
  <div class="table-scroll">
    <table class="pickups faq-table">
      <thead><tr><th scope="col" class="sticky">Pick</th><?php foreach ($firstRound as $slot => $_): ?><th scope="col" class="num">1.<?= str_pad((string) $slot, 2, '0', STR_PAD_LEFT) ?></th><?php endforeach; ?></tr></thead>
      <tbody><tr><th scope="row" class="sticky">Points</th><?php foreach ($firstRound as $v): ?><td class="num"><?= number_format($v, 0) ?></td><?php endforeach; ?></tr></tbody>
    </table>
  </div>
  <p>A future pick's value depends on where it's likely to land. The next draft's order is estimated from this season's record against every team, every week, worst team first, and gets more certain as the season goes on. Drafts after that could land anywhere, so those picks are valued as an average pick in their round.</p>

  <h2>Why does the model like picks less than the market does?</h2>
  <p>Because many picks miss. Plenty of first-rounders never become fantasy starters, and the history counts those misses at zero. Managers tend to price picks by their best-case outcome. That gap is real, and it's one of the more useful things this page shows.</p>

  <h2>How are rookies handled?</h2>
  <p>One partial season says little about a rookie's future, so a player taken in one of the last two league rookie drafts is still partly valued as the pick he was: half in his rookie season, a quarter the year after.</p>

  <h2>What does "Lean" mean?</h2>
  <p>It flags players the two views rank very differently. <b>Model +20</b> means our model ranks him 20 spots higher than the market does, a possible buy. <b>Market +20</b> means the market likes him more, a possible sell. Only big gaps near the top of either list are flagged, since ranks further down swing on tiny differences.</p>

  <h2>How does the trade machine grade a trade?</h2>
  <p>Each team's side is added up both ways. The <b>Model</b> verdict values everything on each team's own <b>timeline</b>, which you set with the slider under each team, from Win now to Rebuild. A contender cares most about this season, a rebuilding team about later ones, so the same players and picks can be worth different amounts to each. When the timelines differ, both teams can come out ahead, which is how good trades happen. The <b>Market</b> verdict has no timeline. It's simply what the market would pay for each side.</p>
  <p>The machine also shows:</p>
  <ul>
    <li><b>Who gets more, season by season.</b> What each team receives in each season, before any timeline weighting. It shows when a trade flips, for example a veteran-for-youth deal that favors one team now and the other in two years.</li>
    <li><b>Starting lineup this season.</b> Each team's best lineup before and after, by current points per game, and who moves in and out of it.</li>
    <li><b>Balance it.</b> The players or picks, by model or by market, that would even the trade up, chosen from the team getting more. It uses a balanced timeline for both teams.</li>
  </ul>
  <p>A trade you build stays in the page address, so you can copy the link and send it to the other owner.</p>

  <h2>What isn't counted?</h2>
  <ul>
    <li>Kickers and defenses.</li>
    <li>Your roster's needs. A third quarterback is worth less to you than to a team that needs one. The trade machine shows how each starting lineup changes, but the values themselves don't depend on who's buying.</li>
    <li>News the projections haven't caught up with, such as a new injury or a depth chart change.</li>
    <li>Players coming back from a long injury are valued on the games they missed, not on how healthy they are now.</li>
  </ul>

  <h2>How often does it update?</h2>
  <p>Values update once a day with the league sync<?php if ($run): ?>, most recently <?= esc(central_time($run['computed_at'], 'M j, g:i a')) ?> CT<?php endif; ?>. The aging curves and pick history are rebuilt once a year, after the NFL season.</p>

  <h2>Where does the data come from?</h2>
  <ul>
    <li><b>Sleeper</b>: stats, projections, ages, rosters and pick ownership.</li>
    <li><b>FantasyCalc</b>: market values.</li>
    <li><b>nflverse</b>: NFL draft order going back to <?= (int) ($model['seasons'][0] ?? 2012) ?>.</li>
  </ul>
  <p>Sleeper's stats and projections and FantasyCalc's values aren't official public services, so a day can occasionally pass without an update.</p>
</div>
