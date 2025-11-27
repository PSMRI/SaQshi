<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

include("assets/conn/db.php");

$Fa = $_SESSION['u_facilityid'];
$p = $_SESSION['assperiod'] ?? $_SESSION['period']; // depends on your naming
$fat = $_SESSION['f_type_id'];
$t = $_SESSION['userid'] ?? 0;

$facility_name_safe = preg_replace('/[^A-Za-z0-9_\-]/', '_', $_SESSION['facname'] ?? 'Facility');

// Fetch department list
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
<div class="row">
  <div class="col-md-12">
    <h3>Scorecard - All Departments</h3>

    <button class="btn-success" onclick="exportScorecard()">Download All Departments Scorecard (Excel)</button>

    <div id="progressLog" style="margin-top:10px; font-size: 14px; color: green;"></div>
  </div>
</div>
<script src="https://unpkg.com/xlsx-populate@latest/browser/xlsx-populate.min.js"></script>

<script>
  async function exportScorecard() {
    document.getElementById("progressLog").innerHTML = "Preparing export...";

    try {
      const response = await fetch("assets/reports/ajax_scorecard_export.php");
      if (!response.ok) {
        throw new Error("Failed to fetch scorecard data.");
      }
      const deptData = await response.json();

      window.XlsxPopulate.fromBlankAsync().then(async workbook => {
        deptData.forEach(dept => {
          const dept_name = dept.dept_name;
          const concernRows = dept.concernRows;
          const detailRows = dept.detailRows;

          const sheet = workbook.addSheet(dept_name.substring(0, 31));

          // Title
          sheet.cell("A1").value("Scorecard - " + dept_name).style({
            bold: true,
            fontColor: "ffffff",
            fill: "1F4E78",
            horizontalAlignment: "center"
          });
          sheet.range("A1:H1").merged(true);

          let rowOffset = 3;

          // Export Concern Table first
          concernRows.forEach((row, i) => {
            row.forEach((val, j) => {
              const targetCell = sheet.cell(rowOffset + i, j + 1);
              targetCell.value(val);
              targetCell.style("border", true);
              if (i === 0) {
                targetCell.style({
                  bold: true,
                  fill: "33334d",
                  fontColor: "ffffff"
                });
              }
            });
          });

          rowOffset += concernRows.length + 2;

          // Export Detailed Table second
          detailRows.forEach((row, i) => {
            row.forEach((val, j) => {
              const targetCell = sheet.cell(rowOffset + i, j + 1);
              targetCell.value(val);
              targetCell.style("border", true);
              if (i === 0) {
                targetCell.style({
                  bold: true,
                  fill: "D9D9D9",
                  fontColor: "000000"
                });
              }
            });
          });

          // Log progress done
          document.getElementById("progressLog").innerHTML += `<div>✅ ${dept_name} ready.</div>`;
        });

        workbook.deleteSheet("Sheet1");

        document.getElementById("progressLog").innerHTML += "<div><b>All departments processed. Downloading file...</b></div>";
        workbook.outputAsync().then(blob => {
          const url = window.URL.createObjectURL(blob);
          const a = document.createElement("a");
          a.href = url;
          a.download = `Scorecard_All_Departments_${new Date().toISOString().slice(0,10)}.xlsx`;
          a.click();
          window.URL.revokeObjectURL(url);
          document.getElementById("progressLog").innerHTML += "<div><b>✅ Download completed!</b></div>";
        });

      });

    } catch (error) {
      console.error(error);
      document.getElementById("progressLog").innerHTML = `<div style="color:red;">Error: ${error.message}</div>`;
    }
  }
</script>