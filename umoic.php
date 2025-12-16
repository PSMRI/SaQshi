<?php
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['department_id']) && !empty($_POST['department_id'])) {
    session_start();
    $_SESSION['dept_id1'] = $_POST['department_id'];
    $_SESSION['dept_name1'] = $_POST['department_name'];
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

include("assets/head/h.php");
$showDeptModal = empty($_SESSION['dept_id1']) || $_SESSION['dept_id1'] == 0;
$dept_name = $_SESSION['dept_name1'] ?? '0';  // For showing in header

/* ============ CLEANUP BUFFER ============ */
function clean_sp_buffers($con) {
    while ($con->more_results() && $con->next_result()) {
        $extra = $con->store_result();
        if ($extra) { $extra->free(); }
    }
}
?>

<div class="pcoded-main-container">
<div class="pcoded-content">

<div class="pagetitle mb-2">
    <h5 class="fw-bold text-primary mb-1">
        <i class="bi bi-person-badge-fill me-2"></i>
        Update Action Plan for <?= htmlspecialchars($dept_name); ?>
        <button type="button" class="btn btn-sm btn-link text-warning ms-2" data-toggle="modal" data-target="#departmentModal">
                <?= ($_SESSION['facilty_type'] == 8)
                    ? "Change Checklist"
                    : "Change Department"; ?>
            </button>
    </h5>
</div>

<div class="card shadow-sm">
<div class="card-body py-3">


<!-- ===========================
    FILTER FORM (FIXED ACTION)
=========================== -->
<form method="post" action="<?= $_SERVER['PHP_SELF']; ?>">
    <div class="form-group row align-items-end">

        <div class="col-auto">
            <label class="form-label small">Assessment Cycle</label>
            <select class="form-control-sm form-control" id="Period" name="Period" required>
                <option value="0">Select Assessment Cycle</option>
                <?php
                $stmt = $con->prepare("CALL get_assessment1(?)");
                $stmt->bind_param('i', $facility_id);
                $stmt->execute();
                $result = $stmt->get_result();

                while ($row = $result->fetch_assoc()) {
                    echo "<option value='{$row['id']}'>{$row['ass_name']}</option>";
                }
                $stmt->close();
                clean_sp_buffers($con);
                ?>
            </select>
        </div>

        <div class="col-auto">
            <label class="form-label small">Area of Concern</label>
            <select class="form-control-sm form-control" id="Concern" name="Concern" required>
                <option value="0">-- Select Area of Concern --</option>
            </select>
        </div>

        <div class="col-auto">
            <label class="form-label small">Priority</label>
            <select class="form-control-sm form-control" id="Priority" name="Priority" required>
                <option value="2">-- Priority --</option>
                <option value="0">Low</option>
                <option value="1">Medium</option>
                <option value="2">High</option>
            </select>
        </div>

        <div class="col-auto">
            <button type="submit" name="submit1" class="btn btn-primary btn-sm">Show Assessment</button>
        </div>

    </div>
</form>

<hr>

<?php
/* ============ LOAD FIRST RECORD ============ */
if (isset($_POST['submit1'])) {

    $_SESSION['period']   = intval($_POST['Period']);
    $_SESSION['priority'] = intval($_POST['Priority']);
    $_SESSION['concern']  = intval($_POST['Concern']);

    $period   = $_SESSION['period'];
    $priority = $_SESSION['priority'];
    $concern  = $_SESSION['concern'];

    if ($period == 0) {
        echo "<div class='alert alert-warning'>Please select an Assessment Cycle.</div>";
      //  exit();
    }

    $query = "CALL updt_dept_action_plan($priority, $facility_id, $period, $dept_id, $concern)";
    $result = $con->query($query);

    $_SESSION['records'] = [];
    $_SESSION['current_index'] = 0;

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $_SESSION['records'][] = $row;
        }
    }

    mysqli_free_result($result);
    clean_sp_buffers($con);

    if (empty($_SESSION['records'])) {
        echo "<div class='alert alert-info'>No compliance updates pending.</div>";
    } else {
        $row = $_SESSION['records'][0];
        include("partials/action_plan_card.php");
    }

    $showDeptModal = false;
}


/* ============ SHOW NEXT ============ */
function show_next_record() {
    global $con;

    clean_sp_buffers($con);

    if (!isset($_SESSION['records'])) return false;

    $_SESSION['current_index']++;

    if ($_SESSION['current_index'] >= count($_SESSION['records'])) {
        echo "<div class='alert alert-info'>No more records available.</div>";
        return false;
    }

    $row = $_SESSION['records'][$_SESSION['current_index']];
    include("partials/action_plan_card.php");
    return true;
}


/* ============ SKIP ============ */
if (isset($_POST['skip'])) {

    if (!show_next_record()) {
        unset($_SESSION['records']);
        unset($_SESSION['current_index']);
    }

    $showDeptModal = false;
   // exit();
}


/* ============ SAVE ============ */
if (isset($_POST['postsubmit2'])) {

    $compliance = intval($_POST['f'] ?? -1);
    $action     = trim($_POST['Action_Taken'] ?? "");
    $ass_id     = intval($_POST['csqa_id'] ?? 0);

    if ($compliance < 0 || $compliance > 2) {
        echo "<div class='alert alert-danger'>Please select a valid compliance.</div>";
        //exit();
    }

    if ($action === "") {
        echo "<div class='alert alert-danger'>Please enter action taken.</div>";
       // exit();
    }

    $call = "CALL updt_insert_dept_action_plan($compliance, $ass_id, ?)";
    $stmt = $con->prepare($call);
    $stmt->bind_param("s", $action);
    $stmt->execute();
    $stmt->close();

    clean_sp_buffers($con);

    echo "<div class='alert alert-success'>Record Updated Successfully!</div>";

    if (!show_next_record()) {
        unset($_SESSION['records']);
        unset($_SESSION['current_index']);
    }

    //exit();
}

?>
</div>
</div>
</div>
</div>

<script>
/* ============ INITIAL AJAX BIND ============ */
function bindConcernLoader() {
    $("#Period").off("change").on("change", function () {
        $.post("assets/responce/response_m1.php", { cid: $(this).val() }, function (data) {
            $("#Concern").html(data);
        });
    });
}

bindConcernLoader(); // First load

/* ============ RE-BIND AFTER SKIP/SAVE ============ */
$(document).on("click", "#skipBtn, #saveBtn", function () {
    setTimeout(() => {
        bindConcernLoader();
    }, 200);
});
</script>
<script>
$(document).ready(function () {
    <?php if ($showDeptModal): ?>
        $('#departmentModal').modal({
            backdrop: 'static',
            keyboard: false
        });
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
<?php include("assets/head/f.php"); ?>
