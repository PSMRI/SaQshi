<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("assets/conn/db.php");

$Fa = $_SESSION['u_facilityid'];
$p = $_SESSION['assperiod'];
$fat = $_SESSION['f_type_id'];
$acc_id = $p; // period used as acc_id
$facility_name_safe = preg_replace('/[^A-Za-z0-9_\-]/', '_', $_SESSION['facname'] ?? 'Facility');
// Fetch department list (for progress log only)
$dept_info = [];
$stmt = $con->prepare("SELECT DISTINCT a.fac_dept_id_fk, b.dept_name
                       FROM concern_subtype_chklist AS a
                       JOIN fac_department AS b ON a.fac_dept_id_fk = b.fac_dept_id
                       WHERE a.fac_type_id_fk = ? 
                       AND a.fac_dept_id_fk IN (
                           SELECT fac_dept_id FROM fac_dept_map 
                           WHERE fac_id = ? AND acc_id = ?
                       )");
$stmt->bind_param("iii", $fat, $Fa, $acc_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $dept_info[$row['fac_dept_id_fk']] = $row['dept_name'];
}
$stmt->close();
?>
<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-body">

                <h3>Outcome Report - All Departments</h3>

                <button class="btn-success" onclick="exportAllDepartmentsOutcome()">Download All Departments Outcome (Excel)</button>

                <div id="progressLog" style="margin-top:10px; font-size: 14px; color: green;"></div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/xlsx-populate/browser/xlsx-populate.min.js"></script>

<script>
    // ✅ Helper: clean Excel sheet name
    function cleanSheetName(name) {
        // Replace forbidden characters
        name = name.replace(/[\\/*[\]:?]/g, "_");

        // Trim spaces
        name = name.trim();

        // Excel sheet name max length is 31
        if (name.length > 31) {
            name = name.substring(0, 31);
        }

        // Ensure not empty
        if (!name) {
            name = "Sheet";
        }
        return name;
    }

    // ✅ Helper: clean file name
    function cleanFileName(name) {
        return name.replace(/[^A-Za-z0-9_\-]/g, "_");
    }

    async function exportAllDepartmentsOutcome() {
        const facilityName = "<?php echo $facility_name_safe; ?>";
        document.getElementById("progressLog").innerHTML = "Preparing export...";

        try {
            const response = await fetch("assets/reports/ajax_outcome_export.php");
            if (!response.ok) {
                throw new Error("Failed to fetch outcome data.");
            }
            const deptData = await response.json();

            window.XlsxPopulate.fromBlankAsync().then(async workbook => {
                deptData.forEach(dept => {
                    const dept_name = cleanSheetName(dept.dept_name);
                    const monthColumns = dept.monthColumns;
                    const rows = dept.rows;

                    const sheet = workbook.addSheet(dept_name);

                    // Title
                    sheet.cell("A1").value("Outcome Report - " + dept_name).style({
                        bold: true,
                        fontColor: "ffffff",
                        fill: "1F4E78",
                        horizontalAlignment: "center"
                    });
                    sheet.range("A1:H1").merged(true);

                    // Column Headers
                    const headers = ["Indicator"].concat(monthColumns);
                    headers.forEach((h, i) => {
                        sheet.cell(2, i + 1).value(h).style({
                            bold: true,
                            fill: "D9D9D9",
                            border: true
                        });
                    });

                    // Data Rows
                    rows.forEach((row, rIdx) => {
                        sheet.cell(rIdx + 3, 1).value(row.indicator).style("border", true);
                        monthColumns.forEach((month, cIdx) => {
                            sheet.cell(rIdx + 3, cIdx + 2).value(row[month] ?? "-").style("border", true);
                        });
                    });

                    // Freeze header row
                    sheet.freezePanes(3, 1);

                    // Set width for better look
                    for (let i = 1; i <= headers.length; i++) {
                        sheet.column(i).width(20);
                    }

                    // Log progress done
                    document.getElementById("progressLog").innerHTML += `<div>✅ ${dept_name} ready.</div>`;
                });

                // Delete empty initial Sheet1
                workbook.deleteSheet("Sheet1");

                // Final download
                document.getElementById("progressLog").innerHTML += "<div><b>All departments processed. Downloading file...</b></div>";

                workbook.outputAsync().then(blob => {
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement("a");
                    a.href = url;
                    const safeFileName = cleanFileName(facilityName) + "_Outcome_All_Departments_" + new Date().toISOString().slice(0, 10) + ".xlsx";
                    a.download = safeFileName;
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