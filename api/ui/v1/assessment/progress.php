<?php
require_once dirname(__DIR__, 3) . "/_bootstrap.php";
require_once dirname(__DIR__, 3) . "/assessment/v1/_service/AssessmentService.php";

$fid  = (int)($_GET['facility_id'] ?? 0);
$peri = (int)($_GET['assessment_period'] ?? 0);

$service = new AssessmentService($con);
respond($service->getProgress($fid, $peri));
