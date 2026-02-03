<?php
include("assets/head/h.php");
include("assets/conn/db.php");

$distid   = (int)$_SESSION['div_id'];
$distname = $_SESSION['div_name'];

/* =========================
   FACILITY TOTALS
========================= */
function getCount($con, $sql) {
    return mysqli_fetch_assoc(mysqli_query($con, $sql))['cnt'] ?? 0;
}

$dhTotal  = getCount($con, "SELECT COUNT(*) cnt FROM facilities WHERE Health_facilty_type=2  AND dist_id=$distid");
$sdhTotal = getCount($con, "SELECT COUNT(*) cnt FROM facilities WHERE Health_facilty_type=10 AND dist_id=$distid");
$chcTotal = getCount($con, "SELECT COUNT(*) cnt FROM facilities WHERE Health_facilty_type=1  AND dist_id=$distid");

$dhMusqanCount  = getCount($con, "SELECT COUNT(DISTINCT facid) cnt FROM department_wise_state_dash WHERE Health_facilty_type=2  AND fac_dept_id_fk IN (33,9)  AND dist_id=$distid");
$sdhMusqanCount = getCount($con, "SELECT COUNT(DISTINCT facid) cnt FROM department_wise_state_dash WHERE Health_facilty_type=10 AND fac_dept_id_fk IN (33,9)  AND dist_id=$distid");
$chcMusqanCount = getCount($con, "SELECT COUNT(DISTINCT facid) cnt FROM department_wise_state_dash WHERE Health_facilty_type=1  AND fac_dept_id_fk IN (3,39) AND dist_id=$distid");

/* =========================
   MAIN REPORT DATA
========================= */
mysqli_query($con,"SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");

$sql = "
SELECT
    MIN(Dist_Name) district,
    MIN(Block_Name) block,
    MIN(fac_name) facility,
    ass_name assessment_name,
    Health_facilty_type,
    SUM(zero) zero_count,
    SUM(one) one_count,
    SUM(two) two_count,
    SUM(non) non_compliant,
    SUM(total) total_checks,
    SUM(marks_obtained) marks_obtained,
    SUM(total_marks) total_marks,
    ROUND(AVG(percentage),2) avg_percentage
FROM department_wise_state_dash
WHERE dist_id=$distid
  AND (
        (Health_facilty_type IN (2,10) AND fac_dept_id_fk IN (33,9)) OR
        (Health_facilty_type = 1       AND fac_dept_id_fk IN (3,39))
      )
GROUP BY facid, ass_name, Health_facilty_type
";

$res = mysqli_query($con,$sql);
$dhData = $sdhData = $chcData = [];

while($r = mysqli_fetch_assoc($res)){
    if($r['Health_facilty_type']==2)  $dhData[]  = $r;
    if($r['Health_facilty_type']==10) $sdhData[] = $r;
    if($r['Health_facilty_type']==1)  $chcData[] = $r;
}
?>
<div class="pcoded-main-container">
<div class="pcoded-content">

<div class="row g-3">
  <?php
  $cards = [
    ['DH','District Hospitals','primary',$dhMusqanCount,$dhTotal,'dh'],
    ['SDH','Sub-Divisional Hospitals','success',$sdhMusqanCount,$sdhTotal,'sdh'],
    ['CHC','Community Health Centres','info',$chcMusqanCount,$chcTotal,'chc']
  ];
  foreach($cards as $c):
  ?>
  <div class="col-md-4">
    <div class="card shadow-sm border-0 bg-<?= $c[2] ?> text-white h-100 report-card" onclick="showReport('<?= $c[5] ?>')">
      <div class="card-body">
        <h5 class="fw-bold"><?= $c[1] ?></h5>
        <p class="mb-1">LaQshya: <strong><?= $c[3] ?></strong> / <?= $c[4] ?></p>
        <small>Click to view compliance report</small>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="text-center my-3">
  <span class="badge bg-success">≥ 80%</span>
  <span class="badge bg-warning text-dark">60–79%</span>
  <span class="badge bg-danger">&lt; 60%</span>
</div>

<div id="report-section" style="display:none;">
  <div class="card shadow-sm">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h5 id="report-title" class="fw-bold text-primary"></h5>
        <div>
          <button class="btn btn-outline-success btn-sm" onclick="downloadChart()">Chart</button>
          <button class="btn btn-outline-primary btn-sm" onclick="downloadExcel()">Excel</button>
        </div>
      </div>

      <canvas id="chartCanvas" height="90"></canvas>

      <div class="table-responsive mt-3">
        <table class="table table-bordered table-sm" id="report-table">
          <thead class="table-light">
            <tr>
              <th>District</th><th>Block</th><th>Facility</th><th>Assessment</th>
              <th>0</th><th>1</th><th>2</th><th>NC</th>
              <th>Total</th><th>Obt.</th><th>Max</th><th>%</th>
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
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/html2canvas"></script>
<script src="https://cdn.jsdelivr.net/npm/file-saver"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>

<script>
const DATA = {
  dh: <?= json_encode($dhData) ?>,
  sdh: <?= json_encode($sdhData) ?>,
  chc: <?= json_encode($chcData) ?>
};

let chart=null;

function showReport(type){
  const rows = DATA[type];
  $('#report-section').show();
  $('#report-title').text(type.toUpperCase() + " Compliance Report");

  let body='', labels=[], values=[], colors=[];

  rows.forEach(r=>{
    const p = parseFloat(r.avg_percentage);
    const cls = p>=80?'table-success':p>=60?'table-warning':'table-danger';

    body+=`
      <tr class="${cls}">
        <td>${r.district}</td><td>${r.block}</td><td>${r.facility}</td>
        <td>${r.assessment_name}</td>
        <td>${r.zero_count}</td><td>${r.one_count}</td><td>${r.two_count}</td>
        <td>${r.non_compliant}</td>
        <td>${r.total_checks}</td><td>${r.marks_obtained}</td>
        <td>${r.total_marks}</td><td>${p}%</td>
      </tr>`;

    labels.push(r.facility);
    values.push(p);
    colors.push(p>=80?'#28a745':p>=60?'#ffc107':'#dc3545');
  });

  $('#report-body').html(body);

  if($.fn.DataTable.isDataTable('#report-table')){
    $('#report-table').DataTable().destroy();
  }
  $('#report-table').DataTable({pageLength:5});

  if(chart) chart.destroy();
  chart = new Chart(chartCanvas,{
    type:'bar',
    data:{labels, datasets:[{data:values, backgroundColor:colors}]},
    options:{plugins:{legend:{display:false}}, scales:{y:{max:100,beginAtZero:true}}}
  });
}

function downloadExcel(){
  const html = document.getElementById("report-table").outerHTML;
  const blob = new Blob([html],{type:'application/vnd.ms-excel'});
  saveAs(blob,"LaQshya_Report.xls");
}

function downloadChart(){
  html2canvas(chartCanvas).then(c=>c.toBlob(b=>saveAs(b,"LaQshya_Chart.png")));
}
</script>
<?php include("assets/head/f.php"); ?>