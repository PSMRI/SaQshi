<?php
/* ==========================================================
   LOGIN ANALYTICS – LIVE HOURLY DASHBOARD
   ========================================================== */

include("assets/head/h.php");
include("assets/conn/db.php");

/* ================= DATE ================= */
$selectedDate = $_GET['login_date'] ?? date('Y-m-d');
$isToday = ($selectedDate === date('Y-m-d'));

/* ================= ALL-TIME KPI ================= */
$kpi = mysqli_fetch_assoc(mysqli_query($con, "
    SELECT
        COUNT(*) AS total_logins,
        SUM(device_type='Mobile') AS mobile_logins,
        SUM(device_type='Desktop') AS web_logins,
        COUNT(DISTINCT user_id) AS unique_users
    FROM login_log
"));

/* ================= TODAY KPI (FOR CHART HEADER) ================= */
$todayKpi = mysqli_fetch_assoc(mysqli_query($con, "
    SELECT COUNT(*) AS today_logins
    FROM login_log
    WHERE DATE(login_time) = '$selectedDate'
"));

/* ================= DEVICE / OS / BROWSER ================= */
$deviceStats  = mysqli_query($con, "SELECT device_type label, COUNT(*) total FROM login_log GROUP BY device_type");
$osStats      = mysqli_query($con, "SELECT os label, COUNT(*) total FROM login_log GROUP BY os");
$browserStats = mysqli_query($con, "SELECT browser label, COUNT(*) total FROM login_log GROUP BY browser");

/* ================= LOGIN TABLE (ALL DATA) ================= */
$logs = mysqli_query($con, "
    SELECT 
        l.login_time,
        s.u_name,
        l.device_type,
        l.os,
        l.browser,
        l.ip_address
    FROM login_log l
    INNER JOIN s_user s ON s.u_id = l.user_id    
    ORDER BY l.login_time DESC
");
?>

<style>
.card{ border-radius:14px; }
.card-header{ font-weight:600; background:#f8f9fa; }

/* KPI */
.kpi-card{
    background:#fff;
    text-align:center;
    box-shadow:0 4px 12px rgba(0,0,0,.06);
}
.kpi-value{ font-size:22px; font-weight:700; }
.kpi-label{ font-size:12px; color:#6c757d; }

/* Stat cards */
.stat-card{
    background:#fff;
    box-shadow:0 6px 16px rgba(0,0,0,.08);
    height:100%;
}
.stat-card h6{
    font-size:14px;
    font-weight:600;
    margin-bottom:10px;
}
.stat-item{
    font-size:13px;
    display:flex;
    justify-content:space-between;
    padding:4px 0;
}

/* Table */
.table-compact th,
.table-compact td{
    padding:7px 9px;
    font-size:12px;
}
.table-compact tbody tr:hover{
    background:#f1f6ff;
}

/* Live blink */
@keyframes blinkLive{
    0%{opacity:1}
    50%{opacity:.3}
    100%{opacity:1}
}
.blink-live{
    animation:blinkLive 1.2s infinite;
    font-weight:600;
}
.text-blue{ color:#0d6efd; }
</style>

<div class="pcoded-main-container">
<div class="pcoded-content">

<!-- ================= HEADER ================= -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-bold text-primary mb-0">
        <i class="bi bi-bar-chart-fill me-2"></i>Login Analytics
    </h5>
    <form method="get">
        <input type="date" name="login_date"
               value="<?= htmlspecialchars($selectedDate) ?>"
               class="form-control form-control-sm"
               onchange="this.form.submit()">
    </form>
</div>

<!-- ================= KPI ================= -->
<div class="row g-3 mb-4">
<?php
$kpis = [
    ['Total Logins', $kpi['total_logins'], 'bi-person-fill', 'text-primary'],
    ['Mobile', $kpi['mobile_logins'], 'bi-phone', 'text-info'],
    ['Desktop', $kpi['web_logins'], 'bi-globe', 'text-success'],
    ['Users', $kpi['unique_users'], 'bi-people', 'text-warning']
];
foreach($kpis as $k):
?>
<div class="col-md-3 col-6">
    <div class="card kpi-card">
        <div class="card-body py-3">
            <i class="bi <?= $k[2] ?> <?= $k[3] ?> fs-4 mb-1"></i>
            <div class="kpi-value"><?= (int)$k[1] ?></div>
            <div class="kpi-label"><?= $k[0] ?></div>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>

<!-- ================= DEVICE / OS / BROWSER ================= -->
<div class="row g-4 mb-4 align-items-stretch">
<?php
$blocks = [
    ['Device', $deviceStats],
    ['Operating System', $osStats],
    ['Browser', $browserStats]
];
foreach($blocks as $b):
?>
<div class="col-md-4 d-flex">
    <div class="card stat-card w-100">
        <div class="card-body">
            <h6><?= $b[0] ?></h6>
            <?php while($r = mysqli_fetch_assoc($b[1])): ?>
            <div class="stat-item">
                <span><?= htmlspecialchars($r['label']) ?></span>
                <strong><?= $r['total'] ?></strong>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>

<!-- ================= HOURLY CHART ================= -->
<div class="card shadow-sm mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>
            Hourly Login (<?= date('d M Y', strtotime($selectedDate)) ?>)
            <span class="badge bg-primary ms-2">
                Total Today: <?= (int)$todayKpi['today_logins'] ?>
            </span>
        </span>
        <?php if($isToday): ?>
            <span class="badge bg-danger blink-live">LIVE</span>
        <?php endif; ?>
    </div>

    <div class="card-body">
        <canvas id="hourChart" height="90"></canvas>

        <div class="d-flex gap-3 justify-content-end small text-muted mt-2">
            <span><i class="bi bi-circle-fill text-danger"></i> High</span>
            <span><i class="bi bi-circle-fill text-warning"></i> Medium</span>
            <span><i class="bi bi-circle-fill text-success"></i> Low</span>
            <span><i class="bi bi-circle-fill text-blue"></i> Zero Login</span>
        </div>
    </div>
</div>

<!-- ================= LOGIN TABLE ================= -->
<div class="card shadow-sm">
    <div class="card-header">Recent Login Activity</div>
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
            <?php while($r = mysqli_fetch_assoc($logs)): ?>
            <tr>
                <td><?= date("d-m-Y H:i", strtotime($r['login_time'])) ?></td>
                <td><?= htmlspecialchars($r['u_name']) ?></td>
                <td><?= htmlspecialchars($r['device_type']) ?></td>
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
let hourChart;

function loadHourly(){
    fetch("assets/get/hourly_login_api.php?date=<?= $selectedDate ?>")
        .then(r=>r.json())
        .then(d=>{
            const labels=d.labels;
            const values=d.values.map(v=>Number(v)||0);

            const uniq=[...new Set(values.filter(v=>v>0))].sort((a,b)=>b-a);
            const max=uniq[0]??0;
            const second=uniq[1]??0;

            const colors=values.map(v=>{
                if(v===0) return '#0d6efd';
                if(v===max) return '#dc3545';
                if(v===second) return '#ffc107';
                return '#198754';
            });

            if(!hourChart){
                hourChart=new Chart(document.getElementById('hourChart'),{
                    type:'line',
                    data:{labels,datasets:[{
                        data:values,
                        borderColor:'#6c757d',
                        backgroundColor:'rgba(13,110,253,.12)',
                        pointBackgroundColor:colors,
                        pointBorderColor:colors,
                        pointRadius:5,
                        fill:true,
                        tension:.4
                    }]},
                    options:{plugins:{legend:{display:false}},scales:{y:{beginAtZero:true}}}
                });
            }else{
                hourChart.data.labels=labels;
                hourChart.data.datasets[0].data=values;
                hourChart.data.datasets[0].pointBackgroundColor=colors;
                hourChart.update();
            }
        });
}

loadHourly();
<?php if($isToday): ?>setInterval(loadHourly,30000);<?php endif; ?>

$(function(){
    $('#loginTable').DataTable({
        pageLength:8,
        lengthChange:false,
        searching:false,
        order:[[0,'desc']]
    });
});
</script>

<?php include("assets/head/f.php"); ?>
