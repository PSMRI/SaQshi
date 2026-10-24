<?php

declare(strict_types=1);

require_once __DIR__ . '/../assets/conn/db.php';

$backupTable = 'assessment_action_plan_score_two_backup_20261020';
$join = "
    FROM assessment_action_plan ap
    INNER JOIN assessment_response ar
        ON ar.assessment_id = ap.assessment_id
        AND COALESCE(ar.dept_id, 0) = COALESCE(ap.dept_id, 0)
        AND COALESCE(ar.checkpoint_id, 0) = COALESCE(ap.checkpoint_id, 0)
    WHERE TRIM(COALESCE(ar.response_value, '')) = '2'
";

$count = $con->query("SELECT COUNT(*) AS total {$join}")->fetch_assoc();
echo 'score_two_action_plans=' . (int)$count['total'] . PHP_EOL;

if (!in_array('--execute', $argv ?? [], true)) {
    echo "Dry run only. Re-run with --execute to back up and delete these rows." . PHP_EOL;
    exit(0);
}

if (!$con->query("CREATE TABLE IF NOT EXISTS `{$backupTable}` LIKE assessment_action_plan")) {
    throw new RuntimeException($con->error);
}

$con->begin_transaction();
try {
    if (!$con->query("INSERT IGNORE INTO `{$backupTable}` SELECT ap.* {$join}")) {
        throw new RuntimeException($con->error);
    }
    $backedUp = $con->affected_rows;
    if (!$con->query("DELETE ap {$join}")) {
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
