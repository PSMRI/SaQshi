<style>
/* ===== CARD CONTAINER ===== */
.scorecard-container {
  max-width: 1100px;
  margin: 0 auto;
}

.scorecard-card {
  border-radius: 10px;
}

/* ===== TABLE LOOK ===== */

.table-compact {
  font-size: 11.5px;
  table-layout: fixed;
}

.table-compact th,
.table-compact td {
  padding: 5px 6px !important;
  vertical-align: middle !important;
  white-space: normal !important;
  word-break: break-word;
  overflow-wrap: break-word;
}



/* ===== HEADERS ===== */
.header-dark {
  background: #2f2f4f;
  color: #fff;
}

.header-orange {
  background: #ff9933;
  color: #fff;
}

.header-light {
  background: #fff4dc;
  font-weight: 600;
}

/* ===== OVERALL SCORE ===== */
.overall-score {
  font-size: 17px;
  font-weight: 700;
  letter-spacing: .5px;
}

/* ===== SECTION TITLE ===== */
.section-row td {
  background: #2f2f4f;
  color: #fff;
  font-weight: 600;
  text-align: center;
}

/* ===== SCROLL CONTROL ===== */
.table-scroll {
  overflow-x: auto;
}

/* ===== BUTTON ===== */
.btn-download {
  font-size: 12px;
  padding: 5px 10px;
}

</style>


<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$dept_id = $_SESSION["dept_id"] ?? 0;
$fat     = $_SESSION["f_type_id"] ?? 0;
$p       = $_SESSION["period"] ?? 0;
$Fa      = $_SESSION['u_facilityid'] ?? 0;
$dept_name = 'N/A';

if ($dept_id > 0) {
    $stmt = mysqli_prepare(
        $con,
        "SELECT dept_name FROM fac_department WHERE fac_dept_id = ? LIMIT 1"
    );
    mysqli_stmt_bind_param($stmt, "i", $dept_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $dept_name);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
}

$_SESSION['dept_name'] = $dept_name;
?>

<div class="row">
  <div class="col-12">
    <div class="card scorecard-card scorecard-container">
      <div class="card-body p-3">

<?php
$q2 = mysqli_query($con, "CALL concern_type_report($fat,$Fa,$dept_id,$p)");

if ($q2 && $row = mysqli_fetch_assoc($q2)) {

  $overall = number_format((float)$row['overall_score'], 2);
$_SESSION['pp1'] = $overall . '%';

?>

<button class="btn btn-success btn-sm btn-download mb-3"
        onclick="exportStyledTableAsExcel()">
  Download Report
</button>

<div class="table-scroll">

<!-- ================= SUMMARY TABLE ================= -->
<table id="table2" class="table table-bordered table-compact text-center mb-4">

<tr>
  <th colspan="6" class="header-dark">
   Facility:- <?= htmlspecialchars($_SESSION['facname'] ?? 'Facility') ?>; Department:-  <?= htmlspecialchars($_SESSION['dept_name'] ?? 'Dept') ?>:– Overall Score Summary
  </th>
</tr>
<tr>
  
  <th class="header-orange">Service</th>
  <th class="header-orange">Patients</th>
  <th colspan="2" rowspan="2" class="header-light">Overall</th>
  <th class="header-orange">Inputs</th>
  <th class="header-orange">Support</th>
</tr>

<tr> 
  <td><?= $row['totalc1'] ? round($row['d1']/$row['totalc1']*100,2).'%' : '0%' ?></td>
  <td><?= $row['totalc2'] ? round($row['d2']/$row['totalc2']*100,2).'%' : '0%' ?></td>
  <td><?= $row['totalc3'] ? round($row['d3']/$row['totalc3']*100,2).'%' : '0%' ?></td>
  <td><?= $row['totalc4'] ? round($row['d4']/$row['totalc4']*100,2).'%' : '0%' ?></td>
</tr>

<tr>
  
  <th class="header-orange">Clinical</th>
  <th class="header-orange">Infection</th>
  <th colspan="2" rowspan="2" class="header-dark overall-score">
    <?= $_SESSION['pp1'] ?>
  </th>
  <th class="header-orange">Quality</th>
  <th class="header-orange">Outcomes</th>
</tr>

<tr>
  
  <td><?= $row['totalc5'] ? round($row['d5']/$row['totalc5']*100,2).'%' : '0%' ?></td>
  <td><?= $row['totalc6'] ? round($row['d6']/$row['totalc6']*100,2).'%' : '0%' ?></td>
  <td><?= $row['totalc7'] ? round($row['d7']/$row['totalc7']*100,2).'%' : '0%' ?></td>
  <td><?= $row['totalc8'] ? round($row['d8']/$row['totalc8']*100,2).'%' : '0%' ?></td>
</tr>

</table>

<?php
mysqli_free_result($q2);
$con->next_result();
?>

<!-- ================= DETAIL TABLE ================= -->
<table id="table3" class="table table-bordered table-compact table-sm">

<tr class="header-dark text-center">
  <th>Ref</th>
  <th colspan="2">Standard</th>
  <th>Obt</th>
  <th>Max</th>
  <th>%</th>
</tr>

<?php
$data = [];

/* ===== SINGLE DB CALL ===== */
$q = mysqli_query(
    $con,
    "CALL get_all_concern_standards_score_fast1($fat,$Fa,$dept_id,$p)"
);

if (!$q) {
    echo "<tr><td colspan='6'>No data found.</td></tr>";
} else {

    /* ===== COLLECT DATA ===== */
    while ($row = mysqli_fetch_assoc($q)) {
        $cid = $row['concern_id'];

        if (!isset($data[$cid])) {
            $data[$cid] = [
                'concern_name' => $row['concern_name'],
                'concern_des'  => $row['concern_des'],
                'rows'         => []
            ];
        }

        $data[$cid]['rows'][] = $row;
    }

    mysqli_free_result($q);
    $con->next_result();

    /* ===== RENDER TABLE ===== */
    foreach ($data as $concern) {

        echo "<tr class='section-row'>
                <td colspan='6'>
                  {$concern['concern_des']} – {$concern['concern_name']}
                </td>
              </tr>";

        foreach ($concern['rows'] as $sc) {

            echo "<tr>
                <td>{$sc['id1']}</td>
                <td colspan='2'>{$sc['area_of_con_subtypedeatils']}</td>
                <td>{$sc['obtained']}</td>
                <td>{$sc['total']}</td>
                <td>{$sc['percentage']}%</td>
              </tr>";
        }
    }
}
?>

</table>
</div>

<?php
} else {
  echo "<div class='alert alert-warning'>No data found for selected period.</div>";
}
?>

      </div>
    </div>
  </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
<script>
function applyInlineStylesFast(table) {
  const cells = table.querySelectorAll('th, td');

  cells.forEach(el => {
    el.style.whiteSpace = 'normal';
    el.style.wordBreak = 'break-word';
    el.style.verticalAlign = 'middle';

    if (el.classList.contains('header-dark')) {
      el.style.background = '#2f2f4f';
      el.style.color = '#fff';
      el.style.fontWeight = 'bold';
    } 
    else if (el.classList.contains('header-orange')) {
      el.style.background = '#ff9933';
      el.style.color = '#fff';
      el.style.fontWeight = 'bold';
    } 
    else if (el.classList.contains('header-light')) {
      el.style.background = '#fff4dc';
      el.style.fontWeight = 'bold';
    } 
    else if (el.closest('.section-row')) {
      el.style.background = '#2f2f4f';
      el.style.color = '#fff';
      el.style.fontWeight = 'bold';
      el.style.textAlign = 'center';
    }
  });
}

function exportStyledTableAsExcel() {

  const table1 = document.getElementById("table2").cloneNode(true);
  const table2 = document.getElementById("table3").cloneNode(true);

  applyInlineStylesFast(table1);
  applyInlineStylesFast(table2);

  const html = `
  <html xmlns:o="urn:schemas-microsoft-com:office:office"
        xmlns:x="urn:schemas-microsoft-com:office:excel">
  <head>
    <meta charset="utf-8">
    <style>
      body { font-family: Arial; margin: 20px; }
      table { border-collapse: collapse; width: 100%; table-layout: fixed; }
      th, td { border:1px solid #444; padding:8px; font-size:13px; }
    </style>
  </head>
  <body>
    ${table1.outerHTML}<br><br>${table2.outerHTML}
  </body>
  </html>`;

  const blob = new Blob([html], { type: 'application/vnd.ms-excel' });
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = 'facility_scorecard.xls';
  a.click();
}
</script>
