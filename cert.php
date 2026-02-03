<?php
// cert.php - Manage Certification Entry
include("assets/head/h.php");

$successMsg = "";
$errorMsg = "";

/* ---------------------------------------------
   PROCESS FORM SUBMISSION (POST)
----------------------------------------------*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['postsubmit'])) {
$fac_nin_raw = $_POST['fac_nin'] ?? '';

$fac_nin = (is_numeric($fac_nin_raw) && $fac_nin_raw !== '')
    ? (int)$fac_nin_raw
    : 0;
  // Collect POST safely
  $dist            = intval($_POST['dist'] ?? 0);
  $block_id        = intval($_POST['block'] ?? 0);
  $fac_id          = intval($_POST['fac_id'] ?? 0);
  //$fac_nin         = trim($_POST['fac_nin'] ?? 0);
  $fac_name        = trim($_POST['fac_name'] ?? '');
  $fac_type        = trim($_POST['fac_type'] ?? '');
  $cert_type       = trim($_POST['cert_type'] ?? '');
  $cert_detailscol = trim($_POST['cert_detailscol'] ?? '');
  $cert_status     = trim($_POST['cert_status'] ?? '');
  $ass_mod         = trim($_POST['ass_mod'] ?? '');
  $date_of_ass     = trim($_POST['date_of_ass'] ?? '');
  $cert_issue      = trim($_POST['cert_issue'] ?? '');
  $score           = trim($_POST['score'] ?? '');
  $lat             = trim($_POST['lat'] ?? '');
  $longi           = trim($_POST['longi'] ?? '');

  // REQUIRED FIELDS FIXED
  $required = [
    'dist',
    'block_id',
    'fac_id',
    'fac_name',
    'fac_type',
    'cert_type',
    'cert_detailscol',
    'cert_status',
    'ass_mod',
    'date_of_ass',
    'cert_issue',
    'score',
    'lat',
    'longi'
  ];

  foreach ($required as $r) {
    if (empty($$r) && $r !== 'score') {
      $errorMsg = "All fields are required. Missing: $r";
      break;
    }
  }

  if ($errorMsg === "" && $score === "") {
    $errorMsg = "All fields are required. Missing: score";
  }

  // NUMERIC VALIDATION
  if ($errorMsg === "") {
    if (!is_numeric($score)) {
      $errorMsg = "Score must be numeric.";
    }
    if (!is_numeric($lat) || !is_numeric($longi)) {
      $errorMsg = "Latitude and Longitude must be numeric.";
    }
  }

  // DATE VALIDATION (backend)
  if ($errorMsg === "") {
    $dt_ass   = strtotime($date_of_ass);
    $dt_issue = strtotime($cert_issue);

    if ($dt_ass === false || $dt_issue === false) {
      $errorMsg = "Invalid dates provided.";
    } elseif ($dt_issue < $dt_ass) {
      $errorMsg = "Issue Date cannot be earlier than Assessment Date.";
    }
  }

  if ($errorMsg === "") {

    // Get District Name
    $dist_name = "";
    $stmtD = $con->prepare("SELECT Dist_name FROM dist_master WHERE Dist_id=?");
    $stmtD->bind_param("i", $dist);
    $stmtD->execute();
    $stmtD->bind_result($dist_name);
    $stmtD->fetch();
    $stmtD->close();

    // Get Block Name
    $block_name = "";
    $stmtB = $con->prepare("SELECT block_name FROM block_master WHERE block_id=?");
    $stmtB->bind_param("i", $block_id);
    $stmtB->execute();
    $stmtB->bind_result($block_name);
    $stmtB->fetch();
    $stmtB->close();

    // Validity = issue date + 1 year
    // VALIDITY BASED ON CERTIFICATION STATUS
    $validity = null;

    if ($cert_status === "Certified") {
      $validity = date("Y-m-d", strtotime($cert_issue . " +3 years"));
    } elseif ($cert_status === "Conditional Certified") {
      $validity = date("Y-m-d", strtotime($cert_issue . " +1 year"));
    } else {
      // Not Certified → no validity
      $validity = null;
    }

    // Insert
    $sql = "INSERT INTO cert_details 
        (dist_id, dist, block_id, block, fac_id, fac_nin, fac_name, fac_type, 
         cert_type, cert_detailscol, cert_issue, validity, score, lat, longi, 
         Cert_status, ass_mod, date_of_ass)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $con->prepare($sql);

    $types = str_repeat("s", 18);

    $stmt->bind_param(
      $types,
      $dist,
      $dist_name,
      $block_id,
      $block_name,
      $fac_id,
      $fac_nin,
      $fac_name,
      $fac_type,
      $cert_type,
      $cert_detailscol,
      $cert_issue,
      $validity,
      $score,
      $lat,
      $longi,
      $cert_status,
      $ass_mod,
      $date_of_ass
    );

    if ($stmt->execute()) {
      $successMsg = "✅ Certification details saved successfully.";
    } else {
      $errorMsg = "❌ Database Error: " . $stmt->error;
    }

    $stmt->close();
  }
}

/* ---------------------------------------------
   LOAD MASTER DATA
---------------------------------------------*/
$districts = [];
$blocksByDistrict = [];
$facilityTypes = [];
$facilitiesByBlock = [];

$resD = $con->query("SELECT Dist_id, Dist_name FROM dist_master");
while ($d = $resD->fetch_assoc()) {
  $districts[$d['Dist_id']] = $d['Dist_name'];
}

$resB = $con->query("SELECT block_id, block_name, dist_id FROM block_master");
while ($b = $resB->fetch_assoc()) {
  $blocksByDistrict[$b['dist_id']][] = $b;
}

$resT = $con->query("SELECT fac_type_id, facilities_type FROM facilities_type");
while ($t = $resT->fetch_assoc()) {
  $facilityTypes[$t['fac_type_id']] = $t['facilities_type'];
}

$resF = $con->query("SELECT fac_id, fac_name, block_id, Health_facilty_type, NIN_no FROM facilities");
while ($f = $resF->fetch_assoc()) {
  $facilitiesByBlock[$f['block_id']][] = [
    "fac_id" => $f["fac_id"],
    "fac_name" => $f["fac_name"],
    "fac_type_name" => $facilityTypes[$f["Health_facilty_type"]] ?? "",
    "fac_nin" => $f["NIN_no"]
  ];
}

?>
<style>
  .glow-card {
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 0 12px rgba(0, 123, 255, 0.15);
  }
</style>

<!-- Load SweetAlert BEFORE any JS uses Swal -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
  /* ------------------------------------------------------------------
   DATA FROM PHP -> JS
------------------------------------------------------------------ */
  const blockData = <?= json_encode($blocksByDistrict) ?>;
  const facilityData = <?= json_encode($facilitiesByBlock) ?>;

  /* ------------------ LOAD BLOCKS ------------------ */
  function loadBlocks(distId) {
    const block = document.getElementById("block");
    block.innerHTML = `<option value="">Select Block</option>`;

    (blockData[distId] || []).forEach(b => {
      block.innerHTML += `<option value="${b.block_id}">${b.block_name}</option>`;
    });

    document.getElementById("facility").innerHTML = `<option value="">Select Facility</option>`;
    document.getElementById("fac_type").value = "";
    document.getElementById("fac_id").value = "";
    document.getElementById("fac_nin").value = "";
  }

  /* ------------------ LOAD FACILITIES ------------------ */
  function loadFacilities(blockId) {
    const fac = document.getElementById("facility");
    fac.innerHTML = `<option value="">Select Facility</option>`;

    (facilityData[blockId] || []).forEach(f => {
      fac.innerHTML += `<option value="${f.fac_name}" 
            data-id="${f.fac_id}" 
            data-nin="${f.fac_nin}" 
            data-type="${f.fac_type_name}">
            ${f.fac_name}
        </option>`;
    });
  }

  /* ------------------ SET FACILITY DATA ------------------ */
  function setFacilityData() {
    const sel = document.getElementById("facility").selectedOptions[0];
    if (!sel) return;

    document.getElementById("fac_type").value = sel.getAttribute("data-type");
    document.getElementById("fac_id").value = sel.getAttribute("data-id");
    const nin = sel.getAttribute("data-nin");
    document.getElementById("fac_nin").value = (nin && nin !== "null") ? nin : 0;
  }

  /* ------------------ AUTO VALIDITY ------------------ */
  function updateValidityDate() {
    const issue = document.getElementById("cert_issue").value;
    const status = document.querySelector('[name="cert_status"]').value;

    if (!issue || !status) {
      document.getElementById("validity").value = "";
      return;
    }

    let d = new Date(issue);

    if (status === "Certified") {
      d.setFullYear(d.getFullYear() + 3);
    } else if (status === "Conditional Certified") {
      d.setFullYear(d.getFullYear() + 1);
    } else {
      document.getElementById("validity").value = "";
      return;
    }

    document.getElementById("validity").value = d.toISOString().split("T")[0];
  }


  /* ------------------ LIVE DATE VALIDATION ------------------ */
  function validateDatesLive() {
    const ass = document.getElementById("date_of_ass").value;
    const issue = document.getElementById("cert_issue").value;

    if (!ass || !issue) return;

    if (new Date(issue) < new Date(ass)) {
      Swal.fire({
        icon: "error",
        title: "Invalid Date Selection",
        html: "<b>Issue Date</b> cannot be earlier than <b>Assessment Date</b>",
      });

      document.getElementById("cert_issue").value = "";
      document.getElementById("validity").value = "";
    }
  }

  /* ------------------ ON SUBMIT VALIDATION ------------------ */
  function validateBeforeSubmit(e) {
    const ass = document.getElementById("date_of_ass").value;
    const issue = document.getElementById("cert_issue").value;

    if (issue && ass && new Date(issue) < new Date(ass)) {
      e.preventDefault();
      Swal.fire({
        icon: "error",
        title: "Invalid Dates",
        html: "Issue Date cannot be earlier than Assessment Date.",
      });
      return false;
    }

    // LAT/LONG check
    if (!document.getElementById("lat").value.trim() ||
      !document.getElementById("longi").value.trim()) {

      e.preventDefault();
      Swal.fire({
        icon: "warning",
        title: "Missing Location",
        html: "Latitude & Longitude are <b>mandatory</b>.",
      });
      return false;
    }

    return true;
  }

  /* ------------------ ATTACH LISTENERS ------------------ */
  document.addEventListener("DOMContentLoaded", function() {
    const form = document.getElementById("certForm");

    document.getElementById("date_of_ass").addEventListener("change", validateDatesLive);
    document.getElementById("cert_issue").addEventListener("change", function() {
      validateDatesLive();
      updateValidityDate();
    });

    if (form) form.addEventListener("submit", validateBeforeSubmit);
  });
</script>

<div class="pcoded-main-container">
  <div class="pcoded-content">

    <div class="pagetitle mb-2">
      <h5 class="fw-bold text-primary">Manage Certification Entry</h5>
    </div>

    <div class="card glow-card shadow-sm">
      <div class="card-body p-4">

        <?php if ($errorMsg): ?>
          <script>
            Swal.fire({
              icon: "error",
              title: "Error",
              html: "<?= $errorMsg ?>",
            });
          </script>
        <?php endif; ?>

        <?php if ($successMsg): ?>
          <script>
            Swal.fire({
              icon: "success",
              title: "Success",
              html: "<?= $successMsg ?>",
              timer: 1800,
              showConfirmButton: false
            });
          </script>
        <?php endif; ?>


        <form id="certForm" method="post" class="row g-3">

          <input type="hidden" id="fac_id" name="fac_id">
          <input type="hidden" id="fac_nin" name="fac_nin">

          <!-- DISTRICT -->
          <div class="col-md-3">
            <label>District</label>
            <select name="dist" class="form-control form-control-sm" onchange="loadBlocks(this.value)" required>
              <option value="">Select District</option>
              <?php foreach ($districts as $id => $name): ?>
                <option value="<?= $id ?>"><?= $name ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- BLOCK -->
          <div class="col-md-3">
            <label>Block</label>
            <select id="block" name="block" class="form-control form-control-sm" onchange="loadFacilities(this.value)" required>
              <option value="">Select Block</option>
            </select>
          </div>

          <!-- FACILITY -->
          <div class="col-md-3">
            <label>Facility</label>
            <select id="facility" name="fac_name" class="form-control form-control-sm" onchange="setFacilityData()" required>
              <option value="">Select Facility</option>
            </select>
          </div>

          <!-- FACILITY TYPE -->
          <div class="col-md-3">
            <label>Facility Type</label>
            <input id="fac_type" name="fac_type" class="form-control form-control-sm" readonly required>
          </div>

          <!-- PROGRAM -->
          <div class="col-md-3">
            <label>Certification Program</label>
            <select name="cert_detailscol" class="form-control form-control-sm" required>
              <option value="">Select</option>
              <option value="NQAS">NQAS</option>
              <option value="LaQshya">LaQshya</option>
              <option value="MusQan">MusQan</option>
            </select>
          </div>

          <!-- CERT TYPE -->
          <div class="col-md-3">
            <label>Certification Type</label>
            <select name="cert_type" class="form-control form-control-sm" required>
              <option value="">Select</option>
              <option value="State">State</option>
              <option value="National">National</option>
            </select>
          </div>

          <!-- STATUS -->
          <div class="col-md-3">
            <label>Certification Status</label>
            <select name="cert_status" class="form-control form-control-sm" required>
              <option value="">Select Status</option>
              <option value="Certified">Certified</option>
              <option value="Conditional Certified">Conditional Certified</option>
              <option value="Not Certified">Not Certified</option>
            </select>
          </div>

          <!-- MODE -->
          <div class="col-md-3">
            <label>Assessment Mode</label>
            <select name="ass_mod" class="form-control form-control-sm" required>
              <option value="">Select Mode</option>
              <option value="Physical">Physical</option>
              <option value="Virtual">Virtual</option>
            </select>
          </div>

          <!-- ASSESSMENT DATE -->
          <div class="col-md-3">
            <label>Assessment Date</label>
            <input type="date" id="date_of_ass" name="date_of_ass" class="form-control form-control-sm" required>
          </div>

          <!-- ISSUE DATE -->
          <div class="col-md-3">
            <label>Issue Date</label>
            <input type="date" id="cert_issue" name="cert_issue" class="form-control form-control-sm" required>
          </div>

          <!-- VALIDITY -->
          <div class="col-md-3">
            <label>Validity</label>
            <input type="date" id="validity" name="validity" class="form-control form-control-sm" readonly>
          </div>

          <!-- SCORE -->
          <div class="col-md-2">
            <label>Score</label>
            <input type="number" name="score" class="form-control form-control-sm" required step="0.01" min="0">
          </div>

          <!-- LAT -->
          <div class="col-md-3">
            <label>Latitude</label>
            <input type="text" id="lat" name="lat" class="form-control form-control-sm" required>
          </div>

          <!-- LNG -->
          <div class="col-md-3">
            <label>Longitude</label>
            <input type="text" id="longi" name="longi" class="form-control form-control-sm" required>
          </div>

          <div class="col-md-2 mt-3">
            <button class="btn btn-success w-100" name="postsubmit">SAVE</button>
          </div>

        </form>


      </div>
      <b>
      <div class="alert alert-warning d-flex align-items-center mb-3">
  <strong class="me-2">⚠️ Important:</strong>
  Facility registration is mandatory before certification details can be entered.
</div>
    </div>
  </div>
</div>

<?php include("assets/head/f.php"); ?>