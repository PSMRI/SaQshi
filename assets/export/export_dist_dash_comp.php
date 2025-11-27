<?php
include(__DIR__ . "/../../assets/conn/db.php");
include(__DIR__ . "/../../assets/conn/session.php");

// Include PhpXlsxGenerator library
require_once(__DIR__ . '/../../PhpXlsxGenerator.php');

// Check if the 'id' parameter is passed and is not empty
if (isset($_GET['id']) && !empty($_GET['id'])) {
    // Get facility ID from GET parameter
    $facilityId = $_GET['id'];

    // Sanitize the facility ID to prevent SQL injection
    $facilityId = mysqli_real_escape_string($con, $facilityId);

    // Get user ID from session
    $userId = $_SESSION['userid'];

    // Fetch facility name from the database
    $query = "SELECT fac_name FROM facilities WHERE fac_id = '$facilityId'";
    $result = mysqli_query($con, $query);

    // Check if the facility exists
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
        $facilityName = $row['fac_name'];

        // Define the Excel file name for download
        $fileName = $facilityName . "_Compliance_Report_" . date('Y-m-d') . ".xlsx";

        // Define column names for Excel
        $excelData[] = array('Department', 'Area of concern', 'Reference No', 'Sub Reference No', 'Measurable Element', 'Checkpoint', 'Assessment Method', 'Means of Verification', 'Compliance');

        // Fetch records from database using stored procedure
        $tablequery = "CALL dist_dash_comp_reports($facilityId, $userId)";
        $q2 = mysqli_query($con, $tablequery);

        if ($q2) {
            // Loop through the fetched data and prepare it for export
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
        } else {
            die('Error while fetching records..!: ' . mysqli_error($con));
        }

    } else {
        die('Error while fetching records..!:');
    }
} else {
    echo 'Sorry no data found.....!';
}

// Close the database connection
mysqli_close($con);

exit;
?>
