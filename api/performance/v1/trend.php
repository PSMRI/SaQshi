<?php

/*!
 * ==========================================================
 * SaQshi Open Source
 * Performance Trend API
 * trend.php
 * Version 1.0.0 | Updated 2026-07-06
 * ==========================================================
 */

require_once __DIR__ . '/../../auth_api.php';
require_once __DIR__ . '/../../assets/conn/db.php';
require_once __DIR__ . '/../../service/PerformanceService.php';

Security::requireMethod('GET');

try {
    $facId = SessionManager::facilityId();
    $showAll = !empty($_GET['all_indicators']) || (($_GET['scope'] ?? '') === 'all');
    $limit = $showAll ? 0 : 12;

    Response::success('Performance trend loaded', [
        'facility' => PerformanceService::facilityMeta($facId),
        'summary' => PerformanceService::summary($con, $facId),
        'month_status' => PerformanceService::monthlyStatus($con, $facId, $_GET),
        'entry_trend' => PerformanceService::trend($con, $facId, $_GET)['series'],
        'indicator_trends' => [
            'KPI' => PerformanceService::indicatorTrends($con, $facId, ['indicator_type' => 'KPI', 'limit' => $limit])['series'],
            'OUTCOME' => PerformanceService::indicatorTrends($con, $facId, ['indicator_type' => 'OUTCOME', 'limit' => $limit])['series']
        ]
    ]);
} catch (Throwable $e) {
    Response::serverError($e->getMessage());
}
