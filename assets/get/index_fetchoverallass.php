<?php
include(__DIR__ . "/../../assets/conn/db.php");
include(__DIR__ . "/../../assets/conn/session.php");

$fid = $_SESSION['u_facilityid'];
$did = $_SESSION['dept_id1'];
$fty = $_SESSION['f_type_id'];

// Fetch data from the database
$query0 = "CALL dept_dash_preriod_g($fid, $did, $fty)";
$result0 = $con->query($query0);

$values = [];
$values1 = [];

// Check if the result has data
if ($result0->num_rows > 0) {
    while ($row = mysqli_fetch_assoc($result0)) {
        $values[] = $row['total'];
        $values1[] = "'" . $row['period'] . "'";
    }
    $h = implode(", ", $values);
    $h1 = implode(", ", $values1);
}

// Prepare the data as an array
$response = [
    'values' => $h,
    'periods' => $h1
];

// Return the data as JSON
header('Content-Type: application/json');
echo json_encode($response);

// Close the database connection
$con->close();
?>
