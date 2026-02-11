<?php
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['department_id']) && !empty($_POST['department_id'])) {
    session_start();
    $_SESSION['dept_id1'] = $_POST['department_id'];
    $_SESSION['dept_name1'] = $_POST['department_name'];  // Save department name
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
?>

<?php
include("assets/head/h.php"); // already handles session_start internally
// Determine whether to show modal
$showDeptModal = empty($_SESSION['dept_id1']) || $_SESSION['dept_id1'] == 0;
$dept_name = $_SESSION['dept_name1'] ?? '';  // For showing in header
?>


<div class="pcoded-main-container">
    <div class="pcoded-content">
        <div class="pagetitle mb-2">
            <h5 class="fw-bold text-primary mb-1">
                <i class="bi bi-person-badge-fill me-2"></i>Outcome Indicator for <?php echo htmlspecialchars($dept_name); ?>
                <button type="button" class="btn btn-sm btn-link text-warning ms-2" data-toggle="modal" data-target="#departmentModal">
                    Change Department
                </button>
                </button>
            </h5>
        </div>

        <div class="card">
            <div class="card-body">
                <form method="post">
                    <div class="row align-items-end">
                        <div class="col-auto">
                            <label for="date1" class="form-label">Select Month</label>
                            <input type="month" class="form-control" name="date1" id="date1"
                                value="<?= isset($_POST['date1']) ? $_POST['date1'] : date('Y-m'); ?>"
                                min="<?= date('Y-m', strtotime('-6 months')); ?>"
                                max="<?= date('Y-m'); ?>" required>
                        </div>
                        <div class="col-auto">
                            <button type="submit" name="submit1" class="btn btn-primary">Fill Data</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <?php
        if (isset($_POST['submit1'])) {
            $new_date1 = date('Y-m', strtotime($_POST['date1']));
            $_SESSION['new_date1'] = $new_date1;

            if ($new_date1 == '1970-01') {
                echo "<div class='alert alert-danger'>Kindly select a valid month.</div>";
            } else {
                $fac_id = $_SESSION['u_facilityid'];
                $dept_id = $_SESSION['dept_id1'];
                $ftype = $_SESSION['f_type_id'];

                $stmt1 = $con->prepare("SELECT COUNT(id_out_hwc) AS total FROM outcome_values_in WHERE month_in = ? AND institute_id = ? AND dept_id = ?");
                $stmt1->bind_param("sii", $new_date1, $fac_id, $dept_id);
                $stmt1->execute();
                $result1 = $stmt1->get_result()->fetch_assoc();

                if ((int)$result1['total'] > 0) {
                    echo "<div class='alert alert-danger'>The values for this month <b>$new_date1</b> have already been recorded.</div>";
                } else {
                    $q3 = mysqli_query($con, "SELECT * FROM out_come_dh WHERE out_come_hwc_factype = $ftype AND out_come_dept = $dept_id");
                    $q = 0;
                    $total = mysqli_num_rows($q3);

                    echo '<form method="post">';
                    while ($row = mysqli_fetch_array($q3)) {
                        $q++;
                        $readonly = ($row['deno'] === 'N/A') ? 'readonly' : '';
                        $isLast = ($q == $total);
                        echo "<div class='card mb-3 outcome-card' id='card$q' style='" . ($q > 1 ? "display:none;" : "") . "'>
              <div class='card-body'>
                <h6 class='card-title text-primary'><i class='bi bi-bar-chart-fill me-2'></i>Outcome Indicator: {$row['out_come_hwcindi']}</h6>
                <div class='alert alert-info py-2 px-3 small d-flex align-items-center'>
                  <i class='bi bi-info-circle-fill me-2'></i>
                  <div><strong>Expected:</strong> Numerator: <b>{$row['num']}</b>, Denominator: <b>{$row['deno']}</b></div>
                </div>
                <div class='row g-2'>
                  <div class='col-md-4'>
                    <input type='number' step='any' class='form-control' name='input" . ($q * 2 - 1) . "' id='input" . ($q * 2 - 1) . "' placeholder='Numerator' oninput='calculateResult($q)'>
                  </div>
                  <div class='col-md-4'>
                    <input type='number' step='any' class='form-control' name='input" . ($q * 2) . "' id='input" . ($q * 2) . "' placeholder='Denominator' oninput='calculateResult($q)' $readonly>
                  </div>
                  <div class='col-md-4'>
                    <input type='text' class='form-control' name='result$q' id='result$q' readonly placeholder='Result'>
                  </div>
                </div>
                <input type='hidden' name='out_come_id{$row['id_out_hwc']}' value='{$row['id_out_hwc']}'>
                <div class='mt-3 d-flex justify-content-between'>
                  " . ($q > 1 ? "<button type='button' class='btn btn-secondary btn-sm' onclick='showPreviousCard($q)'><i class='bi bi-arrow-left'></i> Back</button>" : "<div></div>") . "
                  " . ($isLast ? "<button type='submit' name='postsubmit3' class='btn btn-primary btn-sm'>Submit All</button>" : "<button type='button' class='btn btn-success btn-sm' onclick='showNextCard($q)'> Next <i class='bi bi-arrow-right'></i></button>") . "
                </div>
              </div>
            </div>";
                    }
                    echo "</form>";
                    if ($ftype == 3) {
                        echo "<script src='assets/calculationjs/departmentphc{$dept_id}.js?v=<?= time() ?'></script>";
                    } elseif ($ftype == 9) {
                        echo "<script src='assets/calculationjs/departmentaphc{$dept_id}.js?v=<?= time() ?'></script>";
                    } elseif ($ftype == 8 || $ftype == 4) {
                        echo "<script src='assets/calculationjs/departmenthwc2{$dept_id}.js?v=<?= time() ?'></script>";
                    } elseif ($ftype == 1) {
                        echo "<script src='assets/calculationjs/chc{$dept_id}.js?v=<?= time() ?'></script>";
                    } else {
                        echo "<script src='assets/calculationjs/department{$dept_id}.js?v=<?= time() ?'></script>";
                    }
                }
            }
        }

        if (isset($_POST['postsubmit3'])) {
            $dept_id = $_SESSION['dept_id1'];
            $date1 = $_SESSION['new_date1'];
            $Fa = $_SESSION['u_facilityid'];
            $p = $_SESSION['assperiod'];
            $ftype = $_SESSION['f_type_id'];

            $stmt = $con->prepare("SELECT id_out_hwc FROM out_come_dh WHERE out_come_dept = ? and out_come_hwc_factype= ?");
            $stmt->bind_param("ii", $dept_id, $ftype);
            $stmt->execute();
            $result = $stmt->get_result();

            $success = false;
            $errorMessages = [];
            $index = 0;

            while ($row = $result->fetch_assoc()) {
                $index++;
                $numKey = "input" . ($index * 2 - 1);
                $denKey = "input" . ($index * 2);
                $key = "result$index";
                $numerator = isset($_POST[$numKey]) ? floatval($_POST[$numKey]) : null;
                $denominator = isset($_POST[$denKey]) ? floatval($_POST[$denKey]) : null;
                if (isset($_POST[$key])) {
                    $value = $_POST[$key];
                    $stmtInsert = $con->prepare("CALL insert_outcome_values(?, ?, ?, ?, ?, ?,?,?)");
                    $stmtInsert->bind_param("idsiiidd", $row['id_out_hwc'], $value, $date1, $Fa, $dept_id, $p, $denominator, $numerator);
                    $success = $stmtInsert->execute();
                    if (!$success) {
                        $errorMessages[] = "Error inserting for Outcome ID: {$row['id_out_hwc']}";
                    }
                    $stmtInsert->close();
                } else {
                    $errorMessages[] = "Missing result for Outcome ID: {$row['id_out_hwc']}";
                }
            }
            $stmt->close();

            echo $success ? "<div class='alert alert-success'>Outcome Values inserted successfully!</div>"
                : "<div class='alert alert-danger'>There was an error while inserting data.</div>";

            if (!empty($errorMessages)) {
                echo "<div class='alert alert-danger'>" . implode("<br>", $errorMessages) . "</div>";
            }
        }
        ?>
    </div>
</div>

<!-- Department Modal -->
<div id="departmentModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="departmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form method="post">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="departmentModalLabel">Select Department</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
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
                    <input type="hidden" name="department_name" id="department_name_input" value="">

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Continue</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include("assets/head/f.php"); ?>
<script>
    $('#departmentSelect').change(function() {
        var deptName = $('#departmentSelect option:selected').data('name');
        $('#department_name_input').val(deptName);
    });
</script>
<script>
    var selectedMonth = "<?= $_SESSION['new_date1'] ?? date('Y-m') ?>";
</script>
<script>
    function showNextCard(current) {
    const inputNum = document.getElementById('input' + (current * 2 - 1));
    const inputDen = document.getElementById('input' + (current * 2));
    let isValid = true;

    // Check numerator
    if (!inputNum.value.trim() || isNaN(inputNum.value)) {
        inputNum.focus();
        inputNum.classList.add('is-invalid');
        isValid = false;
    } else {
        inputNum.classList.remove('is-invalid');
    }

    // Check denominator if not readonly
    if (!inputDen.hasAttribute('readonly') && (!inputDen.value.trim() || isNaN(inputDen.value))) {
        inputDen.focus();
        inputDen.classList.add('is-invalid');
        isValid = false;
    } else {
        inputDen.classList.remove('is-invalid');
    }

    if (!isValid) {
        const alertBox = document.createElement('div');
        alertBox.className = 'alert alert-danger mt-3';
        alertBox.innerText = 'Please enter valid numeric values before proceeding.';
        const card = document.getElementById('card' + current);
        const existingAlert = card.querySelector('.alert-danger');
        if (!existingAlert) {
            card.appendChild(alertBox);
            setTimeout(() => alertBox.remove(), 3000);
        }
        return;
    }

    document.getElementById('card' + current).style.display = 'none';
    const nextCard = document.getElementById('card' + (current + 1));
    if (nextCard) nextCard.style.display = 'block';
}


    function showPreviousCard(current) {
        document.getElementById('card' + current).style.display = 'none';
        const prevCard = document.getElementById('card' + (current - 1));
        if (prevCard) prevCard.style.display = 'block';
    }

    // Show department modal on load if needed
    <?php if ($showDeptModal): ?>
        window.addEventListener('load', function() {
            var deptModal = new bootstrap.Modal(document.getElementById('departmentModal'), {
                backdrop: 'static',
                keyboard: false
            });
            deptModal.show();
        });
    <?php endif; ?>
</script>