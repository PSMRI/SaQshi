<?php
include("assets/head/h.php");

$facid        = $_SESSION['u_facilityid'];
$factype      = $_SESSION['f_type_id'];
$assid        = $_SESSION['assperiod'];
$facname      = $_SESSION['facname'];
$factypename1 = $_SESSION['factypename'];
auditLog(
    $con,
    'VIEW_PAGE',
    'Assessment Setup',
    'Opened Facility Assessment Setup page',
    $_SESSION['u_facilityid']
);

// =======================
// Handle Assessment Switch
// =======================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['submit_form1'])) {
    $_SESSION['assperiod'] = $_POST['assis'];
}

// =======================
// Handle department select
// =======================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['submit_form2'])) {
    $_SESSION['dept_id1'] = $_POST['department_id'];
}
?>

<?php
// =======================
// Fetch Current Assessment Name
// =======================
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

<div class="pcoded-main-container">
    <div class="pcoded-content">

        <div class="pagetitle mb-2">
            <h5 class="fw-bold text-primary mb-1">
                <i class="bi bi-question-circle-fill me-2"></i>
                Facility setup for assessment. Current Assessment:
                <span class="text-warning"><?= htmlspecialchars($assessmentName) ?></span>
            </h5>
        </div>

        <div class="row">

            <!-- =====================================================
        LEFT SIDE  → BEGIN NEW ASSESSMENT CYCLE
====================================================== -->
            <div class="col-sm-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white fw-bold">
                        Begin New Assessment Cycle
                    </div>

                    <div class="card-body">

                        <?php
                        $today      = date("Y-m-d");
                        $maxEndDate = date("Y-m-d", strtotime("+1 month"));
                        ?>

                        <form method="post" action="assets/get/create_assessment.php">

                            <div class="row g-2 align-items-end">

                                <!-- Name -->
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold text-primary small">Assessment Name</label>
                                    <input type="text" name="ass_name" class="form-control form-control-sm" required>
                                </div>

                                <!-- Start Date -->
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold small text-primary">Start Date</label>
                                    <input type="date" name="f_date" class="form-control form-control-sm"
                                        value="<?= $today ?>" readonly required>
                                </div>

                                <!-- End Date -->
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold small text-primary">End Date</label>
                                    <input type="date" name="t_date" class="form-control form-control-sm"
                                        min="<?= $today ?>" max="<?= $maxEndDate ?>" required>
                                </div>

                                <!-- Button -->
                                <div class="col-md-2 text-center">
                                    <button type="submit" name="save_assessor" class="btn btn-success btn-sm w-100 mt-4">
                                        Create
                                    </button>
                                </div>

                            </div>

                            <small class="text-muted d-block mt-1">
                                End date must be within 1 month from today.
                            </small>

                            <input type="hidden" name="fac_id_fk" value="<?= $facid ?>">
                            <input type="hidden" name="u_id_fk" value="<?= $_SESSION['user_id'] ?? 0 ?>">

                        </form>

                    </div>

                    <?php if (!empty($_SESSION['ass_create_success'])): ?>
                        <div class="alert alert-success m-2">
                            <?= htmlspecialchars($_SESSION['success']) ?>
                        </div>
                        <?php unset($_SESSION['ass_create_success'], $_SESSION['success']); ?>
                    <?php elseif (!empty($_SESSION['error'])): ?>
                        <div class="alert alert-danger m-2">
                            <?= htmlspecialchars($_SESSION['error']);
                            unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>

                </div>
            </div>


            <!-- =====================================================
        RIGHT SIDE  → SWITCH ASSESSMENT
====================================================== -->
            <div class="col-sm-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white fw-bold">Current Assessment Setup</div>

                    <div class="card-body">

                        <form method="POST" action="">
                            <label class="fw-bold small">Switch Assessment Cycle</label>

                            <select class="form-control form-control-sm mb-2" id="assis" name="assis" required>
                                <option value="">-- Select --</option>
                                <?php
                                $stmt = $con->prepare("SELECT ass_name,id FROM assessment_desc WHERE fac_id_fk = ?");
                                $stmt->bind_param("i", $facid);
                                $stmt->execute();
                                $rs = $stmt->get_result();
                                while ($row = $rs->fetch_assoc()) {
                                    echo "<option value='{$row['id']}'>{$row['ass_name']}</option>";
                                }
                                ?>
                            </select>

                            <button type="submit" name="submit_form1" class="btn btn-primary btn-sm">Update</button>
                        </form>

                        <?php
                        if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['submit_form1'])) {

                            $selectedId = intval($_POST['assis']);                 // Selected assessment id
                            $facilityId = intval($_SESSION['u_facilityid']);       // Facility id

                            // 1. Set all assessments of this facility to 0
                            $con->query("
        UPDATE assessment_desc 
        SET current_assment = 0 
        WHERE fac_id_fk = $facilityId
    ");

                            // 2. Set selected assessment to 1
                            $con->query("
        UPDATE assessment_desc 
        SET current_assment = 1 
        WHERE id = $selectedId
    ");

                            // 3. Update s_user table for all users of this facility
                            $con->query("
        UPDATE s_user 
        SET assessment_id = $selectedId
        WHERE fac_id_fk = $facilityId
    ");

                            // 4. Fetch assessment name for success message
                            $stmt = $con->prepare("SELECT ass_name FROM assessment_desc WHERE id = ?");
                            $stmt->bind_param("i", $selectedId);
                            $stmt->execute();
                            $data = $stmt->get_result()->fetch_assoc();
                            $stmt->close();

                            // 5. Update session value immediately
                            $_SESSION['assperiod'] = $selectedId;

                            echo "<div class='alert alert-success mt-2'>
            Selected <strong>{$data['ass_name']}</strong> as current assessment.
            
          </div>";
          auditLog(
    $con,
    'SWITCH_ASSESSMENT',
    'Assessment Setup',
    'Switched current assessment to '.$data['ass_name'],
    $selectedId
);

                        }


                        ?>

                    </div>
                </div>
            </div>

        </div> <!-- row -->

        <!-- =====================================================
 ACTIVATE / DISABLE DEPARTMENTS / PACKAGES FOR ASSESSMENT
====================================================== -->

<?php
$facilityId       = $_SESSION['u_facilityid'];
$assessmentPeriod = $_SESSION['assperiod'];

// Check if facility is single-department (package-based)
$isSingleDept = ($factype == 8);

// Count active departments/packages
$ac = $con->prepare("
    SELECT COUNT(*) 
    FROM fac_dept_map 
    WHERE fac_id = ? AND acc_id = ?
");
$ac->bind_param("ii", $facilityId, $assessmentPeriod);
$ac->execute();
$ac->bind_result($activeCount);
$ac->fetch();
$ac->close();
?>

<div class="row mt-3">
<div class="col-lg-12">
<div class="card">

    <div class="card-header bg-primary text-white fw-bold">
        <?= $isSingleDept ? "Assessment Package Setup" : "Department Setup" ?>
    </div>

    <div class="card-body">

        <!-- RULE MESSAGE -->
        <?php if ($isSingleDept && $activeCount >= 1): ?>
            <div class="alert alert-warning fw-semibold">
                <i class="bi bi-lock-fill me-2"></i>
                This facility allows only <strong>one assessment package</strong>.
                <br>
                To activate another package, please <strong>recreate the assessment cycle</strong>
                and then assign a new package.
            </div>
        <?php endif; ?>

        <?php
        /* =========================================
           ACTIVATE PACKAGE / DEPARTMENT
        ========================================= */
        if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['activate_department_id'])) {

            $deptId = (int)$_POST['activate_department_id'];

            if ($isSingleDept && $activeCount >= 1) {

                echo "<div class='alert alert-danger'>
                        Only one package is allowed.
                        Please recreate the assessment cycle to change the package.
                      </div>";

            } else {

                $chk = $con->prepare("
                    SELECT 1 FROM fac_dept_map 
                    WHERE fac_id=? AND acc_id=? AND fac_dept_id=?
                ");
                $chk->bind_param("iii", $facilityId, $assessmentPeriod, $deptId);
                $chk->execute();
                $chk->store_result();

                if ($chk->num_rows == 0) {

                    $ins = $con->prepare("
                        INSERT INTO fac_dept_map (fac_id, acc_id, fac_dept_id)
                        VALUES (?, ?, ?)
                    ");
                    $ins->bind_param("iii", $facilityId, $assessmentPeriod, $deptId);
                    $ins->execute();
                    $ins->close();

                    echo "<div class='alert alert-success'>Activated successfully.</div>";
                    $activeCount++;
                    auditLog(
    $con,
    'ACTIVATE_DEPARTMENT',
    'Assessment Setup',
    'Activated department ID '.$deptId,
    $deptId
);


                } else {
                    echo "<div class='alert alert-info'>Already activated.</div>";
                    auditLog(
    $con,
    'ACTIVATE_DEPARTMENT',
    'Assessment Setup',
    'AAlready activated department ID '.$deptId,
    $deptId
);

                }

                $chk->close();
            }
        }

        /* =========================================
           DISABLE PACKAGE / DEPARTMENT (STRICT)
        ========================================= */
        if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['disable_department_id'])) {

            if ($isSingleDept && $activeCount == 1) {

                echo "<div class='alert alert-danger'>
                        This package cannot be disabled.
                        To change the package, please recreate the assessment cycle first.
                      </div>";

            } else {

                $deptId = (int)$_POST['disable_department_id'];

                $del = $con->prepare("
                    DELETE FROM fac_dept_map
                    WHERE fac_id=? AND acc_id=? AND fac_dept_id=?
                ");
                $del->bind_param("iii", $facilityId, $assessmentPeriod, $deptId);
                $del->execute();
                $del->close();

                echo "<div class='alert alert-warning'>Department disabled.</div>";
                $activeCount--;
                auditLog(
    $con,
    'DISABLE_DEPARTMENT',
    'Assessment Setup',
    'Disabled department ID '.$deptId,
    $deptId
);

            }
        }
        ?>

        <?php
        /* =========================================
           FETCH PROGRAMS & DEPARTMENTS
        ========================================= */
        $stmt = $con->prepare("
            SELECT DISTINCT 
                a.fac_dept_id_fk AS dept_id,
                b.dept_name,
                b.program_tag
            FROM concern_subtype_chklist a
            JOIN fac_department b 
                ON a.fac_dept_id_fk = b.fac_dept_id
            WHERE a.fac_type_id_fk = ?
              AND b.active_status = 1
            ORDER BY b.program_tag, b.dept_name
        ");
        $stmt->bind_param("i", $factype);
        $stmt->execute();
        $res = $stmt->get_result();

        $programTabs = [];
        while ($row = $res->fetch_assoc()) {
            $programTabs[$row['program_tag']][] = $row;
        }
        $stmt->close();
        ?>

        <!-- PROGRAM TABS -->
        <ul class="nav nav-tabs mb-3">
            <?php $first = true; foreach ($programTabs as $program => $rows): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $first ? 'active' : '' ?>"
                       data-toggle="tab"
                       href="#tab_<?= strtolower($program) ?>">
                        <?= htmlspecialchars($program) ?>
                    </a>
                </li>
            <?php $first = false; endforeach; ?>
        </ul>

        <!-- TAB CONTENT -->
        <div class="tab-content">
        <?php $first = true; foreach ($programTabs as $program => $deptRows): ?>

            <div class="tab-pane fade <?= $first ? 'show active' : '' ?>"
                 id="tab_<?= strtolower($program) ?>">

                <table class="table table-bordered table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Department / Package</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>

                    <?php foreach ($deptRows as $d):

                        $deptId = $d['dept_id'];

                        $chk = $con->prepare("
                            SELECT 1 FROM fac_dept_map
                            WHERE fac_id=? AND acc_id=? AND fac_dept_id=?
                        ");
                        $chk->bind_param("iii", $facilityId, $assessmentPeriod, $deptId);
                        $chk->execute();
                        $chk->store_result();
                        $isActive = $chk->num_rows > 0;
                        $chk->close();
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($d['dept_name']) ?></td>
                            <td>

                            <?php if ($isActive): ?>

                                <?php if ($isSingleDept && $activeCount == 1): ?>
                                    <span class="badge bg-success px-3 py-2">
                                        Active (Locked)
                                    </span>
                                <?php else: ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="disable_department_id" value="<?= $deptId ?>">
                                        <button class="btn btn-sm btn-danger">Disable</button>
                                    </form>
                                <?php endif; ?>

                            <?php else: ?>

                                <?php if ($isSingleDept && $activeCount >= 1): ?>
                                    <button class="btn btn-sm btn-secondary" disabled>
                                        Activate
                                    </button>
                                <?php else: ?>
                                    <form method="POST" class="d-inline">
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

        <?php $first = false; endforeach; ?>
        </div>

    </div>
</div>
</div>
</div>


            <?php include("assets/head/f.php"); ?>