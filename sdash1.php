<?php include("assets/head/h.php"); ?>
<style>
    /* ================= FACILITY TYPE CARD ================= */

.facility-card {
  width: 170px;
  background: #fff;
  border-radius: 14px;
  padding: 14px 12px 16px;
  position: relative;
  transition: all 0.25s ease;
}

.facility-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 10px 22px rgba(0,0,0,0.12);
}

.facility-strip {
  position: absolute;
  top: 0;
  left: 0;
  height: 5px;
  width: 100%;
  border-radius: 14px 14px 0 0;
}

.facility-icon {
  font-size: 26px;
  color: #0d6efd;
  margin-bottom: 6px;
  text-align: center;
}

.facility-body {
  text-align: center;
}

.facility-type {
  font-weight: 700;
  font-size: 14px;
  letter-spacing: 0.5px;
}

.facility-count {
  font-size: 18px;
  font-weight: 700;
  margin: 4px 0;
}

.facility-progress {
  height: 6px;
  border-radius: 6px;
  margin: 6px 0;
}

.facility-percent {
  font-size: 12px;
  font-weight: 600;
  color: #555;
}

/* Responsive */
@media(max-width:768px){
  .facility-card{
    width: 100%;
  }
}

</style>
<div class="pcoded-main-container">
<div class="pcoded-content">

<h5 class="fw-bold text-primary mb-3">State Dashboard</h5>

<!-- =======================================================
 MAP + CERTIFICATION OVERVIEW
======================================================= -->
<div class="card mb-3">
  <div class="card-body">
    <h6 class="fw-bold">Facility Certification Overview</h6>
    <div id="map" style="height:420px;border:1px solid #ddd;"></div>

    <div class="mt-2 small">
      <span class="me-3">
        <img src="https://maps.gstatic.com/mapfiles/ms2/micons/blue.png" width="14"> State
      </span>
      <span class="me-3">
        <img src="https://maps.gstatic.com/mapfiles/ms2/micons/green.png" width="14"> National
      </span>
      <span>
        <img src="https://maps.gstatic.com/mapfiles/ms2/micons/red.png" width="14"> Expired
      </span>
    </div>
  </div>
</div>

<!-- =======================================================
 CERTIFICATION TABLE
======================================================= -->
<div class="card mb-3">
  <div class="card-body">
    <div class="alert alert-warning mb-2">
      ⚠️ Expired Certifications:
      <strong id="expired-count">0</strong>
      <a href="#" id="download-expired" class="ms-2 fw-bold">Download CSV</a>
    </div>

    <div class="table-responsive">
      <table id="facTable" class="table table-bordered table-striped table-sm"></table>
    </div>
  </div>
</div>

<!-- =======================================================
 PERFORMANCE SUMMARY
======================================================= -->
<div class="card mb-3">
  <div class="card-body">
    <h6 class="fw-bold text-primary">Assessment Summary</h6>

    <div id="performanceCards" class="d-flex gap-2 flex-wrap mb-2"></div>

    <div class="text-end text-muted small">
      Average Compliance:
      <strong id="avgScore">0%</strong>
    </div>
  </div>
</div>
                             
<!-- =======================================================
 FACILITY TYPE PROGRESS
======================================================= -->
<div class="card shadow-sm border-0 mb-3">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h6 class="fw-bold text-primary mb-0">
        Facility Type Progress
      </h6>
      <span class="small text-muted fst-italic">
        Completed / Total
      </span>
    </div>
 <div id="facilityTypeCards" class="d-flex flex-nowrap gap-3 justify-content-center overflow-auto py-3">
    
  </div>
</div>


<!-- =======================================================
 DISTRICT PROGRESS TABLE
======================================================= -->
<div class="card mb-3">
  <div class="card-body">
    <h6 class="fw-bold text-primary">District-wise Assessment Progress</h6>
    <div class="table-responsive">
      <table id="districtTable" class="table table-bordered table-sm"></table>
    </div>
  </div>
</div>

<!-- =======================================================
 PERFORMANCE ZONES
======================================================= -->
<div class="card mb-3">
  <div class="card-body">
    <h6 class="fw-bold text-primary">Performance Zones</h6>
    <div id="zoneTables"></div>
  </div>
</div>

<!-- =======================================================
 DOWNLOADS
======================================================= -->
<div class="mb-3">
  <button class="btn btn-success me-2"
    onclick="window.location.href='/api/dashboard/v1/state/export-kpi.php'">
    Download KPI & Outcome (Excel)
  </button>

  <button class="btn btn-success"
    onclick="window.location.href='/api/dashboard/v1/state/export-actionplan.php'">
    Download Action Plan Summary
  </button>
</div>

</div>
</div>


<!-- =======================================================
 JS SECTION
======================================================= -->
<script>
const STATE_ID = 10;
const API = '/api/dashboard/v1/state';

/* =====================================================
   MAP + CERTIFICATION TABLE
===================================================== */
fetch(`${API}/certification.php?state_id=${STATE_ID}`)
.then(r => r.json())
.then(res => {

  if (res.status !== 'success') {
    console.error('API error', res);
    return;
  }

  const data = res.data || [];

  /* ================= EXPIRED COUNT ================= */
  const expiredCount = data.filter(f => f.is_expired === true).length;
  $('#expired-count').text(expiredCount);

  /* ================= MAP ================= */
  const map = L.map('map').setView([25.32, 82.99], 7);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 18
  }).addTo(map);

  data.forEach(f => {

    if (!f.lat || !f.lng) return;

    const color = f.is_expired
      ? 'red'
      : (f.cert_type || '').toLowerCase() === 'national'
        ? 'green'
        : 'blue';

    L.marker([f.lat, f.lng], {
      icon: new L.Icon({
        iconUrl: `https://maps.gstatic.com/mapfiles/ms2/micons/${color}.png`,
        iconSize: [18, 28],
        iconAnchor: [9, 28]
      })
    })
    .addTo(map)
    .bindTooltip(`
      <b>${f.fac_name}</b><br>
      Type: ${f.fac_type}<br>
      Certification: ${f.cert_type}<br>
      Status: ${f.cert_status}<br>
      Score: ${f.score ?? 'NA'}<br>
      Validity: ${f.validity ?? 'NA'}<br>
      District: ${f.district ?? 'NA'}
    `);
  });

  /* ================= TABLE ================= */
  $('#facTable').DataTable({
    destroy: true,
    data: data,
    pageLength: 5,
    columns: [
      { data: 'fac_name',   title: 'Facility' },
      { data: 'fac_type',   title: 'Type' },
      { data: 'cert_type',  title: 'Cert Type' },
      { data: 'cert_detail',title: 'Details' },
      { data: 'cert_issue', title: 'Issue Date' },
      { data: 'validity',   title: 'Validity' },
      { data: 'score',      title: 'Score' },
      { data: 'cert_status',title: 'Status' },
      { data: 'ass_mode',   title: 'Mode' },
      { data: 'ass_date',   title: 'Assessment Date' },
      { data: 'district',   title: 'District' }
    ],
    createdRow: function (row, rowData) {
      if (rowData.is_expired) {
        $(row).addClass('table-danger');
      }
    }
  });

});

/* =====================================================
  PERFORMANCE SUMMARY
===================================================== */
fetch(`${API}/performance-summary.php?state_id=${STATE_ID}`)
.then(r => r.json())
.then(r => {
  $('#avgScore').text(r.average_score + '%');

  $('#performanceCards').html(`
    <div class="badge bg-success p-2">&gt;80% : ${r.categories.gt80}</div>
    <div class="badge bg-warning text-dark p-2">50–80% : ${r.categories.btw50_80}</div>
    <div class="badge bg-danger p-2">&lt;50% : ${r.categories.lt50}</div>
  `);
});

/* =====================================================
  FACILITY TYPE PROGRESS
===================================================== */
fetch(`${API}/facility-type-progress.php?state_id=${STATE_ID}`)
.then(r => r.json())
.then(r => {
  let html = '';
  r.data.forEach(f => {
    const pct = f.total ? Math.round((f.completed / f.total) * 100) : 0;
    html += `
<div class="facility-card shadow-sm">
  <div class="facility-strip ${pct >= 80 ? 'bg-success' : pct >= 50 ? 'bg-warning' : 'bg-danger'}"></div>

  <div class="facility-icon">
   <i class="${getFacilityIcon(f.type)}"></i>

  </div>

  <div class="facility-body">
    <div class="facility-type">${f.type}</div>

    <div class="facility-count">
      ${f.completed} / ${f.total}
    </div>

    <div class="progress facility-progress">
      <div class="progress-bar
        ${pct >= 80 ? 'bg-success' : pct >= 50 ? 'bg-warning' : 'bg-danger'}"
        style="width:${pct}%">
      </div>
    </div>

    <div class="facility-percent">${pct}% completed</div>
  </div>
</div>
`;

  });
  $('#facilityTypeCards').html(html);
});

/* =====================================================
  DISTRICT PROGRESS TABLE
===================================================== */
fetch(`${API}/district-progress.php?state_id=${STATE_ID}`)
.then(r => r.json())
.then(r => {
  $('#districtTable').DataTable({
    data: r.data,
    pageLength: 5,
    columns: Object.keys(r.data[0]).map(k => ({
      data: k,
      title: k
    }))
  });
});

/* =====================================================
  PERFORMANCE ZONES
===================================================== */
fetch(`${API}/zones.php?state_id=${STATE_ID}`)
.then(r => r.json())
.then(r => {
  let html = '';
  ['green','yellow','red'].forEach(z => {
    html += `<h6 class="mt-3 text-uppercase">${z} Zone</h6>`;
    html += `
      <table class="table table-bordered table-sm">
        <thead>
          <tr>
            <th>District</th>
            <th>Block</th>
            <th>Facility</th>
            <th>Type</th>
            <th>Score</th>
          </tr>
        </thead><tbody>`;
    r[z].forEach(f => {
      html += `
        <tr>
          <td>${f.district}</td>
          <td>${f.block}</td>
          <td>${f.facility}</td>
          <td>${f.type}</td>
          <td>${f.score}%</td>
        </tr>`;
    });
    html += '</tbody></table>';
  });
  $('#zoneTables').html(html);
});

function getFacilityIcon(type){
  switch(type){
    case 'DH': return 'bi bi-hospital';
    case 'CHC': return 'bi bi-hospital-fill';
    case 'PHC': return 'bi bi-building';
    case 'UPHC': return 'bi bi-house-heart';
    case 'AAMSC': return 'bi bi-heart-pulse';
    default: return 'bi bi-hospital';
  }
}

</script>

<?php include("assets/head/f.php"); ?>