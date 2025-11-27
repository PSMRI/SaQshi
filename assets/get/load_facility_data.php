<?php
include(__DIR__ . "/../../assets/conn/db.php");
include(__DIR__ . "/../../assets/conn/session.php");

$dept_id = $_POST['dept_id'] ?? '';
$fac_type_id = $_POST['fac_type_id'] ?? '';
$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$limit = 5;
$offset = ($page - 1) * $limit;

if ($dept_id && $fac_type_id) {
    // Count total records
    $count_query = "
        SELECT COUNT(*) as total
        FROM department_wise_state_dash
        WHERE fac_dept_id_fk = ? AND Health_facilty_type = ?
    ";
    $count_stmt = $con->prepare($count_query);
    $count_stmt->bind_param("ii", $dept_id, $fac_type_id);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $total_rows = $count_result->fetch_assoc()['total'];
    $total_pages = ceil($total_rows / $limit);
    $count_stmt->close();

    // Fetch paginated records
    $query = "
        SELECT 
        Dist_Name,
        Block_Name,
            fac_name,
            department_name,          
            marks_obtained,
            total_marks,
            percentage,
            one, two, zero, non, total,facid,fac_dept_id_fk,ass_period_id,Health_facilty_type
        FROM department_wise_state_dash
        WHERE fac_dept_id_fk = ? AND Health_facilty_type = ?
        LIMIT ? OFFSET ?
    ";
    $stmt = $con->prepare($query);
    $stmt->bind_param("iiii", $dept_id, $fac_type_id, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo '<div class="d-flex justify-content-end mb-2">
        <form method="POST" action="assets/get/export_facility_excel.php" target="_blank">
            <input type="hidden" name="dept_id" value="' . htmlspecialchars($dept_id) . '">
            <input type="hidden" name="fac_type_id" value="' . htmlspecialchars($fac_type_id) . '">
            <button type="submit" class="btn btn-success btn-sm">
                <i class="fa fa-download"></i> Download Excel
            </button>
        </form>
      </div>';

        echo '<div class="table-responsive">
                <table class="table datatable table-bordered table-striped table-hover small">
                    <thead class="thead-dark">
                        <tr>
                        <th>Dist</th>
                           <th>Block</th>
                            <th>Facility Name</th>
                            <th>Department</th>
                            <th>Marks Obtained</th>
                            <th>Total Marks</th>
                            <th>Percentage</th>
                            <th>Compliance</th>
                            <th>Total Indicators</th>
                             <th>📥</th>
                        </tr>
                    </thead>
                    <tbody>';

        while ($row = $result->fetch_assoc()) {
            $percentage = (float)$row['percentage'];
            $badgeClass = 'badge-danger';

            if ($percentage > 90) {
                $badgeClass = 'badge-success';
            } elseif ($percentage >= 60) {
                $badgeClass = 'badge-warning';
            }

           echo '<tr>
    <td>' . htmlspecialchars($row['Dist_Name']) . '</td>
    <td>' . htmlspecialchars($row['Block_Name']) . '</td>
    <td>' . htmlspecialchars($row['fac_name']) . '</td>
    <td>' . htmlspecialchars($row['department_name']) . '</td>
    <td>' . $row['marks_obtained'] . '</td>
    <td>' . $row['total_marks'] . '</td>
    <td><span class="badge ' . $badgeClass . '">' . $percentage . '%</span></td>
    <td>Partial: ' . $row['one'] . ', Full: ' . $row['two'] .  ', Non: ' . $row['zero'] . ', Not assessed: ' . $row['non'] . '</td>
    <td>' . $row['total'] . '</td>
    <td>
        <a href="assets/export/export_dist_dash_comp_dept.php?facid=' . $row["facid"] . '&deptid=' . $row["fac_dept_id_fk"] . '&accid=' . $row["ass_period_id"] . '" target="_blank">
            <i class="bi bi-arrow-down-circle-fill text-primary"></i>
        </a>
    </td>
</tr>';

        }

        echo '</tbody></table></div>';

        // Custom pagination UI
        echo '<nav><ul class="pagination justify-content-center">';

        // First & Previous
        if ($page > 1) {
            echo '<li class="page-item">
                    <a class="page-link page-link-load" href="#" data-page="1">&laquo;</a>
                  </li>';
            echo '<li class="page-item">
                    <a class="page-link page-link-load" href="#" data-page="' . ($page - 1) . '">&lt;</a>
                  </li>';
        } else {
            echo '<li class="page-item disabled"><span class="page-link">&laquo;</span></li>';
            echo '<li class="page-item disabled"><span class="page-link">&lt;</span></li>';
        }

        // Page info
        echo '<li class="page-item disabled"><span class="page-link">Page ' . $page . ' of ' . $total_pages . '</span></li>';

        // Next & Last
        if ($page < $total_pages) {
            echo '<li class="page-item">
                    <a class="page-link page-link-load" href="#" data-page="' . ($page + 1) . '">&gt;</a>
                  </li>';
            echo '<li class="page-item">
                    <a class="page-link page-link-load" href="#" data-page="' . $total_pages . '">&raquo;</a>
                  </li>';
        } else {
            echo '<li class="page-item disabled"><span class="page-link">&gt;</span></li>';
            echo '<li class="page-item disabled"><span class="page-link">&raquo;</span></li>';
        }

        echo '</ul></nav>';
    } else {
        echo '<div class="alert alert-warning">No records found for selected facility type and department.</div>';
    }

    $stmt->close();
} else {
    echo '<div class="alert alert-danger">Invalid request. Please select both facility type and department.</div>';
}
?>
<script>
$(document).on('click', '.page-link-load', function(e) {
    e.preventDefault();
    const page = $(this).data('page');
    const dept_id = $('#departmentDropdown').val();
    const fac_type_id = $('#facilityTypeDropdown').val();

    if (dept_id && fac_type_id) {
        $.ajax({
            url: 'assets/get/load_facility_data.php',
            type: 'POST',
            data: {
                dept_id: dept_id,
                fac_type_id: fac_type_id,
                page: page
            },
            success: function(response) {
                $('#dataContainer').html(response);
            }
        });
    }
});
</script>
