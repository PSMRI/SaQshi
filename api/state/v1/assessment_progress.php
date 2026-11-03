<?php

/*! SaQshi Open Source | State Assessment Progress API | assessment_progress.php | Version 1.0.0 */

require_once __DIR__ . '/_bootstrap.php';

Security::requireMethod('GET');

// Assessment status changes frequently. Never allow browser/proxy caches to
// reuse a response from another district or block filter selection.
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

try {
    Response::success('Assessment progress loaded', StateDashboardService::assessmentProgress($con, $_GET));
} catch (Throwable $e) {
    Response::serverError($e->getMessage());
}
