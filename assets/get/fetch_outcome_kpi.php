<?php
// Set headers for Excel download
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=facility_department_month_report.xls");
header("Pragma: no-cache");
header("Expires: 0");

include(__DIR__ . "/../../assets/conn/db.php");
include(__DIR__ . "/../../assets/conn/session.php");

// Set headers for Excel download
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=facility_department_month_report.xls");
header("Pragma: no-cache");
header("Expires: 0");

// SQL Query
$sql = "
SELECT
    f.dist_name,
    f.block_name,
    f.fac_name,
    GROUP_CONCAT(
        CONCAT(
            d.dept_name,
            ' (',
            t.month_count,
            ' month: ',
            t.month_list,
            ')'
        )
        ORDER BY d.dept_name
        SEPARATOR ', '
    ) AS dept_month_summary
FROM (
    SELECT
        o.institute_id,
        o.dept_id,
        COUNT(
            DISTINCT LEFT(REPLACE(TRIM(o.month_in), '/', '-'), 7)
        ) AS month_count,
        GROUP_CONCAT(
            DISTINCT DATE_FORMAT(
                STR_TO_DATE(
                    CONCAT(
                        LEFT(REPLACE(TRIM(o.month_in), '/', '-'), 7),
                        '-01'
                    ),
                    '%Y-%m-%d'
                ),
                '%b %y'
            )
            ORDER BY LEFT(REPLACE(TRIM(o.month_in), '/', '-'), 7)
            SEPARATOR ', '
        ) AS month_list
    FROM outcome_values_in o
    GROUP BY o.institute_id, o.dept_id
) t
JOIN facilities f 
    ON f.fac_id = t.institute_id
JOIN fac_department d 
    ON d.fac_dept_id = t.dept_id
GROUP BY
    f.dist_name,
    f.block_name,
    f.fac_name;
";

$result = mysqli_query($con, $sql);

// Start Excel table
echo "<table border='1'>";
echo "<tr>
        <th>District</th>
        <th>Block</th>
        <th>Facility Name</th>
        <th>Department Month Summary</th>
      </tr>";

// Output rows
while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>".htmlspecialchars($row['dist_name'])."</td>";
    echo "<td>".htmlspecialchars($row['block_name'])."</td>";
    echo "<td>".htmlspecialchars($row['fac_name'])."</td>";
    echo "<td>".htmlspecialchars($row['dept_month_summary'])."</td>";
    echo "</tr>";
}

echo "</table>";
exit;
?>