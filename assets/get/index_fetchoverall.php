<?php
include(__DIR__ . "/../../assets/conn/db.php");
include(__DIR__ . "/../../assets/conn/session.php");

// Fetch session values
$Fa1 = $_SESSION['u_facilityid'];
$dept_id1 = $_SESSION['dept_id1'];
$p1 = $_SESSION['assperiod'];
$fat1 = $_SESSION['f_type_id'];

// Query to fetch overall progress data
$tablequery1 = "CALL count_zero($Fa1, $dept_id1, $p1, $fat1)";
$q5 = mysqli_query($con, $tablequery1);

// Prepare the response data
$data = [];
if ($row = mysqli_fetch_array($q5)) {
    $data = [
        'non_compliant' => $row['z'],
        'partially_compliant' => $row['o'],
        'fully_compliant' => $row['t'],
        'total_indicators' => $row['total'],
        'total_indicators_count' => $row['total1']
    ];
}

// Free the result and close the connection
mysqli_free_result($q5);
$con->next_result();

// Return data as JSON
header('Content-Type: application/json');
echo json_encode($data);
?>
