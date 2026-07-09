<?php
try {

if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($_POST['department_id'])) {
    session_start();
    $_SESSION['dept_id1'] = $_POST['department_id'];
    $_SESSION['dept_name1'] = $_POST['department_name'];
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

include("assets/head/h.php");

function clean_sp_buffers_safe($con) {
    if ($con instanceof mysqli) {
        while ($con->more_results() && $con->next_result()) {
            $extra = $con->store_result();
            if ($extra) $extra->free();
        }
    }
}

$facility_id = (int)($_SESSION['u_facilityid'] ?? 0);
$showDeptModal = empty($_SESSION['dept_id1']) || $_SESSION['dept_id1'] == 0;
$dept_name = $_SESSION['dept_name1'] ?? '0';
?>

<div class="pcoded-main-container">
<div class="pcoded-content">

<div class="pagetitle mb-2">
    <h5 class="fw-bold text-primary mb-1">
        <i class="bi bi-person-badge-fill me-2"></i>
        Update Action Plan for <?= htmlspecialchars($dept_name); ?>

        <button type="button"
                class="btn btn-sm btn-link text-warning ms-2"
                data-toggle="modal"
                data-target="#departmentModal">
            <?= ($_SESSION['facilty_type'] == 8) ? "Change Checklist" : "Change Department"; ?>
        </button>
    </h5>
</div>

<div class="card shadow-sm">
<div class="card-body py-3">

<form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']); ?>">
    <?= csrf(); ?>

    <div class="form-group row align-items-end">

        <div class="col-auto">
            <label class="form-label small">Assessment Cycle</label>

            <select class="form-control-sm form-control" id="Period" name="Period" required>
                <option value="0">Select Assessment Cycle</option>

                <?php
                try {
                    $stmt = $con->prepare("CALL get_assessment1(?)");

                    if (!$stmt) {
                        throw new Exception($con->error);
                    }

                    $stmt->bind_param("i", $facility_id);
                    $stmt->execute();

                    $result = $stmt->get_result();

                    while ($row = $result->fetch_assoc()) {
                        echo "<option value='" . (int)$row['id'] . "'>" .
                            htmlspecialchars($row['ass_name'], ENT_QUOTES, 'UTF-8') .
                            "</option>";
                    }

                    $stmt->close();
                    clean_sp_buffers_safe($con);

                } catch (Throwable $e) {
                    error_log("UMOIC assessment cycle load error: " . $e->getMessage());
                    echo "<option value='0'>Unable to load assessment cycle</option>";
                }
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
            <button type="submit" name="submit1" class="btn btn-primary btn-sm">
                Show Assessment
            </button>
        </div>

    </div>
</form>

<hr>

<?php
if (isset($_POST['submit1'])) {

    try {

        $_SESSION['period']   = (int)($_POST['Period'] ?? 0);
        $_SESSION['priority'] = (int)($_POST['Priority'] ?? 0);
        $_SESSION['concern']  = (int)($_POST['Concern'] ?? 0);

        $period   = $_SESSION['period'];
        $priority = $_SESSION['priority'];
        $concern  = $_SESSION['concern'];
        $dept_id  = (int)($_SESSION['dept_id1'] ?? 0);

        if ($period == 0) {
            echo "<div class='alert alert-warning'>Please select an Assessment Cycle.</div>";
        } else {

            $query = "CALL updt_dept_action_plan($priority, $facility_id, $period, $dept_id, $concern)";
            $result = $con->query($query);

            if (!$result) {
                throw new Exception($con->error);
            }

            $_SESSION['records'] = [];
            $_SESSION['current_index'] = 0;

            while ($row = $result->fetch_assoc()) {
                $_SESSION['records'][] = $row;
            }

            $result->free();
            clean_sp_buffers_safe($con);

            if (empty($_SESSION['records'])) {
                echo "<div class='alert alert-info'>No compliance updates pending.</div>";
            } else {
                $row = $_SESSION['records'][0];
                include("partials/action_plan_card.php");
            }

            $showDeptModal = false;
        }

    } catch (Throwable $e) {
        error_log("UMOIC load first record error: " . $e->getMessage());

        echo "<div class='alert alert-danger'>
            Unable to load Action Plan at the moment.
            Please try again after some time or contact support.
        </div>";
    }
}

function show_next_record() {
    global $con;

    clean_sp_buffers_safe($con);

    if (!isset($_SESSION['records'])) {
        return false;
    }

    $_SESSION['current_index']++;

    if ($_SESSION['current_index'] >= count($_SESSION['records'])) {
        echo "<div class='alert alert-info'>No more records available.</div>";
        return false;
    }

    $row = $_SESSION['records'][$_SESSION['current_index']];
    include("partials/action_plan_card.php");
    return true;
}

if (isset($_POST['skip'])) {

    if (!show_next_record()) {
        unset($_SESSION['records'], $_SESSION['current_index']);
    }

    $showDeptModal = false;
}

if (isset($_POST['postsubmit2'])) {

    try {

        $compliance = (int)($_POST['f'] ?? -1);
        $action     = trim($_POST['Action_Taken'] ?? "");
        $ass_id     = (int)($_POST['csqa_id'] ?? 0);

        if ($compliance < 0 || $compliance > 2) {
            echo "<div class='alert alert-danger'>Please select a valid compliance.</div>";
        } elseif ($action === "") {
            echo "<div class='alert alert-danger'>Please enter action taken.</div>";
        } else {

            $call = "CALL updt_insert_dept_action_plan($compliance, $ass_id, ?)";

            $stmt = $con->prepare($call);

            if (!$stmt) {
                throw new Exception($con->error);
            }

            $stmt->bind_param("s", $action);

            if (!$stmt->execute()) {
                throw new Exception($stmt->error);
            }

            $stmt->close();

            clean_sp_buffers_safe($con);

            echo "<div class='alert alert-success'>Record Updated Successfully!</div>";

            if (!show_next_record()) {
                unset($_SESSION['records'], $_SESSION['current_index']);
            }
        }

    } catch (Throwable $e) {
        error_log("UMOIC save action plan error: " . $e->getMessage());

        echo "<div class='alert alert-danger'>
            Unable to update Action Plan at the moment.
            Please try again after some time or contact support.
        </div>";
    }
}
?>

</div>
</div>

</div>
</div>

<script>
function bindConcernLoader() {
    $("#Period").off("change").on("change", function () {
        $.post("assets/responce/response_m1.php", { cid: $(this).val() }, function (data) {
            $("#Concern").html(data);
        }).fail(function () {
            $("#Concern").html("<option value='0'>Unable to load Area of Concern</option>");
        });
    });
}

bindConcernLoader();

$(document).on("click", "#skipBtn, #saveBtn", function () {
    setTimeout(() => {
        bindConcernLoader();
    }, 200);
});

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

<div id="departmentModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="departmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">

        <form method="post">
            <?= csrf(); ?>

            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title" id="departmentModalLabel">
                        <?= ($_SESSION['facilty_type'] == 8) ? "Select Checklist" : "Select Department"; ?>
                    </h5>

                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>

                <div class="modal-body">

                    <input type="hidden" name="department_name" id="department_name_input" value="">

                    <label for="departmentSelect" class="form-label">
                        <?= ($_SESSION['facilty_type'] == 8) ? "Checklist" : "Department"; ?>
                    </label>

                    <select class="mb-3 form-control form-control-sm"
                            id="departmentSelect"
                            name="department_id"
                            required>

                        <option value="">
                            <?= ($_SESSION['facilty_type'] == 8)
                                ? "--Select Checklist--"
                                : "--Select Department--"; ?>
                        </option>

                        <?php
                        try {
                            $factype = (int)($_SESSION['f_type_id'] ?? 0);
                            $facid   = (int)($_SESSION['u_facilityid'] ?? 0);
                            $assid   = (int)($_SESSION['assperiod'] ?? 0);

                            $query = "
                                SELECT DISTINCT
                                    a.fac_dept_id_fk,
                                    b.dept_name
                                FROM concern_subtype_chklist AS a
                                JOIN fac_department AS b
                                    ON a.fac_dept_id_fk = b.fac_dept_id
                                WHERE a.fac_type_id_fk = ?
                                AND a.fac_dept_id_fk IN (
                                    SELECT fac_dept_id
                                    FROM fac_dept_map
                                    WHERE fac_id = ?
                                    AND acc_id = ?
                                )
                            ";

                            $stmt = $con->prepare($query);

                            if (!$stmt) {
                                throw new Exception($con->error);
                            }

                            $stmt->bind_param("iii", $factype, $facid, $assid);
                            $stmt->execute();

                            $result = $stmt->get_result();

                            while ($row = $result->fetch_assoc()) {
                                echo "<option value='" . (int)$row['fac_dept_id_fk'] . "'
                                      data-name='" . htmlspecialchars($row['dept_name'], ENT_QUOTES, 'UTF-8') . "'>" .
                                      htmlspecialchars($row['dept_name'], ENT_QUOTES, 'UTF-8') .
                                      "</option>";
                            }

                            $stmt->close();

                        } catch (Throwable $e) {
                            error_log("UMOIC department modal load error: " . $e->getMessage());

                            echo "<option value=''>Unable to load department list</option>";
                        }
                        ?>

                    </select>
                </div>

                <div class="modal-footer">
                    <button type="button"
                            class="btn btn-secondary"
                            data-dismiss="modal">
                        Close
                    </button>

                    <button type="submit"
                            class="btn btn-primary">
                        Continue
                    </button>
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

<?php include("assets/head/f.php"); ?>

<?php
} catch (Throwable $e) {

    error_log("UMOIC PAGE ERROR: " . $e->getMessage());

    echo "<div class='alert alert-danger m-4'>
        Unable to load Action Plan at the moment.
        Please try again after some time or contact support.
    </div>";
}
?>