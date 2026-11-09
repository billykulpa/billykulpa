<?php /** @var ?array $run */
$model = mfl_model();
$rules = mfl_current_rules();
$depth = $run['depth'] ?? mfl_model_depth($rules);
$deep = !empty($depth['waiver']);
$lineupLabel = mfl_lineup_label($rules['lineup']);
$superflex = $rules['qbs'] === 2;
$repl = $run['replacement'] ?? null;
$aging = mfl_aging_table();
$ages = array_keys($aging['RB']);
$firstRound = [];
for ($slot = 1; $slot <= $rules['teams']; $slot++) $firstRound[$slot] = array_sum($model['picks'][(string) $slot] ?? []);
$weights = array_map(fn($w) => round($w * 100) . '%', MFL_VALUE_WEIGHTS);
$season = (int) ($run['season'] ?? date('Y'));
$anchors = $run ? mfl_value_anchors() : [];
$tierNames = [6 => 'Franchise player', 12 => 'Top starter', 24 => 'Every-week starter', 50 => 'Solid starter or rising young player', 100 => 'Flex, depth or a long-shot bet']; ?>
<section class="hero hero--tight">
  <p class="mono-label"><a class="link" href="/trades">Trades</a> · FAQ</p>
  <h1>How trade values are calculated</h1>
</section>

<div class="faq">
  <h2>What are the two values?</h2>
  <p>Every player and pick gets two numbers, worked out separately so you can see where they disagree.</p>
  <ul>
    <li><b>Model</b> is our own estimate: the fantasy points a player should score above a replacement starter, this season and the next four, under this league's scoring. A pick is worth what its draft slot has produced in the past.</li>
    <li><b>Market</b> is what dynasty managers are actually paying, from <a class="link" href="https://fantasycalc.com" rel="noopener">FantasyCalc</a>, which builds its values from real trades in leagues set up like ours: <?= esc(mfl_market_label($rules, true)) ?>.</li>
  </ul>
  <p>The two use different scales, so compare a player's ranks, not the raw numbers. The next answer shows how to read each one.</p>

  <h2>How should I read the numbers?</h2>
  <p><b>The Model number is points.</b> A value of 100 means about 100 more points than a replacement starter would give you, added up over the next five seasons, with nearer seasons counting more. For a sense of size: a player 6 points per game better than replacement who plays all 17 games adds about 100 points in that season alone.</p>
  <p><b>The Market number has no unit.</b> It's FantasyCalc's trade scale, where bigger means more expensive. Only compare it to other Market numbers.</p>
  <?php if (!empty($anchors['tiers'])): ?>
  <p>Where today's values fall, across every player the site values:</p>
  <div class="table-scroll">
    <table class="pickups faq-table faq-tiers">
      <thead><tr><th scope="col" class="sticky">Rank</th><th scope="col">Roughly</th><th scope="col" class="num">Model</th><th scope="col" class="num">Market</th></tr></thead>
      <tbody>
      <?php foreach ($anchors['tiers'] as $rank => $t): ?>
        <tr><th scope="row" class="sticky">#<?= (int) $rank ?></th><td><?= esc($tierNames[$rank] ?? '') ?></td>
          <td class="num"><?= $t['model'] !== null ? number_format($t['model']) : '—' ?></td>
          <td class="num"><?= $t['market'] !== null ? number_format($t['market']) : '—' ?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php $top = reset($anchors['tiers']); ?>
  <p class="note">Read each row as a cutoff: a Model value of <?= number_format((float) $top['model']) ?> or more is a top-<?= (int) array_key_first($anchors['tiers']) ?> player.</p>
  <?php endif; ?>
  <?php if (!empty($anchors['picks'])): ?>
  <p>Where first-round picks land among players:</p>
  <ul>
    <?php foreach ($anchors['picks'] as $p): ?>
    <li><b><?= esc($p['label']) ?></b>: Model <?= number_format($p['model']) ?> (like the #<?= (int) $p['model_rank'] ?> player)<?php if ($p['market'] !== null): ?>, Market <?= number_format($p['market']) ?> (like the #<?= (int) $p['market_rank'] ?> player)<?php endif; ?></li>
    <?php endforeach; ?>
  </ul>
  <?php endif; ?>
  <p>A few rules of thumb:</p>
  <ul>
    <li><b>Small gaps are noise.</b> Every number here is an estimate. Two players within about 10% of each other are a coin flip.</li>
    <?php if ($deep): ?>
    <li><b>Values add up; lineups don't.</b> Two players worth 100 each aren't the same as one worth 200, because only so many can start. Bench players do count for something here, since rosters run deep enough that waivers are thin (see <a class="link" href="#depth">bench depth</a>), but the team getting the best player in a 2-for-1 still usually wins it. The trade machine's Starting lineup panel shows this.</li>
    <?php else: ?>
    <li><b>Values add up; lineups don't.</b> Two players worth 100 each aren't the same as one worth 200, because only so many can start. Depth matters less than it looks, and the team getting the best player in a 2-for-1 usually wins it. The trade machine's Starting lineup panel shows this.</li>
    <?php endif; ?>
    <li><b>The market is the price, the model is the production.</b> The market tells you what you could get for a player today. The model tells you what he's likely to score. Where the model is higher, he may be a buy. Where the market is higher, he may be a sell.</li>
    <li><b>Your timeline changes the answer.</b> A contender should care most about the next few months; a rebuilding team about the years after. Set the timeline in the trade machine, and the Model numbers follow.</li>
    <li><b>Picks are averages.</b> A pick's Model value is the typical outcome for that slot, hits and misses included. Most of what you're buying is the small chance at a star.</li>
  </ul>

  <h2>What is a "replacement starter"?</h2>
  <?php if ($deep): ?>
  <p>The best players who wouldn't start for anyone in a <?= (int) $rules['teams'] ?>-team league with our lineup (<?= esc($lineupLabel) ?>). With rosters this deep, they're on someone's bench, not on waivers. A player's value starts with what he scores above that level, because that's what he adds to a lineup. Bench players add a little more, covered <a class="link" href="#depth">below</a>.</p>
  <?php else: ?>
  <p>The best players who wouldn't start for anyone in a <?= (int) $rules['teams'] ?>-team league with our lineup (<?= esc($lineupLabel) ?>). They're roughly who you could find on waivers. A player is only worth what he scores above that level, because that's what he adds to a lineup.</p>
  <?php endif; ?>
  <?php if ($repl): ?>
  <p>Right now, replacement level is <b><?= number_format($repl['QB'], 1) ?></b> points per game at QB, <b><?= number_format($repl['RB'], 1) ?></b> at RB, <b><?= number_format($repl['WR'], 1) ?></b> at WR and <b><?= number_format($repl['TE'], 1) ?></b> at TE.</p>
  <?php endif; ?>

  <?php if ($superflex): ?>
  <h2>Why are quarterbacks worth so much?</h2>
  <p>The superflex. Quarterbacks score the most points, so nearly every team starts two, and the replacement quarterback is well down the list<?php if ($repl): ?> (<?= number_format($repl['QB'], 1) ?> points per game right now)<?php endif; ?>. A team without a second starter has to fill that slot with a running back or receiver who scores far less, every week. That gap is what a starting quarterback is worth.</p>
  <?php else: ?>
  <h2>Why are quarterbacks worth less than in other leagues?</h2>
  <p>We start one quarterback, so all <?= (int) $rules['teams'] ?> teams start a good one and the <?= (int) $rules['teams'] + 1 ?>th-best is still decent. A quarterback has to score well above that bar to be worth much. Superflex leagues, where teams start two, value them far more.</p>
  <?php endif; ?>

  <?php if ($deep): ?>
  <h2 id="depth">Are bench players worth anything?</h2>
  <p>Yes, in a league this deep. Between full benches, taxi squads and injured reserve, almost every useful player is on a roster, so what's left on waivers scores very little<?php if ($repl): ?>: about <?= number_format($depth['waiver']['QB'], 1) ?> points per game at QB, <?= number_format($depth['waiver']['RB'], 1) ?> at RB, <?= number_format($depth['waiver']['WR'], 1) ?> at WR and <?= number_format($depth['waiver']['TE'], 1) ?> at TE<?php endif; ?>. When a starter is hurt, on a bye or sat for a matchup, a bench player starts instead.</p>
  <p>So on top of his points above a replacement starter, a player is worth his points above that waiver level in the weeks a bench player fills in. How often that happens comes from this league's own lineups: a bench player at each position has started about <?= (int) round(100 * array_sum($depth['fill']) / max(1, count($depth['fill']))) ?>% of weeks. Starters get this too, because adding one pushes your weakest starter to the bench, where he becomes the fill-in.</p>
  <?php endif; ?>

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
  <p>Sleeper's rookie draft rankings only go back to about 2021, so classes are ordered by NFL draft position, <?= esc(mfl_league()['rookie_order_note']) ?></p>
  <p>Average points above replacement over a rookie's first four seasons, by first-round slot:</p>
  <div class="table-scroll">
    <table class="pickups faq-table">
      <thead><tr><th scope="col" class="sticky">Pick</th><?php foreach ($firstRound as $slot => $_): ?><th scope="col" class="num">1.<?= str_pad((string) $slot, 2, '0', STR_PAD_LEFT) ?></th><?php endforeach; ?></tr></thead>
      <tbody><tr><th scope="row" class="sticky">Points</th><?php foreach ($firstRound as $v): ?><td class="num"><?= number_format($v, 0) ?></td><?php endforeach; ?></tr></tbody>
    </table>
  </div>
  <?php $po = (int) (mfl_latest_season()['playoff_teams'] ?? 6); $first = $rules['teams'] - $po + 1; ?>
  <p>A future pick's value depends on where it lands, and this league drafts in reverse order of finish. Teams that miss the playoffs pick first, worst record first. Playoff teams pick <?= $first ?><?= [1 => 'st', 2 => 'nd', 3 => 'rd'][$first % 10] ?? 'th' ?> through <?= (int) $rules['teams'] ?>th by how far they got, so the champion picks last.<?php if (mfl_league()['key'] === 'mfl'): ?> In 2026, the regular season's #2 team picked 10th after finishing third in the playoffs.<?php else: ?> Teams with the same record are separated by points scored, and the lower-scoring team picks first.<?php endif; ?></p>
  <p>Until the season ends, the next draft's order is simulated thousands of times: the rest of the regular season from each team's record against every team, every week, then the playoff bracket. A pick's value is the average across those outcomes, and it sharpens as the season goes on. Once the season is over, the order is exact. Drafts after that could land anywhere, so those picks are valued as an average pick in their round.</p>

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

  <h2>How does Find a deal work?</h2>
  <p>Pick a player or pick on another team, and the machine tries every package of up to three pieces from your roster. A package makes the list when the other team gets at least what it gives up, both by the model on their timeline and by the market (within 5%). The ones shown cost you the least on your own timeline, use fewer roster spots, and help the other team's starting lineup. Each is built around a different main piece, so you see real options rather than one offer three ways.</p>
  <p>Timelines matter here. A team that's rebuilding values picks and young players more than a contender does, so setting their timeline correctly can turn up cheaper offers.</p>

  <h2>What are the Alternatives?</h2>
  <p>Under any trade you build, two backup plans:</p>
  <ul>
    <li><b>If they say no</b>: players at the same position (or picks in the same round) on other teams with a similar model value, so you can ask someone else with the same offer.</li>
    <li><b>Other ways to pay for it</b>: different packages from your roster that should also get the deal done.</li>
  </ul>

  <h2 id="scorecards">How are past trades scored?</h2>
  <p>Signed-in owners see a scorecard on every past trade, with two views of what each team received.</p>
  <ul>
    <li><b>At the time</b>: the model value of each side as of the trade, worked out the way it would have been then: results up to that week, and only the projection for that week, so later injuries don't leak in. Picks are valued with the draft order as it stood then. The market value at the time appears for trades made after the site started saving daily prices (November 2026). FantasyCalc doesn't publish old prices, so earlier trades show a dash.</li>
    <li><b>Now</b>: what each side has actually produced plus what it still holds. <b>So far</b> counts points above a replacement starter in games the player started for that team since the trade. <b>Still held</b> is today's model value of whatever that team still has from the deal: the player if he's still on the roster, the player a pick became, or the pick itself if it hasn't been used.</li>
  </ul>
  <p>A player or pick that gets traded again counts toward the first trade only until it leaves. After that, its value belongs to the later trade, so nothing is counted twice. A player who was dropped counts for what he scored before he was cut.</p>
  <p>The two views answer different questions. <b>At the time</b> asks whether it was a fair deal. <b>Now</b> asks who's winning it so far. In a dynasty league, a trade that looked even can tilt years later.</p>

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
