<?php 
 include(__DIR__ . "/../../assets/conn/db.php");
include(__DIR__ . "/../../assets/conn/session.php");
 
// Include XLSX generator library 
//require_once 'PhpXlsxGenerator.php'; 
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
$fileName =$faciltyname."_Department_Indicators_" . date('Y-m-d') . ".xlsx"; 
 

// Define column names 
$excelData[] = array('Reference No', 'Sub Reference No ', 'Measurable Element', 'Checkpoint','AssMethod','Means of Verification','Compliance'); 



// Fetch records from database and store in an array 
$tablequery1 = "call department_indicator_dash($Fa,$p,$dept_id)";
                        $q2 = mysqli_query($con, $tablequery1);
                        while ($row = mysqli_fetch_array($q2))  {
        $lineData = array($row['c_subtype_Reference_No_fk'], $row['csqa_reference_id'],$row['Measurable_Element'],$row['Checkpoint'],$row['Assessment_Method'],$row['Means_of_Verification'],$row['ass_compliance']);  
        $excelData[] = $lineData; 
    } 

 
// Export data to excel and download as xlsx file 
$xlsx = CodexWorld\PhpXlsxGenerator::fromArray( $excelData ); 
$xlsx->downloadAs($fileName); 
 
exit; 
 
?>