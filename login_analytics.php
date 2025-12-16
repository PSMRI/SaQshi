<?php
/* ==========================================================
   LOGIN ANALYTICS – LIVE HOURLY DASHBOARD
   ========================================================== */

include("assets/head/h.php");
include("assets/conn/db.php");

/* ================= DATE ================= */
$selectedDate = $_GET['login_date'] ?? date('Y-m-d');
$isToday = ($selectedDate === date('Y-m-d'));

/* ================= KPI ================= */
$kpi = mysqli_fetch_assoc(mysqli_query($con,"
    SELECT
        COUNT(*) total_logins,
        SUM(device_type='Mobile') mobile_logins,
        SUM(device_type='Desktop') web_logins,
        COUNT(DISTINCT user_id) unique_users
    FROM login_log
"));

/* ================= CHART DATA ================= */
function chartData($con, $sql){
    $res = mysqli_query($con, $sql);
    $labels = $values = [];
    while ($r = mysqli_fetch_assoc($res)) {
        $labels[] = $r['label'];
        $values[] = (int)$r['total'];
    }
    return [
        'labels' => json_encode($labels),
        'values' => json_encode($values)
    ];
}

$device  = chartData($con, "SELECT device_type label, COUNT(*) total FROM login_log GROUP BY device_type");
$os      = chartData($con, "SELECT os label, COUNT(*) total FROM login_log GROUP BY os");
$browser = chartData($con, "SELECT browser label, COUNT(*) total FROM login_log GROUP BY browser");

$logs = mysqli_query($con,"
    SELECT 
    l.login_time,
    s.u_name,
    l.device_type,
    l.os,
    l.browser,
    l.ip_address
FROM login_log l
INNER JOIN s_user s 
    ON s.u_id = l.user_id
ORDER BY l.login_time DESC;

");
?>

<!-- ================= STYLE ================= -->
<style>
.kpi-card{border-radius:12px;background:#fff;transition:.2s}
.kpi-card:hover{transform:translateY(-3px);box-shadow:0 8px 18px rgba(0,0,0,.12)}
.kpi-value{font-size:22px;font-weight:700}
.kpi-label{font-size:12px}

.chart-mini{border-radius:12px}
.chart-mini .card-header{
    padding:6px 10px;
    font-size:13px;
    font-weight:600;
    background:#f8f9fa
}
.chart-mini .card-body{padding:6px;height:180px}
.chart-mini canvas{height:140px!important}

.table-compact th,
.table-compact td{
    padding:6px 8px;
    font-size:12px;
}
.table-compact tbody tr:hover{
    background:#f1f6ff;
}
</style>

<div class="pcoded-main-container">
<div class="pcoded-content">

<!-- ================= HEADER ================= -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold text-primary mb-0">
        <i class="bi bi-bar-chart-fill me-2"></i>Login Analytics
    </h5>

    <form method="get">
        <input type="date"
               name="login_date"
               value="<?= htmlspecialchars($selectedDate) ?>"
               class="form-control form-control-sm"
               onchange="this.form.submit()">
    </form>
</div>

<!-- ================= KPI ================= -->
<div class="row g-3 mb-3">
<?php
$kpis = [
    ['Total',  $kpi['total_logins'],  'bi-box-arrow-in-right', 'text-primary'],
    ['Mobile', $kpi['mobile_logins'], 'bi-phone',             'text-info'],
    ['Web',    $kpi['web_logins'],    'bi-globe',             'text-success'],
    ['Users',  $kpi['unique_users'],  'bi-people',            'text-warning']
];
foreach ($kpis as $k):
?>
<div class="col-md-3 col-6">
    <div class="card kpi-card text-center">
        <div class="card-body py-2">
            <i class="bi <?= $k[2] ?> <?= $k[3] ?> fs-5"></i>
            <div class="kpi-value"><?= $k[1] ?></div>
            <div class="kpi-label text-muted"><?= $k[0] ?></div>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>

<!-- ================= CHART ROW 1 ================= -->
<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="card chart-mini shadow-sm">
            <div class="card-header">Device</div>
            <div class="card-body"><canvas id="deviceChart"></canvas></div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card chart-mini shadow-sm">
            <div class="card-header">Operating System</div>
            <div class="card-body"><canvas id="osChart"></canvas></div>
        </div>
    </div>
</div>

<!-- ================= CHART ROW 2 ================= -->
<div class="row g-3">
    <div class="col-md-6">
        <div class="card chart-mini shadow-sm">
            <div class="card-header">Browser</div>
            <div class="card-body"><canvas id="browserChart"></canvas></div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card chart-mini shadow-sm">
            <div class="card-header d-flex justify-content-between">
                <span>Hourly Login (<?= date('d M Y', strtotime($selectedDate)) ?>)</span>
                <?php if ($isToday): ?><span class="badge bg-danger">LIVE</span><?php endif; ?>
            </div>
            <div class="card-body"><canvas id="hourChart"></canvas></div>
        </div>
    </div>
</div>

<!-- ================= TABLE ================= -->
<div class="card shadow-sm mt-4">
<div class="card-header fw-bold">Recent Login Activity</div>
<div class="table-responsive">
<table id="loginTable" class="table table-bordered table-compact mb-0">
<thead class="table-light">
<tr>
    <th>Time</th>
    <th>User</th>
    <th>Device</th>
    <th>OS</th>
    <th>Browser</th>
    <th>IP</th>
</tr>
</thead>
<tbody>
<?php while ($r = mysqli_fetch_assoc($logs)): ?>
<tr>
    <td><?= date("d-m-Y H:i", strtotime($r['login_time'])) ?></td>
    <td><?= htmlspecialchars($r['u_name']) ?></td>
    <td>
        <span class="badge <?= $r['device_type']=='Mobile'?'bg-primary':'bg-success' ?>">
            <?= htmlspecialchars($r['device_type']) ?>
        </span>
    </td>
    <td><?= htmlspecialchars($r['os']) ?></td>
    <td><?= htmlspecialchars($r['browser']) ?></td>
    <td><?= htmlspecialchars($r['ip_address']) ?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
</div>

</div>
</div>

<!-- ================= SCRIPTS ================= -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
// Static charts
new Chart(document.getElementById('deviceChart'),{
    type:'doughnut',
    data:{labels:<?= $device['labels']?>,datasets:[{data:<?= $device['values']?>}]}
});
new Chart(document.getElementById('osChart'),{
    type:'bar',
    data:{labels:<?= $os['labels']?>,datasets:[{data:<?= $os['values']?>}]},
    options:{plugins:{legend:{display:false}}}
});
new Chart(document.getElementById('browserChart'),{
    type:'bar',
    data:{labels:<?= $browser['labels']?>,datasets:[{data:<?= $browser['values']?>}]},
    options:{plugins:{legend:{display:false}}}
});

// LIVE hourly chart
let hourChart;
function loadHourly(){
    fetch("assets/get/hourly_login_api.php?date=<?= $selectedDate ?>")
        .then(r => r.json())
        .then(d => {
            if (!hourChart) {
                hourChart = new Chart(document.getElementById('hourChart'),{
                    type:'line',
                    data:{
                        labels:d.labels,
                        datasets:[{
                            data:d.values,
                            borderColor:'#dc3545',
                            backgroundColor:'rgba(220,53,69,.25)',
                            fill:true,
                            tension:.4
                        }]
                    }
                });
            } else {
                hourChart.data.labels = d.labels;
                hourChart.data.datasets[0].data = d.values;
                hourChart.update();
            }
        });
}
loadHourly();
<?php if ($isToday): ?>setInterval(loadHourly,30000);<?php endif; ?>

// DataTable pagination
$(document).ready(function(){
    $('#loginTable').DataTable({
        pageLength: 10,
        lengthChange: false,
        order: [[0,'desc']]
    });
});
</script>

<?php include("assets/head/f.php"); ?>
