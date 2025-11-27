<?php
include("assets/head/h.php");
$dhTotal = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) AS cnt FROM facilities WHERE Health_facilty_type = 2"))['cnt'];
$sdhTotal = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) AS cnt FROM facilities WHERE Health_facilty_type = 10"))['cnt'];

$dhMusqanCount = mysqli_fetch_assoc(mysqli_query($con, "
    SELECT COUNT(DISTINCT facid) AS cnt 
    FROM department_wise_state_dash 
    WHERE fac_dept_id_fk IN (5,6,7,23) AND Health_facilty_type = 2
"))['cnt'];

$sdhMusqanCount = mysqli_fetch_assoc(mysqli_query($con, "
    SELECT COUNT(DISTINCT facid) AS cnt 
    FROM department_wise_state_dash 
    WHERE fac_dept_id_fk IN (5,6,7,23) AND Health_facilty_type = 10
"))['cnt'];

$query = "
    SELECT 
        facid,
        Dist_Name AS district,
        Block_Name AS block,
        fac_name AS facility,
        facility_type,
        ass_name AS assessment_name,
        Health_facilty_type,
        SUM(zero) AS zero_count,
        SUM(one) AS one_count,
        SUM(two) AS two_count,
        SUM(non) AS non_compliant,
        SUM(total) AS total_checks,
        SUM(marks_obtained) AS marks_obtained,
        SUM(total_marks) AS total_marks,
        ROUND(AVG(percentage), 2) AS avg_percentage
    FROM department_wise_state_dash
    WHERE fac_dept_id_fk IN (5,6,7,23)
      AND Health_facilty_type IN (2, 10)
    GROUP BY facid, ass_name, Health_facilty_type
";
$result = mysqli_query($con, $query);
$dhData = [];
$sdhData = [];

while ($row = mysqli_fetch_assoc($result)) {
    if ($row['Health_facilty_type'] == 2) $dhData[] = $row;
    elseif ($row['Health_facilty_type'] == 10) $sdhData[] = $row;
}
?>


<div class="pcoded-main-container">
        <div class="pcoded-content">
       
        <div class="row">
            <div class="col-md-6">
                <div class="card text-white bg-primary" style="cursor:pointer;" onclick="showReport('dh')">
                    <div class="card-body">
                        <h5 class="card-title">District Hospitals (DH)</h5>
                        <p>MusQan: <strong><?= $dhMusqanCount ?></strong> / <strong><?= $dhTotal ?></strong></p>
                        <p>Click to view compliance report.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card text-white bg-success" style="cursor:pointer;" onclick="showReport('sdh')">
                    <div class="card-body">
                        <h5 class="card-title">Sub-Divisional Hospitals (SDH)</h5>
                        <p>MusQan: <strong><?= $sdhMusqanCount ?></strong> / <strong><?= $sdhTotal ?></strong></p>
                        <p>Click to view compliance report.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-3" id="report-section" style="display:none;">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 id="report-title" class="text-primary">MusQan Report</h5>
                            <button class="btn btn-sm btn-outline-secondary" onclick="downloadExcel()">⬇ Export to Excel</button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="report-table">
                                <thead class="table-light">
                                    <tr>
                                       
                                        <th>District</th>
                                        <th>Block</th>
                                        <th>Facility</th>
                                        <th>Assessment</th>
                                        <th>Zero</th>
                                        <th>One</th>
                                        <th>Two</th>
                                        <th>Non-Compliant</th>
                                        <th>Total Checks</th>
                                        <th>Marks Obtained</th>
                                        <th>Total Marks</th>
                                        <th>Avg. %</th>
                                    </tr>
                                </thead>
                                <tbody id="report-body"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card mt-4">
                    <div class="card-body">
                        <h5 class="card-title">DH Facility-wise Compliance</h5>
                        <canvas id="dhChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card mt-4">
                    <div class="card-body">
                        <h5 class="card-title">SDH Facility-wise Compliance</h5>
                        <canvas id="sdhChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<?php include("assets/head/f.php"); ?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.sheetjs.com/xlsx-0.20.0/package/dist/xlsx.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
const dhData = <?= json_encode($dhData); ?>;
const sdhData = <?= json_encode($sdhData); ?>;
let currentData = [];

function showReport(type) {
    currentData = type === 'dh' ? dhData : sdhData;
    const title = type === 'dh' ? "District Hospital (DH) Report" : "Sub-Divisional Hospital (SDH) Report";

    if ($.fn.DataTable.isDataTable('#report-table')) {
        $('#report-table').DataTable().clear().destroy();
    }

    document.getElementById("report-title").innerText = title;
    document.getElementById("report-section").style.display = "block";
    const tbody = document.getElementById("report-body");
    tbody.innerHTML = "";

    currentData.forEach(row => {
        const perc = parseFloat(row.avg_percentage);
        let colorClass = perc >= 80 ? 'table-success' : (perc >= 60 ? 'table-warning' : 'table-danger');

        tbody.innerHTML += `<tr class="${colorClass}">
          
            <td>${row.district}</td>
            <td>${row.block}</td>
            <td>${row.facility}</td>
            <td>${row.assessment_name}</td>
            <td>${row.zero_count}</td>
            <td>${row.one_count}</td>
            <td>${row.two_count}</td>
            <td>${row.non_compliant}</td>
            <td>${row.total_checks}</td>
            <td>${row.marks_obtained}</td>
            <td>${row.total_marks}</td>
            <td>${row.avg_percentage}%</td>
        </tr>`;
    });

    $('#report-table').DataTable({
        pageLength: 10,
        lengthChange: true,
        ordering: true,
        responsive: true,
        language: { search: "Search facility:" }
    });
}

function downloadExcel() {
    if (!currentData.length) return alert("No data to export. Please select DH or SDH.");
    const headers = [
        "Facility ID", "District", "Block", "Facility", "Assessment",
        "Zero", "One", "Two", "Non-Compliant", "Total Checks",
        "Marks Obtained", "Total Marks", "Avg. %"
    ];
    const rows = currentData.map(row => [
        row.facid, row.district, row.block, row.facility, row.assessment_name,
        row.zero_count, row.one_count, row.two_count, row.non_compliant,
        row.total_checks, row.marks_obtained, row.total_marks, row.avg_percentage + "%"
    ]);
    const worksheet = XLSX.utils.aoa_to_sheet([headers, ...rows]);
    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, "MusQan Report");
    XLSX.writeFile(workbook, "MusQan_Report.xlsx");
}

function getBarColors(values) {
    return values.map(val => {
        if (val >= 80) return 'rgba(40,167,69,0.8)'; // green
        if (val >= 60) return 'rgba(255,193,7,0.8)';  // yellow
        return 'rgba(220,53,69,0.8)';                // red
    });
}

function renderCharts() {
    if (window.dhChartObj) window.dhChartObj.destroy();
    if (window.sdhChartObj) window.sdhChartObj.destroy();

    const dhLabels = dhData.map(d => d.facility);
    const dhValues = dhData.map(d => parseFloat(d.avg_percentage));
    const dhColors = getBarColors(dhValues);
    const ctxDH = document.getElementById('dhChart').getContext('2d');
    window.dhChartObj = new Chart(ctxDH, {
        type: 'bar',
        data: {
            labels: dhLabels,
            datasets: [{
                label: 'Avg % Compliance (DH)',
                data: dhValues,
                backgroundColor: dhColors
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false }},
            scales: {
                y: { beginAtZero: true, max: 100 },
                x: { ticks: { autoSkip: false } }
            }
        }
    });

    const sdhLabels = sdhData.map(d => d.facility);
    const sdhValues = sdhData.map(d => parseFloat(d.avg_percentage));
    const sdhColors = getBarColors(sdhValues);
    const ctxSDH = document.getElementById('sdhChart').getContext('2d');
    window.sdhChartObj = new Chart(ctxSDH, {
        type: 'bar',
        data: {
            labels: sdhLabels,
            datasets: [{
                label: 'Avg % Compliance (SDH)',
                data: sdhValues,
                backgroundColor: sdhColors
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false }},
            scales: {
                y: { beginAtZero: true, max: 100 },
                x: { ticks: { autoSkip: false } }
            }
        }
    });
}

document.addEventListener('DOMContentLoaded', renderCharts);
</script>
