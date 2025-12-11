<?php if (!isset($row)) return; ?>

<div class="card shadow-sm border-0 mb-4 modern-card">

    <!-- ======================================================
         SUMMARY TABLE (TOP SECTION)
    ====================================================== -->
    <div class="table-responsive mb-3">
        <table class="table table-bordered table-sm align-middle mb-0 summary-table">
            <thead class="bg-success text-white small">
            <tr>
                <th>Standard</th>
                <th>Res Date</th>
                <th>Ref. No.</th>
                <th>Comp.</th>
                <th>Dept. Remarks</th>
                <th>Priority</th>
                <th>Asse. Method</th>
            </tr>
            </thead>
            <tbody class="small">
            <tr>
                <td><?= $row['c_subtype_Reference_No_fk']; ?></td>
                <td><?= $row['dept_res_date']; ?></td>
                <td><?= $row['csqa_reference_id'] ?? 'N/A'; ?></td>
                <td><?= $row['ass_compliance']; ?></td>
                <td><?= $row['moic_remarcks']; ?></td>
                <td>
                    <span class="badge 
                        <?= ($row['Priority1'] == 'High') ? 'bg-danger' :
                            (($row['Priority1'] == 'Medium') ? 'bg-warning text-dark' : 'bg-success'); ?>">
                        <?= $row['Priority1']; ?>
                    </span>
                </td>
                <td><?= $row['Assessment_Method'] ?? 'N/A'; ?></td>
            </tr>
            </tbody>
        </table>
    </div>

    <!-- ======================================================
         DETAILS TABLE (SECOND SECTION)
    ====================================================== -->
    <div class="table-responsive mb-3">
        <table class="table table-bordered table-sm align-middle mb-0 details-table">
            <thead class="bg-light small fw-bold text-success">
            <tr>
                <th>Means of Verification</th>
                <th>Dept. Action Plan</th>
                <th>Measurable Element</th>
                <th>Checkpoint</th>
            </tr>
            </thead>
            <tbody class="small">
            <tr>
                <td><?= $row['Means_of_Verification'] ?? 'N/A'; ?></td>
                <td><?= $row['dept_action_plan']; ?></td>
                <td><?= $row['Measurable_Element']; ?></td>
                <td><?= $row['Checkpoint']; ?></td>
            </tr>
            </tbody>
        </table>
    </div>

    <!-- ======================================================
         ACTION AREA (INPUTS + BUTTONS)
    ====================================================== -->
    <div class="card-body">

        <form method="post" action="#" id="actionForm">

            <div class="row g-4 mb-3">

                <!-- Action Taken -->
                <div class="col-md-6">
                    <label class="form-label text-success fw-bold" style="font-size: 1rem;">
                        Action Taken
                    </label>

                    <textarea class="form-control form-control-sm"
                              rows="2"
                              name="Action_Taken"
                              id="Action_Taken"
                              placeholder="Describe action taken..."></textarea>
                </div>

                <!-- Compliance -->
                <div class="col-md-6">
                    <label class="form-label text-success fw-bold" style="font-size: 1rem;">
                        Update Compliance
                    </label>

                    <select class="mb-3 form-control" 
                            name="f" id="compliance"
                            style="max-width: 220px;">
                        <option value="">Select</option>
                        <option value="1">1 - Partial</option>
                        <option value="2">2 - Achieved</option>
                    </select>

                    <!-- Hidden Fields -->
                    <input type="hidden" name="record_index" value="<?= $_SESSION['current_index']; ?>">
                    <input type="hidden" name="csqa_id" value="<?= $row['ass_id']; ?>">
                </div>

            </div>

            <!-- ================= BUTTONS ROW ================= -->
            <div class="row mt-2">
                <div class="col-md-12 d-flex gap-2">

                    <!-- Skip Button (Light Red) -->
                    <button type="submit" name="skip" id="skipBtn"
                            class="btn btn-light-danger w-50 py-2 fw-semibold">
                        Skip <i class="bi bi-skip-forward ms-1"></i>
                    </button>

                    <!-- Save Button (Green) -->
                    <button type="submit" name="postsubmit2" id="saveBtn"
                            class="btn btn-success w-50 py-2 fw-semibold">
                        Save & Next <i class="bi bi-arrow-right-circle ms-1"></i>
                    </button>

                </div>
            </div>

        </form>

    </div>
</div>

<!-- ======================================================
     VALIDATION SCRIPT
====================================================== -->
<script>
document.getElementById("skipBtn").addEventListener("click", function() {
    document.getElementById("Action_Taken").removeAttribute("required");
    document.getElementById("compliance").removeAttribute("required");
});

document.getElementById("saveBtn").addEventListener("click", function() {
    document.getElementById("Action_Taken").setAttribute("required", "required");
    document.getElementById("compliance").setAttribute("required", "required");
});
</script>

<style>
/* Light Red Skip Button */
.btn-light-danger {
    background-color: #f8d7da;
    color: #842029;
    border: 1px solid #f5c2c7;
}
.btn-light-danger:hover {
    background-color: #f1b0b7;
    color: #6a1a21;
}

/* Details Table look */
.details-table th {
    text-transform: uppercase;
    font-size: 0.75rem;
}

/* Table text alignment */
.summary-table td,
.details-table td {
    vertical-align: top;
    white-space: normal;
}

/* Mobile Responsive Tweaks */
@media (max-width: 768px) {
    .summary-table th,
    .summary-table td,
    .details-table th,
    .details-table td {
        font-size: 0.75rem;
        padding: 4px;
    }
}
</style>
<?php include("assets/head/f.php"); ?>