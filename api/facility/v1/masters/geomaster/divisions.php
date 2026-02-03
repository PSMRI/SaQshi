<?php
require_once dirname(__DIR__, 4) . "/_bootstrap.php";
$state_id = (int)($_GET['state_id'] ?? 0);

if ($state_id <= 0) {
    respond([
        "status" => "error",
        "message" => "state_id is required"
    ], 400);
}

$sql = "SELECT iddivision, division_name
    FROM division
        WHERE state_id = $state_id
        ORDER BY division_name";

$res = $con->query($sql);

$data = [];
while ($row = $res->fetch_assoc()) {
    $data[] = $row;
}

respond([
    "status" => "success",
    "count"  => count($data),
    "data"   => $data
]);
