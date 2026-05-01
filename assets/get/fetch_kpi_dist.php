<?php
include(__DIR__ . "/../../assets/conn/db.php");
include(__DIR__ . "/../../assets/conn/session.php");
// Excel headers
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=facility_kpi_month_report.xls");
header("Pragma: no-cache");
header("Expires: 0");
$dist_id = $_SESSION['dist'];
// SQL Query (CLEAN + SAFE)
$sql = "
SELECT
    f.dist_name,
    f.block_name,
    f.fac_name,
    COUNT(
        DISTINCT LEFT(REPLACE(TRIM(k.dh_kpi_date), '/', '-'), 7)
    ) AS months_generated,
    GROUP_CONCAT(
        DISTINCT DATE_FORMAT(
            STR_TO_DATE(
                CONCAT(
                    LEFT(REPLACE(TRIM(k.dh_kpi_date), '/', '-'), 7),
                    '-01'
                ),
                '%Y-%m-%d'
            ),
            '%b %y'
        )
        ORDER BY LEFT(REPLACE(TRIM(k.dh_kpi_date), '/', '-'), 7)
        SEPARATOR ', '
    ) AS month_list
FROM dh_kpi_in k
JOIN facilities f 
    ON f.fac_id = k.dh_kpi_fac_id
    WHERE f.dist_id = $dist_id
GROUP BY
    f.dist_name,
    f.block_name,
    f.fac_name
";

$result = mysqli_query($con, $sql);

// Excel Table
echo "<table border='1'>";
echo "<tr style='background:#e9f5ee;font-weight:bold'>
        <th>District</th>
        <th>Block</th>
        <th>Facility Name</th>
        <th>Months Generated</th>
        <th>Month List</th>
      </tr>";

while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['dist_name']) . "</td>";
    echo "<td>" . htmlspecialchars($row['block_name']) . "</td>";
    echo "<td>" . htmlspecialchars($row['fac_name']) . "</td>";
    echo "<td style='text-align:center'>" . $row['months_generated'] . "</td>";
    echo "<td>" . htmlspecialchars($row['month_list']) . "</td>";
    echo "</tr>";
}

echo "</table>";
exit;
?>