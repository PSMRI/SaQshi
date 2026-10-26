<?php

require_once __DIR__ . '/../assets/conn/db.php';

$before = $con->query("SELECT COUNT(*) AS total FROM assessment_action_plan WHERE TRIM(UPPER(COALESCE(achievability, ''))) = 'ACHIEVABLE' AND (UPPER(COALESCE(status, '')) <> 'CLOSED' OR COALESCE(revised_score, -999999) <> 2)")->fetch_assoc();

$con->begin_transaction();
try {
    $sql = "UPDATE assessment_action_plan
            SET status = 'CLOSED', revised_score = 2
            WHERE TRIM(UPPER(COALESCE(achievability, ''))) = 'ACHIEVABLE'
              AND (UPPER(COALESCE(status, '')) <> 'CLOSED' OR COALESCE(revised_score, -999999) <> 2)";
    if (!$con->query($sql)) throw new RuntimeException($con->error);
    $affected = $con->affected_rows;
    $con->commit();
    $remaining = $con->query("SELECT COUNT(*) AS total FROM assessment_action_plan WHERE TRIM(UPPER(COALESCE(achievability, ''))) = 'ACHIEVABLE' AND (UPPER(COALESCE(status, '')) <> 'CLOSED' OR COALESCE(revised_score, -999999) <> 2)")->fetch_assoc();
    echo json_encode(['eligible_before' => (int)$before['total'], 'updated' => $affected, 'remaining' => (int)$remaining['total']]) . PHP_EOL;
} catch (Throwable $error) {
    $con->rollback();
    throw $error;
}
