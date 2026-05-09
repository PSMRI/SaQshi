<?php 
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['department_id']) && !empty($_POST['department_id'])) {
    session_start();
    $_SESSION['dept_id1'] = $_POST['department_id'];
    $_SESSION['dept_name1'] = $_POST['department_name'];  // Save department name
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
include("assets/head/h.php"); 
$showDeptModal = empty($_SESSION['dept_id1']) || $_SESSION['dept_id1'] == 0;
$dept_id = $_SESSION['dept_id1'] ?? 0;
$dept_name = $_SESSION['dept_name1'] ?? 0;  // For showing in header
?>
<div class="pcoded-main-container">
  <div class="pcoded-content">
    <div class="pagetitle mb-2">
      <h5 class="fw-bold text-primary mb-1"><i class="bi bi-person-badge-fill me-2"></i>Assessor Info  for <?php echo htmlspecialchars($dept_name); ?>
       <button type="button" class="btn btn-sm btn-link text-warning ms-2" data-toggle="modal" data-target="#departmentModal">
                    Change Department
                </button>
                </h5>
    </div>

    <?php
    $fid = $_SESSION['u_facilityid'];
    $p = $_SESSION['assperiod'];

    $checkQuery = "SELECT * FROM assessor_info WHERE assessment_period = $p AND institute_id = $fid and dept_id=$dept_id";
    $checkResult = mysqli_query($con, $checkQuery);
    $isEdit = isset($_GET['edit']) && $_GET['edit'] == 'true';
    $row = ($checkResult && mysqli_num_rows($checkResult) > 0) ? mysqli_fetch_assoc($checkResult) : null;

    // Handle Update
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_assessor'])) {
        $ass_name = $_POST['ass_name'];
        $assesse = $_POST['assesse_name'];
        $assessment_date = $_POST['ass_date'];
        $ass_type = $_POST['ass_type'];

        $updateQuery = "UPDATE assessor_info 
                        SET Assessor_name='$ass_name', assessi_name='$assesse', assessment_date='$assessment_date', assessment_name='$ass_type' 
                        WHERE assessment_period=$p AND institute_id=$fid and dept_id=$dept_id";
        $updateResult = mysqli_query($con, $updateQuery);

        if ($updateResult) {
            echo '<div class="alert alert-success alert-dismissible fade show mt-2 small py-2" role="alert">
                    <i class="bi bi-check-circle me-2"></i>Assessor info updated successfully.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                  </div>';
            $row = mysqli_fetch_assoc(mysqli_query($con, $checkQuery)); // reload
        } else {
            echo '<div class="alert alert-danger mt-2 small py-2">Error updating data.</div>';
        }
    }

    // Handle Insert
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_assessor'])) {
        $ass_name = $_POST['ass_name'];
        $assesse = $_POST['assesse_name'];
        $assessment_date = $_POST['ass_date'];
        $ass_type = $_POST['ass_type'];

        $insertQuery = "INSERT INTO assessor_info (Assessor_name, assessi_name, assessment_date, assessment_name, assessment_period, institute_id,dept_id)
                        VALUES ('$ass_name', '$assesse', '$assessment_date', '$ass_type', $p, $fid,$dept_id)";
        $insertResult = mysqli_query($con, $insertQuery);

        if ($insertResult) {
            echo '<div class="alert alert-success alert-dismissible fade show mt-2 small py-2" role="alert">
                    <i class="bi bi-check-circle me-2"></i>Assessor info saved successfully.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                  </div>';
            $row = mysqli_fetch_assoc(mysqli_query($con, $checkQuery)); // reload
        } else {
            echo '<div class="alert alert-danger mt-2 small py-2">Error saving data.</div>';
        }
    }
    ?>

    <div class="card shadow-sm border">
      <div class="card-header py-2 bg-light fw-semibold small">
        <i class="bi bi-info-circle me-2 text-danger"></i>
        <?= $row ? ($isEdit ? 'Edit Assessor Info' : 'Assessor Info (Submitted)') : 'Enter Assessor Info' ?>
      </div>

      <div class="card-body py-3">
        <form method="post" action="">
              <input
        type="hidden"
        name="csrf_token"
        value="<?= e($_SESSION['csrf_token']); ?>"
    >

          <div class="row g-3 mb-2">
            <div class="col-md-6">
              <label class="form-label fw-semibold text-primary small">Name of Assessor*</label>
              <input type="text" class="form-control form-control-sm" name="ass_name"
                     value="<?= $row['Assessor_name'] ?? '' ?>"
                     <?= !$isEdit && $row ? 'readonly' : 'required' ?>>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold text-primary small">Name of Assessee*</label>
              <input type="text" class="form-control form-control-sm" name="assesse_name"
                     value="<?= $row['assessi_name'] ?? '' ?>"
                     <?= !$isEdit && $row ? 'readonly' : 'required' ?>>
            </div>
          </div>

          <div class="row g-3 mb-2">
            <div class="col-md-6">
              <label class="form-label fw-semibold text-primary small">Date of Assessment*</label>
              <input type="date" class="form-control form-control-sm" name="ass_date" id="ass_date"
                     value="<?= $row['assessment_date'] ?? '' ?>"
                     <?= !$isEdit && $row ? 'readonly' : 'required' ?>>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold text-primary small">Type of Assessment*</label>
              <select class="mb-3 form-control form-control-sm" id="ass_type" name="ass_type" <?= !$isEdit && $row ? 'disabled' : 'required' ?>>
                <option value="">--Select--</option>
                <option value="Internal assessment" <?= (isset($row['assessment_name']) && $row['assessment_name'] == 'Internal assessment') ? 'selected' : '' ?>>Internal assessment</option>
                <option value="External Assessment" <?= (isset($row['assessment_name']) && $row['assessment_name'] == 'External Assessment') ? 'selected' : '' ?>>External Assessment</option>
                <option value="State Assessment" <?= (isset($row['assessment_name']) && $row['assessment_name'] == 'State Assessment') ? 'selected' : '' ?>>State Assessment</option>
              </select>
            </div>
          </div>

          <div class="text-center mt-2">
            <?php if (!$row): ?>
              <button type="submit" name="save_assessor" class="btn btn-sm btn-primary px-4">
                <i class="bi bi-save2-fill me-1"></i>Submit
              </button>
            <?php elseif ($isEdit): ?>
              <button type="submit" name="update_assessor" class="btn btn-sm btn-success px-4">
                <i class="bi bi-pencil-square me-1"></i>Update
              </button>
              <a href="?edit=false" class="btn btn-sm btn-secondary ms-2 px-4">Cancel</a>
            <?php else: ?>
              <a href="?edit=true" class="btn btn-sm btn-primary px-4">
                <i class="bi bi-pencil-square me-1"></i>Edit
              </a>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>

    <script>
      document.getElementById('ass_date')?.setAttribute("max", new Date().toISOString().split("T")[0]);

      setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => alert.classList.remove('show'));
      }, 3000);
    </script>
  </div>
</div>

<!-- Department Modal -->
<div id="departmentModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="departmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form method="post">


            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="departmentModalLabel">Select Department</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <!-- Hidden input to store department_name -->
                    <input type="hidden" name="department_name" id="department_name_input" value="">

                    <label for="departmentSelect" class="form-label">Department</label>
                    <select class="mb-3 form-control form-control-sm" id="departmentSelect" name="department_id" required>
                        <option value="">-- Select Department --</option>
                        <?php
                        $factype = $_SESSION['f_type_id'];
                        $facid = $_SESSION['u_facilityid'];
                        $assid = $_SESSION['assperiod'];
                        $query = "SELECT DISTINCT a.fac_dept_id_fk, b.dept_name 
                                  FROM concern_subtype_chklist AS a 
                                  JOIN fac_department AS b ON a.fac_dept_id_fk = b.fac_dept_id 
                                  WHERE a.fac_type_id_fk = ? AND a.fac_dept_id_fk IN (
                                      SELECT fac_dept_id FROM fac_dept_map 
                                      WHERE fac_id = ? AND acc_id = ?
                                  )";
                        $stmt = $con->prepare($query);
                        $stmt->bind_param("iii", $factype, $facid, $assid);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='{$row['fac_dept_id_fk']}' data-name='{$row['dept_name']}'>{$row['dept_name']}</option>";
                        }
                        $stmt->close();
                        ?>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Continue</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
$('#departmentSelect').change(function() {
    var deptName = $('#departmentSelect option:selected').data('name');
    $('#department_name_input').val(deptName);
});
</script>

<script>
$(document).ready(function() {
    <?php if ($showDeptModal): ?>
        $('#departmentModal').modal('show');
    <?php endif; ?>

    $('#Concern').change(function() {
        var Concernid = $(this).val();
        $('#category').html('<option>Loading...</option>');
        $.ajax({
            method: "POST",
            url: "assets/responce/response.php",
            data: { cid: Concernid },
            success: function(data) {
                $('#category').html(data);
            },
            error: function() {
                $('#category').html('<option>Error loading</option>');
            }
        });
    });

    $('p.alert').delay(3000).fadeOut();

    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
});
</script>
<?php include("assets/head/f.php"); ?>
