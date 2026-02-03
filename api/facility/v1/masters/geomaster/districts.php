<?php
require_once dirname(__DIR__, 4) . "/_bootstrap.php";

$division_id = (int)($_GET['division_id'] ?? 0);
if (!$division_id) respondError("division_id required");

$q = $con->query("
    SELECT dist_id, Dist_name
    FROM dist_master
    WHERE division_id = $division_id
    ORDER BY Dist_name
");

$data = [];
while ($r = $q->fetch_assoc()) {
    $data[] = $r;
}

respondSuccess($data);
