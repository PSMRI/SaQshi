<?php
$summary = $_SESSION['summary'];
$aocData = $_SESSION['aocData'];
?>
<pre>
<?php
print_r($summary);
print_r($aocData);
echo "<pre>";
print_r($_POST);
echo "</pre>";
?>
</pre>


<div class="card mb-4">
  <div class="card-body">
    <h5 class="text-center fw-bold mb-3 text-primary"><?= $_SESSION['facname'] ?> - Area of Concern wise Scores</h5>
    <button class="btn btn-success mb-3" onclick="exportToExcel('aocScoresTable')">Export</button>

    <div class="table-responsive">
      <table class="table table-bordered text-center small" id="aocScoresTable">
        <thead class="table-light">
          <tr><th colspan="5" class="bg-secondary text-white">Summary Scores</th></tr>
          <tr>
            <th><?= $summary['c1'] ?></th>
            <th><?= $summary['c2'] ?></th>
            <th>Overall Score of Facility</th>
            <th><?= $summary['c3'] ?></th>
            <th><?= $summary['c4'] ?></th>
          </tr>
          <tr class="bg-light">
            <th><?= $summary['totalc1'] ? round($summary['d1'] / $summary['totalc1'] * 100, 2) . "%" : "0%" ?></th>
            <th><?= $summary['totalc2'] ? round($summary['d2'] / $summary['totalc2'] * 100, 2) . "%" : "0%" ?></th>
            <th><?= $_SESSION['p1'] ?>%</th>
            <th><?= $summary['totalc3'] ? round($summary['d3'] / $summary['totalc3'] * 100, 2) . "%" : "0%" ?></th>
            <th><?= $summary['totalc4'] ? round($summary['d4'] / $summary['totalc4'] * 100, 2) . "%" : "0%" ?></th>
          </tr>
          <tr>
            <th><?= $summary['c3'] ?></th>
            <th><?= $summary['c4'] ?></th>
            <th></th>
            <th><?= $summary['c7'] ?></th>
            <th><?= $summary['c8'] ?></th>
          </tr>
          <tr class="bg-light">
            <th><?= $summary['totalc5'] ? round($summary['d5'] / $summary['totalc5'] * 100, 2) . "%" : "0%" ?></th>
            <th><?= $summary['totalc6'] ? round($summary['d6'] / $summary['totalc6'] * 100, 2) . "%" : "0%" ?></th>
            <th></th>
            <th><?= $summary['totalc7'] ? round($summary['d7'] / $summary['totalc7'] * 100, 2) . "%" : "0%" ?></th>
            <th><?= $summary['totalc8'] ? round($summary['d8'] / $summary['totalc8'] * 100, 2) . "%" : "0%" ?></th>
          </tr>
        </thead>

        <tbody>
          <tr class="table-info fw-bold">
            <td>Reference No.</td>
            <td>Area of Concern/Standards</td>
            <td>Score Obtained</td>
            <td>Max Score</td>
            <td>Percentage</td>
          </tr>

          <?php foreach ($aocData as $aoc): ?>
            <tr><th colspan="5" class="bg-dark text-white text-center"><?= $aoc['name'] ?></th></tr>
            <?php foreach ($aoc['standards'] as $std): 
              $obt = $std['obtained'] ?: 0;
              $tot = $std['total'] ?: 1;
              $perc = $obt ? round(($obt / $tot) * 100, 2) . "%" : "0%";
            ?>
              <tr>
                <td><?= $std['id1'] ?></td>
                <td><?= $std['area_of_con_subtypedeatils'] ?></td>
                <td><?= $obt ?></td>
                <td><?= $tot ?></td>
                <td><?= $perc ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
