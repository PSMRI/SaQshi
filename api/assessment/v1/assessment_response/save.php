<?php
require_once dirname(__DIR__, 4) . "/_bootstrap.php";
// validate_token();

require_once __DIR__ . "/../_service/AssessmentService.php";

/* =====================================================
   Read JSON Input
===================================================== */
$input = json_decode(file_get_contents("php://input"), true);

$payload = [
    "fac_id"       => (int)($input['facility_id'] ?? 0),
    "fac_dept_id"  => (int)($input['department_id'] ?? 0),
    "ass_period"   => (int)($input['assessment_period'] ?? 0),
    "csqa_id"      => (int)($input['csqa_id'] ?? 0),
    "compliance"   => isset($input['compliance']) ? (int)$input['compliance'] : -1,
    "user_id"      => (int)($input['user_id'] ?? 0),
];

/* Basic validation */
if (
    $payload['fac_id'] <= 0 ||
    $payload['fac_dept_id'] <= 0 ||
    $payload['ass_period'] <= 0 ||
    $payload['csqa_id'] <= 0 ||
    $payload['compliance'] < 0 ||
    $payload['compliance'] > 2
) {
    respond(
        ["status" => "error", "message" => "Invalid payload"],
        400
    );
}

/* =====================================================
   Call Assessment Service
===================================================== */
$service = new AssessmentService($con);
$result  = $service->saveResponse($payload);

$statusCode = ($result['status'] === 'success') ? 200 : 500;
respond($result, $statusCode);
