<?php
include(__DIR__ . "/../../assets/conn/db.php");
include(__DIR__ . "/../../assets/conn/session.php");

// Fetch session values
$dept_id = $_SESSION['dept_id1'];
$Fa = $_SESSION['u_facilityid'];
$fat = $_SESSION['f_type_id'];
$p = $_SESSION['assperiod'];

// First Query
$call_qq = "CALL overall_dept_percentage1($dept_id, $Fa, $p, $fat)";
$q22q = mysqli_query($con, $call_qq);
$data1 = [];
while ($row = mysqli_fetch_array($q22q)) {
    $obtainedd = $row['obtained'];
    $totall = $row['total'];
    $percentage1l = ($obtainedd != 0) ? round((($obtainedd / $totall) * 100), 2) : 0;
    $data1['p1l'] = $percentage1l;
    $_SESSION['p1']=$percentage1l;
}
mysqli_free_result($q22q);

$con->next_result();
// Second Query
$call_q = "CALL overall_dept_percentage($dept_id, $Fa, $p, $fat)";
$q22 = mysqli_query($con, $call_q);
$data2 = [];
while ($row = mysqli_fetch_array($q22)) {
    $obtained = $row['obtained'];
    $total = $row['total'];
    $percentage1 = ($obtained != 0) ? round((($obtained / $total) * 100), 2) : 0;
    $data2['p1'] = $percentage1;

    // For visualizing the percentage with different classes
    if ($obtained != null) {
        $percentage = round((($obtained / $total) * 100), 2);
        if ($percentage > 70) {
            $data2['percentage_class'] = 'text-success';
        } elseif ($percentage > 65 && $percentage <= 70) {
            $data2['percentage_class'] = 'text-warning';
        } else {
            $data2['percentage_class'] = 'text-danger';
        }
    } else {
        $data2['percentage_class'] = 'text-danger';
        $percentage = 0;
    }
}
mysqli_free_result($q22);

// Send the data as a JSON response
echo json_encode(['data1' => $data1, 'data2' => $data2]);

// Close database connection
$con->close();
?>
