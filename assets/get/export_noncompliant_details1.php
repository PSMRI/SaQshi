<?php
include(__DIR__ . "/../../assets/conn/db.php");
include(__DIR__ . "/../../assets/conn/session.php");

$facility = $_GET['facility'] ?? '';
$concern = $_GET['concern'] ?? '';
$standard = $_GET['standard'] ?? '';

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"NonCompliant_Details_{$facility}_{$standard}.xls\"");

$query = "SELECT * 
          FROM gap_analysis 
          WHERE compliance = 0
            AND facilities_type = ? 
            AND concern_name = ?";          

$stmt = $con->prepare($query);
$stmt->bind_param("ss", $facility, $concern);
$stmt->execute();
$result = $stmt->get_result();

echo "<table border='1'>";
echo "<tr>
        <th>Facility Type</th>
        <th>Area of Concern</th>
        <th>Standard</th>
        <th>Reference ID</th>
        <th>Measurable Element</th>
        <th>Compliance</th>
      </tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>
            <td>{$row['facilities_type']}</td>
            <td>{$row['concern_name']}</td>
            <td>{$row['c_subtype_Reference_No_fk']}</td>
            <td>{$row['csqa_reference_id']}</td>
            <td>{$row['Measurable_Element']}</td>
            <td>{$row['compliance']}</td>
          </tr>";
}
echo "</table>";

$stmt->close();
$con->close();
?>
