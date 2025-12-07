<?php
include("assets/head/h.php");


$facid = $_SESSION['u_facilityid'];
$factype = $_SESSION['f_type_id'];
$assid =  $_SESSION['assperiod'];
$facname = $_SESSION['facname'];
$factypename1 = $_SESSION['factypename'];

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['submit_form2'])) {
    $_SESSION['dept_id1'] = $_POST['department_id'];
    // echo "<div class='alert alert-success'>Department selected successfully!</div>";
}
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['submit_form1'])) {
    $_SESSION['assperiod'] = $_POST['assis'];
    // echo "<div class='alert alert-success'>Department selected successfully!</div>";
}
?>

<div class="pcoded-main-container">
    <div class="pcoded-content">
        <div class="pagetitle mb-2">
            <?php
            $assessmentName = '';
            if (!empty($_SESSION['assperiod'])) {
                $stmt = $con->prepare("SELECT ass_name FROM assessment_desc WHERE id = ?");
                $stmt->bind_param("i", $_SESSION['assperiod']);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    $assessmentName = $row['ass_name'];
                }
                $stmt->close();
            }
            ?>
            <h5 class="fw-bold text-primary mb-1">
                <i class="bi bi-question-circle-fill me-2"></i>
                Facility setup for assessment. Current Assessment is
                <span class="text-warning"><?= htmlspecialchars($assessmentName) ?></span>
            </h5>

        </div>
        <div class="row">

            <div class="col-sm-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white fw-bold">
                        Begin New Assessment Cycle
                    </div>
                    <div class="card-body">
                        <form method="post" action="assets/get/create_assessment.php">
                            <div class="mb-3">
                                <label for="ass_name" class="form-label fw-semibold text-primary small">
                                    Name of New Assessment Cycle
                                </label>
                                <input type="text" id="ass_name" name="ass_name" class="form-control form-control-sm" required>
                            </div>
                            <div class="text-center">
                                <button type="submit" name="save_assessor" class="btn btn-sm btn-primary px-4">
                                    <i class="bi bi-save2-fill me-1"></i> Submit
                                </button>
                            </div>
                        </form>
                    </div>
                    <?php if (!empty($_SESSION['ass_create_success'])): ?>
                        <div class="alert alert-success">
                            <?= htmlspecialchars($_SESSION['success']) ?>
                        </div>
                        <?php unset($_SESSION['ass_create_success'], $_SESSION['success']); ?>
                    <?php elseif (!empty($_SESSION['error'])): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($_SESSION['error']) ?>
                        </div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="card">
                    <div class="card-header bg-primary text-white fw-bold">
                        Current assessment setup
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <p>
                                <strong>Switch Assesment</strong>
                                <select class="mb-3 form-control form-control-sm" id="assis" name="assis" required>

                                    <option value="">-- Select --</option>
                                    <?php
                                    $query = "SELECT ass_name,id 
                                      FROM assessment_desc
                                      WHERE fac_id_fk = ?";
                                    $stmt = $con->prepare($query);
                                    $stmt->bind_param("i", $facid);
                                    $stmt->execute();
                                    $result = $stmt->get_result();

                                    while ($row = $result->fetch_assoc()) {
                                        echo "<option value='{$row['id']}'>{$row['ass_name']}</option>";
                                    }
                                    ?>
                                    </strong> departments. Please select the department for which the assessment will be carried out.
                                </select>
                            </p>

                            <button type="submit" name="submit_form1" class="btn btn-primary">Update current assessment</button>
                        </form>

                        <?php
                        if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['submit_form1'])) {
                            $_SESSION['assperiod'] = $_POST['assis'];

                            $selectedId = $_POST['assis'];
                            $stmt = $con->prepare("SELECT ass_name FROM assessment_desc WHERE id = ?");
                            $stmt->bind_param("i", $selectedId);
                            $stmt->execute();
                            $result = $stmt->get_result();

                            if ($row = $result->fetch_assoc()) {
                                echo "<div class='alert alert-success'>You have selected <strong>" . htmlspecialchars($row['ass_name']) . "</strong> for current assessment.</div>";
                            }

                            $stmt->close();
                        }
                        ?>

                    </div>
                </div>
            </div>
            <!--div class="col-sm-6">
                <div class="card">
                    <div class="card-header bg-primary text-white fw-bold">
                        Department/Assesment package Setup for Current Assessment
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">

                            <?php
                            if ($factype == 8) {

                            ?>
                                <strong>select the assessment package. </strong>
                            <?php } else {
                            ?>
                                <strong>select the Department. </strong>
                            <?php } ?>
                            <select class="mb-3 form-control form-control-sm" id="department_id" name="department_id" required>

                                <option value="">-- Select --</option>
                                <?php
                                $query = "SELECT DISTINCT a.fac_dept_id_fk, b.dept_name 
                                              FROM concern_subtype_chklist AS a 
                                              JOIN fac_department AS b ON a.fac_dept_id_fk = b.fac_dept_id 
                                              WHERE a.fac_type_id_fk = ? AND a.fac_dept_id_fk IN (
                                                 SELECT fac_dept_id FROM fac_dept_map WHERE fac_id = ? AND acc_id = ?
                                                     )";
                                $stmt = $con->prepare($query);
                                $stmt->bind_param("iii", $factype, $facid, $assid);
                                $stmt->execute();
                                $result = $stmt->get_result();

                                while ($row = $result->fetch_assoc()) {
                                    echo "<option value='{$row['fac_dept_id_fk']}'>{$row['dept_name']}</option>";
                                }

                                $stmt->close();
                                // $con->close();
                                ?>
                            </select>

                            <button type="submit" name="submit_form2" class="btn btn-primary">Submit</button>
                        </form>

                        <?php
                        if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['submit_form2'])) {
                            $_SESSION['dept_id1'] = $_POST['department_id'];

                            $selectedId = $_POST['department_id'];
                            $stmt = $con->prepare("SELECT dept_name FROM fac_department WHERE fac_dept_id = ?");
                            $stmt->bind_param("i", $selectedId);
                            $stmt->execute();
                            $result = $stmt->get_result();

                            if ($row = $result->fetch_assoc()) {
                                echo "<div class='alert alert-success'>You have selected <strong>" . htmlspecialchars($row['dept_name']) . "</strong> for current assessment.</div>";
                            }

                            $stmt->close();
                        }
                        ?>

                    </div>
                </div>
            </div--->
        </div>
        <div class="row">
    <div class="col-lg-12">
        <div class="card">

            <!-- HEADER TITLE -->
            <div class="card-header bg-primary text-white fw-bold">
                <?= ($factype == 8) 
                    ? "Assessment package setup for current assessment" 
                    : "Department setup for current assessment" ?>
            </div>

            <div class="card-body">

                <?php
                $facilityId       = $_SESSION['u_facilityid'];
                $assessmentPeriod = $_SESSION['assperiod'];

                // -----------------------------------------------------
                // Count how many departments already active
                // -----------------------------------------------------
                $activeCountStmt = $con->prepare("
                    SELECT COUNT(*) FROM fac_dept_map 
                    WHERE fac_id = ? AND acc_id = ?
                ");
                $activeCountStmt->bind_param("ii", $facilityId, $assessmentPeriod);
                $activeCountStmt->execute();
                $activeCountStmt->bind_result($activeCount);
                $activeCountStmt->fetch();
                $activeCountStmt->close();

                $isSingleDeptFacility = ($factype == 8);  // Only 1 allowed
                ?>

                <?php
                // -----------------------------------------------------
                // ACTIVATE DEPARTMENT
                // -----------------------------------------------------
                if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['activate_department_id'])) {

                    $deptId = intval($_POST['activate_department_id']);

                    if ($isSingleDeptFacility && $activeCount >= 1) {

                        echo "<div class='alert alert-danger fw-bold'>
                                Only one department/package can be activated for this assessment.<br>
                                If you want to start another package, kindly create a new assessment cycle.
                              </div>";

                    } else {

                        // Prevent duplicate activation
                        $chk = $con->prepare("
                            SELECT 1 FROM fac_dept_map 
                            WHERE fac_id = ? AND acc_id = ? AND fac_dept_id = ?
                        ");
                        $chk->bind_param("iii", $facilityId, $assessmentPeriod, $deptId);
                        $chk->execute();
                        $chk->store_result();

                        if ($chk->num_rows === 0) {

                            $insert = $con->prepare("
                                INSERT INTO fac_dept_map (fac_id, acc_id, fac_dept_id)
                                VALUES (?, ?, ?)
                            ");
                            $insert->bind_param("iii", $facilityId, $assessmentPeriod, $deptId);
                            $insert->execute();
                            $insert->close();

                            echo "<div class='alert alert-success'>Department activated successfully!</div>";
                            $activeCount++;

                        } else {
                            echo "<div class='alert alert-info'>Department already activated.</div>";
                        }

                        $chk->close();
                    }
                }


                // -----------------------------------------------------
                // DISABLE DEPARTMENT
                // -----------------------------------------------------
                if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['disable_department_id'])) {

                    $deptId = intval($_POST['disable_department_id']);

                    $delete = $con->prepare("
                        DELETE FROM fac_dept_map 
                        WHERE fac_id = ? AND acc_id = ? AND fac_dept_id = ?
                    ");
                    $delete->bind_param("iii", $facilityId, $assessmentPeriod, $deptId);

                    if ($delete->execute()) {
                        echo "<div class='alert alert-warning'>Department disabled successfully!</div>";
                        $activeCount--;
                    }
                    $delete->close();
                }
                ?>


                <?php
                // -----------------------------------------------------
                // FETCH ALL DEPARTMENTS GROUPED BY PROGRAM TAG
                // -----------------------------------------------------
                $stmt = $con->prepare("
                    SELECT DISTINCT 
                        a.fac_dept_id_fk AS dept_id,
                        b.dept_name,
                        b.program_tag
                    FROM concern_subtype_chklist a
                    JOIN fac_department b ON a.fac_dept_id_fk = b.fac_dept_id
                    WHERE a.fac_type_id_fk = ?
                      AND b.active_status = 1
                    ORDER BY b.program_tag, b.dept_name
                ");
                $stmt->bind_param("i", $factype);
                $stmt->execute();
                $result = $stmt->get_result();

                $programTabs = [];

                while ($row = $result->fetch_assoc()) {
                    $programTabs[$row['program_tag']][] = $row;
                }

                $stmt->close();
                ?>

                <!-- ============================================================
                     PROGRAM TABS (Auto Generated)
                ===============================================================-->
                <ul class="nav nav-tabs mb-3">
                    <?php 
                    $first = true;
                    foreach ($programTabs as $program => $rows): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $first ? 'active' : '' ?>"
                               data-toggle="tab" href="#tab_<?= strtolower($program) ?>">
                               <?= htmlspecialchars($program) ?>
                            </a>
                        </li>
                    <?php 
                    $first = false;
                    endforeach; 
                    ?>
                </ul>

                <!-- ============================================================
                     TAB CONTENT — Departments under each Program
                ===============================================================-->
                <div class="tab-content">

                    <?php 
                    $first = true;
                    foreach ($programTabs as $program => $rows): ?>
                        
                        <div class="tab-pane fade <?= $first ? 'show active' : '' ?>"
                             id="tab_<?= strtolower($program) ?>">

                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-sm">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Department Name</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <?php foreach ($rows as $d): 
                                            $deptId = $d['dept_id'];

                                            // Check active status
                                            $chk = $con->prepare("
                                                SELECT 1 FROM fac_dept_map
                                                WHERE fac_id = ? AND acc_id = ? AND fac_dept_id = ?
                                            ");
                                            $chk->bind_param("iii", $facilityId, $assessmentPeriod, $deptId);
                                            $chk->execute();
                                            $chk->store_result();
                                            $isActive = $chk->num_rows > 0;
                                            $chk->close();
                                        ?>

                                        <tr>
                                            <td><?= $d['dept_name'] ?></td>
                                            <td>

                                                <?php if ($isActive): ?>

                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="disable_department_id" value="<?= $deptId ?>">
                                                        <button class="btn btn-sm btn-danger">Disable</button>
                                                    </form>

                                                    <span class="btn btn-sm btn-success disabled">Active</span>

                                                <?php else: ?>

                                                    <?php if ($isSingleDeptFacility && $activeCount >= 1): ?>

                                                        <button class="btn btn-sm btn-secondary disabled">
                                                            Activate
                                                        </button>

                                                    <?php else: ?>

                                                        <form method="POST" style="display:inline;">
                                                            <input type="hidden" name="activate_department_id" value="<?= $deptId ?>">
                                                            <button class="btn btn-sm btn-primary">Activate</button>
                                                        </form>

                                                    <?php endif; ?>

                                                <?php endif; ?>

                                            </td>
                                        </tr>

                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                        </div>

                    <?php 
                    $first = false;
                    endforeach; 
                    ?>

                </div>

            </div>
        </div>
    </div>
</div>



    </div>
</div>


<?php include("assets/head/f.php"); ?>