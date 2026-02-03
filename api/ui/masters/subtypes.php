<?php
require_once dirname(__DIR__, 5) . "/_bootstrap.php";

$cid = (int)$_GET['concern_id'];
$fty = (int)$_GET['facility_type'];

$sql = "
SELECT c_subtype_id, Reference_No, area_of_con_subtypedeatils
FROM area_of_concern_subtype
WHERE area_of_con_id = ?
  AND fac_type_id = ?
";

$stmt = $con->prepare($sql);
$stmt->bind_param("ii", $cid, $fty);
$stmt->execute();

$out = [];
$r = $stmt->get_result();
while ($x = $r->fetch_assoc()) {
    $out[] = $x;
}

respond(["status"=>"success","data"=>$out]);
