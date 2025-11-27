<?php
include(__DIR__ . "/../../assets/conn/db.php");
include(__DIR__ . "/../../assets/conn/session.php");
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

$zoneFilter = $_POST['zone'] ?? '';
$results = [];

$query = "SELECT 
    compliance_count AS facility_count,
    compliance,
    concern_name,
    area_of_con_subtypedeatils,
    c_subtype_Reference_No_fk AS standard,
    csqa_reference_id,
    Measurable_Element,
    facilities_type
FROM gap_analysis
WHERE compliance IN (0)" ?? 0;

$res = $con->query($query);

while ($row = $res->fetch_assoc()) {
    $row['compliance_label'] = $row['compliance'] == 1 ? 'Compliant' : 'Non-Compliant';
    $count = (int)$row['facility_count'];

    // Determine zone
    if ($count > 150) {
        $zone = 'Red Zone';
    } elseif ($count >= 100 && $count <= 149) {
        $zone = 'Yellow Zone';
    } elseif ($count >= 50 && $count <= 99) {
        $zone = 'Orange Zone';
    } else {
        $zone = 'Green Zone';
    }

    if ($zone === $zoneFilter) {
        $row['zone'] = $zone;
        $results[] = $row;
    }
}

echo json_encode(['data' => $results]);
?>