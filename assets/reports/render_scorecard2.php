<style>
  /* ================= CARD ================= */
  .report-card {
    border-radius: 12px;
  }

  /* ================= COMPACT TABLE ================= */
  .compact-table {
    width: 100%;
    table-layout: fixed;
    /* required for wrapping */
    font-size: 11px;
  }

  /* ================= HEADER (VERY IMPORTANT) ================= */
  .thead-compact th {
    background: #2f2f4f;
    color: #ffffff;

    font-size: 10px;
    /* small heading text */
    line-height: 1.15;
    padding: 3px 5px;

    text-align: center;
    font-weight: 600;

    white-space: normal;
    /* allow wrapping */
    word-break: normal;
    /* ✅ prevent mid-word split */
    overflow-wrap: break-word;
  }

  /* ================= BODY CELLS ================= */
  .compact-table td {
    padding: 4px 6px;
    line-height: 1.35;
    vertical-align: top;

    white-space: normal;
    word-break: break-word;
    /* OK for body text */
    overflow-wrap: break-word;
  }

  /* ================= CONCERN ROW ================= */
  .concern-row th {
    background: #3d3d5c;
    color: #ffffff;
    text-align: center;
    font-size: 11.5px;
    padding: 5px;
  }

  /* ================= SUBTYPE ROW ================= */
  .subtype-ref {
    background: #66a8ff;
    color: #ffffff;
    text-align: center;
    width: 10%;
    font-weight: 600;
  }

  .subtype-title {
    background: #ffc300;
    font-weight: 600;
    text-align: left;
  }

  /* ================= UTILS ================= */
  .center {
    text-align: center;
  }
</style>

<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$dept_id = $_SESSION["dept_id"] ?? 0;
$p       = $_SESSION["period"] ?? 0;
$Fa      = $_SESSION['u_facilityid'] ?? 0;

/* =========================================================
   FETCH ASSESSOR INFO
========================================================= */
$stmt = mysqli_prepare(
  $con,
  "SELECT 
        a.Assessor_name,
        a.assessi_name,
        a.assessment_date,
        a.assessment_name,
        b.fac_name
     FROM assessor_info a
     JOIN facilities b ON a.institute_id = b.fac_id
     WHERE a.institute_id = ?
       AND a.assessment_period = ?
       AND a.dept_id = ?
     LIMIT 1"
);
mysqli_stmt_bind_param($stmt, "iii", $Fa, $p, $dept_id);
mysqli_stmt_execute($stmt);
$info = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$info) {
  echo "<div class='alert alert-danger'>
            Kindly fill Assessor info first..! 
            <a href='assessor.php'>Assessor info</a>
          </div>";
  exit;
}

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

<div class="card report-card shadow-sm">
  <div class="card-body p-3">

    <div class="d-flex justify-content-between align-items-center mb-2">
      <h6 class="fw-bold mb-0">Assessment Report</h6>
      <button class="btn btn-success btn-sm" onclick="exportToExcel()">Export</button>
    </div>

    <!-- ================= FACILITY INFO ================= -->
    <table id="facility_info_table"
      class="table table-bordered table-sm compact-table mb-3">
      <tr>
        <th colspan="4" style="background:#e67710;color:#fff">
          National Quality Assurance Standards
        </th>
      </tr>
      <tr>
        <th colspan="4" style="background:#e67710;color:#fff">
          <?= htmlspecialchars($_SESSION['facname'] ?? 'Facility') ?>
        </th>
      </tr>
      <tr>
        <th>Hospital</th>
        <td><?= htmlspecialchars($info['fac_name']) ?></td>
        <th>Date</th>
        <td><?= htmlspecialchars($info['assessment_date']) ?></td>
      </tr>
      <tr>
        <th>Assessors</th>
        <td><?= htmlspecialchars($info['Assessor_name']) ?></td>
        <th>Assessee</th>
        <td><?= htmlspecialchars($info['assessi_name']) ?></td>
      </tr>
      <tr>
        <th>Assessment</th>
        <td><?= htmlspecialchars($info['assessment_name']) ?></td>
          <th>Department</th>
        <td><?= htmlspecialchars($_SESSION['dept_name']) ?></td>
      </tr>
      
    </table>

    <!-- ================= MAIN REPORT ================= -->
    <div class="table-responsive">
      <table id="tbl_exporttable_to_xls"
        class="table table-bordered table-sm compact-table">

        <thead class="thead-compact">
          <tr>
            <th>Ref</th>
            <th>Measurable Element</th>
            <th>Checkpoint</th>
            <th>Means of Verification</th>
            <th>Method</th>
            <th>Compliance</th>
          </tr>
        </thead>


        <tbody>
          <?php
          /* =========================================================
   FETCH FULL REPORT (ONE FAST SP CALL)
========================================================= */
          $report = [];
          $q = mysqli_query($con, "CALL fac_tot_reports_all_fast($Fa,$dept_id,$p)");

          while ($row = mysqli_fetch_assoc($q)) {
            $cid = $row['concern_id'];
            $sid = $row['c_subtype_id'];

            if (!isset($report[$cid])) {
              $report[$cid] = [
                'name' => $row['concern_name'],
                'subs' => []
              ];
            }

            if (!isset($report[$cid]['subs'][$sid])) {
              $report[$cid]['subs'][$sid] = [
                'ref'   => $row['Reference_No'],
                'title' => $row['area_of_con_subtypedeatils'],
                'rows'  => []
              ];
            }

            $report[$cid]['subs'][$sid]['rows'][] = $row;
          }
          mysqli_free_result($q);
          $con->next_result();

          /* =========================================================
   RENDER (VERY FAST)
========================================================= */
          foreach ($report as $concern) {

            echo "<tr class='concern-row'>
            <th colspan='6'>" . htmlspecialchars($concern['name']) . "</th>
          </tr>";

            foreach ($concern['subs'] as $sub) {

              echo "<tr>
                <th class='subtype-ref'>" . htmlspecialchars($sub['ref']) . "</th>
                <th colspan='5' class='subtype-title'>
                  " . htmlspecialchars($sub['title']) . "
                </th>
              </tr>";

              foreach ($sub['rows'] as $r) {
                echo "<tr>
                    <td class='center'>" . htmlspecialchars($r['csqa_reference_id']) . "</td>
                    <td>" . htmlspecialchars($r['Measurable_Element']) . "</td>
                    <td>" . htmlspecialchars($r['Checkpoint']) . "</td>
                    <td>" . htmlspecialchars($r['Means_of_Verification']) . "</td>
                    <td>" . htmlspecialchars($r['Assessment_Method']) . "</td>
                    <td class='center'>" . htmlspecialchars($r['ass_compliance']) . "</td>
                  </tr>";
              }
            }
          }
          ?>
        </tbody>
      </table>
    </div>

  </div>
</div>

<!-- ================= EXCEL EXPORT ================= -->
<script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
<script>
function exportToExcel() {

  const infoTable   = document.getElementById('facility_info_table');
  const reportTable = document.getElementById('tbl_exporttable_to_xls');

  if (!infoTable || !reportTable) {
    alert('Tables not found for export');
    return;
  }

  /* ===== CLONE TABLES ===== */
  const infoClone   = infoTable.cloneNode(true);
  const reportClone = reportTable.cloneNode(true);

  /* =====================================================
     FACILITY INFO TABLE (TH + TD BORDERS FIX)
  ===================================================== */
  infoClone.querySelectorAll('th, td').forEach(cell => {
    cell.style.border = '1px solid #444';
    cell.style.padding = '6px';
    cell.style.fontSize = '12px';
    cell.style.verticalAlign = 'middle';
    cell.style.whiteSpace = 'normal';
  });

  /* =====================================================
     REPORT TABLE HEADERS
  ===================================================== */
  reportClone.querySelectorAll('thead th').forEach(th => {
    th.style.backgroundColor = '#2f2f4f';
    th.style.color = '#ffffff';
    th.style.fontWeight = 'bold';
    th.style.textAlign = 'center';
    th.style.border = '1px solid #444';
    th.style.padding = '6px';
    th.style.fontSize = '12px';
  });

  /* =====================================================
     CONCERN ROWS
  ===================================================== */
  reportClone.querySelectorAll('.concern-row th').forEach(th => {
    th.style.backgroundColor = '#3d3d5c';
    th.style.color = '#ffffff';
    th.style.fontWeight = 'bold';
    th.style.textAlign = 'center';
    th.style.border = '1px solid #444';
  });

  /* =====================================================
     SUBTYPE REF
  ===================================================== */
  reportClone.querySelectorAll('.subtype-ref').forEach(td => {
    td.style.backgroundColor = '#66a8ff';
    td.style.color = '#ffffff';
    td.style.fontWeight = 'bold';
    td.style.textAlign = 'center';
    td.style.border = '1px solid #444';
  });

  /* =====================================================
     SUBTYPE TITLE
  ===================================================== */
  reportClone.querySelectorAll('.subtype-title').forEach(td => {
    td.style.backgroundColor = '#ffc300';
    td.style.fontWeight = 'bold';
    td.style.border = '1px solid #444';
  });

  /* =====================================================
     ALL REPORT CELLS (FINAL SAFETY NET)
  ===================================================== */
  reportClone.querySelectorAll('th, td').forEach(cell => {
    cell.style.border = '1px solid #444';
    cell.style.padding = '6px';
    cell.style.fontSize = '12px';
    cell.style.verticalAlign = 'top';
    cell.style.whiteSpace = 'normal';
    cell.style.wordBreak = 'break-word';
  });

  /* =====================================================
     FINAL HTML FOR EXCEL
  ===================================================== */
  const html = `
    <html xmlns:o="urn:schemas-microsoft-com:office:office"
          xmlns:x="urn:schemas-microsoft-com:office:excel"
          xmlns="http://www.w3.org/TR/REC-html40">
    <head>
      <meta charset="utf-8">
    </head>
    <body>
      ${infoClone.outerHTML}
      <br><br>
      ${reportClone.outerHTML}
    </body>
    </html>
  `;

  const blob = new Blob([html], {
    type: 'application/vnd.ms-excel'
  });

  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = 'Assessment_Report.xls';
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
}
</script>
