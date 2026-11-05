<?php /** @var array $headToHead */ ?>
<?php if ($headToHead): ?>
<section>
  <h2>Head to head</h2>
  <div class="table-scroll">
    <table>
      <thead><tr><th scope="col" class="sticky">Opponent</th><th scope="col" class="num">Record</th><th scope="col" class="num">PF</th><th scope="col" class="num">PA</th></tr></thead>
      <tbody>
      <?php foreach ($headToHead as $h): ?>
        <tr>
          <td class="team"><?= mfl_team_link((int) $h['id'], $h['name']) ?></td>
          <td class="num"><?= esc(mfl_record($h)) ?></td>
          <td class="num"><?= number_format($h['pf'], 2) ?></td>
          <td class="num"><?= number_format($h['pa'], 2) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <p class="note">Regular-season and playoff games.</p>
</section>
<?php endif; ?>
