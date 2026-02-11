<?php
include("assets/head/h.php");

/* ======================================================
   FETCH BLOCK NAME
====================================================== */
$block_name = "Unknown Block";

if (!empty($_SESSION['block_id'])) {
    $block_id = (int)$_SESSION['block_id'];
    $stmt = $con->prepare("SELECT block_name FROM block_master WHERE block_id=?");
    $stmt->bind_param("i", $block_id);
    $stmt->execute();
    $stmt->bind_result($block_name);
    $stmt->fetch();
    $stmt->close();
}
?>

<!-- =====================================================
     DASHBOARD CONTAINER
===================================================== -->
<div class="pcoded-main-container">
<div class="pcoded-content">

<!-- ===================== PAGE HEADER ===================== -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="fw-bold text-primary mb-0">
            <i class="bi bi-diagram-3-fill me-2"></i>Block Dashboard
        </h4>
        <small class="text-muted">
            Block: <strong><?= htmlspecialchars($block_name) ?></strong>
        </small>
    </div>
</div>

<!-- ===================== MAP CARD ===================== -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white fw-semibold">
        <i class="bi bi-geo-alt-fill me-2"></i>Facility Certification – Map View
    </div>
    <div class="card-body p-0">
        <div id="map" style="height:420px;"></div>
    </div>
</div>

<!-- ===================== TABLE ===================== -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-light fw-semibold">
        <i class="bi bi-list-check me-2"></i>Facility Certification Details
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="facTable" class="table table-bordered table-striped table-hover small">
                <thead class="table-primary">
                    <tr>
                        <th>Facility Name</th>
                        <th>Type</th>
                        <th>Certification</th>
                        <th>Details</th>
                        <th>Issue Date</th>
                        <th>Validity</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- ===================== SUMMARY CARDS ===================== -->
<div class="row mb-4">
<?php
$call = "CALL block_dash_count($block_id)";
$res  = mysqli_query($con, $call);

$row = mysqli_fetch_assoc($res);

$cards = [
    ['DH',  $row['DHCcomp'],  $row['DH']],
    ['SDH', $row['SDHCcomp'], $row['SDH']],
    ['CHC', $row['CHCcomp'],  $row['CHC']],
    ['PHC', $row['PHCcomp'],  $row['PHC']],
    ['UPHC',$row['UPHCcomp'], $row['UPHC']],
    ['HWC', $row['HWCcomp'],  $row['HWC']]
];

foreach ($cards as $c):
?>
<div class="col-lg-2 col-md-3 col-6 mb-3">
    <div class="card text-center shadow-sm">
        <div class="card-body">
            <i class="bi bi-hospital fs-2 text-primary"></i>
            <h5 class="fw-bold mt-2"><?= $c[1] ?>/<?= $c[2] ?></h5>
            <small class="text-muted"><?= $c[0] ?></small>
        </div>
    </div>
</div>
<?php endforeach;
mysqli_free_result($res);
$con->next_result();
?>
</div>

<!-- ===================== CHARTS ===================== -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header fw-semibold">
                <i class="bi bi-pie-chart-fill me-2"></i>Facility Score Distribution
            </div>
            <div class="card-body">
                <div id="pie-chart" style="height:300px;"></div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header fw-semibold">
                <i class="bi bi-bar-chart-fill me-2"></i>Block-wise Performance
            </div>
            <div class="card-body">
                <div id="stacked-bar" style="height:300px;"></div>
            </div>
        </div>
    </div>
</div>

<!-- ===================== REPORT (PDF) ===================== -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-light fw-semibold d-flex justify-content-between">
        <span><i class="bi bi-file-earmark-text me-2"></i>Block Compliance Summary</span>
        <button class="btn btn-danger btn-sm" onclick="downloadDashboardAsPDF()">
            <i class="bi bi-file-pdf-fill me-1"></i>Download PDF
        </button>
    </div>
    <div class="card-body">
        <div id="report-content">
            <h5 class="text-center text-primary mb-1">Block Compliance Summary – Report</h5>
            <p class="text-center mb-3">
                Block: <strong><?= htmlspecialchars($block_name) ?></strong>
            </p>

            <p class="small">
                This report presents the compliance performance of health facilities within the block.
                Facilities are categorized based on assessment score thresholds to support targeted quality improvement.
            </p>

            <p class="text-muted small">
                <em>Generated on <?= date('d M Y h:i A') ?> | Powered by <strong>SaQshi</strong></em>
            </p>
        </div>
    </div>
</div>

</div>
</div>

<!-- =====================================================
     SCRIPTS
===================================================== -->

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<script>
/* ================= MAP ================= */
var map = L.map('map').setView([25.2, 85.5], 8);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:18}).addTo(map);

/* ================= TABLE & MARKERS ================= */
$.getJSON('assets/get/get_cert_datablock.php', function(data){
    data.forEach(f=>{
        L.marker([f.lat, f.longi]).addTo(map)
         .bindPopup(`<b>${f.fac_name}</b><br>${f.cert_type}`);

        $('#facTable tbody').append(`
            <tr>
                <td>${f.fac_name}</td>
                <td>${f.fac_type}</td>
                <td>${f.cert_type}</td>
                <td>${f.cert_detailscol}</td>
                <td>${f.cert_issue}</td>
                <td>${f.validity}</td>
            </tr>
        `);
    });
    $('#facTable').DataTable();
});

/* ================= PDF ================= */
async function downloadDashboardAsPDF(){
    const { jsPDF } = window.jspdf;
    const el = document.getElementById('report-content');
    const canvas = await html2canvas(el,{scale:2});
    const pdf = new jsPDF('p','mm','a4');
    const imgData = canvas.toDataURL('image/png');
    pdf.addImage(imgData,'PNG',0,0,210,(canvas.height*210)/canvas.width);
    pdf.save('SaQshi_Block_Report.pdf');
}
</script>

<?php include("assets/head/f.php"); ?>
