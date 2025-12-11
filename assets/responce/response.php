<?php
// Database connection
include(__DIR__ . "/../../assets/conn/db.php");
include(__DIR__ . "/../../assets/conn/session.php");
// Check if POST variable is set
if (!empty($_POST["cid"])) {
   // include(__DIR__ . "/../../assessment.php");
    // Get values from session and post
    $cid = $_POST['cid'];
    $assessmentPeriod = $_SESSION['assperiod'] ?? 0;
    $facilityId = $_SESSION['u_facilityid'] ?? 0;
    $deptId = $_SESSION['dept_id1'] ?? 0;
    $facilityType = $_SESSION['f_type_id'] ?? 0;

    // Build query
    $query = "CALL get_Standards_count_load($cid, $facilityType, $facilityId, $deptId, $assessmentPeriod)";
    $result = mysqli_query($con, $query);

    if ($result) {

        if ($result->num_rows > 0) {
            // Populate dropdown
            while ($row = mysqli_fetch_assoc($result)) {
                echo '<option value="' . $row['c_subtype_id'] . '">' . htmlspecialchars($row['area_of_con_subtypedeatils']) . '</option>';
            }
        } else {
            echo '<option value="0">-Checkpoint completed-</option>';
        }

        mysqli_free_result($result);
        $con->next_result();

    } else {
        echo '<option value="0">Error loading standards</option>';
        error_log("Query Error: " . mysqli_error($con));
    }
} else {
    echo '<option value="0">No Concern Selected</option>';
}
?>
