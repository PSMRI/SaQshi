<?php
require_once dirname(__DIR__, 4) . "/_bootstrap.php";
// validate_token();

require_once __DIR__ . "/../_service/AssessmentService.php";

$fid  = (int)($_GET['facility_id'] ?? 0);
$dept = (int)($_GET['department_id'] ?? 0);
$peri = (int)($_GET['assessment_period'] ?? 0);
$fty  = (int)($_GET['facility_type'] ?? 0);
$conc = (int)($_GET['concern_id'] ?? 0);

if ($fid <= 0 || $dept <= 0 || $peri <= 0 || $fty <= 0 || $conc <= 0) {
    respond(
        ["status" => "error", "message" => "Missing parameters"],
        400
    );
}

$service = new AssessmentService($con);

$result = $service->getSubtypeProgress(
    $fid,
    $dept,
    $peri,
    $fty,
    $conc
);

$statusCode = ($result['status'] === 'success') ? 200 : 500;
respond($result, $statusCode);
