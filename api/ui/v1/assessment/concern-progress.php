<?php
require_once dirname(__DIR__, 3) . "/_bootstrap.php";

$fid  = (int)$_GET['facility_id'];
$dept = (int)$_GET['department_id'];
$peri = (int)$_GET['assessment_period'];
$fty  = (int)$_GET['facility_type'];

$sql = "
SELECT
  s.area_of_con_id AS concern_id,
  COUNT(c.csqa_id) total,
  COUNT(a.csqa_id_fk) completed
FROM area_of_concern_subtype s
JOIN concern_subtype_chklist c ON c.c_subtype_id_fk = s.c_subtype_id
LEFT JOIN chk_list_assessment a
 ON a.csqa_id_fk = c.csqa_id
AND a.fac_id_fk = ?
AND a.fac_dept_id_fk = ?
AND a.ass_period_id = ?
WHERE s.fac_type_id = ?
GROUP BY s.area_of_con_id
";

$stmt = $con->prepare($sql);
$stmt->bind_param("iiii", $fid, $dept, $peri, $fty);
$stmt->execute();

$out = [];
$r = $stmt->get_result();
while ($x = $r->fetch_assoc()) {
    $out[] = [
        "concern_id" => (int)$x['concern_id'],
        "total" => (int)$x['total'],
        "completed" => (int)$x['completed'],
        "percent" => $x['total'] ? round(($x['completed']/$x['total'])*100,1) : 0
    ];
}

respond(["status"=>"success","data"=>$out]);
