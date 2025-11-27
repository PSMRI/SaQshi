<?php
include("assets/head/h.php");
?>

<div class="pcoded-main-container">
  <div class="pcoded-content">
    <!-- Page Title -->
    <div class="pagetitle mb-2">
      <h5 class="fw-bold text-primary mb-1">
        <i class="bi bi-person-badge-fill me-2"></i>Reports
      </h5>
    </div>

    <!-- Selection Form -->
    <div class="card">
      <div class="card-body">
        <form method="post" action="#">
          <div class="row g-3 align-items-end">
            <!-- Report Type Dropdown -->
            <div class="col-auto">
              <label class="form-label">Select Report Type</label>
              <select class="form-control form-control-sm" name="rt" id="rt" required onchange="toggleFields()">
                <option value="0">---Select---</option>
                <option value="1">Filled Check List</option>
                <option value="2">Score Card</option>
                <option value="3">Outcome Indicators</option>
                <option value="4">Action Plan</option>
                <option value="5">KPI</option>
                <?php
                $fat =   $_SESSION['f_type_id'];
                if (in_array($fat, [2, 3, 10])) {
                ?>
                
                  <option value="6">MusQan KPI</option>
                  <option value="7">Facility Score Card</option>
                  <option value="8">Facility Outcome Indicators</option>
                   <option value="9">Anexure C for LaQshya</option>
                <?php } ?>
              </select>
            </div>

            <!-- Department Dropdown -->
            <div class="col-auto" id="dept_div">
              <label class="form-label">Select Department</label>
              <select class="form-control form-control-sm" name="Facility_Department" required>
                <option value="0">--Select--</option>
                <?php
                $fsid = $_SESSION['u_facilityid'];
                $query = "SELECT fac_dept_id, dept_name FROM fac_department 
                          WHERE fac_dept_id IN (
                            SELECT fac_dept_id FROM fac_dept_map WHERE fac_id = $fsid 
                          )";
                $result = $con->query($query);
                while ($row = $result->fetch_assoc()) {
                  echo "<option value='{$row['fac_dept_id']}'>{$row['dept_name']}</option>";
                }
                ?>
              </select>
            </div>

            <!-- Assessment Period Dropdown -->
            <div class="col-auto" id="assessment_div">
              <label class="form-label">Select Assessment</label>
              <select class="form-control form-control-sm" name="Period" required>
                <option value="0">---Select---</option>
                <?php
                $query = "SELECT DISTINCT id, ass_name FROM assessment_desc WHERE fac_id_fk = $fsid";
                $result = $con->query($query);
                while ($row = $result->fetch_assoc()) {
                  echo "<option value='{$row['id']}'>{$row['ass_name']}</option>";
                }
                ?>
              </select>
            </div>

            <!-- Hidden fat -->
            <input type="hidden" name="fat" value="<?= $fsid ?>">

            <!-- Submit -->
            <div class="col-auto">
              <button type="submit" name="submit1" class="btn btn-primary">View</button>
            </div>
          </div>
        </form>

      </div>
      <?php
      $fat =   $_SESSION['f_type_id'];
      if (in_array($fat, [8, 4])) {
      ?>
       <div class="alert alert-warning d-flex align-items-start shadow-sm" role="alert">
  <i class="bi bi-info-circle-fill me-2 fs-5 text-dark mt-1"></i>
  <div class="small">
    <strong>Note:</strong> For HWC, if you have entered data for any month before <strong>01-June-2025</strong>, kindly 
    <a href="freports3.php" class="alert-link">click here</a> to view the Outcome and KPI reports.
    <br />
    <strong>Also:</strong> Graphs will not be available for such data.
  </div>
</div>


      <?php } ?>
    </div>

    <!-- Report Renderer -->
    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST["rt"] == 2) {
      $dept_id = $_POST["Facility_Department"];
      $_SESSION['period'] = $_POST['Period'];
      $_SESSION['dept_id'] = $dept_id;
      $_SESSION['fat'] = $_POST['fat'];

      include("assets/reports/render_scorecard.php");
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST["rt"] == 1) {
      $dept_id = $_POST["Facility_Department"];
      $_SESSION['period'] = $_POST['Period'];
      $_SESSION['dept_id'] = $dept_id;
      $_SESSION['fat'] = $_POST['fat'];
      $t = $_SESSION['userid'];

      include("assets/reports/render_scorecard2.php");
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST["rt"] == 3) {
      $dept_id = $_POST["Facility_Department"];
      $_SESSION['period'] = $_POST['Period'];
      $_SESSION['dept_id'] = $dept_id;
      $_SESSION['fat'] = $_POST['fat'];
      $t = $_SESSION['userid'];

      include("assets/reports/render_outcome.php");
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST["rt"] == 5) {
      $dept_id = $_POST["Facility_Department"];
      $_SESSION['period'] = $_POST['Period'];
      $_SESSION['dept_id'] = $dept_id;
      $_SESSION['fat'] = $_POST['fat'];
      $t = $_SESSION['userid'];

      include("assets/reports/render_kpi.php");
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST["rt"] == 7) {
      $dept_id = $_POST["Facility_Department"];
      $_SESSION['period'] = $_POST['Period'];
      $_SESSION['dept_id'] = $dept_id;
      $_SESSION['fat'] = $_POST['fat'];
      $t = $_SESSION['userid'];

      include("assets/reports/render_all_dept_score.php");
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST["rt"] == 4) {
      $dept_id = $_POST["Facility_Department"];
      $_SESSION['period'] = $_POST['Period'];
      $_SESSION['dept_id'] = $dept_id;
      $_SESSION['fat'] = $_POST['fat'];
      $t = $_SESSION['userid'];

      include("assets/reports/render_actionplan.php");
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST["rt"] == 6) {
      $_SESSION['period'] = $_POST['Period'];
      $_SESSION['fat'] = $_POST['fat'];
      $t = $_SESSION['userid'];

      include("assets/reports/render_musqankpi.php");
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST["rt"] == 8) {
      $_SESSION['period'] = $_POST['Period'];
      $_SESSION['fat'] = $_POST['fat'];
      $t = $_SESSION['userid'];

      include("assets/reports/render_alloutcome .php");
    }elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST["rt"] == 9) {
    

    include("assets/reports/render_anexc.php");
    }
    ?>

  </div>
</div>

<!-- JS for hiding/showing fields -->
<script>
  function toggleFields() {
    var rt = document.getElementById('rt').value;

    if (rt == '5' || rt == '6' || rt == '9') {
      // KPI or MusQan KPI → hide both
      document.getElementById('dept_div').style.display = 'none';
      document.getElementById('assessment_div').style.display = 'none';
    } else if (rt == '7' || rt == '8') {
      // Facility Score Card → show only Assessment
      document.getElementById('dept_div').style.display = 'none';
      document.getElementById('assessment_div').style.display = 'block';
    } else {
      // All others → show both
      document.getElementById('dept_div').style.display = 'block';
      document.getElementById('assessment_div').style.display = 'block';
    }
  }
</script>

<?php include("assets/head/f.php"); ?>