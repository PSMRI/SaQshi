<?php

require_once __DIR__ . '/../../auth_api.php';
require_once __DIR__ . '/../../assets/conn/db.php';
require_once __DIR__ . '/../../service/KPIService.php';

Security::requireMethod('GET');

try {
    Response::success('KPI history loaded', KPIService::history($con, SessionManager::facilityId(), $_GET));
} catch (Throwable $e) {
    Response::serverError($e->getMessage());
}
