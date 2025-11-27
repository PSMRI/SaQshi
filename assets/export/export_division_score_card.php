<?php
include(__DIR__ . "/../../assets/conn/db.php");
include(__DIR__ . "/../../assets/conn/session.php");
// Include XLSX generator library 
require_once(__DIR__ . '/../../PhpXlsxGenerator.php');
$dist_id = $_SESSION['div_id'];
$userid = $_SESSION['userid'];
// Excel file name for download 
$fileName = "Division_Facility_indicators_Summary_" . date('Y-m-d') . ".xlsx";
// Define column names 
$excelData[] = array('District', 'Block', 'Fac. Type', 'Facility','Assessment','Non Comp.','Partially Comp.','Fully Comp.','Compliance Completed','Indicators','Comp.%','Obtained Score','Tot Score','Score %','Pending for action Dist.');

// Fetch records from database and store in an array 
$tablequery1 = "SELECT * FROM sarbsoft_nqa.state_dash_view where division_id= $dist_id  order by Dist_Name asc";
$q2 = mysqli_query($con, $tablequery1);
while ($row = mysqli_fetch_array($q2)) {
    $obtained = $row['p'];
    $m = $row['marks'];
    $f = $row['f'];
    if($m==0 or $f==0){
        $p1=0;
    }else{
        $p1 = round((($m / $f) * 100), 2);
    }
    
    if ($obtained != null) {       

        $lineData = array($row['Dist_Name'],  $row['Block_Name'],$row['facilities_type'],$row['fac_name'],$row['ass_name'],$row['zero'],$row['one'],$row['two'],$row['obt'],$row['tot'],$row['p'],$row['marks'],$row['f'],$p1,$row['non']);
        $excelData[] = $lineData;
    }
}


// Export data to excel and download as xlsx file 
$xlsx = CodexWorld\PhpXlsxGenerator::fromArray($excelData);
$xlsx->downloadAs($fileName);

exit;
