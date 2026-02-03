<?php
require_once dirname(__DIR__, 3) . "/_bootstrap.php";

$facilityType = (int)($_GET['facility_type'] ?? 0);
$lang         = $_GET['lang'] ?? 'en';

if ($facilityType <= 0) {
    respond(["status"=>"error","message"=>"facility_type required"], 400);
}

$col = match($lang) {
    "assam" => "concern_name_assam",
    "ben"   => "concern_name_ben",
    "hin"   => "concern_name_hin",
    "odia"  => "concern_name_odia",
    default => "concern_name"
};

$q = $con->query("
    SELECT concern_id, $col AS concern_name
    FROM area_of_concern
    ORDER BY concern_id
");

$data = [];
while ($r = $q->fetch_assoc()) {
    $data[] = $r;
}

respond([
    "status" => "success",
    "data"   => $data
]);
