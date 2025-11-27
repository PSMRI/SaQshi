<?php
include(__DIR__ . "/../../assets/conn/db.php");
include(__DIR__ . "/../../assets/conn/session.php");

// Fetch session values
$Fa = $_SESSION['u_facilityid'];
$dept_id = $_SESSION['dept_id1'];
$fat = $_SESSION['f_type_id'];
$p = $_SESSION['assperiod'];

// Call the stored procedure to fetch the required data
$call_q1 = "CALL count_zero($Fa, $dept_id, $p, $fat)";
$q56 = mysqli_query($con, $call_q1);

// Prepare the result array
$data = [];

while ($row = mysqli_fetch_array($q56)) {
    $obtained = $row['total'];
    $total = $row['total1'];
    $zero=$row['z'];
    $one=$row['o'];
    $fully=$row['t'];

    // Calculate percentage
    if ($obtained != null && $total != 0) {
        $percentage = round((($obtained / $total) * 100), 2);
    } else {
        $percentage = 0;
    }

    // Determine the CSS class for styling based on percentage
    if ($percentage > 70) {
        $percentage_class = 'text-success';
    } else {
        $percentage_class = 'text-danger';
    }

    // Push data to the response array
    $data[] = [
        'obtained' => $obtained,
        'total' => $total,
        'percentage' => $percentage,
        'percentage_class' => $percentage_class,
        'zero'=>$zero,
        'one'=>$one,
        'fully'=>$fully
    ];
}

// Clean up result set
mysqli_free_result($q56);

// Return the data as a JSON response
header('Content-Type: application/json');
echo json_encode($data);

// Close the database connection
$con->close();
?>


