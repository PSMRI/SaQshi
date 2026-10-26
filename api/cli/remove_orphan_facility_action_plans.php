<?php

declare(strict_types=1);

/**
 * Removes CQI action plans associated with the latest ACTIVE/COMPLETED
 * assessments whose facility ID has no record in the local facilities master.
 * A same-structure backup is created before deletion.
 *
 * Usage: php api/cli/remove_orphan_facility_action_plans.php --execute
 */

require_once __DIR__ . '/../assets/conn/db.php';

$execute = in_array('--execute', $argv ?? [], true);
$backupTable = 'assessment_action_plan_orphan_backup_20261020';
$targetSql = "
    SELECT a.assessment_id
    FROM assessment_master a
    INNER JOIN (
        SELECT fac_id_fk, MAX(assessment_id) AS latest_assessment_id
        FROM assessment_master
        WHERE UPPER(COALESCE(status, '')) IN ('ACTIVE', 'COMPLETED')
        GROUP BY fac_id_fk
    ) latest ON latest.fac_id_fk = a.fac_id_fk
        AND latest.latest_assessment_id = a.assessment_id
    LEFT JOIN facilities f ON f.fac_id = a.fac_id_fk
    WHERE UPPER(COALESCE(a.status, '')) IN ('ACTIVE', 'COMPLETED')
      AND f.fac_id IS NULL
";

$countSql = "SELECT COUNT(*) AS action_plan_rows FROM assessment_action_plan ap INNER JOIN ({$targetSql}) targets ON targets.assessment_id = ap.assessment_id";
$countResult = $con->query($countSql);
if (!$countResult) {
    throw new RuntimeException($con->error);
}
$actionPlanRows = (int)($countResult->fetch_assoc()['action_plan_rows'] ?? 0);
echo "orphan_action_plan_rows={$actionPlanRows}" . PHP_EOL;

if (!$execute) {
    echo "Dry run only. Re-run with --execute to back up and delete these rows." . PHP_EOL;
    exit(0);
}

if (!$con->query("CREATE TABLE IF NOT EXISTS `{$backupTable}` LIKE assessment_action_plan")) {
    throw new RuntimeException($con->error);
}

$con->begin_transaction();
try {
    $backupSql = "INSERT IGNORE INTO `{$backupTable}` SELECT ap.* FROM assessment_action_plan ap INNER JOIN ({$targetSql}) targets ON targets.assessment_id = ap.assessment_id";
    if (!$con->query($backupSql)) {
        throw new RuntimeException($con->error);
    }
    $backedUp = $con->affected_rows;

    $deleteSql = "DELETE ap FROM assessment_action_plan ap INNER JOIN ({$targetSql}) targets ON targets.assessment_id = ap.assessment_id";
    if (!$con->query($deleteSql)) {
        throw new RuntimeException($con->error);
    }
    $deleted = $con->affected_rows;
    $con->commit();

    echo "backup_table={$backupTable}" . PHP_EOL;
    echo "rows_backed_up={$backedUp}" . PHP_EOL;
    echo "rows_deleted={$deleted}" . PHP_EOL;
} catch (Throwable $e) {
    $con->rollback();
    throw $e;
}

