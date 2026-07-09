<?php
// ------------------ Handle Department Change ------------------
if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($_POST['department_id'])) {
    session_start();
    $_SESSION['dept_id1']   = $_POST['department_id'];
    $_SESSION['dept_name1'] = $_POST['department_name'];

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}



include("assets/head/h.php");
$showDeptModal = empty($_SESSION['dept_id1']) || $_SESSION['dept_id1'] == 0;
$dept_name = $_SESSION['dept_name1'] ?? '';  // For showing in header
/* -----------------------------------------
   SESSION VARIABLES
------------------------------------------ */
$dept_id   = $_SESSION['dept_id1'] ?? 0;
$f_type_id = $_SESSION['f_type_id'] ?? 0;
$fid       = $_SESSION['u_facilityid'] ?? 0;
$dept_name = $_SESSION['dept_name1'] ?? '0';
$_SESSION['Cn'] = 0;

/* -----------------------------------------
   FETCH FACILITY DATA
------------------------------------------ */
$q = mysqli_query($con, "SELECT Health_facilty_type FROM facilities WHERE fac_id = $fid");
if ($r = mysqli_fetch_assoc($q)) {
    $_SESSION['facilty_type'] = $r['Health_facilty_type'];
}

/* -----------------------------------------
   LANGUAGE MAPPING
------------------------------------------ */
$lang = $_SESSION['lang'] ?? 1;
$langMap = [1=>'assam', 2=>'ben', 3=>'hin', 4=>'odia', 5=>''];
$suffix  = $langMap[$lang] ?? '';

$_SESSION['concern_name'] = $suffix ? "concern_name_$suffix" : "concern_name";
$_SESSION['M']            = $suffix ? "Measurable_Element_$suffix" : "Measurable_Element";
$_SESSION['C']            = $suffix ? "Checkpoint_$suffix" : "Checkpoint";
$_SESSION['Means']        = $suffix ? "Means_of_Verification_$suffix" : "Means_of_Verification";
?>

<div class="pcoded-main-container">
    <div class="pcoded-content">

        <!-- Page Title -->
        <div class="pagetitle mb-2">
            <h5 class="fw-bold text-primary">
                Assessment for <?= htmlspecialchars($dept_name) ?>
               <button type="button" class="btn btn-sm btn-link text-warning ms-2" data-toggle="modal" data-target="#departmentModal">
                <?= ($_SESSION['facilty_type'] == 8)
                    ? "Change Checklist"
                    : "Change Department"; ?>
            </button>
            </h5>
        </div>

        <!-- Filter Section -->
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-light fw-bold py-2">
                <i class="bi bi-funnel-fill text-primary me-2"></i> Filter Checklist
            </div>

            <div class="card-body py-2">
                <form id="filterForm">

                    <div class="row g-2">

                        <!-- Area of Concern -->
                        <div class="col-md-4">
                            <label class="fw-bold small mb-1">Area of Concern</label>
                            <select class="form-control form-control-sm" id="Concern" name="Concern">
                                <option value="0">- Select Area Of Concern -</option>
                                <?php
                                // Clean up previous results
                                while ($con->more_results() && $con->next_result()) {}

                                $ftype = $_SESSION['facilty_type'];
                                $colname = $_SESSION['concern_name'];

                                $sql = "CALL get_area_of_con($ftype,'$colname')";
                                $res = $con->query($sql);

                                if ($res && $res->num_rows > 0) {
                                    while ($rr = mysqli_fetch_assoc($res)) {
                                        echo "<option value='{$rr['concern_id']}'>{$rr['concern_name']}</option>";
                                    }
                                    mysqli_free_result($res);
                                    $con->next_result();
                                }
                                ?>
                            </select>
                        </div>

                        <!-- Standard -->
                        <div class="col-md-4">
                            <label class="fw-bold small mb-1">Standard</label>
                            <select class="form-control form-control-sm" name="category" id="category" required>
                                <option value="">-- Select --</option>
                            </select>
                        </div>

                        <!-- Assessment Method -->
                        <div class="col-md-4">
                            <label class="fw-bold small mb-1">Assessment Method</label>
                            <select class="form-control form-control-sm" name="Assessment_Method">
                                <option value="">Any</option>
                                <option value="SI">SI</option>
                                <option value="OB">OB</option>
                                <option value="PI">PI</option>
                                <option value="RR">RR</option>
                                <?php if ($_SESSION['facilty_type'] == 4) echo '<option value="CI">CI</option>'; ?>
                            </select>
                        </div>

                    </div>
                    <?php
// New assessment started
unset($_SESSION['ASSESSMENT_COMPLETED']);
unset($_SESSION['JUST_COMPLETED']); ?>
                    <button type="submit" class="btn btn-primary btn-sm mt-3">
                        <i class="bi bi-search me-1"></i> Load Checklist
                    </button>

                </form>
            </div>
        </div>

        <!-- Assessment Output -->
        <div id="assessmentBox"></div>

    </div>
</div>

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

<!-- JavaScript Section -->
<script>
$("#departmentSelect").change(function() {
    $("#department_name_input").val($(this).find(":selected").data("name"));
});


/* =======================
   Load Standards on Concern Change
======================= */
$("#Concern").change(function(){
    $("#category").html("<option>Loading...</option>");

    $.post("assets/responce/response.php",
        { cid: $(this).val() },
        function(data){ $("#category").html(data); }
    );
});


/* =======================
   Load Checklist
======================= */
$("#filterForm").submit(function(e){
    e.preventDefault();

    $.post("assets/get/fetch_list.php", $(this).serialize(), function(res){
        $("#assessmentBox").html(res);
    });
});

/* Navigation Actions */
function doAction(action){
    $.post("assets/get/assessment_engine.php", { action: action }, function(res){
        $("#assessmentBox").html(res);
    });
}

function saveAction(){
    $.post("assets/get/assessment_engine.php",
        $("#answerForm").serialize() + "&action=save",
        function(res){
            $("#assessmentBox").html(res);
        }
    );
}
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