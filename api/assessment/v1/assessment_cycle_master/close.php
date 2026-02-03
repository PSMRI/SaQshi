<?php
require_once dirname(__DIR__, 3) . "/_bootstrap.php";

$id = (int)(json_decode(file_get_contents("php://input"), true)['assessment_id'] ?? 0);

if ($id <= 0) {
    respond(["status"=>"error","message"=>"Invalid id"], 400);
}

$con->query("
    UPDATE assessment_desc
    SET current_assment = 0
    WHERE id = $id
");

respond(["status"=>"success","message"=>"Assessment closed"]);
