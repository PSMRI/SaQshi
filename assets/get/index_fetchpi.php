<?php
include(__DIR__ . "/../../assets/conn/db.php");
include(__DIR__ . "/../../assets/conn/session.php");

// Fetch session values for the first part
$fid = $_SESSION['u_facilityid'];
$assid = $_SESSION['assperiod'];

// Call the stored procedure to fetch the required data for the first query
$query_1 = "CALL facility_dept_caht1($fid, $assid)";
$result_1 = $con->query($query_1);

// Prepare the result array for the first query
$data1 = [];

if ($row = mysqli_fetch_assoc($result_1)) {
    $data1 = [
        'fully_compliant' => $row['t'],
        'partially_compliant' => $row['o'],
        'non_compliant' => $row['z'],
    ];
}

// Clean up result set for the first query
mysqli_free_result($result_1);
$con->next_result();
// Fetch session values for the second part
$dept_id = $_SESSION['dept_id1'];
$Fa = $_SESSION['u_facilityid'];
$fat = $_SESSION['f_type_id'];
$p = $_SESSION['assperiod'];

// Call the stored procedure for the second query
$call_qq = "CALL overall_dept_percentage1($dept_id, $Fa, $p, $fat)";
$q22q = mysqli_query($con, $call_qq);

// Prepare the result for the second query
$data2 = [];

while ($row = mysqli_fetch_array($q22q)) {
    $obtainedd = $row['obtained'];
    $totall = $row['total'];

    if ($obtainedd != 0) {
        $percentage1l = round((($obtainedd / $totall) * 100), 2);
        $data2['percentage'] = $percentage1l;
    } else {
        $data2['percentage'] = 0;
    }
}

// Clean up result set for the second query
mysqli_free_result($q22q);

// Prepare the final JSON response containing both results
$response = [
    'compliance_data' => $data1,
    'percentage_data' => $data2
];

// Return the data as a JSON response
header('Content-Type: application/json');
echo json_encode($response);

// Close the database connection
$con->close();
?>
