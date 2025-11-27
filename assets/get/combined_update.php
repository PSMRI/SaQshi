<?php
include(__DIR__ . "/../../assets/conn/db.php");
include(__DIR__ . "/../../assets/conn/session.php");

// Fetch the data for update1.php
$dep = $_SESSION['dept_id1'];
$pi = $_SESSION['assperiod'];
$fid = $_SESSION['u_facilityid'];

$query1 = $con->query("SELECT count(ass_id) as total FROM chk_list_assessment 
                        WHERE ass_compliance IN (0, 1)  
                          AND moic_remarcks IS NULL 
                          AND fac_id_fk = $fid 
                          AND fac_dept_id_fk = $dep 
                          AND ass_period_id = $pi;");
$row1 = $query1->fetch_assoc();
$data1 = $row1['total'];

// Fetch the data for update2.php
$query2 = $con->query("SELECT count(ass_id) as total FROM chk_list_assessment 
                        WHERE ass_compliance IN (0, 1)  
                          AND (moic_remarcks = 'Achievable' OR DQA_remarcks = 'Achievable') 
                          AND fac_id_fk = $fid 
                          AND fac_dept_id_fk = $dep 
                          AND ass_period_id = $pi;");
$row2 = $query2->fetch_assoc();
$data2 = $row2['total'];

// Return both sets of data as a JSON response
echo json_encode([
    'data1' => $data1,
    'data2' => $data2
]);

// Close the connection
$con->close();
?>
