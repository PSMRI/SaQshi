<?php
  /*This page:
  - Allows selecting Department (modal)
  - Selects Period / Concern / Priority and shows action-plan cards (CALL updt_dept_action_plan)
  - Supports updating compliance (postsubmit2)
  - Supports SKIP (skip) which loads the next card without saving
*/

/* ---------------- Handle Department Selection (modal) ---------------- */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['department_id']) && !empty($_POST['department_id'])) {
    // Save selection in session and reload page
    $_SESSION['dept_id1'] = $_POST['department_id'];
    $_SESSION['dept_name1'] = $_POST['department_name'] ?? '';  // Save department name (if provided)
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

include("assets/head/h.php");

$fsid = $_SESSION['u_facilityid'] ?? 0;
$showDeptModal = empty($_SESSION['dept_id1']) || $_SESSION['dept_id1'] == 0;
$dept_id = $_SESSION['dept_id1'] ?? 0;
$dept_name = $_SESSION['dept_name1'] ?? '';  // For showing in header

// We'll keep the main stored-proc call text in session as $_SESSION['q1'] when user clicks Show Assessment
// $_SESSION['q1'] is expected to be something like:
//   "CALL updt_dept_action_plan($priority,$facility,$period,$department,$concern)"

?>
<div class="pcoded-main-container">
  <div class="pcoded-content">
    <div class="pagetitle mb-2">
      <h5 class="fw-bold text-primary mb-1">
        <i class="bi bi-person-badge-fill me-2"></i>
        Update Action Plan for <?php echo htmlspecialchars($dept_name ?: ' - Select Department -'); ?>
        <button type="button" class="btn btn-sm btn-link text-warning ms-2" data-toggle="modal" data-target="#departmentModal">Change Department</button>
      </h5>
    </div>

    <div class="card shadow-sm">
      <div class="card-body py-3">

        <!-- Selection form -->
        <form method="post" action="#" enctype="multipart/form-data">
          <div class="form-group row align-items-end">
            <!-- Assessment Period Dropdown -->
            <div class="col-auto">
              <label class="form-label small">Assessment Period</label>
              <select class="form-control-sm form-control" id="Period" name="Period" required>
                <option value="0">Select Assessment Period</option>
                <?php
                // Load periods using stored procedure
                $stmt = $con->prepare("CALL get_assessment1(?)");
                $stmt->bind_param('i', $fsid);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                  echo "<option value='" . htmlspecialchars($row['id']) . "'>" . htmlspecialchars($row['ass_name']) . "</option>";
                }
                $stmt->close();
                $con->next_result();
                ?>
              </select>
            </div>

            <!-- Area of Concern -->
            <div class="col-auto">
              <label class="form-label small">Area of Concern</label>
              <select class="form-control-sm form-control" id="Concern" name="Concern" required>
                <option value="0">-Select Area of concern-</option>
              </select>
            </div>

            <!-- Priority -->
            <div class="col-auto">
              <label class="form-label small">Priority</label>
              <select class="form-control-sm form-control" id="Priority" name="Priority" required>
                <option value="2">-Priority-</option>
                <option value="0">Low</option>
                <option value="1">Medium</option>
                <option value="2">High</option>
              </select>
            </div>

            <!-- Submit -->
            <div class="col-auto">
              <button type="submit" name="submit1" class="btn btn-primary btn-sm">Show Assessment</button>
            </div>
          </div>
        </form>

        <hr>

        <?php
        /* ---------------- Handle 'Show Assessment' (submit1) ---------------- */
        if (isset($_POST['submit1'])) {

          // Save selections to session for subsequent use (update/skip)
          $_SESSION['xxp2'] = $_POST['Period'];
          $_SESSION['xxp1'] = $_POST['Priority'];

          $_SESSION['FDepartment'] = $_SESSION['dept_id1'];
          $_SESSION['period'] = intval($_POST['Period']);
          $_SESSION['Priority'] = intval($_POST['Priority']);
          $_SESSION['concern'] = intval($_POST['Concern']);

          $period = $_SESSION['period'];
          $priority = $_SESSION['Priority'];
          $facility = $_SESSION['u_facilityid'];
          $department = $_SESSION['FDepartment'];
          $concern = $_SESSION['concern'];

          if ($period == 0) {
            echo "<div class='alert alert-warning'>Kindly select assessment period...!!!!</div>";
            echo "<script>setTimeout(() => location.href='umoic.php', 2000);</script>";
            exit;
          }

          // Save CALL in session (used by skip and other operations)
          $_SESSION['q1'] = "CALL updt_dept_action_plan($priority,$facility,$period,$department,$concern)";

          // Execute the stored procedure and display returned cards
          $result = $con->query($_SESSION['q1']);
          if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
              // Each iteration will supply $row to the included partial; ensure partial uses $row
              include('partials/action_plan_card.php');
            }
            mysqli_free_result($result);
            $con->next_result();
          } else {
            // No records to show
            echo "<div class='alert alert-info'>No compliance updates pending..!</div>";
            $con->next_result();
          }
        }
 if (isset($_POST['skip'])) {

          // When user clicks Skip & Next, we should not save anything.
          // We will attempt to re-run the query that fetches pending records.
          // Prefer to use the per-card query (if provided) or fallback to session q1.

          // If action_plan_card.php sends a hidden field 'csqa_id1' (the current query to fetch next),
          // try to use it; else use $_SESSION['q1'].
          $query_call = $_POST['csqa_id1'] ?? '';
          $runQuery = !empty($query_call) ? $query_call : ($_SESSION['q1'] ?? '');

          if (!empty($runQuery)) {
            $nextResult = $con->query($runQuery);

            if ($nextResult && $nextResult->num_rows > 0) {
              while ($row = $nextResult->fetch_assoc()) {
                include('partials/action_plan_card.php');
              }
              mysqli_free_result($nextResult);
              $con->next_result();
            } else {
              echo "<div class='alert alert-info'>No more records available.</div>";
              $con->next_result();
            }
          } else {
            echo "<div class='alert alert-warning'>Skip cannot proceed: no query found to fetch next record.</div>";
          }
        }

        /* ---------------- Handle 'Update' (postsubmit2) ---------------- */
        if (isset($_POST['postsubmit2'])) {

          // Expecting form fields from action_plan_card.php:
          // - f[] or f (compliance value)
          // - Action_Taken (string)
          // - csqa_id (int) -> assessment record id
          // - csqa_id1 (string) -> query to re-run (the stored-proc call for next)
          $compliance = isset($_POST['f']) ? intval($_POST['f']) : 3;
          $action = $_POST['Action_Taken'] ?? '';
          $ass_id = intval($_POST['csqa_id'] ?? 0);
          $query_call = $_POST['csqa_id1'] ?? '';

          if ($compliance === 3 || $compliance === 0 && $compliance !== 0 && $compliance !== 1 && $compliance !== 2) {
            echo "<div class='alert alert-danger'>Please Select Compliance Value !!!</div>";
            exit;
          }

          // Perform update (stored procedure updt_insert_dept_action_plan)
          $update = "CALL updt_insert_dept_action_plan(?, ?)";
          // Note: original code used CALL updt_insert_dept_action_plan($compliance, $ass_id, ?)
          // but to avoid SQL injection and quoting issues, we'll prepare with two params.
          // If your stored proc signature requires int,int,varchar adjust accordingly.
          // We will call using the int compliance and int ass_id and pass action text separately.
          // If stored-proc signature is different, revert to original prepare string accordingly.

          // Some MySQL setups require parameters in order; adjust if needed:
          // For compatibility with original code which used: CALL updt_insert_dept_action_plan($compliance, $ass_id, ?)
          // we'll construct the call dynamically but still use prepared statement for action param.

          $callStr = "CALL updt_insert_dept_action_plan($compliance, $ass_id, ?)";
          $stmt = $con->prepare($callStr);
          $stmt->bind_param('s', $action);
          if (!$stmt->execute()) {
            echo "<div class='alert alert-danger'>Update failed: " . htmlspecialchars($stmt->error) . "</div>";
            $stmt->close();
            $con->next_result();
            exit;
          }
          $stmt->close();

          echo "<div class='alert alert-success'>Compliance Updated ..!</div>";

          // After update, re-run the query_call if provided; otherwise run the saved $_SESSION['q1']
          $runQuery = !empty($query_call) ? $query_call : ($_SESSION['q1'] ?? '');

          if (!empty($runQuery)) {
            $result = $con->query($runQuery);
            if ($result && $result->num_rows > 0) {
              while ($row = $result->fetch_assoc()) {
                include('partials/action_plan_card.php');
              }
              mysqli_free_result($result);
              $con->next_result();
            } else {
              echo "<div class='alert alert-info'>No more records available.</div>";
              $con->next_result();
            }
          } else {
            echo "<div class='alert alert-info'>No further query available to load next record.</div>";
          }
        }

        /* ---------------- Handle 'Skip' (skip) ---------------- */
       
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



  // On period change, fetch concerns for that period
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
            $factype = $_SESSION['f_type_id'] ?? 0;
            $facid = $_SESSION['u_facilityid'] ?? 0;
            $assid = $_SESSION['assperiod'] ?? 0;
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
              echo "<option value='" . htmlspecialchars($row['fac_dept_id_fk']) . "' data-name='" . htmlspecialchars($row['dept_name']) . "'>" . htmlspecialchars($row['dept_name']) . "</option>";
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
