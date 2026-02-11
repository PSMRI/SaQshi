<?php
/* ===============================
   Handle Department Selection
================================ */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['department_id']) && !empty($_POST['department_id'])) {
    session_start();
    $_SESSION['dept_id1']   = (int)$_POST['department_id'];
    $_SESSION['dept_name1'] = $_POST['department_name'];
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

include("assets/head/h.php"); // session + DB

$showDeptModal = empty($_SESSION['dept_id1']);
$dept_name     = $_SESSION['dept_name1'] ?? '';
?>

<div class="pcoded-main-container">
<div class="pcoded-content">

<!-- ================= HEADER ================= -->
<div class="pagetitle mb-2">
    <h5 class="fw-bold text-primary mb-1">
        <i class="bi bi-person-badge-fill me-2"></i>
        Outcome Indicator for <?= htmlspecialchars($dept_name) ?>
        <button class="btn btn-sm btn-link text-warning ms-2"
                data-toggle="modal" data-target="#departmentModal">
            Change Department
        </button>
    </h5>
</div>

<!-- ================= MONTH FORM ================= -->
<div class="card">
<div class="card-body">
<form method="post">
    <div class="row align-items-end">
        <div class="col-auto">
            <label class="form-label">Select Month</label>
            <input type="month" class="form-control"
                   name="date1"
                   value="<?= $_POST['date1'] ?? date('Y-m') ?>"
                   min="<?= date('Y-m', strtotime('-6 months')) ?>"
                   max="<?= date('Y-m') ?>" required>
        </div>
        <div class="col-auto">
            <button class="btn btn-primary" name="submit1">Fill Data</button>
        </div>
    </div>
</form>
</div>
</div>

<?php
/* ===============================
   LOAD INDICATORS
================================ */
if (isset($_POST['submit1'])) {

    $new_date1 = date('Y-m', strtotime($_POST['date1']));
    $_SESSION['new_date1'] = $new_date1;

    if ($new_date1 === '1970-01') {
        echo "<div class='alert alert-danger'>Invalid month selected.</div>";
        exit;
    }

    $fac_id  = $_SESSION['u_facilityid'];
    $dept_id = $_SESSION['dept_id1'];
    $ftype   = $_SESSION['f_type_id'];

    /* ---- Expected count ---- */
    $stmt = $con->prepare("
        SELECT COUNT(*) AS expected
        FROM out_come_dh
        WHERE out_come_hwc_factype = ?
          AND out_come_dept = ?
    ");
    $stmt->bind_param("ii", $ftype, $dept_id);
    $stmt->execute();
    $expected = (int)$stmt->get_result()->fetch_assoc()['expected'];
    $stmt->close();

    /* ---- Filled count ---- */
    $stmt = $con->prepare("
        SELECT COUNT(id_out_hwc) AS filled
        FROM outcome_values_in
        WHERE month_in = ?
          AND institute_id = ?
          AND dept_id = ?
    ");
    $stmt->bind_param("sii", $new_date1, $fac_id, $dept_id);
    $stmt->execute();
    $filled = (int)$stmt->get_result()->fetch_assoc()['filled'];
    $stmt->close();

    /* ---- FINAL DECISION ---- */
    if ($filled === $expected) {
        echo "<div class='alert alert-danger'>
                Outcome values for <b>$new_date1</b> are already completed.
              </div>";
        exit;
    }

    if ($filled > 0) {
        echo "<div class='alert alert-warning'>
                Partial data found for <b>$new_date1</b>.
                Completed: $filled / $expected
              </div>";
    }

    /* ---- Load indicators ---- */
    $q3 = $con->prepare("
        SELECT * FROM out_come_dh
        WHERE out_come_hwc_factype = ?
          AND out_come_dept = ?
    ");
    $q3->bind_param("ii", $ftype, $dept_id);
    $q3->execute();
    $res = $q3->get_result();

    $q = 0;
    $total = $res->num_rows;

    echo "<form method='post'>";

    while ($row = $res->fetch_assoc()) {
        $q++;
        $readonly = ($row['deno'] === 'N/A') ? 'readonly' : '';
        $isLast = ($q === $total);
        ?>

<div class="card mb-3" id="card<?= $q ?>" style="<?= $q > 1 ? 'display:none;' : '' ?>">
<div class="card-body">

<h6 class="text-primary">
    <i class="bi bi-bar-chart-fill me-2"></i>
    <?= htmlspecialchars($row['out_come_hwcindi']) ?>
</h6>

<div class="alert alert-info small">
    Expected → Num: <b><?= $row['num'] ?></b>,
    Den: <b><?= $row['deno'] ?></b>
</div>

<div class="row g-2">
    <div class="col-md-4">
        <input type="number" step="any"
               class="form-control"
               name="input<?= $q*2-1 ?>"
               id="input<?= $q*2-1 ?>"
               placeholder="Numerator"
               oninput="calculateResult(<?= $q ?>)">
    </div>

    <div class="col-md-4">
        <input type="number" step="any"
               class="form-control"
               name="input<?= $q*2 ?>"
               id="input<?= $q*2 ?>"
               placeholder="Denominator"
               oninput="calculateResult(<?= $q ?>)" <?= $readonly ?>>
    </div>

    <div class="col-md-4">
        <input class="form-control"
               name="result<?= $q ?>"
               id="result<?= $q ?>" readonly>
    </div>
</div>

<div class="mt-3 d-flex justify-content-between">
    <?= $q > 1
        ? "<button type='button' class='btn btn-secondary btn-sm' onclick='showPreviousCard($q)'>Back</button>"
        : "<div></div>" ?>

    <?= $isLast
        ? "<button class='btn btn-primary btn-sm' name='postsubmit3'>Submit All</button>"
        : "<button type='button' class='btn btn-success btn-sm' onclick='showNextCard($q)'>Next</button>" ?>
</div>

</div>
</div>

<?php
    }
    echo "</form>";
    $q3->close();
}

/* ===============================
   INSERT DATA
================================ */
if (isset($_POST['postsubmit3'])) {

    $dept_id = $_SESSION['dept_id1'];
    $date1   = $_SESSION['new_date1'];
    $Fa      = $_SESSION['u_facilityid'];
    $p       = $_SESSION['assperiod'];
    $ftype   = $_SESSION['f_type_id'];

    $stmt = $con->prepare("
        SELECT id_out_hwc
        FROM out_come_dh
        WHERE out_come_dept = ?
          AND out_come_hwc_factype = ?
    ");
    $stmt->bind_param("ii", $dept_id, $ftype);
    $stmt->execute();
    $res = $stmt->get_result();

    $i = 0;
    while ($r = $res->fetch_assoc()) {
        $i++;
        $num = $_POST["input".($i*2-1)];
        $den = $_POST["input".($i*2)];
        $val = $_POST["result$i"];

        $ins = $con->prepare("CALL insert_outcome_values(?,?,?,?,?,?,?,?)");
        $ins->bind_param(
            "idsiiidd",
            $r['id_out_hwc'], $val, $date1, $Fa,
            $dept_id, $p, $den, $num
        );
        $ins->execute();
        $ins->close();
    }

    echo "<div class='alert alert-success'>Outcome values saved successfully.</div>";
}
?>

</div>
</div>

<?php include("assets/head/f.php"); ?>

<script>
$('#departmentSelect').change(function () {
    $('#department_name_input').val(
        $('#departmentSelect option:selected').data('name')
    );
});
</script>

<script>
<?php if ($showDeptModal): ?>
window.addEventListener('load', () => {
    new bootstrap.Modal(
        document.getElementById('departmentModal'),
        {backdrop:'static', keyboard:false}
    ).show();
});
<?php endif; ?>
</script>
