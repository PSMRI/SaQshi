<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$dept_id = $_SESSION['dept_id'];
$Fa = $_SESSION['u_facilityid'];
$p = $_SESSION['assperiod'];
$F = $_SESSION['f_type_id'];

$queryStr = "CALL moic_action_plan_view($Fa, $p, $dept_id)";
$_SESSION['q1'] = $queryStr;
$_SESSION['q'] = mysqli_query($con, $queryStr);
$query = $_SESSION['q'];

if ($query && $query->num_rows > 0): ?>

    <div class="card shadow-sm mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Action Plan</h5>
            <button class="btn btn-sm btn-success" onclick="exportToExcel('table4')">Export</button>
        </div>

        <div class="card-body p-2">
            <div class="table-responsive">
                <table class="table table-sm table-bordered" id="table4" style="border-color:#FF5733;">
                    <thead class="table-success text-center">
                        <tr>
                            <th colspan="10">Action Plan</th>
                        </tr>
                        <tr>
                            <th>Standard</th>
                            <th>Ref.</th>
                            <th>Mea. Element</th>
                            <th>Checkpoint</th>
                            <th>Ass. Method</th>
                            <th>Veri. Meth.</th>
                            <th>Action Plan</th>
                            <th>Compliance</th>
                            <th>Resp. Person</th>
                            <th>Due Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($query)): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['c_subtype_Reference_No_fk']) ?></td>
                                <td><?= htmlspecialchars($row['csqa_reference_id']) ?></td>
                                <td><?= htmlspecialchars($row['Measurable_Element']) ?></td>
                                <td><?= htmlspecialchars($row['Checkpoint']) ?></td>
                                <td><?= htmlspecialchars($row['Assessment_Method']) ?></td>
                                <td><?= htmlspecialchars($row['Means_of_Verification']) ?></td>
                                <td><?= htmlspecialchars($row['action_plan']) ?></td>
                                <td><?= htmlspecialchars($row['ass_compliance']) ?></td>
                                <td><?= htmlspecialchars($row['dept_res']) ?></td>
                                <td><?= htmlspecialchars($row['dept_res_date']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php else: ?>
    <div class="alert alert-warning mt-3" role="alert">
        <strong>No action plan found!</strong> Please check if data is entered for this facility.
    </div>
<?php 
endif;

// Always free result and move to next query
if (isset($query)) {
    mysqli_free_result($query);
}
$con->next_result();
?>

<script>
function exportToExcel(tableId, filename = 'Action_Plan_Report.xls') {
    const table = document.getElementById(tableId);
    if (!table) {
        alert("Table not found.");
        return;
    }
    const html = `
      <html xmlns:o="urn:schemas-microsoft-com:office:office" 
            xmlns:x="urn:schemas-microsoft-com:office:excel" 
            xmlns="http://www.w3.org/TR/REC-html40">
      <head><meta charset="utf-8"></head>
      <body>${table.outerHTML}</body></html>`;
    const blob = new Blob([html], { type: "application/vnd.ms-excel" });
    const url = URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = filename;
    a.click();
    URL.revokeObjectURL(url);
}
</script>
