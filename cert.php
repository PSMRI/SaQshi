<?php
include("assets/head/h.php");

$successMsg = '';

if (isset($_POST['postsubmit'])) {
  $fac_name   = $_POST['fac_name'] ?? '';
  $cert_type  = $_POST['cert_type'] ?? '';
  $cert_issue = $_POST['cert_issue'] ?? '';
  $validity   = '';
  $score      = $_POST['score'] ?? null;
  $lat        = isset($_POST['lat']) ? floatval($_POST['lat']) : null;
  $longi      = isset($_POST['longi']) ? floatval($_POST['longi']) : null;
  $dist       = $_POST['dist'] ?? null;
  $block      = $_POST['block'] ?? null;
  $fac_type   = $_POST['fac_type'] ?? '';
  $update_id  = $_POST['update_id'] ?? '';

  // Get District name
  $dist_name = '';
  if ($dist !== null) {
    $stmt = $con->prepare("SELECT Dist_name FROM dist_master WHERE Dist_id = ?");
    $stmt->bind_param("i", $dist);
    $stmt->execute();
    $stmt->bind_result($dist_name);
    $stmt->fetch();
    $stmt->close();
  }

  // Calculate validity (1 year from issue date)
  if ($cert_issue !== '') {
    $validity = date('Y-m-d', strtotime($cert_issue . ' +1 year'));
  }

  // ✅ UPDATE if record already exists (has update_id)
  if (!empty($update_id)) {
    $stmt = $con->prepare("UPDATE cert_details 
          SET dist_id=?, dist=?, block=?, fac_name=?, fac_type=?, cert_type=?, cert_issue=?, validity=?, score=?, lat=?, longi=?
          WHERE id=?");
    $stmt->bind_param(
      "isisssssddsi",
      $dist,
      $dist_name,
      $block,
      $fac_name,
      $fac_type,
      $cert_type,
      $cert_issue,
      $validity,
      $score,
      $lat,
      $longi,
      $update_id
    );
    $successMsg = $stmt->execute()
      ? "✅ Certification details updated successfully."
      : "❌ Failed to update details.";
    $stmt->close();

  // ✅ Otherwise, INSERT new record
  } else {
    $stmt = $con->prepare("INSERT INTO cert_details 
          (dist_id, dist, block, fac_name, fac_type, cert_type, cert_issue, validity, score, lat, longi)
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param(
      "isisssssdds",
      $dist,
      $dist_name,
      $block,
      $fac_name,
      $fac_type,
      $cert_type,
      $cert_issue,
      $validity,
      $score,
      $lat,
      $longi
    );
    $successMsg = $stmt->execute()
      ? "✅ Certification details saved successfully."
      : "❌ Failed to save details.";
    $stmt->close();
  }
}

// ===== Load Dropdown Data =====
$districts = $blocksByDistrict = $facilitiesByBlock = $facilityTypes = $certMap = [];

$distRes = $con->query("SELECT * FROM dist_master");
while ($d = $distRes->fetch_assoc()) {
  $districts[$d['Dist_id']] = $d['Dist_name'];
}

$blockRes = $con->query("SELECT block_id, block_name, dist_id FROM block_master");
while ($b = $blockRes->fetch_assoc()) {
  $blocksByDistrict[$b['dist_id']][] = $b;
}

$typeRes = $con->query("SELECT * FROM facilities_type");
while ($t = $typeRes->fetch_assoc()) {
  $facilityTypes[$t['fac_type_id']] = $t['facilities_type'];
}

$facilityRes = $con->query("SELECT * FROM facilities");
while ($f = $facilityRes->fetch_assoc()) {
  $facilitiesByBlock[$f['block_id']][] = [
    'id' => $f['fac_id'],
    'fac_name' => $f['fac_name'],
    'fac_type_id' => $f['Health_facilty_type'],
    'fac_type_name' => $facilityTypes[$f['Health_facilty_type']] ?? ''
  ];
}

$certRes = $con->query("SELECT * FROM cert_details");
while ($r = $certRes->fetch_assoc()) {
  $certMap[$r['fac_name']] = $r;
}
?>


<!-- Glow Style -->
<style>
.glow-card {
  background: #ffffff;
  border-radius: 12px;
  box-shadow: 0 0 12px rgba(0, 123, 255, 0.5);
  transition: box-shadow 0.3s ease-in-out;
}
.glow-card:hover {
  box-shadow: 0 0 20px rgba(235, 81, 39, 0.8);
}
</style>

<script>
const blockData = <?= json_encode($blocksByDistrict) ?>;
const facilityData = <?= json_encode($facilitiesByBlock) ?>;
const certData = <?= json_encode($certMap) ?>;

function loadBlocks(distId) {
  const blockSelect = document.getElementById('block');
  blockSelect.innerHTML = '<option value="">Select Block</option>';
  (blockData[distId] || []).forEach(b => {
    blockSelect.innerHTML += `<option value="${b.block_id}">${b.block_name}</option>`;
  });
  document.getElementById('facility').innerHTML = '<option value="">Select Facility</option>';
  document.getElementById('fac_type').value = '';
  resetCertInputs();
}

function loadFacilities(blockId) {
  const facSelect = document.getElementById('facility');
  facSelect.innerHTML = '<option value="">Select Facility</option>';
  (facilityData[blockId] || []).forEach(f => {
    facSelect.innerHTML += `<option value="${f.fac_name}" data-type="${f.fac_type_name}">${f.fac_name}</option>`;
  });
  document.getElementById('fac_type').value = '';
  resetCertInputs();
}

function setFacilityTypeAndCertData() {
  const fac = document.getElementById('facility');
  const selected = fac.options[fac.selectedIndex];
  document.getElementById('fac_type').value = selected.getAttribute('data-type');
  const fname = fac.value;
  if (certData[fname]) {
    const cert = certData[fname];
    document.getElementById('cert_type').value = cert.cert_type;
    document.getElementById('cert_issue').value = cert.cert_issue;
    document.getElementById('validity').value = cert.validity;
    document.getElementById('score').value = cert.score;
    document.getElementById('lat').value = cert.lat;
    document.getElementById('longi').value = cert.longi;
    document.getElementById('update_id').value = cert.id; // ✅ auto-set update ID
  } else {
    resetCertInputs();
  }
}

function updateValidityDate() {
  const issue = document.getElementById('cert_issue').value;
  if (issue) {
    const issueDate = new Date(issue);
    issueDate.setFullYear(issueDate.getFullYear() + 1);
    const yyyy = issueDate.getFullYear();
    const mm = String(issueDate.getMonth() + 1).padStart(2, '0');
    const dd = String(issueDate.getDate()).padStart(2, '0');
    document.getElementById('validity').value = `${yyyy}-${mm}-${dd}`;
  } else {
    document.getElementById('validity').value = '';
  }
}

function resetCertInputs() {
  ['cert_type', 'cert_issue', 'validity', 'score', 'lat', 'longi'].forEach(id => {
    document.getElementById(id).value = '';
  });
  document.getElementById('update_id').value = '';
}
</script>

<div class="pcoded-main-container">
  <div class="pcoded-content">
    <div class="pagetitle mb-2">
      <h5 class="fw-bold text-primary mb-1">Manage Certification Entry</h5>
    </div>

    <div class="card glow-card shadow border-1">
      <div class="card-body p-4">
        <form method="post" action="#" class="row g-3">
          <input type="hidden" name="update_id" id="update_id">

          <!-- District, Block, Facility -->
          <div class="col-md-3">
            <label class="floating-label">District</label>
            <select class="form-control form-control-sm" name="dist" onchange="loadBlocks(this.value)" required>
              <option value="">Select District</option>
              <?php foreach ($districts as $id => $name): ?>
                <option value="<?= $id ?>"><?= $name ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-3">
            <label class="floating-label">Block</label>
            <select class="form-control form-control-sm" name="block" id="block" onchange="loadFacilities(this.value)" required>
              <option value="">Select Block</option>
            </select>
          </div>

          <div class="col-md-3">
            <label class="floating-label">Facility</label>
            <select class="form-control form-control-sm" name="fac_name" id="facility" onchange="setFacilityTypeAndCertData()" required>
              <option value="">Select Facility</option>
            </select>
          </div>

          <!-- Facility Type -->
          <div class="col-md-2">
            <label class="floating-label">Facility Type</label>
            <input type="text" name="fac_type" id="fac_type" class="form-control form-control-sm" readonly>
          </div>

          <!-- Certification Type -->
          <div class="col-md-3">
            <label class="floating-label">Certification Type</label>
            <select class="form-control form-control-sm" name="cert_type" id="cert_type" required>
              <option value="">-- Select Type --</option>
              <option value="National">National</option>
              <option value="State">State</option>
            </select>
          </div>

          <!-- Issue & Validity -->
          <div class="col-md-3">
            <label class="floating-label">Issue Date</label>
            <input type="date" name="cert_issue" id="cert_issue" class="form-control form-control-sm" onchange="updateValidityDate()" required>
          </div>

          <div class="col-md-3">
            <label class="floating-label">Validity (1 Year)</label>
            <input type="date" name="validity" id="validity" class="form-control form-control-sm" readonly>
          </div>

          <!-- Score, Lat, Long -->
          <div class="col-md-2">
            <label class="floating-label">Score</label>
            <input type="text" name="score" id="score" class="form-control form-control-sm" required oninput="this.value = this.value.replace(/[^0-9]/g, '')">
          </div>

          <div class="col-md-3">
            <label class="floating-label">Latitude</label>
            <input type="text" name="lat" id="lat" class="form-control form-control-sm" required oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1')">
          </div>

          <div class="col-md-3">
            <label class="floating-label">Longitude</label>
            <input type="text" name="longi" id="longi" class="form-control form-control-sm" required oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1')">
          </div>

          <!-- Save Button -->
          <div class="col-md-2 mt-4">
            <button type="submit" name="postsubmit" class="btn btn-success w-100">Save</button>
          </div>
        </form>

        <!-- Info Notice -->
        <div class="alert alert-info fw-bold mt-3">
          ⚠️ Latitude and Longitude must be provided — records without them will not be shown on the map.
        </div>

        <!-- Success / Error Message -->
        <?php if (!empty($successMsg)): ?>
          <div class="alert alert-info mt-3"><?= $successMsg ?></div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php include("assets/head/f.php"); ?>
