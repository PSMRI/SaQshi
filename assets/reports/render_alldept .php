<script>
  document.addEventListener('DOMContentLoaded', () => {
    const table = document.getElementById('stdTable');
    if (table) {
      applyStandardWiseColors(table);
    }
  });
</script>

<style>
  body {
    font-family: Arial, sans-serif;
    margin: 10px;
    font-size: 12px;
  }

  table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 18px;
  }

  th,
  td {
    border: 1px solid #333;
    padding: 4px 6px;
    text-align: center;
    vertical-align: middle;
    line-height: 1.2;
  }

  /* Headers */
  .header {
    font-size: 16px;
    font-weight: bold;
  }

  .subheader {
    background: #ff5a5a;
    color: #fff;
    font-size: 13px;
    font-weight: bold;
  }

  /* Scores */
  .score {
    font-size: 14px;
    font-weight: bold;
    color: #0b5ed7;
  }

  .yellow {
    background: #ffc107;
    font-size: 14px;
    font-weight: bold;
  }

  .green {
    background: #0bb64b;
    color: #fff;
    font-size: 13px;
    font-weight: bold;
  }

  .pink {
    background: #ffb6d9;
    font-size: 13px;
    font-weight: bold;
  }

  /* Compact print */
  @media print {
    body {
      margin: 0
    }

    table {
      page-break-inside: avoid
    }
  }
</style>


<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$fat = $_SESSION["f_type_id"] ?? 0;
$p   = $_SESSION["period"] ?? 0;
$Fa  = $_SESSION['u_facilityid'] ?? 0;

$result = mysqli_query($con, "CALL depart_dash_dh_reports($fat, $Fa, $p)");

$deptScore = [];   // dept-wise %

/* ===== Weighted totals ===== */
$hospitalTotal = $hospitalObt = 0;
$musqanTotal   = $musqanObt   = 0;
$laqshyaTotal  = $laqshyaObt  = 0;

while ($row = mysqli_fetch_assoc($result)) {

  $deptId = (int)$row['id1'];
  $total  = (float)$row['total'];
  $obt    = (float)$row['Obtained'];

  /* ---- dept-wise percentage ---- */
  $deptScore[$deptId] = ($total > 0)
    ? round(($obt / $total) * 100, 2)
    : 0;

  /* ---- Hospital score (ALL depts) ---- */
  $hospitalTotal += $total;
  $hospitalObt   += $obt;

  /* ---- MusQan departments ---- */
  if (in_array($deptId, [5, 6, 7, 23])) {
    $musqanTotal += $total;
    $musqanObt   += $obt;
  }

  /* ---- LaQshya departments ---- */
  if (in_array($deptId, [9, 33])) {
    $laqshyaTotal += $total;
    $laqshyaObt   += $obt;
  }
}
mysqli_next_result($con);

/* ===== FINAL WEIGHTED SCORES ===== */
$hospitalScore = ($hospitalTotal > 0)
  ? round(($hospitalObt / $hospitalTotal) * 100, 2)
  : 0;

$musqanScore = ($musqanTotal > 0)
  ? round(($musqanObt / $musqanTotal) * 100, 2)
  : 0;

$laqshyaScore = ($laqshyaTotal > 0)
  ? round(($laqshyaObt / $laqshyaTotal) * 100, 2)
  : 0;
?>
<?php
$result = mysqli_query($con, "CALL hospital_are_of_concern_allrpt($fat, $Fa, $p)");
/* ================= GET SESSION VALUES FIRST ================= */
$fat = $_SESSION["f_type_id"] ?? 0;
$p   = $_SESSION["period"] ?? 0;
$Fa  = $_SESSION['u_facilityid'] ?? 0;


$areaScore = [];          // score by concern_id
$grandTotal = 0;          // SUM(total)
$grandObtained = 0;       // SUM(obtained)

while ($row = mysqli_fetch_assoc($result)) {

  $cid   = (int)$row['concern_id'];
  $total = (float)$row['total'];
  $obt   = (float)$row['Obtained'];

  $score = ($total > 0)
    ? round(($obt / $total) * 100, 2)
    : 0;

  $areaScore[$cid] = $score;

  /* ===== weighted aggregation ===== */
  $grandTotal    += $total;
  $grandObtained += $obt;
}
mysqli_next_result($con);

/* ================= CORRECT HOSPITAL SCORE ================= */
$hospitalAreaScore = ($grandTotal > 0)
  ? round(($grandObtained / $grandTotal) * 100, 2)
  : 0;
mysqli_next_result($con);
?>
<?php
/* ================= GET SESSION VALUES FIRST ================= */
$fat = $_SESSION["f_type_id"] ?? 0;
$p   = $_SESSION["period"] ?? 0;
$Fa  = $_SESSION['u_facilityid'] ?? 0;

/* ================= CALL STORED PROCEDURE ================= */
$musqanresult = mysqli_query(
  $con,
  "CALL Area_of_concern_musqan_overall_fac_dept($fat, $Fa, $p)"
);

/* ================= INIT VARIABLES ================= */
$musqanareaScore = [];          // score by concern_id
$musqangrandTotal = 0;          // SUM(total)
$musqangrandObtained = 0;       // SUM(obtained)

/* ================= PROCESS RESULT ================= */
while ($row = mysqli_fetch_assoc($musqanresult)) {

  $musqancid   = (int)$row['id1'];
  $musqantotal = (float)$row['total'];
  $musqanobt   = (float)$row['Obtained'];

  $musqanscore = ($musqantotal > 0)
    ? round(($musqanobt / $musqantotal) * 100, 2)
    : 0;

  $musqanareaScore[$musqancid] = $musqanscore;

  /* ===== weighted aggregation ===== */
  $musqangrandTotal    += $musqantotal;
  $musqangrandObtained += $musqanobt;
}

/* IMPORTANT: clear results for next CALL */
mysqli_next_result($con);

/* ================= FINAL WEIGHTED SCORE ================= */
$musqanhospitalAreaScore = ($musqangrandTotal > 0)
  ? round(($musqangrandObtained / $musqangrandTotal) * 100, 2)
  : 0;

?>
<?php
$data = [];

$q = mysqli_query(
  $con,
  "CALL get_all_concern_standards_score_programwise($fat,$Fa,$p)"
);

while ($r = mysqli_fetch_assoc($q)) {

  $cid  = $r['concern_id'];
  $ref  = $r['Reference_No'];
  $prog = $r['program_tag'];

  if (!isset($data[$cid])) {
    $data[$cid] = [
      'concern' => $r['concern_des'] . ' - ' . $r['concern_name'],
      'rows' => []
    ];
  }

  if (!isset($data[$cid]['rows'][$ref])) {
    $data[$cid]['rows'][$ref] = [
      'ref'   => $ref,
      'name'  => $r['area_of_con_subtypedeatils'],
      'NQAS'     => 'NA',
      'LAQSHYA'  => 'NA',
      'MUSQAN'   => 'NA'
    ];
  }

  $data[$cid]['rows'][$ref][$prog] =
    ($r['total'] > 0) ? $r['percentage'] . '%' : 'NA';
}

mysqli_next_result($con);
?>
<div class="row">
  <div class="col-12">
    <div class="card scorecard-card scorecard-container">
      <div class="card-body p-3">


        <button onclick="exportExcel()" style="margin-bottom:15px;"
          class="btn btn-success btn-sm">
          Download Report
        </button>
        <div id="exportBlock">
          <table id="scorecardTable"
            style="width:100%;border-collapse:collapse;text-align:center;font-size:12px;">

            <tr>
              <td colspan="7"
                style="font-size:16px;font-weight:bold;border:1px solid #333;">
                NQAS SCORE CARD – DISTRICT HOSPITAL
              </td>
              <td colspan="2"
                style="border:1px solid #333;">
                <b>Version:</b> DH/NQAS-2020<br>
                <b>Revision:</b> 00
              </td>
            </tr>

            <tr>
              <td colspan="9"
                bgcolor="#ff5a5a"
                style="background-color:#ff5a5a;color:#fff;
             font-size:13px;font-weight:bold;border:1px solid #333;">
                Hospital Score Card (Department wise)
              </td>
            </tr>

            <!-- ROW 1 HEADERS -->
            <tr>
              <th style="border:1px solid #333;">Accident & Emergency</th>
              <th style="border:1px solid #333;">OPD</th>
              <th style="border:1px solid #333;">Labour Room</th>
              <th style="border:1px solid #333;">Maternity Ward</th>
              <th style="border:1px solid #333;">Paediatrics OPD</th>
              <td colspan="4" rowspan="2"
                bgcolor="#ffc107"
                style="background-color:#ffc107;
             font-weight:bold;border:1px solid #333;">
                Hospital Score
              </td>
            </tr>

            <!-- ROW 1 VALUES -->
            <tr>
              <td style="font-weight:bold;color:#0b5ed7;border:1px solid #333;"><?= $deptScore[1] ?? 0 ?>%</td>
              <td style="font-weight:bold;color:#0b5ed7;border:1px solid #333;"><?= $deptScore[2] ?? 0 ?>%</td>
              <td style="font-weight:bold;color:#0b5ed7;border:1px solid #333;"><?= $deptScore[3] ?? 0 ?>%</td>
              <td style="font-weight:bold;color:#0b5ed7;border:1px solid #333;"><?= $deptScore[4] ?? 0 ?>%</td>
              <td style="font-weight:bold;color:#0b5ed7;border:1px solid #333;"><?= $deptScore[5] ?? 0 ?>%</td>
            </tr>

            <!-- ROW 2 HEADERS -->
            <tr>
              <th style="border:1px solid #333;">Paediatrics Ward</th>
              <th style="border:1px solid #333;">SNCU</th>
              <th style="border:1px solid #333;">NRC</th>
              <th style="border:1px solid #333;">OT</th>
              <th style="border:1px solid #333;">M-OT</th>
              <td colspan="4" rowspan="2"
                bgcolor="#ffc107"
                style="background-color:#ffc107;
             font-weight:bold;border:1px solid #333;">
                <?= $hospitalScore ?>%
              </td>
            </tr>

            <tr>
              <td style="font-weight:bold;color:#0b5ed7;border:1px solid #333;"><?= $deptScore[6] ?? 0 ?>%</td>
              <td style="font-weight:bold;color:#0b5ed7;border:1px solid #333;"><?= $deptScore[7] ?? 0 ?>%</td>
              <td style="font-weight:bold;color:#0b5ed7;border:1px solid #333;"><?= $deptScore[23] ?? 0 ?>%</td>
              <td style="font-weight:bold;color:#0b5ed7;border:1px solid #333;"><?= $deptScore[8] ?? 0 ?>%</td>
              <td style="font-weight:bold;color:#0b5ed7;border:1px solid #333;"><?= $deptScore[9] ?? 0 ?>%</td>
            </tr>

            <!-- PROGRAM SCORES -->
            <tr>
              <th style="border:1px solid #333;">PP Unit</th>
              <th style="border:1px solid #333;">ICU</th>
              <th style="border:1px solid #333;">IPD</th>
              <th style="border:1px solid #333;">Blood Bank</th>
              <th style="border:1px solid #333;">Lab</th>
              <td colspan="2" bgcolor="#0bb64b"
                style="background-color:#0bb64b;color:#fff;
             font-weight:bold;border:1px solid #333;">
               LaQshya
              </td>
              <td colspan="2" bgcolor="#ffb6d9"
                style="background-color:#ffb6d9;
             font-weight:bold;border:1px solid #333;">
                MusQan
              </td>
            </tr>

            <tr>
              <td style="font-weight:bold;color:#0b5ed7;border:1px solid #333;"><?= $deptScore[10] ?? 0 ?>%</td>
              <td style="font-weight:bold;color:#0b5ed7;border:1px solid #333;"><?= $deptScore[11] ?? 0 ?>%</td>
              <td style="font-weight:bold;color:#0b5ed7;border:1px solid #333;"><?= $deptScore[12] ?? 0 ?>%</td>
              <td style="font-weight:bold;color:#0b5ed7;border:1px solid #333;"><?= $deptScore[13] ?? 0 ?>%</td>
              <td style="font-weight:bold;color:#0b5ed7;border:1px solid #333;"><?= $deptScore[14] ?? 0 ?>%</td>
              <td colspan="2" bgcolor="#0bb64b"
                style="background-color:#0bb64b;color:#fff;
             font-weight:bold;border:1px solid #333;">
                <?= $laqshyaScore ?>%
              </td>
              <td colspan="2" bgcolor="#ffb6d9"
                style="background-color:#ffb6d9;
             font-weight:bold;border:1px solid #333;">
                <?= $musqanScore ?>%
              </td>
            </tr>

            <!-- GENERAL ADMIN -->
            <tr>
              <th colspan="9"
                style="border:1px solid #333;">
                General Administration
              </th>
            </tr>
            <tr>
              <td colspan="9"
                style="font-weight:bold;color:#0b5ed7;border:1px solid #333;">
                <?= $deptScore[19] ?? 0 ?>%
              </td>
            </tr>

          </table>
          <table
            style="width:100%;
         border-collapse:collapse;
         margin-top:30px;
         font-size:12px;
         text-align:center;">

            <!-- HEADER -->
            <tr>
              <td colspan="4"
                bgcolor="#00b0f0"
                style="background-color:#00b0f0;
             font-weight:bold;
             border:1px solid #333;
             padding:8px;">
                HOSPITAL QUALITY SCORE CARD<br>(AREA OF CONCERN WISE)
              </td>

              <td style="border:none;"></td>

              <td colspan="4"
                bgcolor="#ffb6d9"
                style="background-color:#ffb6d9;
             font-weight:bold;
             border:1px solid #333;
             padding:8px;">
                MUSQAN QUALITY SCORE CARD<br>(AREA OF CONCERN WISE)
              </td>
            </tr>

            <!-- TOP HEADERS -->
            <tr>
              <th style="border:1px solid #333;">Service Provision</th>
              <th style="border:1px solid #333;">Patients' Rights</th>
              <th style="border:1px solid #333;">Inputs</th>
              <th style="border:1px solid #333;">Support Services</th>

              <td style="border:none;"></td>

              <th style="border:1px solid #333;">Service Provision</th>
              <th style="border:1px solid #333;">Patients' Rights</th>
              <th style="border:1px solid #333;">Inputs</th>
              <th style="border:1px solid #333;">Support Services</th>
            </tr>

            <!-- TOP VALUES -->
            <tr>
              <td style="border:1px solid #333;font-weight:bold;color:#0b5ed7;">
                <?= $areaScore[1] ?? 0 ?>%
              </td>
              <td style="border:1px solid #333;font-weight:bold;color:#0b5ed7;">
                <?= $areaScore[2] ?? 0 ?>%
              </td>
              <td style="border:1px solid #333;font-weight:bold;color:#0b5ed7;">
                <?= $areaScore[3] ?? 0 ?>%
              </td>
              <td style="border:1px solid #333;font-weight:bold;color:#0b5ed7;">
                <?= $areaScore[4] ?? 0 ?>%
              </td>

              <td style="border:none;"></td>

              <td style="border:1px solid #333;font-weight:bold;color:#0b5ed7;">
                <?= $musqanareaScore[1] ?? 0 ?>%
              </td>
              <td style="border:1px solid #333;font-weight:bold;color:#0b5ed7;">
                <?= $musqanareaScore[2] ?? 0 ?>%
              </td>
              <td style="border:1px solid #333;font-weight:bold;color:#0b5ed7;">
                <?= $musqanareaScore[3] ?? 0 ?>%
              </td>
              <td style="border:1px solid #333;font-weight:bold;color:#0b5ed7;">
                <?= $musqanareaScore[4] ?? 0 ?>%
              </td>
            </tr>

            <!-- HOSPITAL SCORE ROW -->
            <tr>
              <th colspan="4"
                style="border:1px solid #333;">
                Hospital Score
              </th>

              <td style="border:none;"></td>

              <th colspan="4"
                style="border:1px solid #333;">
                Hospital Score
              </th>
            </tr>

            <tr>
              <td colspan="4"
                bgcolor="#ffc107"
                style="background-color:#ffc107;
             font-weight:bold;
             border:1px solid #333;">
                <?= $hospitalAreaScore ?>%
              </td>

              <td style="border:none;"></td>

              <td colspan="4"
                bgcolor="#ffc107"
                style="background-color:#ffc107;
             font-weight:bold;
             border:1px solid #333;">
                <?= $musqanhospitalAreaScore ?>%
              </td>
            </tr>

            <!-- LOWER HEADERS -->
            <tr>
              <th style="border:1px solid #333;">Clinical Services</th>
              <th style="border:1px solid #333;">Infection Control</th>
              <th style="border:1px solid #333;">Quality Management</th>
              <th style="border:1px solid #333;">Outcome</th>

              <td style="border:none;"></td>

              <th style="border:1px solid #333;">Clinical Services</th>
              <th style="border:1px solid #333;">Infection Control</th>
              <th style="border:1px solid #333;">Quality Management</th>
              <th style="border:1px solid #333;">Outcome</th>
            </tr>

            <!-- LOWER VALUES -->
            <tr>
              <td style="border:1px solid #333;font-weight:bold;color:#0b5ed7;">
                <?= $areaScore[5] ?? 0 ?>%
              </td>
              <td style="border:1px solid #333;font-weight:bold;color:#0b5ed7;">
                <?= $areaScore[6] ?? 0 ?>%
              </td>
              <td style="border:1px solid #333;font-weight:bold;color:#0b5ed7;">
                <?= $areaScore[7] ?? 0 ?>%
              </td>
              <td style="border:1px solid #333;font-weight:bold;color:#0b5ed7;">
                <?= $areaScore[8] ?? 0 ?>%
              </td>

              <td style="border:none;"></td>

              <td style="border:1px solid #333;font-weight:bold;color:#0b5ed7;">
                <?= $musqanareaScore[5] ?? 0 ?>%
              </td>
              <td style="border:1px solid #333;font-weight:bold;color:#0b5ed7;">
                <?= $musqanareaScore[6] ?? 0 ?>%
              </td>
              <td style="border:1px solid #333;font-weight:bold;color:#0b5ed7;">
                <?= $musqanareaScore[7] ?? 0 ?>%
              </td>
              <td style="border:1px solid #333;font-weight:bold;color:#0b5ed7;">
                <?= $musqanareaScore[8] ?? 0 ?>%
              </td>
            </tr>

          </table>
          <table id="stdTable" width="100%" cellpadding="6" cellspacing="0"
            style="border-collapse:collapse;font-family:Arial;font-size:12px;">
            <tr>
              <th>Reference No</th>
              <th colspan="5">Area of Concern & Standards</th>
              <th>NQAS Score</th>
              <th>LaQshya Score</th>
              <th>MusQan Score</th>
            </tr>

            <?php foreach ($data as $block): ?>
              <tr>
                <td colspan="9"><?= htmlspecialchars($block['concern']) ?></td>
              </tr>

              <?php foreach ($block['rows'] as $r): ?>
                <tr>
                  <td><?= htmlspecialchars($r['ref']) ?></td>
                  <td colspan="5"><?= htmlspecialchars($r['name']) ?></td>
                  <td><?= $r['NQAS'] ?></td>
                  <td><?= $r['LAQSHYA'] ?></td>
                  <td><?= $r['MUSQAN'] ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endforeach; ?>
          </table>




        </div>

      </div>
    </div>
  </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
<script>
  function applyStandardWiseColors(table) {

    const rows = table.querySelectorAll('tr');

    rows.forEach((row, i) => {
      const cells = row.querySelectorAll('th, td');

      // HEADER
      if (i === 0) {
        cells.forEach(c => setCell(c, '#4472C4', '#ffffff'));
        return;
      }

      // AREA OF CONCERN ROW
      if (cells.length === 1 && cells[0].colSpan === 9) {
        setCell(cells[0], '#7F7F7F', '#ffffff');
        cells[0].style.textAlign = 'center';
        return;
      }

      // STANDARD ROW
      if (cells.length === 5) {
        setCell(cells[0], '#0070C0', '#ffffff'); // Reference
        setCell(cells[1], '#FFFF00', '#000000'); // Standard
        setCell(cells[2], '#44546A', '#ffffff'); // NQAS
        setCell(cells[3], '#00B0F0', '#ffffff'); // LaQshya
        setCell(cells[4], '#70AD47', '#ffffff'); // MusQan
        cells[1].style.textAlign = 'left';
      }
    });
  }

  function setCell(cell, bg, fg, align = 'center') {

  let style =
    'border:1px solid #000;' +
    'padding:6px;' +
    'font-weight:bold;' +
    'white-space:normal;' +
    'word-break:break-word;' +
    'text-align:' + align + ';' +
    'vertical-align:middle;' +
    'mso-align:' + align + ';' +                  // ⭐ Excel
    'mso-vertical-align:middle;';                 // ⭐ Excel

  if (bg) {
    style += 'background-color:' + bg + ';';
    cell.setAttribute('bgcolor', bg);             // ⭐ CRITICAL
  }

  if (fg) {
    style += 'color:' + fg + ';';
  }

  cell.setAttribute('style', style);
}
</script>
<script>
  function exportExcel() {

    const tables = document.querySelectorAll('#exportBlock table');
    let excelBody = '';

    tables.forEach(tbl => {
      const clone = tbl.cloneNode(true);

      // 🎯 Apply same UI logic
      if (clone.id === 'stdTable') {
        applyStandardWiseColors(clone);
      }

      excelBody += clone.outerHTML + '<br><br>';
    });

    const html =
      `<html xmlns:o="urn:schemas-microsoft-com:office:office"
       xmlns:x="urn:schemas-microsoft-com:office:excel">
<head><meta charset="utf-8"></head>
<body>${excelBody}</body>
</html>`;

    const blob = new Blob([html], {
      type: 'application/vnd.ms-excel'
    });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'standard_wise_report.xls';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
  }
</script>