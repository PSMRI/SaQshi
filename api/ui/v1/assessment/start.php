<?php
require_once dirname(__DIR__, 5) . "/_bootstrap.php";
require_once dirname(__DIR__, 3) . "/assessment/v1/_service/AssessmentService.php";

$fid  = (int)($_GET['facility_id'] ?? 0);
$dept = (int)($_GET['department_id'] ?? 0);
$peri = (int)($_GET['assessment_period'] ?? 0);

if ($fid <= 0 || $dept <= 0 || $peri <= 0) {
    respond(["status"=>"error","message"=>"Missing parameters"], 400);
}

$service = new AssessmentService($con);
$res     = $service->resumeAssessment($fid, $dept, $peri);

respond(["status"=>"success","data"=>$res['data']]);
