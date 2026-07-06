<?php

require_once __DIR__ . '/../../auth_api.php';
require_once __DIR__ . '/../../assets/conn/db.php';
require_once __DIR__ . '/../../service/DashboardService.php';

Security::requireMethod('GET');

try {
    Response::success('Performance summary loaded', DashboardService::summary($con, SessionManager::facilityId()));
} catch (Throwable $e) {
    Response::serverError($e->getMessage());
}
