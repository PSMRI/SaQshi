<?php
try {

    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['department_id']) && !empty($_POST['department_id'])) {
        session_start();
        $_SESSION['dept_id1'] = $_POST['department_id'];
        $_SESSION['dept_name1'] = $_POST['department_name'];
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    include("assets/head/h.php");

    function clean_sp_buffers_safe($con)
    {
        if ($con instanceof mysqli) {
            while ($con->more_results() && $con->next_result()) {
                $extra = $con->store_result();
                if ($extra) {
                    $extra->free();
                }
            }
        }
    }

    $showDeptModal = empty($_SESSION['dept_id1']) || $_SESSION['dept_id1'] == 0;
    $dept_name = $_SESSION['dept_name1'] ?? '0';

?>
    <div class="pcoded-main-container">
        <div class="pcoded-content">

            <div class="pagetitle mb-2">
                <h5 class="fw-bold text-primary mb-1">
                    <i class="bi bi-person-badge-fill me-2"></i>
                    Generate action plan for <?= htmlspecialchars($dept_name); ?>

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

                    <form method="post" action="#" id="actionPlanFilterForm">
                        <?= csrf(); ?>

                        <div class="row g-3 align-items-end">

                            <div class="col-md-4">
                                <label class="form-label text-primary fw-semibold">
                                    Select Assessment Cycle
                                </label>

                                <select class="mb-1 form-control form-control-sm" id="Period1" name="Period">
                                    <option value="0">Select Assessment Cycle</option>

                                    <?php
                                    try {
                                        $fsid = (int)($_SESSION['u_facilityid'] ?? 0);

                                        $query = "CALL get_assessment1($fsid)";
                                        $result = $con->query($query);

                                        if ($result && $result->num_rows > 0) {
                                            while ($row = $result->fetch_assoc()) {
                                                echo "<option value='" . (int) $row['id'] . "'>" .
                                                    htmlspecialchars($row['ass_name'], ENT_QUOTES, 'UTF-8') .
                                                    "</option>";
                                            }

                                            $result->free();
                                        } else {
                                            echo "<option value='' disabled selected>No Action Plan to work on</option>";
                                        }

                                        clean_sp_buffers_safe($con);
                                    } catch (Throwable $e) {
                                        error_log("MOIC assessment cycle load error: " . $e->getMessage());
                                        echo "<option value='0'>Unable to load assessment cycle</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label text-primary fw-semibold">
                                    Select Area Of Concern
                                </label>

                                <select class="mb-1 form-control form-control-sm" id="Concern1" name="Concern">
                                    <option value="0">-Select-</option>
                                </select>
                            </div>

                            <div class="col-md-2">
                                <button type="submit"
                                    name="submit1"
                                    id="actionPlanBtn"
                                    class="btn btn-primary btn-sm"
                                    disabled>
                                    <i class="bi bi-pencil-square me-1"></i>
                                    Action Plan
                                </button>
                            </div>

                        </div>
                    </form>

                </div>
            </div>

            <?php if (isset($_POST['submit1']) || isset($_POST['submit2']) || isset($_POST['submit3'])): ?>

                <div class="card mt-4">
                    <div class="card-body">

                        <?php
                        try {
                            include('partials/moic_logic_render.php');
                        } catch (Throwable $e) {
                            error_log("MOIC action plan render error: " . $e->getMessage());

                            echo "<div class='alert alert-danger'>
                    Unable to load Action Plan at the moment.
                    Please try again after some time or contact support.
                </div>";
                        }
                        ?>

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

            $('#department_name_input').val($('#departmentSelect option:selected').data('name'));

            $('#departmentSelect').change(function() {
                var deptName = $('#departmentSelect option:selected').data('name');
                $('#department_name_input').val(deptName);
            });

            const $period = $("#Period1");
            const $concern = $("#Concern1");
            const $actionButton = $("#actionPlanBtn");

            // Initially disable the Action Plan button
            $actionButton.prop("disabled", true);

            $period.on("change", function() {

                const assessmentPeriodId = parseInt($(this).val(), 10) || 0;

                $actionButton.prop("disabled", true);

                if (assessmentPeriodId <= 0) {
                    $concern.html(
                        "<option value='0' selected>--Select Area of Concern--</option>"
                    );
                    return;
                }

                $concern.html(
                    "<option value='0' selected>Loading...</option>"
                );

                $.ajax({
                    method: "POST",
                    cache: false,
                    url: "assets/responce/response_m.php",
                    data: {
                        cid: assessmentPeriodId
                    },
                    dataType: "html",

                    success: function(data) {

                        const response = $.trim(data);

                        if (response === "") {
                            $concern.html(
                                "<option value='0' selected disabled>" +
                                "--No Action Plan to work--" +
                                "</option>"
                            );

                            $actionButton.prop("disabled", true);
                            return;
                        }

                        $concern.html(response);

                        // Check whether a valid Area of Concern exists
                        const validOptions = $concern.find("option").filter(function() {
                            return (parseInt($(this).val(), 10) || 0) > 0;
                        });

                        if (validOptions.length === 0) {
                            $actionButton.prop("disabled", true);
                        } else {
                            $actionButton.prop("disabled", false);
                        }
                    },

                    error: function() {
                        $concern.html(
                            "<option value='0' selected disabled>" +
                            "Unable to load Area of Concern" +
                            "</option>"
                        );

                        $actionButton.prop("disabled", true);
                    }
                });
            });

            $concern.on("change", function() {
                const concernId = parseInt($(this).val(), 10) || 0;
                $actionButton.prop("disabled", concernId <= 0);
            });

            $("#actionPlanFilterForm").on("submit", function(event) {

                const concernId = parseInt($concern.val(), 10) || 0;

                if (concernId <= 0) {
                    event.preventDefault();

                    Swal.fire({
                        icon: "warning",
                        title: "No Action Plan",
                        text: "There is no Action Plan available for the selected assessment cycle."
                    });

                    return false;
                }
            });


            <?php if ($showDeptModal): ?>
                $('#departmentModal').modal('show');
            <?php endif; ?>

        });
    </script>

    <!-- Department Modal -->
    <div id="departmentModal"
        class="modal fade"
        tabindex="-1"
        role="dialog"
        aria-labelledby="departmentModalLabel"
        aria-hidden="true">

        <div class="modal-dialog modal-dialog-centered" role="document">

            <form method="post">

                <div class="modal-content">

                    <div class="modal-header">

                        <h5 class="modal-title" id="departmentModalLabel">
                            <?= ($_SESSION['facilty_type'] == 8) ? "Select Checklist" : "Select Department"; ?>
                        </h5>

                        <button type="button"
                            class="close"
                            data-dismiss="modal"
                            aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>

                    </div>

                    <div class="modal-body">

                        <input type="hidden"
                            name="department_name"
                            id="department_name_input"
                            value="">

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
                                error_log("MOIC department modal load error: " . $e->getMessage());

                                echo "<option value=''>
                                Unable to load department list
                            </option>";
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

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {

            document.addEventListener("submit", function(e) {

                const form = e.target;

                if (!form.classList.contains("actionForm")) return;

                const activeBtn = document.activeElement;

                if (!activeBtn || !activeBtn.classList.contains("saveBtn")) return;

                let review = form.querySelector("select[name='f']").value;
                let responsible = form.querySelector("input[name='res']").value.trim();
                let date = form.querySelector("input[name='todate']").value.trim();
                let comment = form.querySelector("textarea[name='comment']").value.trim();

                if (review === "0" || review === "3") {

                    e.preventDefault();

                    Swal.fire({
                        icon: "warning",
                        title: "Dept. Review Required",
                        text: "Kindly select Dept. Review: Achievable or Non-achievable."
                    });

                    return false;
                }

                if (review === "1") {

                    if (responsible === "" || date === "" || comment === "") {

                        e.preventDefault();

                        Swal.fire({
                            icon: "warning",
                            title: "Missing Details",
                            text: "Please enter Responsible Person, Time Period Date, and Action Plan."
                        });

                        return false;
                    }
                }
            });

        });
    </script>

    <?php include("assets/head/f.php"); ?>

<?php
} catch (Throwable $e) {

    error_log("MOIC PAGE ERROR: " . $e->getMessage());

    echo "<div class='alert alert-danger m-4'>
        Unable to load Action Plan at the moment.
        Please try again after some time or contact support.
    </div>";
}
?>