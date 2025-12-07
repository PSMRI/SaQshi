<?php
// facility_profile_with_cert_beauty.php
include("assets/head/h.php");

// ==========================
// SESSION VALIDATION
// ==========================
if (!isset($_SESSION['u_facilityid']) || !isset($_SESSION['factynin'])) {
  die("<div class='alert alert-danger m-3'>Access denied. Facility session missing.</div>");
}

$fac_id  = (int)$_SESSION['u_facilityid'];  // internal facility ID
$fac_nin = (int)$_SESSION['factynin'];      // NIN number of facility

$success = $error = $certMsg = "";


// =======================================================
//               UPDATE FACILITY DETAILS
// =======================================================
if (isset($_POST['save_facility']) && (int)($_POST['fac_id'] ?? 0) === $fac_id) {

  $division_id = (int)$_POST['division_id'];
  $dist_id     = (int)$_POST['dist_id'];
  $block_id    = (int)$_POST['block_id'];
  $Health_type = (int)$_POST['Health_facilty_type'];

  $fac_name = trim($_POST['fac_name'] ?? "");
  $lat      = trim($_POST['lat'] ?? "");
  $longit   = trim($_POST['longit'] ?? "");
  $NIN_no   = trim($_POST['NIN_no'] ?? "");

  // Required fields
  if (
    $division_id == 0 || $dist_id == 0 || $block_id == 0 ||
    $Health_type == 0 || $fac_name == "" || $lat == "" || $longit == "" || $NIN_no == ""
  ) {
    $error = "All fields are required.";
  } elseif (!preg_match("/^[A-Za-z\s]+$/", $fac_name)) {
    $error = "Facility Name must contain only letters & spaces.";
  } elseif (!preg_match("/^\d{10}$/", $NIN_no)) {
    $error = "NIN Number must be exactly 10 digits.";
  }

  if ($error === "") {

    // Fetch real names
    $division_name = "";
    $stmt = $con->prepare("SELECT division_name FROM division WHERE iddivision=?");
    $stmt->bind_param("i", $division_id);
    $stmt->execute();
    $stmt->bind_result($division_name);
    $stmt->fetch();
    $stmt->close();

    $dist_name = "";
    $stmt = $con->prepare("SELECT Dist_name FROM dist_master WHERE Dist_id=?");
    $stmt->bind_param("i", $dist_id);
    $stmt->execute();
    $stmt->bind_result($dist_name);
    $stmt->fetch();
    $stmt->close();

    $block_name = "";
    $stmt = $con->prepare("SELECT block_name FROM block_master WHERE block_id=?");
    $stmt->bind_param("i", $block_id);
    $stmt->execute();
    $stmt->bind_result($block_name);
    $stmt->fetch();
    $stmt->close();

    // Update facility
    $stmt = $con->prepare("
            UPDATE facilities SET
                division_id=?, division=?,
                dist_id=?, Dist_Name=?,
                block_id=?, Block_Name=?,
                fac_name=?, Health_facilty_type=?,
                lat=?, longit=?, NIN_no=?
            WHERE fac_id=?
        ");

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

    if ($stmt->execute()) {
      $success = "Facility updated successfully.";
    } else {
      $error = "❌ Update Failed: " . $stmt->error;
    }
    $stmt->close();
  }
}



// =======================================================
//         ADD CERTIFICATION RECORD (USING fac_nin)
// =======================================================
if (isset($_POST['save_cert'])) {

  $cert_type     = trim($_POST['cert_type']);        // National / State
  $cert_details  = trim($_POST['cert_detailscol']);  // NQAS / LaQshya / MusQan / Kayakalp
  $cert_status   = trim($_POST['cert_status']);      // Certified / Conditional Certified
  $ass_mode      = trim($_POST['ass_mode']);         // Physical / Virtual

  $date_of_ass   = trim($_POST['date_of_ass']);
  $cert_issue    = trim($_POST['cert_issue']);
  $score         = trim($_POST['score']);
  $lat_c         = trim($_POST['lat']);
  $long_c        = trim($_POST['longi']);

  // Required fields
  if (
    $cert_type == "" || $cert_details == "" || $cert_status == "" || $ass_mode == "" ||
    $date_of_ass == "" || $cert_issue == "" || $score == "" || $lat_c == "" || $long_c == ""
  ) {

    $certMsg = "❌ All certification fields are required.";
  } elseif (strtotime($cert_issue) < strtotime($date_of_ass)) {
    $certMsg = "❌ Issue Date cannot be earlier than Assessment Date.";
  } else {

    // Auto validity
    $validity = date('Y-m-d', strtotime($cert_issue . " +1 year"));

    // Load facility details
    $stmt = $con->prepare("
            SELECT fac_name, Dist_Name, Block_Name, Health_facilty_type, dist_id, block_id
            FROM facilities 
            WHERE fac_id=?
        ");
    $stmt->bind_param("i", $fac_id);
    $stmt->execute();
    $stmt->bind_result(
      $fac_name_db,
      $dist_name_db,
      $block_name_db,
      $fac_type_db,
      $dist_id_db,
      $block_id_db
    );
    $stmt->fetch();
    $stmt->close();

    // Convert fac_type ID → NAME
    $fac_type_name = "";
    $stmt2 = $con->prepare("SELECT facilities_type FROM facilities_type WHERE fac_type_id=?");
    $stmt2->bind_param("i", $fac_type_db);
    $stmt2->execute();
    $stmt2->bind_result($fac_type_name);
    $stmt2->fetch();
    $stmt2->close();

    // INSERT certification
    $stmt = $con->prepare("
            INSERT INTO cert_details
                (dist, block, fac_name, fac_type,
                 cert_type, cert_detailscol, cert_issue, validity, score,
                 lat, longi, dist_id, block_id, fac_id, fac_nin,
                 Cert_status, ass_mod, date_of_ass)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

    // 18 variables → 18 types
    $stmt->bind_param(
      "ssssssssdddiiiisss",
      $dist_name_db,
      $block_name_db,
      $fac_name_db,
      $fac_type_name,     // Facility Type NAME

      $cert_type,         // National / State
      $cert_details,      // NQAS / LaQshya / MusQan / Kayakalp
      $cert_issue,
      $validity,
      $score,

      $lat_c,
      $long_c,
      $dist_id_db,
      $block_id_db,
      $fac_id,
      $fac_nin,

      $cert_status,
      $ass_mode,
      $date_of_ass
    );

    if ($stmt->execute()) {
      $certMsg = "✔ Certification added successfully.";
    } else {
      $certMsg = "❌ Insert Error: " . $stmt->error;
    }

    $stmt->close();
  }
}




// =======================================================
//       FETCH FACILITY + MASTERS + CERTIFICATION LIST
// =======================================================
$stmt = $con->prepare("SELECT * FROM facilities WHERE fac_id=?");
$stmt->bind_param("i", $fac_id);
$stmt->execute();
$facility = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$facility) {
  die("<div class='alert alert-warning'>Facility not found.</div>");
}

$divisions = $con->query("SELECT iddivision, division_name FROM division ORDER BY division_name")->fetch_all(MYSQLI_ASSOC);
$districts = $con->query("SELECT Dist_id, Dist_name, division_id FROM dist_master ORDER BY Dist_name")->fetch_all(MYSQLI_ASSOC);
$blocks    = $con->query("SELECT block_id, block_name, dist_id FROM block_master ORDER BY block_name")->fetch_all(MYSQLI_ASSOC);
$facilityTypes = $con->query("SELECT fac_type_id, facilities_type FROM facilities_type")->fetch_all(MYSQLI_ASSOC);

// Fetch previous certifications by fac_nin
$certHistory = $con->query("
    SELECT * FROM cert_details
    WHERE fac_nin = $fac_nin
    ORDER BY cert_issue DESC
");
?>
<!-- Extra vendor CSS -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<style>
  .form-group {
    margin-bottom: .6rem;
  }

  .form-control,
  .custom-select {
    height: calc(1.95rem + 2px);
    font-size: .85rem;
  }

  .form-control-sm {
    height: calc(1.8rem + 2px);
  }

  .floating-label {
    position: relative;
  }

  .floating-label>label {
    position: absolute;
    top: -0.55rem;
    left: .6rem;
    background: #fff;
    padding: 0 .2rem;
    font-size: .70rem;
    color: #777;
  }

  .pc-card {
    border-radius: 12px;
    box-shadow: 0 8px 28px rgba(15, 23, 42, .06);
  }

  .leaflet-map {
    height: 220px;
    border-radius: 12px;
    border: 1px solid #ccc;
  }

  .btn-geo {
    background: #0ea5e9;
    color: #fff;
  }

  .btn-save {
    background: #0d6efd;
    color: #fff;
  }
</style>


<div class="pcoded-main-container mt-3">
  <div class="pcoded-content">

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5><i class="fa-solid fa-hospital"></i> Facility Profile</h5>
      <div class="text-muted">Logged Facility NIN: <strong><?= $fac_nin ?></strong></div>
    </div>

    <!-- SUCCESS / ERROR / CERT MESSAGES -->
    <?php if ($success): ?>
      <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <?php if ($certMsg): ?>
      <div class="alert alert-info"><?= $certMsg ?></div>
    <?php endif; ?>


    <!-- MAIN CARD -->
    <div class="card pc-card">
      <div class="card-body">

        <!-- TABS -->
        <ul class="nav nav-tabs mb-3">
          <li class="nav-item">
            <a class="nav-link active" data-toggle="tab" href="#profile">Profile</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" data-toggle="tab" href="#certification">Certification</a>
          </li>
        </ul>


        <div class="tab-content">

          <!-- ======================================
                        PROFILE TAB
            ======================================= -->
          <div class="tab-pane fade show active" id="profile">

            <div class="pc-card">
              <div class="card-body">

                <!-- VIEW MODE -->
                <div id="viewMode">
                  <div class="form-row">

                    <div class="form-group col-md-4">
                      <div class="floating-label">
                        <input class="form-control form-control-sm" value="<?= $facility['state_name'] ?>" readonly>
                        <label>State</label>
                      </div>
                    </div>

                    <div class="form-group col-md-4">
                      <div class="floating-label">
                        <input class="form-control form-control-sm" value="<?= $facility['division'] ?>" readonly>
                        <label>Division</label>
                      </div>
                    </div>

                    <div class="form-group col-md-4">
                      <div class="floating-label">
                        <input class="form-control form-control-sm" value="<?= $facility['Dist_Name'] ?>" readonly>
                        <label>District</label>
                      </div>
                    </div>

                    <div class="form-group col-md-4">
                      <div class="floating-label">
                        <input class="form-control form-control-sm" value="<?= $facility['Block_Name'] ?>" readonly>
                        <label>Block</label>
                      </div>
                    </div>

                    <div class="form-group col-md-4">
                      <div class="floating-label">
                        <input class="form-control form-control-sm" value="<?= $facility['fac_name'] ?>" readonly>
                        <label>Facility Name</label>
                      </div>
                    </div>

                    <div class="form-group col-md-4">
                      <div class="floating-label">
                        <?php
                        $facTypeName = "";
                        $stmtT = $con->prepare("SELECT facilities_type FROM facilities_type WHERE fac_type_id=?");
                        $stmtT->bind_param("i", $facility['Health_facilty_type']);
                        $stmtT->execute();
                        $stmtT->bind_result($facTypeName);
                        $stmtT->fetch();
                        $stmtT->close();
                        ?>
                        <input class="form-control form-control-sm" value="<?= $facTypeName ?>" readonly>

                        <label>Facility Type</label>
                      </div>
                    </div>

                    <div class="form-group col-md-4">
                      <div class="floating-label">
                        <input class="form-control form-control-sm" value="<?= $facility['NIN_no'] ?>" readonly>
                        <label>NIN Number</label>
                      </div>
                    </div>

                    <div class="form-group col-md-4">
                      <div class="floating-label">
                        <input class="form-control form-control-sm" value="<?= $facility['lat'] ?>" readonly>
                        <label>Latitude</label>
                      </div>
                    </div>

                    <div class="form-group col-md-4">
                      <div class="floating-label">
                        <input class="form-control form-control-sm" value="<?= $facility['longit'] ?>" readonly>
                        <label>Longitude</label>
                      </div>
                    </div>

                  </div>

                  <button id="editBtn" class="btn btn-outline-primary btn-sm mt-2">
                    <i class="fa-solid fa-pen"></i> Edit
                  </button>
                </div>
                <!-- END VIEW MODE -->


                <!-- EDIT MODE -->
                <div id="editMode" style="display:none;">
                  <form method="POST">

                    <input type="hidden" name="fac_id" value="<?= $fac_id ?>">

                    <div class="form-row">

                      <div class="form-group col-md-4">
                        <label>State</label>
                        <input class="form-control form-control-sm" value="<?= $facility['state_name'] ?>" readonly>
                      </div>

                      <div class="form-group col-md-4">
                        <label>Division</label>
                        <select name="division_id" id="division_id" class="form-control form-control-sm" required>
                          <option value="">Select</option>
                          <?php foreach ($divisions as $d): ?>
                            <option value="<?= $d['iddivision'] ?>" <?= ($d['iddivision'] == $facility['division_id']) ? 'selected' : '' ?>>
                              <?= $d['division_name'] ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>

                      <div class="form-group col-md-4">
                        <label>District</label>
                        <select name="dist_id" id="dist_id" class="form-control form-control-sm" required>
                          <option value="">Select District</option>
                        </select>
                      </div>

                      <div class="form-group col-md-4">
                        <label>Block</label>
                        <select name="block_id" id="block_id" class="form-control form-control-sm" required>
                          <option value="">Select Block</option>
                        </select>
                      </div>

                      <div class="form-group col-md-4">
                        <label>Facility Type</label>
                        <select name="Health_facilty_type" class="form-control form-control-sm" required>
                          <option value="">Select Type</option>
                          <?php foreach ($facilityTypes as $ft): ?>
                            <option value="<?= $ft['fac_type_id'] ?>" <?= ($ft['fac_type_id'] == $facility['Health_facilty_type']) ? 'selected' : '' ?>>
                              <?= $ft['facilities_type'] ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>

                      <div class="form-group col-md-4">
                        <label>Facility Name</label>
                        <input type="text" name="fac_name" class="form-control form-control-sm"
                          value="<?= $facility['fac_name'] ?>" required>
                      </div>

                      <div class="form-group col-md-4">
                        <label>NIN Number</label>
                        <input type="text" name="NIN_no" maxlength="10"
                          value="<?= $facility['NIN_no'] ?>"
                          class="form-control form-control-sm" required>
                      </div>

                      <div class="form-group col-md-4">
                        <label>Latitude</label>
                        <input type="text" name="lat" id="lat"
                          value="<?= $facility['lat'] ?>"
                          class="form-control form-control-sm" readonly>
                      </div>

                      <div class="form-group col-md-4">
                        <label>Longitude</label>
                        <input type="text" name="longit" id="longit"
                          value="<?= $facility['longit'] ?>"
                          class="form-control form-control-sm" readonly>
                      </div>

                      <div class="form-group col-12">
                        <div id="editMap" class="leaflet-map"></div>
                        <button type="button" id="useGeo" class="btn btn-geo btn-sm mt-2">
                          <i class="fa-solid fa-location-crosshairs"></i> Use Current Location
                        </button>
                      </div>

                    </div>

                    <button class="btn btn-save btn-sm" name="save_facility">Save</button>
                    <button type="button" class="btn btn-light btn-sm" id="cancelEdit">Cancel</button>

                  </form>
                </div>
                <!-- END EDIT MODE -->

              </div>
            </div>

          </div>
          <!-- END PROFILE TAB -->
          <div class="tab-pane fade" id="certification">
            <div class="pc-card mb-3">
              <div class="card-body">
                <form method="POST">
                  <div class="form-row">
                    <div class="form-group col-md-4">
                      <label>Certification Level</label>
                      <select name="cert_type" class="form-control form-control-sm" required>
                        <option value="">Select</option>
                        <option value="National">National</option>
                        <option value="State">State</option>
                      </select>
                    </div>
                    <div class="form-group col-md-4">
                      <label>Certification Program</label>
                      <select name="cert_detailscol" class="form-control form-control-sm" required>
                        <option value="">Select</option>
                        <option value="NQAS">NQAS</option>
                        <option value="LaQshya">LaQshya</option>
                        <option value="MusQan">MusQan</option>
                        <option value="Kayakalp">Kayakalp</option>
                      </select>
                    </div>
                    <div class="form-group col-md-4">
                      <label>Status</label>
                      <select name="cert_status" class="form-control form-control-sm" required>
                        <option value="">Select</option>
                        <option value="Certified">Certified</option>
                        <option value="Conditional Certified">Conditional Certified</option>
                      </select>
                    </div>
                    <div class="form-group col-md-4">
                      <label>Assessment Mode</label>
                      <select name="ass_mode" class="form-control form-control-sm" required>
                        <option value="">Select</option>
                        <option value="Physical">Physical</option>
                        <option value="Virtual">Virtual</option>
                      </select>
                    </div>
                    <div class="form-group col-md-4">
                      <label>Date of Assessment</label>
                      <input type="date" name="date_of_ass" id="date_of_ass"
                        class="form-control form-control-sm" required>
                    </div>
                    <div class="form-group col-md-4">
                      <label>Issue Date</label>
                      <input type="date" name="cert_issue" id="cert_issue"
                        class="form-control form-control-sm" required>
                    </div>
                    <div class="form-group col-md-4">
                      <label>Score</label>
                      <input type="number" name="score" step="0.01"
                        class="form-control form-control-sm" required>
                    </div>

                    <div class="form-group col-md-4">
                      <label>Latitude</label>
                      <input type="text" name="lat" id="cert_lat"
                        value="<?= $facility['lat'] ?>"
                        class="form-control form-control-sm" readonly>
                    </div>

                    <div class="form-group col-md-4">
                      <label>Longitude</label>
                      <input type="text" name="longi" id="cert_long"
                        value="<?= $facility['longit'] ?>"
                        class="form-control form-control-sm" readonly>
                    </div>
                     <button type="button" id="useGeoCert" class="btn btn-geo btn-sm">
                      <i class="fa-solid fa-location-dot"></i> Use Current Location
                    </button>
                    <button class="btn btn-success btn-sm" name="save_cert">
                      <i class="fa-solid fa-plus"></i> Add Certification
                    </button>                   
                  </div>
                  <div class="form-group col-md-12">
                    <div id="certMap" class="leaflet-map"></div>

                  </div>


                </form>

              </div>
            </div>

            <!-- ========================== -->
            <!-- TABLE BELOW THE FORM       -->
            <!-- ========================== -->

            <div class="pc-card">
              <div class="card-body">

                <h6 class="mb-2">Previous Certifications</h6>

                <table id="certTable" class="table table-bordered table-sm">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>Level</th>
                      <th>Program</th>
                      <th>Status</th>
                      <th>Assessment Mode</th>
                      <th>Issue Date</th>
                      <th>Validity</th>
                      <th>Score</th>
                    </tr>
                  </thead>

                  <tbody>
                    <?php $i = 1;
                    while ($c = $certHistory->fetch_assoc()): ?>
                      <tr>
                        <td><?= $i++ ?></td>
                        <td><?= $c['cert_type'] ?></td>
                        <td><?= $c['cert_detailscol'] ?></td>
                        <td><?= $c['Cert_status'] ?></td>
                        <td><?= $c['ass_mod'] ?></td>
                        <td><?= $c['cert_issue'] ?></td>
                        <td><?= $c['validity'] ?></td>
                        <td><?= $c['score'] ?></td>
                      </tr>
                    <?php endwhile; ?>
                  </tbody>
                </table>

              </div>
            </div>

          </div>


        </div>

      </div>
    </div>

  </div>
</div>
<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<!-- Leaflet -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
  // ===========================================================================
  // LOAD DISTRICTS BASED ON SELECTED DIVISION
  // ===========================================================================
  const districts = <?= json_encode($districts) ?>;
  const blocks = <?= json_encode($blocks) ?>;

  const currentDist = "<?= $facility['dist_id'] ?>";
  const currentBlock = "<?= $facility['block_id'] ?>";

  function loadDistricts(divId) {
    const distSel = document.getElementById("dist_id");
    distSel.innerHTML = '<option value="">Select District</option>';

    districts
      .filter(d => d.division_id == divId)
      .forEach(d => {
        const opt = document.createElement("option");
        opt.value = d.Dist_id;
        opt.textContent = d.Dist_name;
        if (d.Dist_id == currentDist) opt.selected = true;
        distSel.appendChild(opt);
      });

    loadBlocks(distSel.value);
  }


  // ===========================================================================
  // LOAD BLOCKS BASED ON DISTRICT
  // ===========================================================================
  function loadBlocks(distId) {
    const blkSel = document.getElementById("block_id");
    blkSel.innerHTML = '<option value="">Select Block</option>';

    blocks
      .filter(b => b.dist_id == distId)
      .forEach(b => {
        const opt = document.createElement("option");
        opt.value = b.block_id;
        opt.textContent = b.block_name;
        if (b.block_id == currentBlock) opt.selected = true;
        blkSel.appendChild(opt);
      });
  }


  // ===========================================================================
  // LEAFLET MAP INITIALIZATION
  // ===========================================================================
  function initLeafletMap(containerId, lat, lng, onChange) {
    const map = L.map(containerId).setView([lat, lng], 15);

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      maxZoom: 19
    }).addTo(map);

    const marker = L.marker([lat, lng], {
      draggable: true
    }).addTo(map);

    function update(latlng) {
      marker.setLatLng(latlng);
      if (onChange) onChange(latlng);
    }

    map.on("click", e => update(e.latlng));
    marker.on("dragend", e => update(e.target.getLatLng()));

    setTimeout(() => map.invalidateSize(), 400);

    return {
      map,
      marker,
      update
    };
  }


  // ===========================================================================
  // LIVE DATE VALIDATION (Assessment < Issue ? Reject)
  // ===========================================================================
  function validateCertDates() {
    const ass = document.getElementById("date_of_ass").value;
    const issue = document.getElementById("cert_issue").value;

    if (!ass || !issue) return;

    let aa = new Date(ass);
    let ii = new Date(issue);

    if (ii < aa) {
      alert("❌ Issue Date cannot be earlier than Assessment Date.");
      document.getElementById("cert_issue").value = "";
    }
  }


  // ===========================================================================
  // DOCUMENT READY
  // ===========================================================================
  $(function() {

    // -----------------------------------------
    // TOGGLE PROFILE VIEW / EDIT
    // -----------------------------------------
    $("#editBtn").click(() => {
      $("#viewMode").hide();
      $("#editMode").fadeIn(180);
      setTimeout(() => window._editMap?.map.invalidateSize(), 300);
    });

    $("#cancelEdit").click(() => {
      $("#editMode").hide();
      $("#viewMode").fadeIn(180);
    });


    // -----------------------------------------
    // CASCADING SELECTS
    // -----------------------------------------
    $("#division_id").change(function() {
      loadDistricts(this.value);
    });

    // Load existing values automatically
    if ($("#division_id").val()) {
      loadDistricts($("#division_id").val());
    }

    $("#dist_id").change(function() {
      loadBlocks(this.value);
    });


    // -----------------------------------------
    // MAP FOR EDIT PROFILE (LAT/LNG)
    // -----------------------------------------
    const initialLat = parseFloat("<?= $facility['lat'] ?>") || 25.0;
    const initialLong = parseFloat("<?= $facility['longit'] ?>") || 82.0;

    window._editMap = initLeafletMap("editMap", initialLat, initialLong, (ll) => {
      $("#lat").val(ll.lat.toFixed(6));
      $("#longit").val(ll.lng.toFixed(6));
    });

    $("#useGeo").click(() => {
      if (!navigator.geolocation) return alert("Geolocation not supported");

      navigator.geolocation.getCurrentPosition(pos => {
        const lat = pos.coords.latitude;
        const lon = pos.coords.longitude;

        window._editMap.update({
          lat,
          lng: lon
        });
        $("#lat").val(lat.toFixed(6));
        $("#longit").val(lon.toFixed(6));
        window._editMap.map.setView([lat, lon], 16);
      });
    });


    // -----------------------------------------
    // MAP FOR CERTIFICATION TAB
    // -----------------------------------------
    const certLat = parseFloat($("#cert_lat").val()) || initialLat;
    const certLong = parseFloat($("#cert_long").val()) || initialLong;

    window._certMap = initLeafletMap("certMap", certLat, certLong, (ll) => {
      $("#cert_lat").val(ll.lat.toFixed(6));
      $("#cert_long").val(ll.lng.toFixed(6));
    });

    $("#useGeoCert").click(() => {
      if (!navigator.geolocation) return alert("Geolocation not supported");

      navigator.geolocation.getCurrentPosition(pos => {
        const lat = pos.coords.latitude;
        const lon = pos.coords.longitude;

        window._certMap.update({
          lat,
          lng: lon
        });
        $("#cert_lat").val(lat.toFixed(6));
        $("#cert_long").val(lon.toFixed(6));
        window._certMap.map.setView([lat, lon], 16);
      });
    });


    // -----------------------------------------
    // LIVE DATE VALIDATION (Assessment vs Issue)
    // -----------------------------------------
    $("#date_of_ass, #cert_issue").change(validateCertDates);


    // -----------------------------------------
    // DATATABLE INITIALIZATION
    // -----------------------------------------
    $("#certTable").DataTable({
      pageLength: 8,
      lengthChange: false,
      ordering: true,
      order: [
        [5, "desc"]
      ],
      language: {
        search: "",
        searchPlaceholder: "Search certifications..."
      }
    });

  });
</script>

<?php include("assets/head/f.php"); ?>