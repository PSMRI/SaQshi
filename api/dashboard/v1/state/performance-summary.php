<?php
require_once dirname(__DIR__, 3) . "/_bootstrap.php";
require_once dirname(__DIR__, 4) . "/assets/conn/session.php";
$sql = "SELECT p1 FROM state_dash_view WHERE p1 <> 0 AND fac_id NOT IN (1)";
$res = $con->query($sql);

$gt80 = $btw50_80 = $lt50 = 0;
$total = $sum = 0;

while ($r = $res->fetch_assoc()) {
    $p = (float)$r['p1'];
    $total++;
    $sum += $p;

    if ($p > 80) $gt80++;
    elseif ($p >= 50) $btw50_80++;
    else $lt50++;
}

respond([
    "status" => "success",
    "total_assessments" => $total,
    "average_score" => $total ? round($sum / $total, 2) : 0,
    "categories" => [
        "gt80" => $gt80,
        "btw50_80" => $btw50_80,
        "lt50" => $lt50
    ]
]);
