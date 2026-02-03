<?php
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="Facility_actionplan_Summary.csv"');

// Output UTF-8 BOM to ensure Excel opens as UTF-8
echo "\xEF\xBB\xBF";

include(__DIR__ . "/../../assets/conn/db.php");
include(__DIR__ . "/../../assets/conn/session.php");

// Open output stream
$output = fopen('php://output', 'w');

// Table header
$header = ['District', 'Block', 'Facility', 'Facility Type', 'Assessment Name', 'Total Action Plan', 'Worked on Action Plan','Action Plan Left'];
fputcsv($output, $header);

// SQL query
$sql = "
    SELECT 
        Dist_Name, 
        Block_Name, 
        fac_name, 
        facilities_type,
        ass_name, 
        total_action_plan, 
         worked_on_action_plan,
        action_plan_left
       
    FROM action_plan_chk
";

// Execute the query
$result = $con->query($sql);

if ($result) {
    while($row = $result->fetch_assoc()) {
        // Write each row to CSV
        fputcsv($output, [
            $row['Dist_Name'],
            $row['Block_Name'],
            $row['fac_name'],
            $row['facilities_type'],
            $row['ass_name'],
            $row['total_action_plan'],
            $row['worked_on_action_plan'],
            $row['action_plan_left']
            
        ]);
    }
} else {
    fputcsv($output, ['Error fetching data']);
}

// Close DB and output stream
fclose($output);
$con->close();
exit;
?>
