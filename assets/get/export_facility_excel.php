<?php
require_once(__DIR__ . "/../../assets/conn/db.php");
require_once(__DIR__ . "/../../assets/conn/session.php");
require_once(__DIR__ . '/../../PhpXlsxGenerator.php');

$dept_id = $_POST['dept_id'] ?? '';
$fac_type_id = $_POST['fac_type_id'] ?? '';

$fileName = "State_Facility_Indicators_Summary_" . date('Y-m-d') . ".xlsx";

// Define column headers
$excelData[] = array(
    'District', 'Block', 'Facility', 'Department',
    'Marks Obtained', 'Total Marks', 'Percentage',
    'Partially Comp.', 'Fully Comp.', 'Non-Comp.',
    'Not Assessed', 'Total Indicators'
);

if ($dept_id && $fac_type_id) {
    $query = "
        SELECT 
            Dist_Name,
            Block_Name,
            fac_name,
            department_name,          
            marks_obtained,
            total_marks,
            percentage,
            one, 
            two, 
            zero, 
            non, 
            total
        FROM department_wise_state_dash
        WHERE fac_dept_id_fk = ? AND Health_facilty_type = ?
    ";

    $stmt = $con->prepare($query);
    $stmt->bind_param("ii", $dept_id, $fac_type_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $excelData[] = array(
            $row['Dist_Name'],
            $row['Block_Name'],
            $row['fac_name'],
            $row['department_name'],
            $row['marks_obtained'],
            $row['total_marks'],
            $row['percentage'] . '%',
            $row['one'],
            $row['two'],
            $row['zero'],
            $row['non'],
            $row['total']
        );
    }

    // Export data to Excel
    $xlsx = CodexWorld\PhpXlsxGenerator::fromArray($excelData);
    $xlsx->downloadAs($fileName);
    exit;
} else {
    echo "Invalid request: Department or Facility Type is missing.";
}
?>
