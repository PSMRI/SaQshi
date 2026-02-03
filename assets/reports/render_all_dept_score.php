<?php
ini_set('max_execution_time', 3000);
if (session_status() === PHP_SESSION_NONE) session_start();
require_once "assets/conn/db.php";

$fat = $_SESSION["f_type_id"] ?? 0;
$p   = $_SESSION["period"] ?? 0;
$Fa  = $_SESSION['u_facilityid'] ?? 0;

$facility_safe = preg_replace('/[^A-Za-z0-9_\-]/', '_', $_SESSION['facname'] ?? 'Facility');

/* ================= DEPARTMENTS ================= */
$departments = [];
$stmt = $con->prepare("
  SELECT d.fac_dept_id, d.dept_name
  FROM fac_dept_map m
  JOIN fac_department d ON d.fac_dept_id = m.fac_dept_id
  WHERE m.fac_id = ? AND m.acc_id = ?
");
$stmt->bind_param("ii", $Fa, $p);
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) {
  $departments[$r['fac_dept_id']] = $r['dept_name'];
}
$stmt->close();
?>
<div class="card">
  <div class="card-body text-center">

    <button id="exportButton"
            class="btn btn-success btn-lg"
            onclick="exportMultiDeptAsExcel()">
      Download All Departments Scorecard
    </button>

    <div id="exportSpinner" style="display:none;margin-top:10px;font-weight:bold">
      <span class="spinner-border spinner-border-sm"></span>
      Generating Excel, please wait…
    </div>

    <div id="progressLog" style="font-size:13px;color:green;margin-top:10px"></div>

  </div>
</div>
<div style="display:none">
<?php foreach ($departments as $dept_id => $dept_name): ?>

<?php
$data = [];
$q = mysqli_query($con, "CALL fac_tot_reports_all_fast($Fa,$dept_id,$p)");
while ($r = mysqli_fetch_assoc($q)) $data[] = $r;
mysqli_free_result($q);
$con->next_result();

$grouped = [];
foreach ($data as $r) {
  $cid = $r['concern_id'];
  $sid = $r['c_subtype_id'];

  $grouped[$cid]['name'] = $r['concern_name'];
  $grouped[$cid]['subs'][$sid]['ref'] = $r['Reference_No'];
  $grouped[$cid]['subs'][$sid]['title'] = $r['area_of_con_subtypedeatils'];
  $grouped[$cid]['subs'][$sid]['rows'][] = $r;
}
?>

<div class="dept-section" data-dept-name="<?= htmlspecialchars($dept_name) ?>">
<table>

<?php foreach ($grouped as $concern): ?>
<tr><th colspan="6"><?= $concern['name'] ?></th></tr>

<?php foreach ($concern['subs'] as $sub): ?>
<tr>
  <th><?= $sub['ref'] ?></th>
  <th colspan="5"><?= $sub['title'] ?></th>
</tr>

<?php foreach ($sub['rows'] as $r): ?>
<tr>
  <td><?= $r['csqa_reference_id'] ?></td>
  <td><?= $r['Measurable_Element'] ?></td>
  <td><?= $r['Checkpoint'] ?></td>
  <td><?= $r['Means_of_Verification'] ?></td>
  <td><?= $r['Assessment_Method'] ?></td>
  <td><?= $r['ass_compliance'] ?></td>
</tr>
<?php endforeach; ?>
<?php endforeach; ?>
<?php endforeach; ?>
</table>
</div>

<?php endforeach; ?>
</div>
<script src="https://unpkg.com/xlsx-populate/browser/xlsx-populate.min.js"></script>
<script>
async function exportMultiDeptAsExcel() {

  const spinner = document.getElementById("exportSpinner");
  const btn     = document.getElementById("exportButton");
  const log     = document.getElementById("progressLog");

  spinner.style.display = "block";
  btn.disabled = true;
  log.innerHTML = "⏳ Starting export…<br>";

  try {

    const workbook = await XlsxPopulate.fromBlankAsync();
    const sections = document.querySelectorAll(".dept-section");

    let sheetIndex = 0;

    for (const section of sections) {

      const deptName = section.dataset.deptName || `Dept_${sheetIndex + 1}`;
      const safeName = deptName.replace(/[\\\/\*\[\]\:\?]/g, "_").substring(0, 31);

      const sheet = sheetIndex === 0
        ? workbook.sheet(0).name(safeName)
        : workbook.addSheet(safeName);

      let row = 1;

      /* =====================================================
         HEADER BLOCK (EXACT FORMAT YOU ASKED)
      ===================================================== */

      sheet.range(row, 1, row, 6).merged(true).value("National Quality Assurance Standards")
        .style({
          bold: true,
          horizontalAlignment: "center",
          verticalAlignment: "center",
          wrapText: true,
          border: true,
          fontSize: 13
        });
      row++;

      sheet.range(row, 1, row, 6).merged(true).value("<?= addslashes($_SESSION['facname']) ?>")
        .style({
          bold: true,
          horizontalAlignment: "center",
          verticalAlignment: "center",
          wrapText: true,
          border: true,
          fontSize: 12
        });
      row++;

      sheet.range(row, 1, row, 6).merged(true)
        .value(`Department: ${deptName} (NQAS)`)
        .style({
          bold: true,
          horizontalAlignment: "center",
          verticalAlignment: "center",
          wrapText: true,
          border: true,
          fontSize: 11
        });
      row += 2;

      /* =====================================================
         TABLE HEADER ROW
      ===================================================== */
      const headers = [
        "Ref",
        "Measurable Element",
        "Checkpoint",
        "Verification",
        "Method",
        "Compliance"
      ];

      headers.forEach((h, i) => {
        sheet.cell(row, i + 1).value(h).style({
          fill: "2f2f4f",
          fontColor: "ffffff",
          bold: true,
          border: true,
          horizontalAlignment: "center",
          verticalAlignment: "center",
          wrapText: true,
          fontSize: 11
        });
      });

      row++;

      /* =====================================================
         DATA FROM HTML TABLES
      ===================================================== */
      section.querySelectorAll("tr").forEach(tr => {

        const cells = tr.querySelectorAll("th,td");

        /* -------- Concern Row -------- */
        if (cells.length === 1 && cells[0].tagName === "TH") {

          sheet.range(row, 1, row, 6).merged(true)
            .value(cells[0].innerText.trim())
            .style({
              fill: "3d3d5c",
              fontColor: "ffffff",
              bold: true,
              border: true,
              horizontalAlignment: "center",
              verticalAlignment: "center",
              wrapText: true,
              fontSize: 11
            });

          row++;
          return;
        }

        /* -------- Standard Row -------- */
        if (cells.length > 1 && cells[0].tagName === "TH" && cells[1].tagName === "TH") {

          sheet.cell(row, 1).value(cells[0].innerText.trim())
            .style({
              fill: "66a8ff",
              fontColor: "ffffff",
              bold: true,
              border: true,
              horizontalAlignment: "center",
              verticalAlignment: "center",
              wrapText: true,
              fontSize: 11
            });

          sheet.range(row, 2, row, 6).merged(true)
            .value(cells[1].innerText.trim())
            .style({
              fill: "ffc300",
              bold: true,
              border: true,
              verticalAlignment: "center",
              wrapText: true,
              fontSize: 11
            });

          row++;
          return;
        }

        /* -------- ME ROWS -------- */
        let col = 1;
        cells.forEach(td => {
          sheet.cell(row, col).value(td.innerText.trim())
            .style({
              border: true,
              wrapText: true,
              verticalAlignment: "top",
              fontSize: 11
            });
          col++;
        });

        row++;
      });

      log.innerHTML += `✔ ${deptName} exported<br>`;
      sheetIndex++;
    }

    if (workbook.sheet("Sheet1")) {
      workbook.deleteSheet("Sheet1");
    }

    const blob = await workbook.outputAsync();
    const url  = URL.createObjectURL(blob);

    const a = document.createElement("a");
    a.href = url;
    a.download = "<?= $facility_safe ?>_All_Department_Scorecard.xlsx";
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);

    log.innerHTML += "<br><b>✅ Export Complete</b>";

  } catch (err) {

    console.error(err);
    alert("Export failed. Check console.");
    log.innerHTML += "<br><span style='color:red'>❌ Export failed</span>";

  } finally {

    spinner.style.display = "none";
    btn.disabled = false;
  }
}
</script>
