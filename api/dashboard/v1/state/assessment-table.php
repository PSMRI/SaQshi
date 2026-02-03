<?php
require_once dirname(__DIR__, 3) . "/_bootstrap.php";

$res = $con->query("SELECT * FROM state_dash_view WHERE fac_id NOT IN (1)");
$data = [];

while ($r = $res->fetch_assoc()) {
    $r['p1'] = ($r['marks'] && $r['f'])
        ? round(($r['marks'] / $r['f']) * 100, 2)
        : 0;
    $data[] = $r;
}

respond(["status"=>"success","data"=>$data]);
