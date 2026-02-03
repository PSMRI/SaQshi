<?php
require_once dirname(__DIR__, 3) . "/_bootstrap.php";
require_once dirname(__DIR__, 3) . "/assessment/v1/_service/AssessmentService.php";

$input = json_decode(file_get_contents("php://input"), true);

$fid  = (int)($input['facility_id'] ?? 0);
$dept = (int)($input['department_id'] ?? 0);
$peri = (int)($input['assessment_period'] ?? 0);
$uid  = (int)($input['user_id'] ?? 0);

if ($fid <= 0 || $dept <= 0 || $peri <= 0 || $uid <= 0) {
    respond(["status"=>"error","message"=>"Invalid payload"], 400);
}

$service = new AssessmentService($con);
$result  = $service->completeAssessment($fid, $dept, $peri, $uid);

respond($result);
