<?php /** @var array $seasons */ /** @var bool $showTeam */ ?>
<div class="table-scroll">
  <table>
    <thead><tr><th scope="col">Season</th><th scope="col" class="sticky"><?= $showTeam ? 'Team' : 'Team name' ?></th><th scope="col"><?= $showTeam ? '' : 'Owner' ?></th>
      <th scope="col" class="num">Record</th><th scope="col" class="num">PF</th><th scope="col" class="num">PA</th><th scope="col" class="num">Finish</th></tr></thead>
    <tbody>
    <?php foreach ($seasons as $s): ?>
      <tr>
        <td><a class="team-link" href="/standings/<?= (int) $s['season'] ?>"><?= (int) $s['season'] ?></a></td>
        <td class="team"><?= $showTeam ? mfl_team_link((int) $s['franchise_id'], $s['team_name']) : esc($s['team_name']) ?></td>
        <td><?= $showTeam ? '' : mfl_owner_link($s['owner_name'], $s['real_name']) ?></td>
        <td class="num"><?= esc(mfl_record($s)) ?></td>
        <td class="num"><?= number_format($s['pf'], 2) ?></td>
        <td class="num"><?= number_format($s['pa'], 2) ?></td>
        <td class="num"><?= esc($s['finish']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
