<?php
include("assets/head/h.php");
include("assets/conn/db.php");

echo '<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>';
echo '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>';
echo '<script src="https://cdn.jsdelivr.net/npm/html2canvas"></script>';
echo '<script src="https://cdn.jsdelivr.net/npm/file-saver"></script>';
 $distid= $_SESSION['div_id'];
 $distname=$_SESSION['div_name'];
$dhTotal = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) AS cnt FROM facilities WHERE Health_facilty_type = 2 and dist_id=$distid"))['cnt'];
$sdhTotal = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) AS cnt FROM facilities WHERE Health_facilty_type = 10 and dist_id=$distid"))['cnt'];
$chcTotal = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) AS cnt FROM facilities WHERE Health_facilty_type = 1 and dist_id=$distid"))['cnt'];

$dhMusqanCount = mysqli_fetch_assoc(mysqli_query($con, "
    SELECT COUNT(DISTINCT facid) AS cnt 
    FROM department_wise_state_dash 
    WHERE Health_facilty_type = 2 AND fac_dept_id_fk IN (33, 9) and dist_id=$distid
"))['cnt'];

$sdhMusqanCount = mysqli_fetch_assoc(mysqli_query($con, "
    SELECT COUNT(DISTINCT facid) AS cnt 
    FROM department_wise_state_dash 
    WHERE Health_facilty_type = 10 AND fac_dept_id_fk IN (33, 9) and dist_id=$distid
"))['cnt'];

$chcMusqanCount = mysqli_fetch_assoc(mysqli_query($con, "
    SELECT COUNT(DISTINCT facid) AS cnt 
    FROM department_wise_state_dash 
    WHERE Health_facilty_type = 1 AND fac_dept_id_fk IN (3, 39) and dist_id=$distid
"))['cnt'];

mysqli_query($con, "SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");

$query = "
    SELECT 
       
        MIN(Dist_Name) AS district,
        MIN(Block_Name) AS block,
        MIN(fac_name) AS facility,
        MIN(facility_type) AS facility_type,
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
    WHERE 
       ( (Health_facilty_type IN (2,10) AND fac_dept_id_fk IN (33,9)) OR
        (Health_facilty_type = 1 AND fac_dept_id_fk IN (3,39))) and dist_id=$distid
    GROUP BY facid, ass_name, Health_facilty_type
";

$result = mysqli_query($con, $query);
$dhData = [];
$sdhData = [];
$chcData = [];

while ($row = mysqli_fetch_assoc($result)) {
    if ($row['Health_facilty_type'] == 2) $dhData[] = $row;
    elseif ($row['Health_facilty_type'] == 10) $sdhData[] = $row;
    elseif ($row['Health_facilty_type'] == 1) $chcData[] = $row;
}
?>

<div class="pcoded-main-container">
  <div class="pcoded-content">
    <div class="row">
      <div class="col-md-4">
        <div class="card text-white bg-primary" style="cursor:pointer;" onclick="showReport('dh')">
          <div class="card-body">
            <h5 class="card-title">District Hospitals (DH)</h5>
            <p>LaQshya: <strong><?= $dhMusqanCount ?></strong> / <strong><?= $dhTotal ?></strong></p>
            <p>Click to view compliance report.</p>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card text-white bg-success" style="cursor:pointer;" onclick="showReport('sdh')">
          <div class="card-body">
            <h5 class="card-title">Sub-Divisional Hospitals (SDH)</h5>
            <p>LaQshya: <strong><?= $sdhMusqanCount ?></strong> / <strong><?= $sdhTotal ?></strong></p>
            <p>Click to view compliance report.</p>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card text-white bg-info" style="cursor:pointer;" onclick="showReport('chc')">
          <div class="card-body">
            <h5 class="card-title">Community Health Centres (CHC)</h5>
            <p>LaQshya: <strong><?= $chcMusqanCount ?></strong> / <strong><?= $chcTotal ?></strong></p>
            <p>Click to view compliance report.</p>
          </div>
        </div>
      </div>
    </div>

    <div class="text-center mt-2">
      <span style="display:inline-block;width:20px;height:20px;background:#28a745;"></span> ≥ 80%
      <span style="display:inline-block;width:20px;height:20px;background:#ffc107;margin-left:15px;"></span> 60–79%
      <span style="display:inline-block;width:20px;height:20px;background:#dc3545;margin-left:15px;"></span> < 60%
    </div>

    <div class="row mt-3" id="report-section" style="display:none;">
      <div class="col-md-12">
        <div class="card">
          <div class="card-body">
            <div class="d-flex justify-content-between">
              <h5 id="report-title" class="text-primary mb-2"></h5>
              <div>
                <button class="btn btn-outline-success mb-2" onclick="downloadChartAsImage()">Download Chart</button>
                <button class="btn btn-outline-primary mb-2" onclick="downloadExcel()">Download as Excel</button>
              </div>
            </div>
            <canvas id="chartCanvas" height="100" class="mb-3"></canvas>
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
  </div>
</div>
<?php include("assets/head/f.php"); ?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script>
const dhData = <?= json_encode($dhData); ?>;
const sdhData = <?= json_encode($sdhData); ?>;
const chcData = <?= json_encode($chcData); ?>;
let currentData = [];
let chartInstance = null;

function showReport(type) {
  currentData = type === 'dh' ? dhData : type === 'sdh' ? sdhData : chcData;
  const title = type === 'dh' ? "District Hospital (DH) Report" : type === 'sdh' ? "Sub-Divisional Hospital (SDH) Report" : "CHC Report";

  if ($.fn.DataTable.isDataTable('#report-table')) {
    $('#report-table').DataTable().clear().destroy();
  }

  document.getElementById("report-title").innerText = title;
  document.getElementById("report-section").style.display = "block";
  const tbody = document.getElementById("report-body");
  let html = "";
  const chartLabels = [];
  const chartData = [];
  const chartColors = [];

  currentData.forEach(row => {
    const perc = parseFloat(row.avg_percentage);
    let colorClass = perc >= 80 ? 'table-success' : (perc >= 60 ? 'table-warning' : 'table-danger');
    html += `<tr class="${colorClass}">
       
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
    chartLabels.push(`${row.facility} (${row.assessment_name})`);
    chartData.push(perc);
    chartColors.push(perc >= 80 ? '#28a745' : perc >= 60 ? '#ffc107' : '#dc3545');
  });
  tbody.innerHTML = html;

  $('#report-table').DataTable({
    pageLength: 5,
    lengthChange: true,
    ordering: true,
    responsive: true,
    language: { search: "Search facility:" }
  });

  const ctx = document.getElementById('chartCanvas').getContext('2d');
  if (chartInstance) chartInstance.destroy();
  chartInstance = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: chartLabels,
      datasets: [{
        label: 'Avg. % Compliance',
        data: chartData,
        backgroundColor: chartColors
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { display: false },
        tooltip: { callbacks: { label: ctx => ctx.parsed.y + '%' } }
      },
      scales: {
        y: { beginAtZero: true, max: 100, title: { display: true, text: 'Percentage' } },
        x: { ticks: { autoSkip: false } }
      }
    }
  });
}

function downloadExcel() {
  const table = document.getElementById("report-table");
  const html = table.outerHTML;
  const blob = new Blob([`<html><head><meta charset='utf-8'></head><body>${html}</body></html>`], { type: "application/vnd.ms-excel" });
  const url = URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = url;
  a.download = "LaQshya_state_Report.xls";
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);
}

function downloadChartAsImage() {
  html2canvas(document.getElementById("chartCanvas")).then(canvas => {
    canvas.toBlob(blob => {
      saveAs(blob, "LaQshya_state_chat.png");
    });
  });
}
</script>
