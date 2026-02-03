<?php
require_once dirname(__DIR__, 5) . "/_bootstrap.php";

$res = $con->query("SELECT concern_id, concern_name FROM area_of_concern");
$data = [];

while ($r = $res->fetch_assoc()) {
    $data[] = $r;
}

respond(["status"=>"success","data"=>$data]);
