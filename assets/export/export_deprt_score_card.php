<?php 
include(__DIR__ . "/../../assets/conn/db.php");
include(__DIR__ . "/../../assets/conn/session.php");
// Include XLSX generator library 
require_once(__DIR__ . '/../../PhpXlsxGenerator.php');
$dept_id = $_SESSION['dept_id1'];
$Fa = $_SESSION['u_facilityid'];
$fat = $_SESSION['f_type_id'];
$p =  $_SESSION['assperiod'];
$query="select fac_name from facilities where fac_id=$Fa";
$result = mysqli_query($con, $query);
$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
$faciltyname = $row['fac_name'];
// Excel file name for download 
$fileName = $faciltyname."_Department_Score_Card_" . date('Y-m-d') . ".xlsx"; 
 
// Define column names 
$excelData[] = array('Areas of Concern', 'Obtained Score ', 'Total Score', 'Score Percentage'); 

// Fetch records from database and store in an array 
$tablequery1 = "call Area_of_concern_NQAS($fat,$Fa,$dept_id,$p)";
                        $q2 = mysqli_query($con, $tablequery1);
                        while ($row = mysqli_fetch_array($q2))  {
                            $obtained = $row['Obtained'];
                            $total = $row['total'];
                            if ($obtained != null) {
                                $percentage = round((($obtained / $total) * 100), 2);

        $lineData = array($row['concern_name'], $row['Obtained'],  $total = $row['total'],  $percentage);  
        $excelData[] = $lineData; 
    } 
} 
 
// Export data to excel and download as xlsx file 
$xlsx = CodexWorld\PhpXlsxGenerator::fromArray( $excelData ); 
$xlsx->downloadAs($fileName); 
 
exit; 
 
?>