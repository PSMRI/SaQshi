<?php

/*!
 * ==========================================================
 * SaQshi Open Source
 * Performance Outcome List API
 * outcome_list.php
 * Version 1.0.0 | Updated 2026-07-06
 * ==========================================================
 */

require_once __DIR__ . '/../../auth_api.php';
require_once __DIR__ . '/../../assets/conn/db.php';
require_once __DIR__ . '/../../service/OutcomeService.php';
require_once __DIR__ . '/../../service/PerformanceService.php';

Security::requireMethod('GET');

try {
    $facId = SessionManager::facilityId();
    $facility = PerformanceService::facilityMeta($facId);
    $facilityTypeId = (int)($_GET['facility_type_id'] ?? $facility['fac_type_id'] ?? 0);
    $departmentId = (int)($_GET['department_id'] ?? $_GET['dept_id'] ?? $_GET['fac_dept_id'] ?? 0);
    $rule = PerformanceService::facilityTypeRule($facilityTypeId);
    // Outcome reporting is configured by facility type, not by the departments
    // activated for an assessment.  An assessment may therefore be incomplete
    // (or not yet created) without preventing monthly outcome entry.
    $activeAssessment = PerformanceService::activeAssessment($con, $facId);
    $activeDepartments = PerformanceService::activeDepartmentIds($con, $facId);
    $items = OutcomeService::list($facilityTypeId, $departmentId);

    if ($departmentId > 0) {
        $items = array_values(array_filter($items, fn($item) => (int)($item['department_id'] ?? 0) === $departmentId));
    }

    Response::success('Outcome list loaded', [
        'facility' => $facility,
        'rule' => $rule,
        'effective_indicator_type' => 'OUTCOME',
        'active_assessment' => $activeAssessment,
        'active_department_ids' => $activeDepartments,
        'items' => $items
    ]);
} catch (Throwable $e) {
    Response::serverError($e->getMessage());
}
