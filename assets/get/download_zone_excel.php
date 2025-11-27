<?php
include(__DIR__ . "/../../assets/conn/db.php");
include(__DIR__ . "/../../assets/conn/session.php");

$dept_id = $_GET['dept_id'] ?? '';
$fac_type_id = $_GET['fac_type_id'] ?? '';

$zone = $_GET['zone'] ?? '';

if (!$dept_id || !$fac_type_id || !in_array($zone, ['green', 'yellow', 'red'])) {
    exit('Invalid request');
}

$query = "
    SELECT csqa_reference_id, Measurable_Element, c_subtype_Reference_No_fk,
           concern_name, area_of_con_subtypedeatils, compliance_percent
    FROM department_indicators_gap
    WHERE fac_type_id = ? AND fac_dept_id_fk = ?
";
$stmt = $con->prepare($query);
$stmt->bind_param("ii", $fac_type_id, $dept_id);
$stmt->execute();
$result = $stmt->get_result();

$filtered = [];
while ($row = $result->fetch_assoc()) {
    $percent = floatval($row['compliance_percent']);
    if ($zone === 'green' && $percent > 60) {
        $filtered[] = $row;
    } elseif ($zone === 'yellow' && $percent >= 40 && $percent <= 60) {
        $filtered[] = $row;
    } elseif ($zone === 'red' && $percent < 40) {
        $filtered[] = $row;
    }
}

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"{$zone}_zone_compliance.xls\"");

echo "CSQA Ref ID\tSubType Ref\tMeasurable Element\tConcern\tDetails\tCompliance (%)\n";

foreach ($filtered as $row) {
    echo "{$row['csqa_reference_id']}\t{$row['c_subtype_Reference_No_fk']}\t";
    echo "{$row['Measurable_Element']}\t{$row['concern_name']}\t";
    echo "{$row['area_of_con_subtypedeatils']}\t{$row['compliance_percent']}\n";
}
exit;
?>
