<?php
include(__DIR__ . "/../../assets/conn/db.php");
include(__DIR__ . "/../../assets/conn/session.php");

// Fetch session values
$Fa = $_SESSION['u_facilityid'];
$dept_id = $_SESSION['dept_id1'];
$fat = $_SESSION['f_type_id'];
$p = $_SESSION['assperiod'];

// Query to fetch data
$tablequery1 = "CALL Area_of_concern_NQAS($fat, $Fa, $dept_id, $p)";
$q2 = mysqli_query($con, $tablequery1);

// Prepare the response data
$data = [];
while ($row = mysqli_fetch_array($q2)) {
    $obtained = $row['Obtained'];
    $total = $row['total'];
    
    if ($obtained != null && $total != 0) {
        $percentage = round((($obtained / $total) * 100), 2);
    } else {
        $percentage = 0;
    }

    $data[] = [
        'concern_name' => $row['concern_name'],
        'percentage' => $percentage,
        'obtained' => $obtained,
        'total' => $total
    ];
}

// Free the result and close the connection
mysqli_free_result($q2);
$con->next_result();

// Return data as JSON
header('Content-Type: application/json');
echo json_encode($data);
?>
