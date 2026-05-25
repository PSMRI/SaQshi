<?php
include(__DIR__ . "/../../assets/conn/db.php");
include(__DIR__ . "/../../assets/conn/session.php");

$facility = $_GET['facility'] ?? '';
$concern = $_GET['concern'] ?? '';
$standard = $_GET['standard'] ?? '';

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"NonCompliant_Details_{$facility}_{$standard}.xls\"");

$query = "SELECT * 
          FROM gap_analysis_updated 
          WHERE compliance = 0
            AND facilities_type = ? 
            AND concern_name = ?
            AND c_subtype_Reference_No_fk = ?";

$stmt = $con->prepare($query);
$stmt->bind_param("sss", $facility, $concern, $standard);
$stmt->execute();
$result = $stmt->get_result();

echo "<table border='1'>";
echo "<tr>
        <th>Facility Type</th>
        <th>Area of Concern</th>
        <th>Standard</th>
        <th>Reference ID</th>
        <th>Measurable Element</th>
        <th> Checkpoint </th>
        <th>Compliance</th>
        <th>List of Facilities </th>
        <th>Count</th>
      </tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>
            <td>{$row['facilities_type']}</td>
            <td>{$row['concern_name']}</td>
            <td>{$row['c_subtype_Reference_No_fk']}</td>
            <td>{$row['csqa_reference_id']}</td>
            <td>{$row['Measurable_Element']}</td>
             <td>{$row['Checkpoint']}</td>
            <td>{$row['compliance']}</td>
            <td>{$row['facility_names']}</td>
            <td>{$row['compliance_count']}</td>
          </tr>";
}
echo "</table>";

$stmt->close();
$con->close();
?>
