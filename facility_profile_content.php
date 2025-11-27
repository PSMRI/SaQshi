<?php
include("assets/head/h.php");


if (!isset($_SESSION['u_facilityid'])) {
  die("<div class='alert alert-danger'>Access denied. Facility ID missing in session.</div>");
}

$fac_id = (int)$_SESSION['u_facilityid'];
$success = $error = "";

/* =========================
   🟢 UPDATE Facility Record
   ========================= */
if (isset($_POST['save_facility']) && (int)$_POST['fac_id'] === $fac_id) {
  $division_id  = (int)($_POST['division_id'] ?? 0);
  $dist_id      = (int)($_POST['dist_id'] ?? 0);
  $block_id     = (int)($_POST['block_id'] ?? 0);
  $Health_type  = (int)($_POST['Health_facilty_type'] ?? 0);
  $fac_name     = trim($_POST['fac_name'] ?? '');
  $lat          = floatval($_POST['lat'] ?? 0);
  $longit       = floatval($_POST['longit'] ?? 0);
  $NIN_no       = trim($_POST['NIN_no'] ?? '');

  // ✅ Server-side validation
  if (!preg_match("/^[A-Za-z\s]+$/", $fac_name)) {
    $error = "❌ Facility Name should contain only letters and spaces.";
  } elseif (!preg_match("/^\d{10}$/", $NIN_no)) {
    $error = "❌ NIN Number must be exactly 10 digits.";
  }

  if (empty($error)) {
    // Fetch master names
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

    // ✅ Update facility record
    $stmt = $con->prepare("UPDATE facilities 
        SET division_id=?, division=?, dist_id=?, Dist_Name=?, block_id=?, Block_Name=?, 
            fac_name=?, Health_facilty_type=?, lat=?, longit=?, NIN_no=? 
        WHERE fac_id=?");
    $stmt->bind_param(
      "isisssssddsi",
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
      $success = "✅ Facility details updated successfully.";
    } else {
      $error = "❌ Update failed: " . $stmt->error;
    }
    $stmt->close();
  }
}

/* =========================
   🟡 FETCH Facility Details
   ========================= */
$stmt = $con->prepare("SELECT * FROM facilities WHERE fac_id=?");
$stmt->bind_param("i", $fac_id);
$stmt->execute();
$res = $stmt->get_result();
$facility = $res->fetch_assoc();
$stmt->close();

if (!$facility) {
  die("<div class='alert alert-warning'>No facility found for this user.</div>");
}

// Preload master data
$divisions = $con->query("SELECT iddivision, division_name FROM division ORDER BY division_name")->fetch_all(MYSQLI_ASSOC);
$districts = $con->query("SELECT Dist_id, Dist_name, division_id FROM dist_master ORDER BY Dist_name")->fetch_all(MYSQLI_ASSOC);
$blocks = $con->query("SELECT block_id, block_name, dist_id FROM block_master ORDER BY block_name")->fetch_all(MYSQLI_ASSOC);
$facilityTypes = $con->query("SELECT fac_type_id, facilities_type FROM facilities_type ORDER BY facilities_type")->fetch_all(MYSQLI_ASSOC);
?>

<style>
.card { background:#fff; padding:18px; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,.08); }
.label { font-weight:600; margin-bottom:4px; display:block; font-size:13px; }
.value, select, input { font-size:14px; padding:6px; border-radius:6px; border:1px solid #ccc; width:100%; }
input[readonly] { background-color:#f5f5f5; cursor:not-allowed; }
.btn { padding:8px 12px; border:none; border-radius:6px; cursor:pointer; }
.btn-edit { background:#0d6efd; color:#fff; }
.btn-save { background:#198754; color:#fff; }
.btn-cancel { background:#6c757d; color:#fff; }
.btn-geo { background:#0dcaf0; color:#04293a; margin-left:6px; }
.notice { padding:8px; border-radius:6px; margin-bottom:10px; }
.notice.success { background:#e8f8ee; color:#256b42; }
.notice.error { background:#fbeaea; color:#b02a37; }
.row { display:flex; flex-wrap:wrap; gap:12px; margin-bottom:10px; }
.col { flex:1 1 250px; }
</style>

<div class="pcoded-main-container">
  <div class="pcoded-content">
    <h5 class="fw-bold text-primary mb-3">Facility Profile</h5>

    <?php if ($success): ?><div class="notice success"><?= $success ?></div><?php endif; ?>
    <?php if ($error): ?><div class="notice error"><?= $error ?></div><?php endif; ?>

    <div class="card">
      <!-- View Mode -->
      <div id="viewMode">
        <div class="row">
          <div class="col"><label class="label">State</label><div class="value"><?= htmlspecialchars($facility['state_name']) ?></div></div>
          <div class="col"><label class="label">Division</label><div class="value"><?= htmlspecialchars($facility['division']) ?></div></div>
          <div class="col"><label class="label">District</label><div class="value"><?= htmlspecialchars($facility['Dist_Name']) ?></div></div>
          <div class="col"><label class="label">Block</label><div class="value"><?= htmlspecialchars($facility['Block_Name']) ?></div></div>
        </div>

        <div class="row">
          <div class="col"><label class="label">Facility Name</label><div class="value"><?= htmlspecialchars($facility['fac_name']) ?></div></div>
          <div class="col"><label class="label">Facility Type ID</label><div class="value"><?= htmlspecialchars($facility['Health_facilty_type']) ?></div></div>
          <div class="col"><label class="label">NIN Number</label><div class="value"><?= htmlspecialchars($facility['NIN_no']) ?></div></div>
        </div>

        <div class="row">
          <div class="col"><label class="label">Latitude</label><div class="value"><?= htmlspecialchars($facility['lat']) ?></div></div>
          <div class="col"><label class="label">Longitude</label><div class="value"><?= htmlspecialchars($facility['longit']) ?></div></div>
        </div>

        <button id="editBtn" class="btn btn-edit">Edit</button>
      </div>

      <!-- Edit Mode -->
      <div id="editMode" style="display:none;">
        <form method="post" action="">
          <input type="hidden" name="fac_id" value="<?= $fac_id ?>">

          <div class="row">
            <div class="col">
              <label class="label">State (Read-only)</label>
              <input type="text" name="state_name" value="<?= htmlspecialchars($facility['state_name']) ?>" readonly>
            </div>

            <div class="col">
              <label class="label">Division</label>
              <select name="division_id" id="division_id" required>
                <option value="">-- Select Division --</option>
                <?php foreach ($divisions as $d): ?>
                  <option value="<?= $d['iddivision'] ?>" <?= ($d['iddivision'] == $facility['division_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($d['division_name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col">
              <label class="label">District</label>
              <select name="dist_id" id="dist_id" required>
                <option value="">-- Select District --</option>
              </select>
            </div>

            <div class="col">
              <label class="label">Block</label>
              <select name="block_id" id="block_id" required>
                <option value="">-- Select Block --</option>
              </select>
            </div>
          </div>

          <div class="row">
            <div class="col">
              <label class="label">Facility Type</label>
              <select name="Health_facilty_type" id="fac_type" required>
                <option value="">-- Select Facility Type --</option>
                <?php foreach ($facilityTypes as $ft): ?>
                  <option value="<?= $ft['fac_type_id'] ?>" <?= ($ft['fac_type_id'] == $facility['Health_facilty_type']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($ft['facilities_type']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col">
              <label class="label">Facility Name</label>
              <input type="text" name="fac_name" value="<?= htmlspecialchars($facility['fac_name']) ?>" 
                     required pattern="[A-Za-z\s]+" 
                     title="Only letters and spaces are allowed">
            </div>

            <div class="col">
              <label class="label">NIN Number (10 digits)</label>
              <input type="text" name="NIN_no" id="NIN_no" maxlength="10" minlength="10" 
                     pattern="\d{10}" title="Enter 10 digits only"
                     value="<?= htmlspecialchars($facility['NIN_no']) ?>" required
                     oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,10)">
            </div>
          </div>

          <div class="row">
            <div class="col">
              <label class="label">Latitude</label>
              <input type="text" name="lat" id="lat" value="<?= htmlspecialchars($facility['lat']) ?>" readonly>
            </div>

            <div class="col">
              <label class="label">Longitude</label>
              <input type="text" name="longit" id="longit" value="<?= htmlspecialchars($facility['longit']) ?>" readonly>
            </div>
          </div>

          <button type="button" id="useGeo" class="btn btn-geo">📍 Use Current Location</button>
          <button type="submit" name="save_facility" class="btn btn-save">Save</button>
          <button type="button" id="cancelEdit" class="btn btn-cancel">Cancel</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php include("assets/head/f.php"); ?>

<script>
const districts = <?= json_encode($districts) ?>;
const blocks = <?= json_encode($blocks) ?>;
const currentDist = "<?= $facility['dist_id'] ?>";
const currentBlock = "<?= $facility['block_id'] ?>";

function loadDistricts(divId) {
  const distSel = document.getElementById('dist_id');
  distSel.innerHTML = '<option value="">-- Select District --</option>';
  districts.filter(d => d.division_id == divId).forEach(d => {
    const opt = document.createElement('option');
    opt.value = d.Dist_id;
    opt.textContent = d.Dist_name;
    if (d.Dist_id == currentDist) opt.selected = true;
    distSel.appendChild(opt);
  });
  loadBlocks(currentDist);
}

function loadBlocks(distId) {
  const blkSel = document.getElementById('block_id');
  blkSel.innerHTML = '<option value="">-- Select Block --</option>';
  blocks.filter(b => b.dist_id == distId).forEach(b => {
    const opt = document.createElement('option');
    opt.value = b.block_id;
    opt.textContent = b.block_name;
    if (b.block_id == currentBlock) opt.selected = true;
    blkSel.appendChild(opt);
  });
}

document.getElementById('division_id').addEventListener('change', e => loadDistricts(e.target.value));
document.getElementById('dist_id').addEventListener('change', e => loadBlocks(e.target.value));

window.addEventListener('load', () => {
  const divId = document.getElementById('division_id').value;
  if (divId) loadDistricts(divId);
});

// Toggle modes
document.getElementById('editBtn').onclick = () => {
  document.getElementById('viewMode').style.display = 'none';
  document.getElementById('editMode').style.display = 'block';
};
document.getElementById('cancelEdit').onclick = () => {
  document.getElementById('editMode').style.display = 'none';
  document.getElementById('viewMode').style.display = 'block';
};

// Geolocation handler (auto-updates readonly fields)
document.getElementById('useGeo').onclick = () => {
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(pos => {
      document.getElementById('lat').value = pos.coords.latitude.toFixed(6);
      document.getElementById('longit').value = pos.coords.longitude.toFixed(6);
    }, err => alert('Error: ' + err.message), {enableHighAccuracy:true});
  } else {
    alert('Geolocation not supported');
  }
};
</script>
