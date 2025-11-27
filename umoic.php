<?php


if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['department_id']) && !empty($_POST['department_id'])) {
  session_start();
    $_SESSION['dept_id1'] = $_POST['department_id'];
    $_SESSION['dept_name1'] = $_POST['department_name'];  // Save department name
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

include("assets/head/h.php");

$fsid = $_SESSION['u_facilityid'] ?? 0;
$showDeptModal = empty($_SESSION['dept_id1']) || $_SESSION['dept_id1'] == 0;
$dept_id = $_SESSION['dept_id1'] ?? 0;
$dept_name = $_SESSION['dept_name1'] ?? '';  // For showing in header
?>

<div class="pcoded-main-container">
  <div class="pcoded-content">
    <div class="pagetitle mb-2">
      <h5 class="fw-bold text-primary mb-1">
        <i class="bi bi-person-badge-fill me-2"></i>
        Update Action Plan for <?php echo htmlspecialchars($dept_name); ?>

         <button type="button" class="btn btn-sm btn-link text-warning ms-2" data-toggle="modal" data-target="#departmentModal">
              Change Department
      </h5>
    </div>

    <div class="card shadow-sm">
      <div class="card-body py-3">
        <form method="post" action="#" enctype="multipart/form-data">
          <div class="form-group row">
            <!-- Assessment Period Dropdown -->
            <div class="col-auto">
              <br>
              <select class="form-control-sm form-control" id="Period" name="Period" required>
                <option value="0">Select Assessment Period</option>
                <?php
                $stmt = $con->prepare("CALL get_assessment1(?)");
                $stmt->bind_param('i', $fsid);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                  echo "<option value='{$row['id']}'>{$row['ass_name']}</option>";
                }
                $stmt->close();
                $con->next_result();
                ?>
              </select>
            </div>

            <!-- Area of Concern -->
            <div class="col-auto">
              <br>
              <select class="form-control-sm form-control" id="Concern" name="Concern" required>
                <option value="0">-Select Area of concern-</option>
              </select>
            </div>

            <!-- Priority -->
            <div class="col-auto">
              <br>
              <select class="form-control-sm form-control" id="Priority" name="Priority" required>
                <option value="2">-Priority-</option>
                <option value="0">Low</option>
                <option value="1">Medium</option>
                <option value="2">High</option>
              </select>
            </div>

            <!-- Submit -->
            <div class="col-auto">
              <br>
              <button type="submit" name="submit1" class="btn btn-primary btn-sm">Show Assessment</button>
            </div>
          </div>
        </form>

        <?php
        if (isset($_POST['submit1'])) {
          $_SESSION['xxp2'] = $_POST['Period'];
          $_SESSION['xxp1'] = $_POST['Priority'];

          $_SESSION['FDepartment'] = $_SESSION['dept_id1'];
          $_SESSION['period'] = $_POST['Period'];
          $_SESSION['Priority'] = $_POST['Priority'];
          $_SESSION['concern'] = $_POST['Concern'];

          $period = $_SESSION['period'];
          $priority = $_SESSION['Priority'];
          $facility = $_SESSION['u_facilityid'];
          $department = $_SESSION['FDepartment'];
          $concern = $_SESSION['concern'];

          if ($period == 0) {
            echo "<div class='alert alert-warning'>Kindly select assessment period...!!!!</div>";
            echo "<script>setTimeout(() => location.href='umoic.php', 3000);</script>";
            exit;
          }

          $_SESSION['q1'] = "CALL updt_dept_action_plan($priority,$facility,$period,$department,$concern)";
          $result = $con->query($_SESSION['q1']);

          if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
              include('partials/action_plan_card.php');
            }
          } else {
            echo "<div class='alert alert-info'>No compliance updates pending..!</div>";
          }

          $con->next_result();
        }

        if (isset($_POST['postsubmit2'])) {
          $compliance = $_POST['f'];
          $action = $_POST['Action_Taken'];
          $ass_id = $_POST['csqa_id'];
          $query_call = $_POST['csqa_id1'];

          if ($compliance == 3) {
            echo "<div class='alert alert-danger'>Please Select Compliance Value !!!</div>";
            exit;
          }

          $update = "CALL updt_insert_dept_action_plan($compliance, $ass_id, ?)";
          $stmt = $con->prepare($update);
          $stmt->bind_param('s', $action);
          $stmt->execute();
          $stmt->close();

          echo "<div class='alert alert-success'>Compliance Updated ..!</div>";

          $result = $con->query($query_call);
          while ($row = $result->fetch_assoc()) {
            include('partials/action_plan_card.php');
          }

          $con->next_result();
        }
        ?>
      </div>
    </div>
  </div>
</div>

<!-- JS Scripts -->
 <script>
$('#departmentSelect').change(function() {
    var deptName = $('#departmentSelect option:selected').data('name');
    $('#department_name_input').val(deptName);
});
</script>
<script>
$(document).ready(function () {
  <?php if ($showDeptModal): ?>
    $('#departmentModal').modal('show');
  <?php endif; ?>

  var selectedPeriod = $('#Period').val();
  if (selectedPeriod !== "0") {
    $.post('assets/responce/response_m1.php', { cid: selectedPeriod }, function (data) {
      $('#Concern').html(data);
      <?php if (!empty($_SESSION['concern'])): ?>
        $('#Concern').val('<?php echo $_SESSION['concern']; ?>');
      <?php endif; ?>
    });
  }

  $('#Period').change(function () {
    $.post('assets/responce/response_m1.php', { cid: $(this).val() }, function (data) {
      $('#Concern').html(data);
    });
  });

  // Save department name on modal selection
  $('#departmentSelect').change(function() {
    var deptName = $('#departmentSelect option:selected').data('name');
    $('#department_name_input').val(deptName);
  });
});
</script>

<!-- Department Modal -->
<div id="departmentModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="departmentModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <form method="post">
      <input type="hidden" name="department_name" id="department_name_input" value="">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="departmentModalLabel">Select Department</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
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
                      WHERE a.fac_type_id_fk = ? 
                      AND a.fac_dept_id_fk IN (
                        SELECT fac_dept_id 
                        FROM fac_dept_map 
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
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary">Continue</button>
        </div>
      </div>
    </form>
  </div>
</div>

<?php include("assets/head/f.php"); ?>
