<?php
require_once dirname(__DIR__, 3) . "/_bootstrap.php";

$sql = "SELECT Dist_Name, p1 FROM state_dash_view WHERE p1 <> 0 AND fac_id NOT IN (1)";
$res = $con->query($sql);

$dist = [];

while ($r = $res->fetch_assoc()) {

    $d = $r['Dist_Name'];
    $p = (float)$r['p1'];

    if (!isset($dist[$d])) {
        $dist[$d] = [
            "<40" => 0,
            "40-50" => 0,
            "50-80" => 0,
            "80-90" => 0,
            ">=90" => 0
        ];
    }

    if ($p < 40) $dist[$d]["<40"]++;
    elseif ($p < 50) $dist[$d]["40-50"]++;
    elseif ($p < 80) $dist[$d]["50-80"]++;
    elseif ($p < 90) $dist[$d]["80-90"]++;
    else $dist[$d][">=90"]++;
}

respond(["status"=>"success","data"=>$dist]);
