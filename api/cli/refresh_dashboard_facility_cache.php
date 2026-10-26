<?php

/**
 * Builds the facility-level dashboard cache used for fast state reporting.
 * Run: php api/cli/refresh_dashboard_facility_cache.php --refresh
 */

require_once __DIR__ . '/../assets/conn/db.php';

if (!in_array('--refresh', $argv, true)) {
    fwrite(STDERR, "Usage: php api/cli/refresh_dashboard_facility_cache.php --refresh\n");
    exit(1);
}

$schema = <<<'SQL'
CREATE TABLE dashboard_facility_assessment_cache (
    cache_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    fac_id INT NOT NULL,
    nin_no VARCHAR(30) NULL,
    facility_name VARCHAR(120) NOT NULL,
    facility_type_id INT NULL,
    division VARCHAR(80) NULL,
    district VARCHAR(80) NULL,
    block VARCHAR(80) NULL,
    division_id INT NULL,
    district_id INT NULL,
    block_id INT NULL,
    assessment_id BIGINT NULL,
    assessment_name VARCHAR(255) NULL,
    assessment_status VARCHAR(30) NOT NULL DEFAULT 'NOT STARTED',
    assessment_total INT NOT NULL DEFAULT 0,
    assessment_active INT NOT NULL DEFAULT 0,
    assessment_completed INT NOT NULL DEFAULT 0,
    assessment_cancelled INT NOT NULL DEFAULT 0,
    assessment_other INT NOT NULL DEFAULT 0,
    assessment_start_date DATE NULL,
    assessment_expected_end_date DATE NULL,
    assessment_completed_on DATETIME NULL,
    response_zero INT NOT NULL DEFAULT 0,
    response_one INT NOT NULL DEFAULT 0,
    response_two INT NOT NULL DEFAULT 0,
    response_other INT NOT NULL DEFAULT 0,
    checkpoint_completed INT NOT NULL DEFAULT 0,
    checkpoint_total INT NOT NULL DEFAULT 0,
    checkpoint_completion_percent DECIMAL(7,2) NOT NULL DEFAULT 0,
    obtained_score DECIMAL(14,2) NOT NULL DEFAULT 0,
    total_possible_score DECIMAL(14,2) NOT NULL DEFAULT 0,
    score_percent DECIMAL(7,2) NOT NULL DEFAULT 0,
    refreshed_at DATETIME NOT NULL,
    PRIMARY KEY (cache_id),
    UNIQUE KEY uq_cache_assessment (assessment_id),
    KEY idx_cache_facility (fac_id),
    KEY idx_cache_geography (division, district, block),
    KEY idx_cache_type (facility_type_id),
    KEY idx_cache_status (assessment_status),
    KEY idx_cache_nin (nin_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

if (!$con->query('DROP TABLE IF EXISTS dashboard_facility_assessment_cache')) {
    throw new RuntimeException('Could not replace dashboard cache: ' . $con->error);
}
if (!$con->query($schema)) {
    throw new RuntimeException('Could not create dashboard cache: ' . $con->error);
}

$responseValueColumn = 'score';
$responseValueCheck = $con->query("SHOW COLUMNS FROM assessment_response LIKE 'response_value'");
if ($responseValueCheck && $responseValueCheck->num_rows > 0) {
    $responseValueColumn = 'response_value';
}

$insert = <<<SQL
INSERT INTO dashboard_facility_assessment_cache (
    fac_id, nin_no, facility_name, facility_type_id, division, district, block,
    division_id, district_id, block_id, assessment_id, assessment_name,
    assessment_status, assessment_total, assessment_active, assessment_completed, assessment_cancelled, assessment_other,
    assessment_start_date, assessment_expected_end_date, assessment_completed_on,
    response_zero, response_one, response_two, response_other,
    checkpoint_completed, checkpoint_total, checkpoint_completion_percent,
    obtained_score, total_possible_score, score_percent, refreshed_at
)
SELECT
    a.fac_id_fk, CAST(f.NIN_no AS CHAR), COALESCE(f.fac_name, ''), f.Health_facilty_type,
    f.division, f.Dist_Name, f.Block_Name, f.division_id, f.dist_id, f.block_id,
    a.assessment_id, a.assessment_name, COALESCE(UPPER(a.status), 'NOT STARTED'),
    CASE WHEN a.assessment_id IS NULL THEN 0 ELSE 1 END,
    CASE WHEN UPPER(COALESCE(a.status, '')) = 'ACTIVE' THEN 1 ELSE 0 END,
    CASE WHEN UPPER(COALESCE(a.status, '')) = 'COMPLETED' THEN 1 ELSE 0 END,
    CASE WHEN UPPER(COALESCE(a.status, '')) = 'CANCELLED' THEN 1 ELSE 0 END,
    CASE WHEN a.assessment_id IS NOT NULL AND UPPER(COALESCE(a.status, '')) NOT IN ('ACTIVE', 'COMPLETED', 'CANCELLED') THEN 1 ELSE 0 END,
    a.start_date, a.end_date, a.completed_on,
    COALESCE(r.response_zero, 0), COALESCE(r.response_one, 0), COALESCE(r.response_two, 0), COALESCE(r.response_other, 0),
    COALESCE(r.checkpoint_completed, 0), COALESCE(d.checkpoint_total, 0),
    CASE WHEN COALESCE(d.checkpoint_total, 0) > 0 THEN ROUND(COALESCE(r.checkpoint_completed, 0) * 100 / d.checkpoint_total, 2) ELSE 0 END,
    COALESCE(r.obtained_score, 0), COALESCE(r.total_possible_score, 0),
    CASE WHEN COALESCE(r.total_possible_score, 0) > 0 THEN ROUND(r.obtained_score * 100 / r.total_possible_score, 2) ELSE 0 END,
    NOW()
FROM assessment_master a
LEFT JOIN facilities f ON f.fac_id = a.fac_id_fk
LEFT JOIN (
    SELECT assessment_id,
           SUM(CASE WHEN CAST({$responseValueColumn} AS CHAR) = '0' THEN 1 ELSE 0 END) AS response_zero,
           SUM(CASE WHEN CAST({$responseValueColumn} AS CHAR) = '1' THEN 1 ELSE 0 END) AS response_one,
           SUM(CASE WHEN CAST({$responseValueColumn} AS CHAR) = '2' THEN 1 ELSE 0 END) AS response_two,
           SUM(CASE WHEN {$responseValueColumn} IS NULL OR CAST({$responseValueColumn} AS CHAR) NOT IN ('0', '1', '2') THEN 1 ELSE 0 END) AS response_other,
           COUNT(DISTINCT checkpoint_id) AS checkpoint_completed,
           SUM(COALESCE(score, 0)) AS obtained_score,
           SUM(COALESCE(max_score, 0)) AS total_possible_score
    FROM assessment_response
    GROUP BY assessment_id
) r ON r.assessment_id = a.assessment_id
LEFT JOIN (
    SELECT assessment_id, SUM(GREATEST(COALESCE(total_checkpoints, 0), 0)) AS checkpoint_total
    FROM assessment_department
    GROUP BY assessment_id
) d ON d.assessment_id = a.assessment_id
WHERE UPPER(COALESCE(a.status, '')) <> 'PENDING'
SQL;

$con->begin_transaction();
try {
    if (!$con->query($insert)) {
        throw new RuntimeException($con->error);
    }
    $rows = $con->affected_rows;
    $con->commit();
    echo "dashboard_facility_assessment_cache refreshed: {$rows} assessment records\n";
} catch (Throwable $e) {
    $con->rollback();
    throw $e;
}
