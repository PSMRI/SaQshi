<?php
require_once dirname(__DIR__, 4) . "/_bootstrap.php";


$q = $con->query("
    SELECT state_id, state_name, state_code
    FROM state_master
    ORDER BY state_name
");

$data = [];
while ($r = $q->fetch_assoc()) {
    $data[] = $r;
}

respond([
    "status" => "success",
    "count"  => count($data),
    "data"   => $data
]);
