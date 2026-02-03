<?php
require_once dirname(__DIR__, 3) . "/_bootstrap.php";
require_once dirname(__DIR__, 3) . "/assessment/v1/_service/AssessmentService.php";

$input = json_decode(file_get_contents("php://input"), true);

$service = new AssessmentService($con);
$result  = $service->saveResponse([
    "fac_id"      => (int)$input['facility_id'],
    "fac_dept_id" => (int)$input['department_id'],
    "ass_period"  => (int)$input['assessment_period'],
    "csqa_id"     => (int)$input['csqa_id'],
    "compliance"  => (int)$input['compliance'],
    "user_id"     => (int)$input['user_id']
]);

respond($result);
