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
$dept_name = $_SESSION['dept_name1'] ?? '0';  // For showing in header

?>
<div class="pcoded-main-container">
  <div class="pcoded-content">
    <div class="pagetitle mb-2">
      <h5 class="fw-bold text-primary mb-1"><i class="bi bi-person-badge-fill me-2"></i>Generate action plan for  <?php echo htmlspecialchars($dept_name); ?>
    <button type="button" class="btn btn-sm btn-link text-warning ms-2" data-toggle="modal" data-target="#departmentModal">
                <?= ($_SESSION['facilty_type'] == 8)
                    ? "Change Checklist"
                    : "Change Department"; ?>
            </button>
    </h5>
    </div>
    <div class="card shadow-sm">
      <div class="card-body py-3">
        <form method="post" action="#">
          <div class="row g-3 align-items-end">
            <div class="col-md-4">
              <label class="form-label text-primary fw-semibold">Select Assessment Cycle</label>
              <select class="mb-1 form-control form-control-sm" id="Period1" name="Period">
                <option value="0">Select Assessment Cycle</option>
                <?php
                $fsid = $_SESSION['u_facilityid'];
                $query = "CALL get_assessment1($fsid)";
                $result = $con->query($query);
                if ($result->num_rows > 0) {
                  while ($row = mysqli_fetch_assoc($result)) {
                    echo "<option value='{$row['id']}'>{$row['ass_name']}</option>";
                  }
                  mysqli_free_result($result);
                  $con->next_result();
                }
                ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label text-primary fw-semibold">Select Area Of Concern</label>
              <select class="mb-1 form-control form-control-sm" id="Concern1" name="Concern">
                <option value="0">-Select-</option>
              </select>
            </div>
            <div class="col-md-2">
              <button type="submit" name="submit1" class="btn btn-primary btn-sm">
                <i class="bi bi-pencil-square me-1"></i>Action Plan</button>
            </div>
            <!------div class="col-md-2">
              <button type="submit" name="submit3" class="btn btn-success btn-sm">
                <i class="bi bi-eye-fill me-1"></i>View Filled</button>
            </div> --->
          </div>
        </form>
      </div>
    </div>
    <?php if (isset($_POST['submit1']) || isset($_POST['submit2']) || isset($_POST['submit3'])): ?>
      <div class="card mt-4">
        <div class="card-body">
          <?php include('partials/moic_logic_render.php'); ?>
        </div>
      </div>
    <?php endif; ?>
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
    // On page load, pre-fill department_name_input with selected option
    $('#department_name_input').val($('#departmentSelect option:selected').data('name'));

    $('#departmentSelect').change(function() {
        var deptName = $('#departmentSelect option:selected').data('name');
        $('#department_name_input').val(deptName);
    });
    $("#Period1").on('change', function() {
        var Concernid = $('#Period1').val();
        $.ajax({
            method: "POST",
            cache: false,
            url: "assets/responce/response_m.php",
            data: { cid: Concernid },
            datatype: "html",
            success: function(data) {
                $("#Concern1").html(data);
            },
            error: function(data) {}
        });
    });
    <?php if ($showDeptModal): ?>
      $('#departmentModal').modal('show');
    <?php endif; ?>
});
</script>
<!-- Department Modal -->
<div id="departmentModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="departmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form method="post">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="departmentModalLabel">
                        <?php echo ($_SESSION['facilty_type'] == 8)
                            ? "Select Checklist"
                            : "Select Department"; ?>
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <!-- Hidden input to store department_name -->
                    <input type="hidden" name="department_name" id="department_name_input" value="">

                    <label for="departmentSelect" class="form-label">
                        <?php echo ($_SESSION['facilty_type'] == 8)
                            ? "Checklist"
                            : "Department"; ?>
                    </label>
                    <select class="mb-3 form-control form-control-sm" id="departmentSelect" name="department_id" required>
                        <option value=""> <?php echo ($_SESSION['facilty_type'] == 8)
                                                ? "--Select Checklist--"
                                                : "--Select Department--"; ?></option>
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {

    // Event Delegation → works for forms loaded AFTER submit too
    document.addEventListener("submit", function (e) {

        const form = e.target;

        // Only validate forms with class 'actionForm'
        if (!form.classList.contains("actionForm")) return;

        // Only validate when SAVE button is clicked (not skip)
        const activeBtn = document.activeElement;
        if (!activeBtn || !activeBtn.classList.contains("saveBtn")) return;

        let review      = form.querySelector("select[name='f']").value;
        let responsible = form.querySelector("input[name='res']").value.trim();
        let date        = form.querySelector("input[name='todate']").value.trim();
        let comment     = form.querySelector("textarea[name='comment']").value.trim();

        // CASE 1 — Dept Review not selected
        if (review === "0" || review === "3") {
            e.preventDefault();
            Swal.fire({
                icon: "warning",
                title: "Dept. Review Required",
                text: "Kindly select Dept. Review: Achievable or Non-achievable.",
            });
            return false;
        }

        // CASE 2 — Achievable requires more fields
        if (review === "1") {

            if (responsible === "" || date === "" || comment === "") {
                e.preventDefault();
                Swal.fire({
                    icon: "warning",
                    title: "Missing Details",
                    text: "Please enter Responsible Person, Time Period Date, and Action Plan.",
                });
                return false;
            }
        }
    });

});
</script>



<?php include("assets/head/f.php"); ?>
