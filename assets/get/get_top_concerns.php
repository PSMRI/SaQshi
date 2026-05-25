<?php
include(__DIR__ . "/../../assets/conn/db.php");
include(__DIR__ . "/../../assets/conn/session.php");
header("Content-Type: application/json");

// First Query: Top Concern + Standard
$sql1 = "
SELECT *
FROM (
    SELECT 
        facilities_type,
        concern_name,
        c_subtype_Reference_No_fk AS standard,
        COUNT(*) AS non_compliant_count,
        RANK() OVER (
            PARTITION BY facilities_type 
            ORDER BY COUNT(*) DESC
        ) AS rnk
    FROM gap_analysis_updated
    WHERE compliance = 0
    GROUP BY facilities_type, concern_name, c_subtype_Reference_No_fk
) ranked
WHERE rnk = 1;
"??0;

$result1 = $con->query($sql1);
$data1 = [];
while ($row = $result1->fetch_assoc()) {
    $data1[] = $row;
}

// Second Query: Top Concern only
$sql2 = "
SELECT *
FROM (
    SELECT 
        facilities_type,
        concern_name,
        COUNT(*) AS non_compliant_count,
        RANK() OVER (PARTITION BY facilities_type ORDER BY COUNT(*) DESC) AS rnk
    FROM gap_analysis_updated
    WHERE compliance = 0
    GROUP BY facilities_type, concern_name
) ranked
WHERE rnk = 1;
";

$result2 = $con->query($sql2);
$data2 = [];
while ($row = $result2->fetch_assoc()) {
    $data2[] = $row;
}

echo json_encode([
    "with_standard" => $data1,
    "without_standard" => $data2
]);
?>
