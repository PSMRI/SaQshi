<?php
ini_set('max_execution_time', 3000);
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

include("assets/conn/db.php");

$dept_id = $_SESSION["dept_id"] ?? 0;
$fat     = $_SESSION["f_type_id"] ?? 0;
$p       = $_SESSION["period"] ?? 0;
$Fa      = $_SESSION['u_facilityid'] ?? 0;
$t       = $_SESSION['userid'] ?? 0;

$facility_name_safe = preg_replace('/[^A-Za-z0-9_\-]/', '_', $_SESSION['facname'] ?? 'Facility');

$dept_info = [];

$stmt = $con->prepare("SELECT DISTINCT a.fac_dept_id_fk, b.dept_name
                       FROM concern_subtype_chklist AS a
                       JOIN fac_department AS b ON a.fac_dept_id_fk = b.fac_dept_id
                       WHERE a.fac_type_id_fk = ? 
                       AND a.fac_dept_id_fk IN (
                           SELECT fac_dept_id FROM fac_dept_map 
                           WHERE fac_id = ? AND acc_id = ?
                       )");
$stmt->bind_param("iii", $fat, $Fa, $p);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
  $dept_info[$row['fac_dept_id_fk']] = $row['dept_name'];
}

$stmt->close();
?>
<style>
  .hidden-export-table {
    display: none !important;
  }
</style>
<div class="row">
  <div class="col-sm-12">
    <div class="card">
      <div class="card-body">
         <h3>Score card - All Departments</h3>

        <button id="exportButton" class="btn btn-success mb-3" onclick="exportMultiDeptAsExcel()">Download All Departments score card</button>
        <div id="exportSpinner" class="mb-3" style="display:none; color:blue; font-weight:bold;">
          <span class="spinner-border spinner-border-sm"></span> Please wait, generating Excel...
        </div>
        <div id="progressLog" style="font-size: 14px; color: green; margin-bottom: 10px;"></div>
      </div>
    </div>
  </div>
</div>
<?php
foreach ($dept_info as $dept_id => $dept_name) {
  // Fetch Assessor Info
  $query = "SELECT a.Assessor_name, a.assessi_name, a.assessment_date, a.assessment_name, b.fac_name
              FROM assessor_info a
              JOIN facilities b ON a.institute_id = b.fac_id
              WHERE a.institute_id = $Fa AND a.assessment_period = $p";
  $result = mysqli_query($con, $query);
  $info = mysqli_fetch_array($result);
?>


  <!-- Concern Type Report Table -->
  <?php
  $tablequery1 = "CALL concern_type_report($fat, $Fa, $dept_id, $p)";
  $q2 = mysqli_query($con, $tablequery1);

  if ($q2 && $row = mysqli_fetch_array($q2)) {
  ?>

    <div class="table-responsive small hidden-export-table">
      <table id="table2_<?php echo $dept_id; ?>" class="table table-bordered w-100 text-center align-middle">

        <tr>

          <td colspan="6" style="background-color:#33334d; color:white; font-weight:bold; text-align:center;">
            <?= htmlspecialchars($_SESSION['facname'] ?? 'Facility') ?> Overall Score & Area of Concern wise Scores
          </td>
        </tr>
        <tr>
          <td style="border:none;"></td>
          <td style="background-color:#ff9933; color:white; font-weight:bold;">Service Provision</td>
          <td style="background-color:#ff9933; color:white; font-weight:bold;">Patients' Rights</td>
          <td colspan="2" rowspan="2" style="background-color:#fff4dc; font-weight:bold; text-align:center; font-size:16px;">Overall Facility Score</td>
          <td style="background-color:#ff9933; color:white; font-weight:bold;">Inputs</td>
          <td style="background-color:#ff9933; color:white; font-weight:bold;">Support Services</td>
        </tr>
        <tr>
          <td style="border:none;"></td>
          <td style="background-color:#fff4dc; font-weight:bold;"> <?= $row['totalc1'] ? round($row['d1'] / $row['totalc1'] * 100, 2) . '%' : '0%' ?> </td>
          <td style="background-color:#fff4dc; font-weight:bold;"> <?= $row['totalc2'] ? round($row['d2'] / $row['totalc2'] * 100, 2) . '%' : '0%' ?> </td>
          <td style="border:none;"></td>
          <td style="background-color:#fff4dc; font-weight:bold;"> <?= $row['totalc3'] ? round($row['d3'] / $row['totalc3'] * 100, 2) . '%' : '0%' ?> </td>
          <td style="background-color:#fff4dc; font-weight:bold;"> <?= $row['totalc4'] ? round($row['d4'] / $row['totalc4'] * 100, 2) . '%' : '0%' ?> </td>
        </tr>
        <tr>
          <td style="border:none;"></td>
          <td style="background-color:#ff9933; color:white; font-weight:bold;">Clinical Services</td>
          <td style="background-color:#ff9933; color:white; font-weight:bold;">Infection Control</td>
          <td colspan="2" rowspan="2" style="background-color:#33334d; color:white; font-weight:bold; font-size:20px;"> <?= $_SESSION['p1'] ?? '0%' ?> </td>
          <td style="background-color:#ff9933; color:white; font-weight:bold;">Quality Management</td>
          <td style="background-color:#ff9933; color:white; font-weight:bold;">Outcomes</td>
        </tr>
        <tr>
          <td style="border:none;"></td>
          <td style="background-color:#fff4dc; font-weight:bold;"> <?= $row['totalc5'] ? round($row['d5'] / $row['totalc5'] * 100, 2) . '%' : '0%' ?> </td>
          <td style="background-color:#fff4dc; font-weight:bold;"> <?= $row['totalc6'] ? round($row['d6'] / $row['totalc6'] * 100, 2) . '%' : '0%' ?> </td>
          <td style="border:none;"></td>
          <td style="background-color:#fff4dc; font-weight:bold;"> <?= $row['totalc7'] ? round($row['d7'] / $row['totalc7'] * 100, 2) . '%' : '0%' ?> </td>
          <td style="background-color:#fff4dc; font-weight:bold;"> <?= $row['totalc8'] ? round($row['d8'] / $row['totalc8'] * 100, 2) . '%' : '0%' ?> </td>
        </tr>
      </table>
    </div>
  <?php
  }
  mysqli_free_result($q2);
  $con->next_result();
  ?>

  <!-- Department Detailed Report Table -->
  <div class="table-responsive small hidden-export-table">
    <table id="table_dept_<?php echo $dept_id; ?>" data-sheet-name="<?php echo $dept_name; ?>"
      data-facility-name="<?php echo $_SESSION['facname']; ?>"
      data-hospital-name="<?php echo $info['fac_name']; ?>"
      data-assessment-date="<?php echo $info['assessment_date']; ?>"
      data-assessors="<?php echo $info['Assessor_name']; ?>"
      data-assessee="<?php echo $info['assessi_name']; ?>"
      data-assessment-type="<?php echo $info['assessment_name']; ?>" data-action-plan-date="........"
      class="table table-bordered w-100 text-center align-middle">
      <thead>
        <tr class="table-dark text-center">
          <th>Reference No.</th>
          <th>Measurable Element</th>
          <th>Checkpoint</th>
          <th>Compliance</th>
          <th>Assessment Method</th>
          <th>Means of Verification</th>
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
                                               AND fac_type_id = $fat
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
                                        <td>{$item['ass_compliance']}</td>
                                        <td>{$item['Assessment_Method']}</td>
                                        <td>{$item['Means_of_Verification']}</td>
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
?>

</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/xlsx-populate/browser/xlsx-populate.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/xlsx-populate/browser/xlsx-populate.min.js"></script>
<div id="progressLog" style="margin-top:10px; font-family:Arial; font-size:13px;"></div>

<script src="https://unpkg.com/xlsx-populate/browser/xlsx-populate.min.js"></script>
<script>
  async function exportAllDepartmentsOutcome(facilityName) {
    document.getElementById("exportSpinner").style.display = "inline-block";
    document.getElementById("exportButton").disabled = true;
    document.getElementById("progressLog").innerHTML = "⏳ Starting export...<br>";

    // Create new workbook
    const workbook = await XlsxPopulate.fromBlankAsync();

    // Collect all department tables
    const deptTables = document.querySelectorAll(".dept-section");

    deptTables.forEach((deptSection, index) => {
      const deptName = deptSection.getAttribute("data-dept-name") || "Dept_" + (index + 1);

      // Replace invalid Excel sheet name chars
      const safeDeptName = deptName.replace(/[\\\/\*\[\]\:\?]/g, "_").substring(0, 31);

      // Add new sheet
      const sheet = workbook.addSheet(safeDeptName);

      // First table (concern type summary)
      const firstTable = deptSection.querySelector(".report-table");
      if (firstTable) {
        let row = 1;
        firstTable.querySelectorAll("tr").forEach((tr) => {
          let col = 1;
          tr.querySelectorAll("th,td").forEach((td) => {
            sheet.cell(row, col).value(td.innerText.trim());
            col++;
          });
          row++;
        });
      }

      // Second table (detailed report)
      const secondTable = deptSection.querySelector(".report-table + .report-table");
      if (secondTable) {
        let row = sheet.usedRange().endCell().rowNumber() + 2;
        secondTable.querySelectorAll("tr").forEach((tr) => {
          let col = 1;
          tr.querySelectorAll("th,td").forEach((td) => {
            sheet.cell(row, col).value(td.innerText.trim());
            col++;
          });
          row++;
        });
      }

      // Log progress
      const logLine = document.createElement("div");
      logLine.textContent = `✔ Processed department: ${deptName}`;
      document.getElementById("progressLog").appendChild(logLine);
    });

    // Delete default blank sheet
    workbook.deleteSheet("Sheet1");

    // Export workbook
    workbook.outputAsync().then((blob) => {
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = facilityName + "_Scorecard.xlsx";
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      window.URL.revokeObjectURL(url);

      // Reset UI
      document.getElementById("exportSpinner").style.display = "none";
      document.getElementById("exportButton").disabled = false;
      document.getElementById("progressLog").innerHTML += "<br><b>✅ Export complete!</b>";
    });
  }
</script>