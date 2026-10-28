<?php

/**
 * Refreshes the facility-level dashboard cache through the MySQL procedure.
 *
 * The procedure is installed by:
 * api/sql/schema/2026_10_28_dashboard_facility_cache_event.sql
 *
 * Run: php api/cli/refresh_dashboard_facility_cache.php --refresh
 */

require_once __DIR__ . '/../assets/conn/db.php';

if (!in_array('--refresh', $argv, true)) {
    fwrite(STDERR, "Usage: php api/cli/refresh_dashboard_facility_cache.php --refresh\n");
    exit(1);
}

$procedureName = 'refresh_dashboard_facility_assessment_cache';
$procedureCheck = $con->prepare(
    'SELECT 1
       FROM information_schema.ROUTINES
      WHERE ROUTINE_SCHEMA = DATABASE()
        AND ROUTINE_TYPE = \'PROCEDURE\'
        AND ROUTINE_NAME = ?'
);

if (!$procedureCheck) {
    throw new RuntimeException('Could not verify dashboard cache refresh procedure: ' . $con->error);
}

$procedureCheck->bind_param('s', $procedureName);
$procedureCheck->execute();
$procedureExists = $procedureCheck->get_result()->num_rows > 0;
$procedureCheck->close();

if (!$procedureExists) {
    throw new RuntimeException(
        'Dashboard cache refresh procedure is not installed. Apply api/sql/schema/2026_10_28_dashboard_facility_cache_event.sql first.'
    );
}

if (!$con->query("CALL {$procedureName}()")) {
    throw new RuntimeException('Could not refresh dashboard cache: ' . $con->error);
}

while ($con->more_results() && $con->next_result()) {
    $result = $con->store_result();
    if ($result instanceof mysqli_result) {
        $result->free();
    }
}

$count = $con->query('SELECT COUNT(*) AS total FROM dashboard_facility_assessment_cache');
if (!$count) {
    throw new RuntimeException('Dashboard cache refreshed but could not read its row count: ' . $con->error);
}

$total = (int)($count->fetch_assoc()['total'] ?? 0);
echo "dashboard_facility_assessment_cache refreshed: {$total} assessment records\n";
