<?php

declare(strict_types=1);

require_once __DIR__ . '/../assets/conn/db.php';

$execute = in_array('--execute', $argv ?? [], true);
$backupTable = 'assessment_action_plan_non_achievable_dqac_backup_20261020';
$join = "
    FROM assessment_action_plan ap
    INNER JOIN assessment_master a ON a.assessment_id = ap.assessment_id
    INNER JOIN facilities f ON f.fac_id = a.fac_id_fk
    INNER JOIN s_user dqac ON dqac.role_id_fk = 4
        AND dqac.dist_id = f.dist_id
        AND COALESCE(dqac.is_active, 1) = 1
    WHERE UPPER(TRIM(COALESCE(ap.achievability, ''))) = 'NON ACHIEVABLE'
";

$count = $con->query("SELECT COUNT(*) AS total {$join}")->fetch_assoc();
echo 'matching_non_achievable_plans=' . (int)$count['total'] . PHP_EOL;

if (!$execute) {
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
    $updateSql = "UPDATE assessment_action_plan ap
        INNER JOIN assessment_master a ON a.assessment_id = ap.assessment_id
        INNER JOIN facilities f ON f.fac_id = a.fac_id_fk
        INNER JOIN s_user dqac ON dqac.role_id_fk = 4 AND dqac.dist_id = f.dist_id AND COALESCE(dqac.is_active, 1) = 1
        SET ap.responsible_person = dqac.u_name,
            ap.responsible_dist_id = f.dist_id
        WHERE UPPER(TRIM(COALESCE(ap.achievability, ''))) = 'NON ACHIEVABLE'
          AND (
              COALESCE(ap.responsible_person, '') <> dqac.u_name
              OR ap.responsible_dist_id IS NULL
              OR ap.responsible_dist_id <> f.dist_id
          )";
    if (!$con->query($updateSql)) {
        throw new RuntimeException($con->error);
    }
    $updated = $con->affected_rows;
    $con->commit();
    echo "backup_table={$backupTable}" . PHP_EOL;
    echo "rows_backed_up={$backedUp}" . PHP_EOL;
    echo "rows_assigned={$updated}" . PHP_EOL;
} catch (Throwable $e) {
    $con->rollback();
    throw $e;
}
