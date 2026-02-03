<?php
require_once dirname(__DIR__, 3) . "/_bootstrap.php";

$sql = "SELECT * FROM state_dash_view WHERE p1 <> 0 AND fac_id NOT IN (1)";
$res = $con->query($sql);

$zones = ["green"=>[], "yellow"=>[], "red"=>[]];

while ($r = $res->fetch_assoc()) {

    $entry = [
        "district"=>$r['Dist_Name'],
        "block"=>$r['Block_Name'],
        "facility"=>$r['fac_name'],
        "type"=>$r['facilities_type'],
        "score"=>$r['p1']
    ];

    if ($r['p1'] > 80) $zones['green'][] = $entry;
    elseif ($r['p1'] >= 50) $zones['yellow'][] = $entry;
    else $zones['red'][] = $entry;
}

respond(["status"=>"success"] + $zones);
