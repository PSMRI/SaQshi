<?php
require_once dirname(__DIR__, 3) . "/_bootstrap.php";

$facilityType = (int)($_GET['facility_type'] ?? 0);
$concernId    = (int)($_GET['concern_id'] ?? 0);

if ($facilityType<=0 || $concernId<=0) {
    respond(["status"=>"error","message"=>"facility_type & concern_id required"], 400);
}

$q = $con->query("
    SELECT 
        c_subtype_id,
        Reference_No,
        area_of_con_subtypedeatils
    FROM area_of_concern_subtype
    WHERE fac_type_id = $facilityType
      AND area_of_con_id = $concernId
    ORDER BY c_subtype_id
");

$data = [];
while ($r = $q->fetch_assoc()) {
    $data[] = [
        "subtype_id"   => $r['c_subtype_id'],
        "reference_no"=> $r['Reference_No'],
        "description" => $r['area_of_con_subtypedeatils']
    ];
}

respond([
    "status"=>"success",
    "data"=>$data
]);
