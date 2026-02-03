<?php
require_once dirname(__DIR__, 4) . "/_bootstrap.php";
// validate_token();

require_once __DIR__ . "/../_service/AssessmentService.php";

$fid  = (int)($_GET['facility_id'] ?? 0);
$dept = (int)($_GET['department_id'] ?? 0);
$peri = (int)($_GET['assessment_period'] ?? 0);

if ($fid <= 0 || $dept <= 0 || $peri <= 0) {
    respond(
        ["status" => "error", "message" => "Missing parameters"],
        400
    );
}

$service = new AssessmentService($con);

/* =====================================================
   Call Assessment Service
===================================================== */
$result = $service->resumeAssessment($fid, $dept, $peri);

$statusCode = ($result['status'] === 'success') ? 200 : 500;
respond($result, $statusCode);
