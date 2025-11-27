<?php
// Include session.php for session management
include('session.php');

// Include database configuration file
include_once("db.php");

// Include PhpXlsxGenerator library
require_once 'PhpXlsxGenerator.php';

// Get facility ID from GET parameter
$facilityId = $_GET['id'];

// Get user ID from session
$userId = $_SESSION['userid'];

// Fetch facility name from database
$query = "SELECT fac_name FROM facilities WHERE fac_id = $facilityId";
$result = mysqli_query($con, $query);
$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
$facilityName = $row['fac_name'];

// Define Excel file name for download
$fileName = $facilityName . "_Compliance_Report_" . date('Y-m-d') . ".xlsx";

// Define column names for Excel
$excelData[] = array('Department', 'Area of concern', 'Reference No', 'Sub Reference No', 'Measurable Element', 'Checkpoint', 'Assessment Method', 'Means of Verification', 'Compliance');

// Fetch records from database and store in an array
$tablequery = "CALL dist_facility_all_rpt_reports($facilityId, $userId)";
$q2 = mysqli_query($con, $tablequery);
while ($row = mysqli_fetch_array($q2, MYSQLI_ASSOC)) {
    $lineData = array(
        $row['dept_name'],
        $row['concern_name'],
        $row['c_subtype_Reference_No_fk'],
        $row['csqa_reference_id'],
        $row['Measurable_Element'],
        $row['Checkpoint'],
        $row['Assessment_Method'],
        $row['Means_of_Verification'],
        $row['ass_compliance']
    );
    $excelData[] = $lineData;
}

// Export data to Excel and download as xlsx file
$xlsx = CodexWorld\PhpXlsxGenerator::fromArray($excelData);
$xlsx->downloadAs($fileName);

exit;
?>
