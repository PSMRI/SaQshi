<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
include(__DIR__ . "/../../assets/conn/db.php");
include(__DIR__ . "/../../assets/conn/session.php");

if (isset($_POST['fac_type_id'])) {
    $fac_type_id = intval($_POST['fac_type_id']);

    // Get department IDs used by facilities of that type
    $query = "SELECT DISTINCT c.fac_dept_id_fk, d.dept_name
FROM concern_subtype_chklist AS c
JOIN fac_department AS d ON d.fac_dept_id = c.fac_dept_id_fk
WHERE c.fac_type_id_fk= $fac_type_id";

    $result = mysqli_query($con, $query);

    $dept_ids = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $dept_ids[] = $row['fac_dept_id_fk'];
    }

    if (!empty($dept_ids)) {
        $dept_id_list = implode(",", array_map('intval', $dept_ids));

        // Now fetch matching departments
        $deptQuery = "SELECT fac_dept_id, dept_name 
                      FROM fac_department 
                      WHERE active_status = 1 
                      AND fac_dept_id IN ($dept_id_list)
                      ORDER BY dept_name";

        $deptResult = mysqli_query($con, $deptQuery);

        echo '<option value="">-- Select Department --</option>';
        while ($row = mysqli_fetch_assoc($deptResult)) {
            echo '<option value="' . $row['fac_dept_id'] . '">' . $row['dept_name'] . '</option>';
        }
    } else {
        echo '<option value="">No departments found</option>';
    }
}
?>
