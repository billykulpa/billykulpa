<?php /** @var array $teams */ ?>
<section class="hero hero--tight">
  <p class="mono-label">All-time</p>
  <h1>Teams</h1>
</section>
<section>
  <div class="table-scroll">
    <table>
      <thead><tr><th scope="col">Team</th><th scope="col" class="wide-only">Owner</th><th scope="col" class="num">Record</th>
        <th scope="col" class="num">Win %</th><th scope="col" class="num">PF</th><th scope="col" class="num wide-only">PA</th>
        <th scope="col" class="num wide-only">Playoffs</th><th scope="col" class="num">Titles</th></tr></thead>
      <tbody>
      <?php foreach ($teams as $t): ?>
        <tr>
          <td class="team"><?= mfl_team_link((int) $t['id'], $t['name']) ?></td>
          <td class="wide-only"><?= mfl_owner_link($t['owner_name'], $t['real_name']) ?></td>
          <td class="num"><?= esc(mfl_record($t)) ?></td>
          <td class="num"><?= esc(mfl_pct($t['win_pct'])) ?></td>
          <td class="num"><?= number_format($t['pf'], 2) ?></td>
          <td class="num wide-only"><?= number_format($t['pa'], 2) ?></td>
          <td class="num wide-only"><?= (int) $t['playoffs'] ?></td>
          <td class="num"><?= (int) $t['titles'] ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <p class="note">Records and points are regular season. A franchise keeps its history through owner changes; each owner's page shows only their own run.</p>
</section>
