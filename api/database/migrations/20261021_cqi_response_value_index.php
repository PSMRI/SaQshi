<?php

declare(strict_types=1);

require_once __DIR__ . '/../../assets/conn/db.php';

$columnName = 'cqi_response_value';
$indexName = 'idx_response_assessment_cqi_value';
$column = $con->query("SHOW COLUMNS FROM assessment_response LIKE '{$columnName}'");

if (!$column || $column->num_rows === 0) {
    if (!$con->query(
        "ALTER TABLE assessment_response
         ADD COLUMN {$columnName} TINYINT
         GENERATED ALWAYS AS (
             CASE TRIM(COALESCE(response_value, ''))
                 WHEN '0' THEN 0
                 WHEN '1' THEN 1
                 WHEN '2' THEN 2
                 ELSE NULL
             END
         ) STORED"
    )) {
        throw new RuntimeException('Unable to add CQI response value column: ' . $con->error);
    }
}

$existing = $con->query("SHOW INDEX FROM assessment_response WHERE Key_name = '{$indexName}'");
if (!$existing || $existing->num_rows === 0) {
    if (!$con->query("ALTER TABLE assessment_response ADD INDEX {$indexName} (assessment_id, {$columnName})")) {
        throw new RuntimeException('Unable to add CQI response index: ' . $con->error);
    }
}

echo "{$columnName} and {$indexName} are ready" . PHP_EOL;
