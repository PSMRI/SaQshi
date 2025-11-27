<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$dept_id = $_SESSION["dept_id"] ?? 0;
$fat     = $_SESSION["f_type_id"] ?? 0;
$p       = $_SESSION["period"] ?? 0;
$Fa      = $_SESSION['u_facilityid'] ?? 0;
$t       = $_SESSION['userid'] ?? 0;
?>
<div class="row">
  <div class="col-md-12">
    <div class="card">
      <div class="card-body">
        <?php
        $query = "SELECT a.Assessor_name,  a.assessi_name, a.assessment_date, a.assessment_name, b.fac_name
                  FROM assessor_info a
                  JOIN facilities b ON a.institute_id = b.fac_id
                  WHERE a.institute_id = $Fa AND a.assessment_period = $p and a.dept_id=$dept_id";

        $result = mysqli_query($con, $query);

        if ($result && mysqli_num_rows($result) > 0) {
          while ($info = mysqli_fetch_array($result)) {
        ?>
        <input type="button" value="Export" class="btn btn-success mb-3" onclick="exportToExcel()" />

          <!-- Assessor Info Table -->
          <div class="table-responsive small mb-4">
            <table class="table table-bordered w-100 text-center align-middle">
              <thead>
                <tr><th colspan="6" class="text-white text-center" style="background-color:#e67710">National Quality Assurance Standards</th></tr>
                <tr><th colspan="6" class="text-white text-center" style="background-color:#e67710"><?php echo $_SESSION['facname']; ?></th></tr>
              </thead>
              <tbody>
                <tr>
                  <th colspan="2">Name of Hospital</th>
                  <td colspan="4"><?php echo $info['fac_name']; ?></td>
                </tr>
                <tr>
                  <th colspan="2">Date of Assessment</th>
                  <td colspan="4"><?php echo $info['assessment_date']; ?></td>
                </tr>
                <tr>
                  <th colspan="2">Name of Assessors</th>
                  <td colspan="4"><?php echo $info['Assessor_name']; ?></td>
                </tr>
                <tr>
                  <th colspan="2">Name of Assessee</th>
                  <td colspan="4"><?php echo $info['assessi_name']; ?></td>
                </tr>
                <tr>
                  <th colspan="2">Type of Assessment</th>
                  <td colspan="4"><?php echo $info['assessment_name']; ?></td>
                </tr>
                <tr>
                  <th colspan="2">Action Plan Submission Date</th>
                  <td colspan="4">........</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Main Assessment Table -->
          <div class="table-responsive small">
            <table id="tbl_exporttable_to_xls" class="table table-bordered w-100 text-center align-middle">
              <thead>
                <tr class="table-dark text-center">
                  <th>Reference No.</th>
                  <th>Measurable Element</th>
                  <th>Checkpoint</th>
                    <th>Means of Verification</th>
                      <th>Assessment Method</th>
                  <th>Compliance</th>
                  <th>Remarks</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $aocQuery = "SELECT concern_name, concern_id FROM area_of_concern";
                $aocResult = mysqli_query($con, $aocQuery);
                while ($aoc = mysqli_fetch_array($aocResult)) {
                  $conid = $aoc['concern_id'];
                  echo "<tr><th colspan='7' class='text-white text-center' style='background-color:#3d3d5c'>{$aoc['concern_name']}</th></tr>";

                  $subtypeQuery = "SELECT Reference_No, c_subtype_id, area_of_con_subtypedeatils
                                    FROM sarbsoft_nqa.area_of_concern_subtype
                                    WHERE c_subtype_id IN (
                                        SELECT DISTINCT c_subtype_id_fk
                                        FROM chk_list_assessment
                                        WHERE fac_id_fk = $Fa
                                          AND ass_period_id = $p
                                          AND fac_dept_id_fk = $dept_id
                                          AND area_of_con_id_fk = $conid
                                         
                                    )
                                    ORDER BY c_subtype_id ASC";

                  $subtypeResult = mysqli_query($con, $subtypeQuery);
                  while ($sub = mysqli_fetch_array($subtypeResult)) {
                    $sub_id = $sub['c_subtype_id'];
                    echo "<tr>
                            <th style='background-color:#66a8ff; color:white'>{$sub['Reference_No']}</th>
                            <th colspan='6' style='background-color:#FFC300' class='text-center'>{$sub['area_of_con_subtypedeatils']}</th>
                          </tr>";

                    $detailQuery = "CALL fac_tot_reports($Fa, $t, $sub_id, $conid, $dept_id, $p)";
                    $detailResult = mysqli_query($con, $detailQuery);
                    while ($item = mysqli_fetch_array($detailResult)) {
                      echo "<tr>
                              <td style='background-color:#66a8ff; color:white'>{$item['csqa_reference_id']}</td>
                              <td>{$item['Measurable_Element']}</td>
                              <td>{$item['Checkpoint']}</td>
                              <td>{$item['Means_of_Verification']}</td>
                                <td>{$item['Assessment_Method']}</td>
                              <td>{$item['ass_compliance']}</td> 
                              <td>{$item['Remarks']}</td>
                            </tr>";
                    }
                    mysqli_free_result($detailResult);
                    $con->next_result();
                  }
                  mysqli_free_result($subtypeResult);
                  $con->next_result();
                }
                mysqli_free_result($aocResult);
                $con->next_result();
                ?>
              </tbody>
            </table>
          </div>
        <?php
          }
          mysqli_free_result($result);
          $con->next_result();
        } else {
          echo "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                  Kindly Fill Assessor info first..! <a href='assessor.php'> Assessor info </a>
                  <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                </div>";
        }
        ?>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
<script>
function exportToExcel() {
  // Get both tables by their IDs or tag positions
  const infoTable = document.querySelector('.table.table-bordered.w-100.text-center.align-middle');
  const mainTable = document.getElementById('tbl_exporttable_to_xls');

  // Clone tables to avoid modifying live DOM
  const infoClone = infoTable.cloneNode(true);
  const mainClone = mainTable.cloneNode(true);

  // Combine both tables into one HTML for Excel export
  const html = `
    <html xmlns:o="urn:schemas-microsoft-com:office:office"
          xmlns:x="urn:schemas-microsoft-com:office:excel"
          xmlns="http://www.w3.org/TR/REC-html40">
    <head>
      <meta charset="utf-8">
      <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 30px; }
        th, td { border: 1px solid #444; padding: 8px; text-align: center; font-size: 13px; }
        th { background-color: #f2f2f2; }
      </style>
    </head>
    <body>
      ${infoClone.outerHTML}
      ${mainClone.outerHTML}
    </body>
    </html>
  `;

  // Create Excel Blob
  const blob = new Blob([html], { type: 'application/vnd.ms-excel' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = 'Assessment_Report.xls';
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
}
</script>
