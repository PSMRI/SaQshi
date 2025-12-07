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
$f_type_id = $_SESSION['f_type_id'] ?? 0;
$fid = $_SESSION['u_facilityid'] ?? 0;
$dept_name = $_SESSION['dept_name1'] ?? 0;  // For showing in header
$_SESSION['Cn'] = 0;

$query = "SELECT Health_facilty_type, fac_name FROM facilities WHERE fac_id = $fid";
$result = mysqli_query($con, $query);
if ($row = mysqli_fetch_assoc($result)) {
    $_SESSION['facilty_type'] = $row['Health_facilty_type'];
    $facname = $row['fac_name'];
}

$lang = $_SESSION['lang'] ?? 5;
$langMap = [1 => 'assam', 2 => 'ben', 3 => 'hin', 4 => 'odia', 5 => ''];
$suffix = $langMap[$lang] ?? '';

$_SESSION['concern_name'] = $suffix ? "concern_name_{$suffix}" : 'concern_name';
$_SESSION['M'] = $suffix ? "Measurable_Element_{$suffix}" : 'Measurable_Element';
$_SESSION['C'] = $suffix ? "Checkpoint_{$suffix}" : 'Checkpoint';
$_SESSION['Means'] = $suffix ? "Means_of_Verification_{$suffix}" : 'Means_of_Verification';
$area_of_con_subtypedeatils = $suffix ? "area_of_con_subtypedeatils_{$suffix}" : 'area_of_con_subtypedeatils';
?>

<div class="pcoded-main-container">
    <div class="pcoded-content">
        <div class="pagetitle">
            <h5 class="fw-bold text-primary">
                Assessment for <?php echo htmlspecialchars($dept_name); ?>
                <button type="button" class="btn btn-sm btn-link text-warning ms-2" data-toggle="modal" data-target="#departmentModal">
                    Change Department
                </button>
            </h5>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-body">
                        <form enctype="multipart/form-data" method="post" action="#">
                            <div class="form-group row">
                                <div class="col-auto">
                                    <select class="mb-3 form-control form-control-sm" id="Concern" name="Concern">
                                        <option value="0">-Select Area Of Concern-</option>
                                        <?php
                                        $urole = $_SESSION['urole'] ?? 0;
                                        $ftype = $_SESSION['facilty_type'] ?? 0;
                                        $concern_name = $_SESSION['concern_name'] ?? '';
                                        if ($urole == 2 || $urole == 1) {
                                            $query = "CALL get_area_of_con($ftype,'$concern_name')";
                                            $result = $con->query($query);
                                            if ($result && $result->num_rows > 0) {
                                                while ($row = mysqli_fetch_assoc($result)) {
                                                    echo "<option value='{$row['concern_id']}'>{$row['concern_name']}</option>";
                                                }
                                                mysqli_free_result($result);
                                                $con->next_result();
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>

                                <div class="col-auto">
                                    <select class="mb-3 form-control form-control-sm" id="category" name="category">
                                        <option value="0">-Select Standard-</option>
                                    </select>
                                </div>

                                <div class="col-auto">
                                    <select class="mb-3 form-control form-control-sm" id="Assessment_Method" name="Assessment_Method">
                                        <option value="">-Select Assessment Method-</option>
                                        <option value="SI">SI</option>
                                        <option value="OB">OB</option>
                                        <option value="PI">PI</option>
                                        <option value="RR">RR</option>
                                        <?php if ($_SESSION['facilty_type'] == 4) echo '<option value="CI">CI</option>'; ?>
                                    </select>
                                </div>

                                <div class="col-auto">
                                    <button type="submit" name="postsubmit" class="btn btn-primary btn-sm">Show Checklist</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-body">
                        <?php include("partials/assessment_logic_handler.php"); ?>
                    </div>
                </div>
            </div>
        </div>
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
                data: {
                    cid: Concernid
                },
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