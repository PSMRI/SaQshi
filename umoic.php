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
$dept_name = $_SESSION['dept_name1'] ?? '';
$dept_id = $_SESSION['dept_id1'] ?? 0;


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
        Update Action Plan for <?= htmlspecialchars($dept_name ?: "--- Select Department ---") ?>
        <button type="button" class="btn btn-sm btn-link text-warning ms-2" data-toggle="modal" data-target="#departmentModal">
            Change Department
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
            <label class="form-label small">Assessment Period</label>
            <select class="form-control-sm form-control" id="Period" name="Period" required>
                <option value="0">Select Assessment Period</option>
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
        echo "<div class='alert alert-warning'>Please select an assessment period.</div>";
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

<?php include("assets/head/f.php"); ?>
