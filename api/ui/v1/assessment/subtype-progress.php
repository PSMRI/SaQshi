<?php
require_once dirname(__DIR__, 3) . "/_bootstrap.php";
require_once dirname(__DIR__, 3) . "/assessment/v1/_service/AssessmentService.php";

$service = new AssessmentService($con);

respond($service->getSubtypeProgress(
    (int)$_GET['facility_id'],
    (int)$_GET['department_id'],
    (int)$_GET['assessment_period'],
    (int)$_GET['facility_type'],
    (int)$_GET['concern_id']
));
