<?php
// Excel headers
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=SaQshi_All_Facilities_Outcome_2Year_Tracker_Report.xls");
header("Pragma: no-cache");
header("Expires: 0");

include(__DIR__ . "/../../assets/conn/db.php");
include(__DIR__ . "/../../assets/conn/session.php");

// ✅ SQL (clean + safe)
$sql = "
SELECT
    f.dist_name,
    f.block_name,
    f.fac_name,
    DATE_FORMAT(
        STR_TO_DATE(CONCAT(REPLACE(o.month_in,'/','-'), '-01'), '%Y-%m-%d'),
        '%b-%y'
    ) AS month_year,
    GROUP_CONCAT(DISTINCT d.dept_name ORDER BY d.dept_name SEPARATOR ', ') AS departments
FROM outcome_values_in o
JOIN facilities f ON f.fac_id = o.institute_id
JOIN fac_department d ON d.fac_dept_id = o.dept_id
GROUP BY 
    f.dist_name,
    f.block_name,
    f.fac_name,
    month_year
ORDER BY 
    f.dist_name,
    f.block_name,
    f.fac_name,
    month_year
";

$result = mysqli_query($con, $sql);

// ✅ Prepare pivot data
$data = [];

while ($row = mysqli_fetch_assoc($result)) {
    $key = $row['dist_name'] . '|' . $row['block_name'] . '|' . $row['fac_name'];

    $data[$key]['dist_name'] = $row['dist_name'];
    $data[$key]['block_name'] = $row['block_name'];
    $data[$key]['fac_name'] = $row['fac_name'];

    $data[$key][$row['month_year']] = $row['departments'];
}

// ✅ Generate rolling 24 months (dynamic)
$months = [];
$start = new DateTime('first day of this month');
$start->modify('-23 months');

for ($i = 0; $i < 24; $i++) {
    $months[] = $start->format('M-y');
    $start->modify('+1 month');
}

// ✅ Start Excel output
echo "<table border='1'>";

// Header
echo "<tr>
<th><b>District</b></th>
<th><b>Block</b></th>
<th><b>Facility Name</b></th>";

foreach ($months as $m) {
    echo "<th><b>" . htmlspecialchars($m) . "</b></th>";
}
echo "</tr>";

// Data rows
foreach ($data as $row) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['dist_name']) . "</td>";
    echo "<td>" . htmlspecialchars($row['block_name']) . "</td>";
    echo "<td>" . htmlspecialchars($row['fac_name']) . "</td>";

    foreach ($months as $m) {
        $val = $row[$m] ?? '';

        // Optional: highlight missing months
        $style = $val ? "" : "style='background-color:#f8d7da'";

        echo "<td $style>" . htmlspecialchars($val) . "</td>";
    }

    echo "</tr>";
}

echo "</table>";
exit;
?>