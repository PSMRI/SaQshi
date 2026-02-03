<?php
require_once dirname(__DIR__, 4) . "/_bootstrap.php";

$dist_id = (int)($_GET['district_id'] ?? 0);
if (!$dist_id) respondError("district_id required");

$q = $con->query("
    SELECT block_id, block_name
    FROM block_master
    WHERE dist_id = $dist_id
    ORDER BY block_name
");

$data = [];
while ($r = $q->fetch_assoc()) {
    $data[] = $r;
}

respondSuccess($data);
