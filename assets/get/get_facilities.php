<?php
include(__DIR__ . "/../../assets/conn/db.php");
include(__DIR__ . "/../../assets/conn/session.php");

$term = mysqli_real_escape_string($con, $_GET['term'] ?? '');

$result = mysqli_query($con, "
    SELECT fac_id, fac_name 
    FROM facilities 
    WHERE fac_name LIKE '%$term%' 
    ORDER BY fac_name ASC 
    LIMIT 10
");

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = [
        'label' => $row['fac_name'],
        'value' => $row['fac_name'],
        'id'    => $row['fac_id']
    ];
}

echo json_encode($data);
