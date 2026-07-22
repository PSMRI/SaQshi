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
                <i class="bi bi-person-badge-fill me-2"></i>
                Outcome Indicator for <?= htmlspecialchars($dept_name); ?>
                <button type="button"
                    class="btn btn-sm btn-link text-warning ms-2"
                    data-toggle="modal"
                    data-target="#departmentModal">
                    Change Department
                </button>
            </h5>
        </div>

        <div class="card">
            <div class="card-body">
                <form method="post">
                    <?= csrf(); ?>
                    <div class="row align-items-end">
                        <div class="col-auto">
                            <label class="form-label">Select Month</label>
                            <input type="month"
                                class="form-control"
                                name="date1"
                                value="<?= htmlspecialchars((string)($_POST['date1'] ?? date('Y-m')), ENT_QUOTES, 'UTF-8'); ?>"
                                required>
                        </div>
                        <div class="col-auto">
                            <button type="submit"
                                name="submit1"
                                class="btn btn-primary">
                                Fill Data
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <?php
        $dataExists = false;
        $existingData = [];

        if (isset($_POST['submit1'])) {

            $new_date1 = date('Y-m', strtotime($_POST['date1']));
            $_SESSION['new_date1'] = $new_date1;

            $fac_id = $_SESSION['u_facilityid'];
            $dept_id = $_SESSION['dept_id1'];
            $ftype = $_SESSION['f_type_id'];

            // Check if data exists
            $stmt1 = $con->prepare("
        SELECT id_out_hwc, neu_val, deno_val, values_in
        FROM outcome_values_in
        WHERE month_in=? AND institute_id=? AND dept_id=?
    ");
            $stmt1->bind_param("sii", $new_date1, $fac_id, $dept_id);
            $stmt1->execute();
            $resExist = $stmt1->get_result();

            while ($rowExist = $resExist->fetch_assoc()) {
                $dataExists = true;
                $existingData[$rowExist['id_out_hwc']] = $rowExist;
            }

            if ($dataExists) {
                echo "<div class='alert alert-warning'>
                Data already recorded for <b>$new_date1</b>. You can edit and update.
              </div>";
            }

            $q3 = mysqli_query($con, "
        SELECT * FROM out_come_dh
        WHERE out_come_hwc_factype = $ftype
        AND out_come_dept = $dept_id
    ");

            $q = 0;
            $total = mysqli_num_rows($q3);

            echo "<form method='post'>";
            echo csrf();
            while ($row = mysqli_fetch_array($q3)) {

                $q++;

                $existNum = $existingData[$row['id_out_hwc']]['neu_val'] ?? '';
                $existDen = $existingData[$row['id_out_hwc']]['deno_val'] ?? '';
                $existRes = $existingData[$row['id_out_hwc']]['values_in'] ?? '';

                $readonly = ($row['deno'] === 'N/A') ? 'readonly' : '';
                $isLast = ($q == $total);

                echo "
        <div class='card mb-3' id='card$q' " . ($q > 1 ? "style=display:none" : "") . ">
        <div class='card-body'>
          <h6 class='card-title text-primary'><i class='bi bi-bar-chart-fill me-2'></i>Outcome Indicator: {$row['out_come_hwcindi']}</h6>
                <div class='alert alert-info py-2 px-3 small d-flex align-items-center'>
                  <i class='bi bi-info-circle-fill me-2'></i>
                  <div><strong>Expected:</strong> Numerator: <b>{$row['num']}</b>, Denominator: <b>{$row['deno']}</b></div>
                </div>

        <div class='row g-2'>
        <div class='col-md-4'>
        <input type='number'
               step='any'
               class='form-control'
               name='input" . ($q * 2 - 1) . "'
               id='input" . ($q * 2 - 1) . "'
               value='$existNum'' placeholder='Numerator'
               oninput='calculateResult($q)'>
        </div>

        <div class='col-md-4'>
        <input type='number'
               step='any'
               class='form-control'
               name='input" . ($q * 2) . "'
               id='input" . ($q * 2) . "'
               value='$existDen' ' placeholder='Denominator'
               $readonly
               oninput='calculateResult($q)'>
        </div>

        <div class='col-md-4'>
        <input type='text'
               class='form-control'
               name='result$q'
               id='result$q'
               value='$existRes'
               readonly>
        </div>
        </div>

        <div class='mt-3 d-flex justify-content-between'>";

                if ($q > 1) {
                    echo "<button type='button'
                    class='btn btn-secondary btn-sm'
                    onclick='showPreviousCard($q)'>Back</button>";
                } else {
                    echo "<div></div>";
                }

                if ($isLast) {
                    echo "<button type='submit'
                    name='postsubmit3'
                    class='btn btn-primary btn-sm'>
                    " . ($dataExists ? "Update All" : "Submit All") . "
                  </button>";
                } else {
                    echo "<button type='button'
                    class='btn btn-success btn-sm'
                    onclick='showNextCard($q)'>Next</button>";
                }

                echo "</div></div></div>";
            }

            echo "</form>";
            if ($ftype == 3) {
                echo "<script src='assets/calculationjs/departmentphc{$dept_id}.js?v=" . time() . "'></script>";
            } elseif ($ftype == 9) {
                echo "<script src='assets/calculationjs/departmentaphc{$dept_id}.js?v=" . time() . "'></script>";
            } elseif ($ftype == 8 || $ftype == 4) {
                echo "<script src='assets/calculationjs/departmenthwc2{$dept_id}.js?v=" . time() . "'></script>";
            } elseif ($ftype == 1) {
                echo "<script src='assets/calculationjs/chc{$dept_id}.js?v=" . time() . "'></script>";
            } else {
                echo "<script src='assets/calculationjs/department{$dept_id}.js?v=" . time() . "'></script>";
            }
        }
        ?>

        <?php
        /* =========================
   INSERT OR UPDATE
========================= */
        if (isset($_POST['postsubmit3'])) {

            $dept_id = $_SESSION['dept_id1'];
            $date1 = $_SESSION['new_date1'];
            $Fa = $_SESSION['u_facilityid'];
            $p = $_SESSION['assperiod'];
            $ftype = $_SESSION['f_type_id'];

            $stmt = $con->prepare("
        SELECT id_out_hwc FROM out_come_dh
        WHERE out_come_dept=? AND out_come_hwc_factype=?
    ");
            $stmt->bind_param("ii", $dept_id, $ftype);
            $stmt->execute();
            $result = $stmt->get_result();

            $index = 0;
            $success = true;

            while ($row = $result->fetch_assoc()) {

                $index++;

                $numerator = floatval($_POST["input" . ($index * 2 - 1)] ?? 0);
                $denominator = floatval($_POST["input" . ($index * 2)] ?? 0);
                $value = $_POST["result$index"] ?? 0;

                // Check if record exists
                $check = $con->prepare("
            SELECT id_out_hwc FROM outcome_values_in
            WHERE id_out_hwc=? AND month_in=? AND institute_id=? AND dept_id=?
        ");
                $check->bind_param("isii", $row['id_out_hwc'], $date1, $Fa, $dept_id);
                $check->execute();
                $exists = $check->get_result()->num_rows > 0;

                if ($exists) {

                    $stmtUpdate = $con->prepare("
                UPDATE outcome_values_in
                SET neu_val=?, deno_val=?, values_in=?
                WHERE id_out_hwc=? AND month_in=? AND institute_id=? AND dept_id=?
            ");
                    $stmtUpdate->bind_param(
                        "dddissi",
                        $numerator,
                        $denominator,
                        $value,
                        $row['id_out_hwc'],
                        $date1,
                        $Fa,
                        $dept_id
                    );
                    $stmtUpdate->execute();
                    $stmtUpdate->close();
                } else {

                    $stmtInsert = $con->prepare("CALL insert_outcome_values(?, ?, ?, ?, ?, ?,?,?)");
                    $stmtInsert->bind_param(
                        "idsiiidd",
                        $row['id_out_hwc'],
                        $value,
                        $date1,
                        $Fa,
                        $dept_id,
                        $p,
                        $denominator,
                        $numerator
                    );
                    $stmtInsert->execute();
                    $stmtInsert->close();
                }
            }

            echo "<div class='alert alert-success'>Data Saved Successfully</div>";
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
        $('#departmentModal').modal({
            backdrop: 'static',
            keyboard: false
        });
        $('#departmentModal').modal('show');
    <?php endif; ?>
</script>
<script>
    document.addEventListener("DOMContentLoaded", function() {

        // Target ONLY the outcome form (the one with postsubmit3 button)
        const outcomeForm = document.querySelector("form[action='']") ||
            document.querySelectorAll("form")[1];

        if (!outcomeForm) return;

        outcomeForm.addEventListener("keydown", function(e) {

            if (e.key === "Enter") {

                const activeElement = document.activeElement;

                // Prevent submit
                e.preventDefault();

                // Find current card
                const currentCard = activeElement.closest(".card");
                if (!currentCard) return;

                // Find next button inside that card
                const nextBtn = currentCard.querySelector(".btn-success");

                if (nextBtn) {
                    nextBtn.click();
                }
            }

        });

    });
</script>


<?php include("assets/head/f.php"); ?>
