<?php
include("assets/head/h.php");

$msg = "";

// Capture selections
$selected_factype = $_POST['factype_id'] ?? "";
$selected_dept    = $_POST['dept_id'] ?? "";

// --------------------------------------------
// HANDLE SAVE ALL (ONLY UPDATE — NO INSERT)
// --------------------------------------------
if (isset($_POST['save_all']) && isset($_POST['indicator_ids'])) {

    foreach ($_POST['indicator_ids'] as $id) {

        $id = intval($id);
        $source = trim($_POST['source_'.$id] ?? "");

        if ($source == "") continue; // skip empty entries

        // UPDATE only — NO INSERT
        $stmt = $con->prepare("UPDATE out_come_dh SET out_come_source=? WHERE id_out_hwc=?");
        $stmt->bind_param("si", $source, $id);
        $stmt->execute();
        $stmt->close();
    }

    $msg = "<div class='alert alert-success'>All outcomes updated successfully!</div>";
}

// --------------------------------------------
// Load Facility Types
// --------------------------------------------
$factypes = $con->query("SELECT fac_type_id, facilities_type FROM facilities_type ORDER BY facilities_type");

// --------------------------------------------
// Load Departments After Facility Type Selection
// --------------------------------------------
$dept_list = [];
if ($selected_factype != "") {

    $q = $con->prepare("
        SELECT DISTINCT 
            a.fac_dept_id_fk AS dept_id,
            b.dept_name
        FROM concern_subtype_chklist a
        JOIN fac_department b ON a.fac_dept_id_fk = b.fac_dept_id
        WHERE a.fac_type_id_fk = ?
          AND b.active_status = 1
        ORDER BY b.dept_name
    ");
    $q->bind_param("i", $selected_factype);
    $q->execute();
    $dept_list = $q->get_result()->fetch_all(MYSQLI_ASSOC);
    $q->close();
}

// --------------------------------------------
// Load Outcome Indicators for Selected Department
// --------------------------------------------
$outcomes = [];
if ($selected_dept != "") {

    $q = $con->prepare("SELECT * FROM out_come_dh WHERE out_come_dept = ? ORDER BY id_out_hwc");
    $q->bind_param("i", $selected_dept);
    $q->execute();
    $outcomes = $q->get_result()->fetch_all(MYSQLI_ASSOC);
    $q->close();
}
?>

<style>
.card { 
    border-radius: 10px; 
    box-shadow: 0 3px 12px rgba(0,0,0,.08); 
}

.table-smaller td, 
.table-smaller th {
    padding: 6px 8px !important;
    font-size: 0.80rem !important;
    vertical-align: top;
}

.table-smaller textarea {
    height: 55px;
    font-size: 0.78rem;
    resize: vertical;
}

.table thead th {
    background: #f8f9fa;
    font-weight: 600;
}

.table tbody tr:hover {
    background: #f6faff;
}

label {
    font-size: 0.85rem;
    font-weight: 600;
}
</style>

<div class="pcoded-main-container mt-3">
<div class="pcoded-content">

<h5 class="text-primary fw-bold mb-3">Outcome Source Entry (Bulk Update)</h5>

<?= $msg ?>

<div class="card">
<div class="card-body">

<form method="POST">

    <!-- Facility Type -->
    <div class="form-group mb-3">
        <label>Facility Type</label>
        <select name="factype_id" class="form-control form-control-sm" onchange="this.form.submit()">
            <option value="">Select Facility Type</option>

            <?php while($ft = $factypes->fetch_assoc()): ?>
                <option value="<?= $ft['fac_type_id'] ?>"
                    <?= ($selected_factype == $ft['fac_type_id']) ? "selected" : "" ?>>
                    <?= $ft['facilities_type'] ?>
                </option>
            <?php endwhile; ?>

        </select>
    </div>

    <!-- Department -->
    <?php if ($selected_factype != ""): ?>
    <div class="form-group mb-3">
        <label>Department</label>
        <select name="dept_id" class="form-control form-control-sm" onchange="this.form.submit()">
            <option value="">Select Department</option>

            <?php foreach ($dept_list as $d): ?>
                <option value="<?= $d['dept_id'] ?>"
                    <?= ($selected_dept == $d['dept_id']) ? "selected" : "" ?>>
                    <?= $d['dept_name'] ?>
                </option>
            <?php endforeach; ?>

        </select>
    </div>
    <?php endif; ?>

    <!-- OUTCOME LIST -->
    <?php if ($selected_dept != ""): ?>

    <h6 class="fw-bold mb-2">Indicators for this Department</h6>

    <?php if (count($outcomes) == 0): ?>
        <div class="alert alert-warning">No indicators found for this department.</div>
    <?php else: ?>

        <div class="table-responsive">
            <table class="table table-bordered table-sm table-smaller">
                <thead>
                    <tr>
                        <th style="width: 45%">Indicator</th> 
                        <th style="width: 55%">Source Entry</th>
                    </tr>
                </thead>
                <tbody>

                    <?php foreach ($outcomes as $o): ?>
                        <tr>
                            <td>
                                <textarea class="form-control" readonly><?= $o['out_come_hwcindi'] ?></textarea>
                                <input type="hidden" name="indicator_ids[]" value="<?= $o['id_out_hwc'] ?>">
                            </td>                          
                            <td>
                                <textarea name="source_<?= $o['id_out_hwc'] ?>" 
                                          class="form-control"
                                          placeholder="Enter source methodology..."><?= $o['out_come_source'] ?></textarea>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                </tbody>
            </table>
        </div>

        <button class="btn btn-success btn-sm mt-2" name="save_all">
            ✔ Save All
        </button>

    <?php endif; ?>
    <?php endif; ?>

</form>

</div>
</div>

</div>
</div>

<?php include("assets/head/f.php"); ?>
