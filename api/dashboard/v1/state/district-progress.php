<?php
require_once dirname(__DIR__, 3) . "/_bootstrap.php";

$res = $con->query("CALL state_dash_count_dist1(1)");
$data = [];

while ($r = $res->fetch_assoc()) {
    $data[] = $r;
}

$con->next_result();

respond(["status"=>"success","data"=>$data]);
