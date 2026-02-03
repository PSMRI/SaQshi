<?php
require_once dirname(__DIR__, 3) . "/_bootstrap.php";

$in = json_decode(file_get_contents("php://input"), true);
$id = (int)($in['assessment_id'] ?? 0);

if ($id <= 0) {
    respond(["status"=>"error","message"=>"Invalid id"], 400);
}

/* Find facility */
$row = $con->query("
    SELECT fac_id_fk FROM assessment_desc WHERE id = $id
")->fetch_assoc();

if (!$row) {
    respond(["status"=>"error","message"=>"Assessment not found"], 404);
}

$fac = (int)$row['fac_id_fk'];

$con->begin_transaction();

$con->query("
    UPDATE assessment_desc
    SET current_assment = 0
    WHERE fac_id_fk = $fac
");

$con->query("
    UPDATE assessment_desc
    SET current_assment = 1
    WHERE id = $id
");

$con->commit();

respond(["status"=>"success"]);
