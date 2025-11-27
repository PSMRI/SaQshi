<?php
// facility_profile_with_cert_beauty.php  (Flat Able compact + Leaflet + BS4 DataTables)
include("assets/head/h.php");          // must start session and include $con (mysqli)
// include("assets/conn/db.php");      // uncomment if DB is not included above

if (!isset($_SESSION['u_facilityid'])) {
    die("<div class='alert alert-danger m-3'>Access denied. Facility ID missing in session.</div>");
}

$fac_id = (int)$_SESSION['u_facilityid'];
$success = $error = $certMsg = "";

/* =========================
   UPDATE Facility Record
   ========================= */
if (isset($_POST['save_facility']) && (int)($_POST['fac_id'] ?? 0) === $fac_id) {
    $division_id  = (int)($_POST['division_id'] ?? 0);
    $dist_id      = (int)($_POST['dist_id'] ?? 0);
    $block_id     = (int)($_POST['block_id'] ?? 0);
    $Health_type  = (int)($_POST['Health_facilty_type'] ?? 0);
    $fac_name     = trim($_POST['fac_name'] ?? '');
    $lat          = (float)($_POST['lat'] ?? 0);
    $longit       = (float)($_POST['longit'] ?? 0);
    $NIN_no       = trim($_POST['NIN_no'] ?? '');

    if (!preg_match("/^[A-Za-z\s]+$/", $fac_name)) {
        $error = "Facility Name should contain only letters and spaces.";
    } elseif (!preg_match("/^\d{10}$/", $NIN_no)) {
        $error = "NIN Number must be exactly 10 digits.";
    }

    if ($error === "") {
        $division_name = $dist_name = $block_name = '';

        if ($division_id) {
            $stmt = $con->prepare("SELECT division_name FROM division WHERE iddivision=?");
            $stmt->bind_param("i", $division_id);
            $stmt->execute();
            $stmt->bind_result($division_name);
            $stmt->fetch();
            $stmt->close();
        }

        if ($dist_id) {
            $stmt = $con->prepare("SELECT Dist_name FROM dist_master WHERE Dist_id=?");
            $stmt->bind_param("i", $dist_id);
            $stmt->execute();
            $stmt->bind_result($dist_name);
            $stmt->fetch();
            $stmt->close();
        }

        if ($block_id) {
            $stmt = $con->prepare("SELECT block_name FROM block_master WHERE block_id=?");
            $stmt->bind_param("i", $block_id);
            $stmt->execute();
            $stmt->bind_result($block_name);
            $stmt->fetch();
            $stmt->close();
        }

        $stmt = $con->prepare("UPDATE facilities 
            SET division_id=?, division=?, dist_id=?, Dist_Name=?, block_id=?, Block_Name=?, 
                fac_name=?, Health_facilty_type=?, lat=?, longit=?, NIN_no=? 
            WHERE fac_id=?");
        $stmt->bind_param(
            "isisissiddsi",
            $division_id,
            $division_name,
            $dist_id,
            $dist_name,
            $block_id,
            $block_name,
            $fac_name,
            $Health_type,
            $lat,
            $longit,
            $NIN_no,
            $fac_id
        );
        if ($stmt->execute()) { $success = "Facility details updated successfully."; }
        else { $error = "Update failed: " . $stmt->error; }
        $stmt->close();
    }
}

/* =========================
   ADD Certification Record
   ========================= */
if (isset($_POST['save_cert'])) {
    $cert_type    = trim($_POST['cert_type'] ?? '');
    $cert_details = trim($_POST['cert_detailscol'] ?? '');
    $cert_issue   = trim($_POST['cert_issue'] ?? '');
    $score        = trim($_POST['score'] ?? '');
    $lat_c        = (float)($_POST['lat'] ?? 0);
    $long_c       = (float)($_POST['longi'] ?? 0);

    $validity = $cert_issue ? date('Y-m-d', strtotime($cert_issue . ' +1 year')) : null;

    $stmtF = $con->prepare("SELECT fac_name, Dist_Name, Block_Name, Health_facilty_type, dist_id, block_id FROM facilities WHERE fac_id=?");
    $stmtF->bind_param("i", $fac_id);
    $stmtF->execute();
    $stmtF->bind_result($fac_name_db, $dist_name_db, $block_name_db, $fac_type_db, $dist_id_db, $block_id_db);
    $stmtF->fetch();
    $stmtF->close();

    $stmt = $con->prepare("INSERT INTO cert_details 
        (dist, block, fac_name, fac_type, cert_type, cert_detailscol, cert_issue, validity, score, lat, longi, dist_id, block_id, fac_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param(
        "sssssssssddiii",
        $dist_name_db,
        $block_name_db,
        $fac_name_db,
        $fac_type_db,  // change to (int)$fac_type_db + 'i' if numeric column
        $cert_type,
        $cert_details,
        $cert_issue,
        $validity,
        $score,
        $lat_c,
        $long_c,
        $dist_id_db,
        $block_id_db,
        $fac_id
    );
    if ($stmt->execute()) { $certMsg = "Certification added successfully."; }
    else { $certMsg = "Error adding certification: " . $stmt->error; }
    $stmt->close();
}

/* =========================
   FETCH Facility + Masters + Cert History
   ========================= */
$stmt = $con->prepare("SELECT * FROM facilities WHERE fac_id=?");
$stmt->bind_param("i", $fac_id);
$stmt->execute();
$res = $stmt->get_result();
$facility = $res->fetch_assoc();
$stmt->close();

if (!$facility) {
    die("<div class='alert alert-warning m-3'>No facility found for this user.</div>");
}

$divisions     = $con->query("SELECT iddivision, division_name FROM division ORDER BY division_name")->fetch_all(MYSQLI_ASSOC);
$districts     = $con->query("SELECT Dist_id, Dist_name, division_id FROM dist_master ORDER BY Dist_name")->fetch_all(MYSQLI_ASSOC);
$blocks        = $con->query("SELECT block_id, block_name, dist_id FROM block_master ORDER BY block_name")->fetch_all(MYSQLI_ASSOC);
$facilityTypes = $con->query("SELECT fac_type_id, facilities_type FROM facilities_type ORDER BY facilities_type")->fetch_all(MYSQLI_ASSOC);
$certHistory   = $con->query("SELECT * FROM cert_details WHERE fac_id=" . (int)$fac_id . " ORDER BY cert_issue DESC");

$facTypeMap = [];
foreach ($facilityTypes as $ft) { $facTypeMap[(string)$ft['fac_type_id']] = $ft['facilities_type']; }
$facTypeName = $facTypeMap[(string)$facility['Health_facilty_type']] ?? '—';
?>
<!-- Extra vendor CSS for this page only -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">

<!-- DataTables (Bootstrap 4 skin to match Flat Able) -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css"/>

<!-- Leaflet -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>

<style>
  /* Compact form controls (BS4) */
  .form-group { margin-bottom: .6rem; }
  .form-control, .custom-select {
      height: calc(1.95rem + 2px);
      padding: .25rem .5rem;
      font-size: .85rem;
      line-height: 1.2;
  }
  .form-control:read-only { background-color: #f8f9fa; }
  .form-control-sm, .custom-select-sm { height: calc(1.8rem + 2px); font-size: .82rem; }

  .floating-label { position: relative; }
  .floating-label > label {
    position: absolute; top: -0.55rem; left: .6rem; background: #fff; padding: 0 .25rem;
    font-size: .70rem; color: #6c757d;
  }

  .pc-card { border-radius: 12px; box-shadow: 0 8px 28px rgba(15,23,42,.06); }
  .pc-card .card-body { padding: 1rem 1rem 1.25rem; }

  .leaflet-map { height: 220px; border-radius: 12px; overflow: hidden; border: 1px solid #e5e7eb; }

  .btn-geo{ background:#0ea5e9; color:#fff; border:0; padding:.35rem .75rem; font-size:.85rem; }
  .btn-save{ background:#0d6efd; color:#fff; border:0; padding:.4rem .9rem; font-size:.85rem; }
  .btn-light { padding:.4rem .9rem; font-size:.85rem; }

  .table thead th{ background:#f6f7fb; font-weight:600; }
  .badge-muted { color:#6c757d; font-size:.8rem; }
</style>

<div class="pcoded-main-container mt-3">
  <div class="pcoded-content">

    <div class="d-flex align-items-center justify-content-between mb-3">
      <h5 class="mb-0"><i class="fa-solid fa-hospital me-2"></i>Facility Profile</h5>
      <div class="badge-muted">Logged facility ID: <strong><?= $fac_id ?></strong></div>
    </div>

    <?php if ($success): ?>
      <div class="alert alert-success py-2 px-3 mb-3"><i class="fa-solid fa-circle-check mr-2"></i><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-danger py-2 px-3 mb-3"><i class="fa-solid fa-triangle-exclamation mr-2"></i><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($certMsg): ?>
      <div class="alert alert-info py-2 px-3 mb-3"><i class="fa-solid fa-file-circle-plus mr-2"></i><?= htmlspecialchars($certMsg) ?></div>
    <?php endif; ?>

    <div class="card pc-card">
      <div class="card-body">
        <!-- BS4 tabs (use data-toggle, not data-bs-toggle) -->
        <ul class="nav nav-tabs mb-3" id="facilityTabs" role="tablist">
          <li class="nav-item">
            <a class="nav-link active text-uppercase" id="profile-tab" data-toggle="tab" href="#profile" role="tab" aria-controls="profile" aria-selected="true">
              Profile
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link text-uppercase" id="certification-tab" data-toggle="tab" href="#certification" role="tab" aria-controls="certification" aria-selected="false">
              Certification
            </a>
          </li>
        </ul>

        <div class="tab-content" id="facilityTabsContent">
          <!-- PROFILE TAB -->
          <div class="tab-pane fade show active" id="profile" role="tabpanel" aria-labelledby="profile-tab">
            <div class="row">
              <div class="col-12">
                <div class="pc-card">
                  <div class="card-body">
                    <!-- View Mode: 3 fields per row -->
                    <div id="viewMode">
                      <div class="form-row">
                        <div class="form-group col-md-4">
                          <div class="floating-label">
                            <input type="text" class="form-control form-control-sm" value="<?= htmlspecialchars($facility['state_name']) ?>" readonly>
                            <label>State</label>
                          </div>
                        </div>
                        <div class="form-group col-md-4">
                          <div class="floating-label">
                            <input type="text" class="form-control form-control-sm" value="<?= htmlspecialchars($facility['division']) ?>" readonly>
                            <label>Division</label>
                          </div>
                        </div>
                        <div class="form-group col-md-4">
                          <div class="floating-label">
                            <input type="text" class="form-control form-control-sm" value="<?= htmlspecialchars($facility['Dist_Name']) ?>" readonly>
                            <label>District</label>
                          </div>
                        </div>

                        <div class="form-group col-md-4">
                          <div class="floating-label">
                            <input type="text" class="form-control form-control-sm" value="<?= htmlspecialchars($facility['Block_Name']) ?>" readonly>
                            <label>Block</label>
                          </div>
                        </div>
                        <div class="form-group col-md-4">
                          <div class="floating-label">
                            <input type="text" class="form-control form-control-sm" value="<?= htmlspecialchars($facility['fac_name']) ?>" readonly>
                            <label>Facility Name</label>
                          </div>
                        </div>
                        <div class="form-group col-md-4">
                          <div class="floating-label">
                            <input type="text" class="form-control form-control-sm" value="<?= htmlspecialchars($facTypeName) ?>" readonly>
                            <label>Facility Type</label>
                          </div>
                        </div>

                        <div class="form-group col-md-4">
                          <div class="floating-label">
                            <input type="text" class="form-control form-control-sm" value="<?= htmlspecialchars($facility['NIN_no']) ?>" readonly>
                            <label>NIN Number</label>
                          </div>
                        </div>
                        <div class="form-group col-md-4">
                          <div class="floating-label">
                            <input type="text" class="form-control form-control-sm" value="<?= htmlspecialchars($facility['lat']) ?>" readonly>
                            <label>Latitude</label>
                          </div>
                        </div>
                        <div class="form-group col-md-4">
                          <div class="floating-label">
                            <input type="text" class="form-control form-control-sm" value="<?= htmlspecialchars($facility['longit']) ?>" readonly>
                            <label>Longitude</label>
                          </div>
                        </div>
                      </div>

                      <button id="editBtn" class="btn btn-outline-primary btn-sm mt-2">
                        <i class="fa-solid fa-pen-to-square mr-1"></i> Edit
                      </button>
                    </div>

                    <!-- Edit Mode: 3 fields per row -->
                    <div id="editMode" style="display:none;">
                      <form method="post" action="" autocomplete="off" novalidate>
                        <input type="hidden" name="fac_id" value="<?= $fac_id ?>">

                        <div class="form-row">
                          <div class="form-group col-md-4">
                            <label class="mb-1 small">State (Read-only)</label>
                            <input class="form-control form-control-sm" type="text" name="state_name"
                                   value="<?= htmlspecialchars($facility['state_name']) ?>" readonly>
                          </div>

                          <div class="form-group col-md-4">
                            <label class="mb-1 small">Division</label>
                            <select class="custom-select custom-select-sm" name="division_id" id="division_id" required>
                              <option value="">-- Select Division --</option>
                              <?php foreach ($divisions as $d): ?>
                                <option value="<?= $d['iddivision'] ?>" <?= ($d['iddivision'] == $facility['division_id']) ? 'selected' : '' ?>>
                                  <?= htmlspecialchars($d['division_name']) ?>
                                </option>
                              <?php endforeach; ?>
                            </select>
                          </div>

                          <div class="form-group col-md-4">
                            <label class="mb-1 small">District</label>
                            <select class="custom-select custom-select-sm" name="dist_id" id="dist_id" required>
                              <option value="">-- Select District --</option>
                            </select>
                          </div>

                          <div class="form-group col-md-4">
                            <label class="mb-1 small">Block</label>
                            <select class="custom-select custom-select-sm" name="block_id" id="block_id" required>
                              <option value="">-- Select Block --</option>
                            </select>
                          </div>

                          <div class="form-group col-md-4">
                            <label class="mb-1 small">Facility Type</label>
                            <select class="custom-select custom-select-sm" name="Health_facilty_type" id="fac_type" required>
                              <option value="">-- Select Facility Type --</option>
                              <?php foreach ($facilityTypes as $ft): ?>
                                <option value="<?= $ft['fac_type_id'] ?>" <?= ($ft['fac_type_id'] == $facility['Health_facilty_type']) ? 'selected' : '' ?>>
                                  <?= htmlspecialchars($ft['facilities_type']) ?>
                                </option>
                              <?php endforeach; ?>
                            </select>
                          </div>

                          <div class="form-group col-md-4">
                            <label class="mb-1 small">Facility Name</label>
                            <input class="form-control form-control-sm" type="text" name="fac_name"
                                   value="<?= htmlspecialchars($facility['fac_name']) ?>"
                                   required pattern="[A-Za-z\s]+" title="Only letters and spaces are allowed">
                          </div>

                          <div class="form-group col-md-4">
                            <label class="mb-1 small">NIN Number (10 digits)*</label>
                            <input class="form-control form-control-sm" type="text" name="NIN_no" id="NIN_no"
                                   maxlength="10" minlength="10" pattern="\d{10}"
                                   title="Enter 10 digits only"
                                   value="<?= htmlspecialchars($facility['NIN_no']) ?>" required
                                   oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,10)">
                          </div>

                          <div class="form-group col-md-4">
                            <label class="mb-1 small">Latitude</label>
                            <input class="form-control form-control-sm" type="text" name="lat" id="lat"
                                   value="<?= htmlspecialchars($facility['lat']) ?>" readonly>
                          </div>

                          <div class="form-group col-md_4 col-md-4">
                            <label class="mb-1 small">Longitude</label>
                            <input class="form-control form-control-sm" type="text" name="longit" id="longit"
                                   value="<?= htmlspecialchars($facility['longit']) ?>" readonly>
                          </div>

                          <div class="form-group col-12">
                            <div id="editMap" class="leaflet-map"></div>
                            <div class="mt-2 d-flex align-items-center">
                              <button type="button" id="useGeo" class="btn btn-geo mr-2">
                                <i class="fa-solid fa-location-crosshairs mr-1"></i> Use Current Location
                              </button>
                              <small class="text-muted">Click map or drag marker to update coordinates.</small>
                            </div>
                          </div>
                        </div>

                        <div class="mt-2 d-flex">
                          <button type="submit" name="save_facility" class="btn btn-save mr-2">
                            <i class="fa-solid fa-floppy-disk mr-1"></i> Save
                          </button>
                          <button type="button" id="cancelEdit" class="btn btn-light">Cancel</button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- CERTIFICATION TAB -->
          <div class="tab-pane fade" id="certification" role="tabpanel" aria-labelledby="certification-tab">
            <div class="row">
              <div class="col-lg-5">
                <div class="pc-card">
                  <div class="card-body">
                    <h6 class="mb-2">Add Certification</h6>
                    <form method="post">
                      <div class="form-row">
                        <div class="form-group col-12">
                          <label class="mb-1 small">Certification Type</label>
                          <select name="cert_type" class="custom-select custom-select-sm" required>
                            <option value="">Select Certification Type</option>
                            <option value="National">National</option>
                            <option value="State">State</option>
                          </select>
                        </div>

                        <div class="form-group col-12">
                          <label class="mb-1 small">Certification Details</label>
                          <input type="text" name="cert_detailscol" class="form-control form-control-sm" required>
                        </div>

                        <div class="form-group col-md-6">
                          <label class="mb-1 small">Issue Date</label>
                          <input type="date" name="cert_issue" class="form-control form-control-sm" required>
                        </div>

                        <div class="form-group col-md-6">
                          <label class="mb-1 small">Score</label>
                          <input type="number" step="0.01" name="score" class="form-control form-control-sm" required>
                        </div>

                        <div class="form-group col-md-6">
                          <label class="mb-1 small">Latitude</label>
                          <input type="text" id="cert_lat" name="lat" class="form-control form-control-sm" value="<?= htmlspecialchars($facility['lat']) ?>" readonly>
                        </div>
                        <div class="form-group col-md-6">
                          <label class="mb-1 small">Longitude</label>
                          <input type="text" id="cert_long" name="longi" class="form-control form-control-sm" value="<?= htmlspecialchars($facility['longit']) ?>" readonly>
                        </div>

                        <div class="form-group col-12">
                          <div id="certMap" class="leaflet-map"></div>
                          <div class="mt-2 d-flex align-items-center">
                            <button type="button" id="useGeoCert" class="btn btn-geo mr-2">
                              <i class="fa-solid fa-location-dot mr-1"></i> Get Location
                            </button>
                            <small class="text-muted">Click map or drag marker to set certification coordinates.</small>
                          </div>
                        </div>

                        <div class="form-group col-12">
                          <button type="submit" name="save_cert" class="btn btn-success btn-sm">
                            <i class="fa-solid fa-plus mr-1"></i> Add Certification
                          </button>
                        </div>
                      </div>
                    </form>
                  </div>
                </div>
              </div>

              <div class="col-lg-7">
                <div class="pc-card">
                  <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <h6 class="mb-0">Previous Certifications</h6>
                      <small class="text-muted">Search & sort</small>
                    </div>

                    <div class="table-responsive">
                      <table id="certTable" class="table table-striped table-bordered table-sm">
                        <thead>
                          <tr>
                            <th>#</th>
                            <th>Type</th>
                            <th>Details</th>
                            <th>Issue Date</th>
                            <th>Validity</th>
                            <th>Score</th>
                          </tr>
                        </thead>
                       <tbody>
<?php if ($certHistory && $certHistory->num_rows > 0): $i = 1; ?>
  <?php while ($c = $certHistory->fetch_assoc()): ?>
    <tr>
      <td><?= $i++ ?></td>
      <td><?= htmlspecialchars($c['cert_type']) ?></td>
      <td><?= htmlspecialchars($c['cert_detailscol']) ?></td>
      <td><?= htmlspecialchars($c['cert_issue']) ?></td>
      <td><?= htmlspecialchars($c['validity']) ?></td>
      <td><?= htmlspecialchars($c['score']) ?></td>
    </tr>
  <?php endwhile; ?>
<?php endif; // IMPORTANT: no fallback row with colspan ?>
</tbody>

                      </table>
                    </div>

                  </div>
                </div>
              </div>
            </div>
          </div><!-- /CERTIFICATION TAB -->
        </div><!-- /tab-content -->
      </div>
    </div>

  </div>
</div>

<?php include("assets/head/f.php"); ?>

<!-- Page JS (use BS4-compatible libs; Flat Able already loads jQuery & bootstrap.min.js) -->
<!-- DataTables core + BS4 integration -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>

<!-- Leaflet -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQC1N0C6G2Z3e1rV0CcdN1S9GKa3I1C2f2P3p3p3s=" crossorigin=""></script>

<script>
  // preload master arrays for cascading selects
  const districts    = <?= json_encode($districts) ?>;
  const blocks       = <?= json_encode($blocks) ?>;
  const currentDist  = "<?= $facility['dist_id'] ?>";
  const currentBlock = "<?= $facility['block_id'] ?>";

  function loadDistricts(divId) {
    const distSel = document.getElementById('dist_id');
    if (!distSel) return;
    distSel.innerHTML = '<option value="">-- Select District --</option>';
    const filtered = districts.filter(d => String(d.division_id) === String(divId));
    filtered.forEach(d => {
      const opt = document.createElement('option');
      opt.value = d.Dist_id;
      opt.textContent = d.Dist_name;
      if (String(d.Dist_id) === String(currentDist)) opt.selected = true;
      distSel.appendChild(opt);
    });
    const chosen = distSel.value || (filtered[0]?.Dist_id ?? "");
    loadBlocks(chosen);
  }

  function loadBlocks(distId) {
    const blkSel = document.getElementById('block_id');
    if (!blkSel) return;
    blkSel.innerHTML = '<option value="">-- Select Block --</option>';
    blocks.filter(b => String(b.dist_id) === String(distId)).forEach(b => {
      const opt = document.createElement('option');
      opt.value = b.block_id;
      opt.textContent = b.block_name;
      if (String(b.block_id) === String(currentBlock)) opt.selected = true;
      blkSel.appendChild(opt);
    });
  }

  // Leaflet helper
  function initLeafletMap(containerId, lat, lng, onChange) {
    const map = L.map(containerId, { scrollWheelZoom: true }).setView([lat, lng], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    const marker = L.marker([lat, lng], { draggable: true }).addTo(map);

    function update(latlng) {
      marker.setLatLng(latlng);
      if (onChange) onChange(latlng);
    }

    map.on('click', (e) => update(e.latlng));
    marker.on('dragend', (e) => update(e.target.getLatLng()));

    // fix for hidden tab rendering
    setTimeout(() => { map.invalidateSize(); }, 300);

    return { map, marker, update };
  }

  $(function() {
    // Hash support for BS4 tabs
    const hash = window.location.hash;
    if (hash && document.querySelector(`[href="${hash}"]`)) {
      $('a[href="' + hash + '"]').tab('show');
    }
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
      if (e.target.getAttribute('href') === '#certification') {
        if (window._certMap?.map) window._certMap.map.invalidateSize();
      }
      if (e.target.getAttribute('href') === '#profile') {
        if (window._editMap?.map) window._editMap.map.invalidateSize();
      }
    });

    // Toggle view/edit mode
    $('#editBtn').on('click', function(){
      $('#viewMode').hide();
      $('#editMode').fadeIn(120, () => {
        if (window._editMap?.map) window._editMap.map.invalidateSize();
      });
    });
    $('#cancelEdit').on('click', function(){
      $('#editMode').hide();
      $('#viewMode').fadeIn(120);
    });

    // Cascading selects
    const divisionEl = document.getElementById('division_id');
    if (divisionEl) {
      divisionEl.addEventListener('change', e => { loadDistricts(e.target.value); });
      if (divisionEl.value) loadDistricts(divisionEl.value);
    }
    const distEl = document.getElementById('dist_id');
    if (distEl) distEl.addEventListener('change', e => loadBlocks(e.target.value));

    // Initial map centers
    const initLat  = parseFloat('<?= $facility['lat'] ?: 25.0 ?>') || 25.0;
    const initLong = parseFloat('<?= $facility['longit'] ?: 85.0 ?>') || 85.0;

    // Edit map -> updates #lat and #longit
    window._editMap = initLeafletMap('editMap', initLat, initLong, (ll) => {
      $('#lat').val(ll.lat.toFixed(6));
      $('#longit').val(ll.lng.toFixed(6));
    });

    // Geolocation for edit map
    $('#useGeo').on('click', function() {
      if (!navigator.geolocation) return alert('Geolocation not supported');
      navigator.geolocation.getCurrentPosition(function(pos) {
        const lat = pos.coords.latitude;
        const lon = pos.coords.longitude;
        window._editMap.update({ lat, lng: lon });
        $('#lat').val(lat.toFixed(6));
        $('#longit').val(lon.toFixed(6));
        window._editMap.map.setView([lat, lon], 16);
      }, function(err) {
        alert('Error: ' + err.message);
      }, { enableHighAccuracy: true, timeout: 10000 });
    });

    // Certification map -> updates #cert_lat and #cert_long
    const certLat  = parseFloat($('#cert_lat').val())  || initLat;
    const certLong = parseFloat($('#cert_long').val()) || initLong;
    window._certMap = initLeafletMap('certMap', certLat, certLong, (ll) => {
      $('#cert_lat').val(ll.lat.toFixed(6));
      $('#cert_long').val(ll.lng.toFixed(6));
    });

    // Geolocation for cert map
    $('#useGeoCert').onClick = null;
    $('#useGeoCert').on('click', function() {
      if (!navigator.geolocation) return alert('Geolocation not supported');
      navigator.geolocation.getCurrentPosition(function(pos) {
        const lat = pos.coords.latitude;
        const lon = pos.coords.longitude;
        window._certMap.update({ lat, lng: lon });
        $('#cert_lat').val(lat.toFixed(6));
        $('#cert_long').val(lon.toFixed(6));
        window._certMap.map.setView([lat, lon], 16);
      }, function(err) {
        alert('Error: ' + err.message);
      }, { enableHighAccuracy: true, timeout: 10000 });
    });

    // DataTable (BS4)
    $('#certTable').DataTable({
      pageLength: 8,
      lengthChange: false,
      ordering: true,
      order: [[3, 'desc']],
      language: { search: "", searchPlaceholder: "Search certifications..." },
      dom: '<"row mb-2"<"col-sm-6"f><"col-sm-6 text-right"l>>t<"row mt-2"<"col-sm-6"i><"col-sm-6"p>>'
    });
  });
</script>
