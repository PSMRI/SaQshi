<?php

/**
 * DashboardService.php
 * -------------------------------------------------------
 * Performance dashboard service facade.
 * -------------------------------------------------------
 */

require_once __DIR__ . '/PerformanceService.php';

class DashboardService
{
    public static function dashboard(mysqli $con, int $facilityId): array
    {
        return PerformanceService::dashboard($con, $facilityId);
    }

    public static function summary(mysqli $con, int $facilityId): array
    {
        return PerformanceService::summary($con, $facilityId);
    }
}
