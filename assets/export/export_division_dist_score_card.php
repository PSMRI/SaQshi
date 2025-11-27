<?php
include(__DIR__ . "/../../assets/conn/db.php");
include(__DIR__ . "/../../assets/conn/session.php");
// Include XLSX generator library 
require_once(__DIR__ . '/../../PhpXlsxGenerator.php');
$dist_id = $_SESSION['div_id'];
$userid = $_SESSION['userid'];
// Excel file name for download 
$fileName = "Division's_dist_Summary_" . date('Y-m-d') . ".xlsx";
// Define column names 
$excelData[] = array('District', 'CHC', 'CHCcomp', 'DH','DHcomp','PHC','PHCcomp','HWC','HWCcomp','APHC','APHCcomp');

// Fetch records from database and store in an array 
$tablequery1 = "call Division_dash_count_dist($dist_id)";
$q2 = mysqli_query($con, $tablequery1);
while ($row = mysqli_fetch_array($q2)) {
    $excelData[] = array(
        $row['Dist_Name'],
        $row['CHC'],  // Total CHC
        $row['CHCcomp'],  // Completed CHC
        $row['DH'],  // Total DH
        $row['DHcomp'],  // Completed DH
        $row['PHC'],  // Total PHC
        $row['PHCcomp'],  // Completed PHC
        $row['HWC'],  // Total HWC
        $row['HWCcomp'],  // Completed HWC
        $row['APHC'],  // Total APHC
        $row['APHCcomp']  // Completed APHC
    );
}

// Export data to excel and download as xlsx file 
$xlsx = CodexWorld\PhpXlsxGenerator::fromArray($excelData);
$xlsx->downloadAs($fileName);

exit;
