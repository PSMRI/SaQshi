<?php

/**
 * list.php
 * -------------------------------------------------------
 * Lists all assessments for the logged-in user's facility.
 *
 * Method:
 * GET
 *
 * URL:
 * /api/assessment/v1/list.php
 * -------------------------------------------------------
 */

require_once __DIR__ . '/../../auth_api.php';
require_once __DIR__ . '/../../assets/conn/db.php';

Security::requireMethod('GET');

try {

    $facId  = SessionManager::facilityId();
    $userId = SessionManager::userId();

    if ($facId <= 0) {
        Response::error('Facility not assigned to logged-in user');
    }

    if ($userId <= 0) {
        Response::error('User session not found');
    }

    $sql = "
        SELECT
            a.assessment_id,
            a.assessment_name,
            a.framework_code,
            a.fac_id_fk,
            a.start_date,
            a.end_date,
            a.status,
            a.created_by,
            a.created_on,
            a.updated_on,
            a.completed_on,
            a.cancelled_on,
            COALESCE(ds.active_departments, 0) AS active_departments,
            COALESCE(dd.started_departments, 0) AS started_departments,
            COALESCE(dd.completed_departments, 0) AS completed_departments,
            COALESCE(rs.answered_checkpoints, 0) AS answered_checkpoints,
            COALESCE(rs.obtained_score, 0) AS obtained_score,
            COALESCE(rs.total_score, 0) AS total_score,
            COALESCE(rs.score_percent, 0) AS score_percent
        FROM assessment_master a
        LEFT JOIN (
            SELECT
                ass_period_id,
                COUNT(DISTINCT dept_id) AS active_departments
            FROM assessment_department_status
            WHERE fac_id_fk = ?
              AND is_active = 1
            GROUP BY ass_period_id
        ) ds
            ON ds.ass_period_id = a.assessment_id
        LEFT JOIN (
            SELECT
                assessment_id,
                COUNT(DISTINCT dept_id) AS started_departments,
                COUNT(DISTINCT CASE WHEN status = 'COMPLETED' THEN dept_id END) AS completed_departments
            FROM assessment_department
            WHERE fac_id_fk = ?
              AND is_active = 1
            GROUP BY assessment_id
        ) dd
            ON dd.assessment_id = a.assessment_id
        LEFT JOIN (
            SELECT
                assessment_id,
                COUNT(response_id) AS answered_checkpoints,
                ROUND(COALESCE(SUM(score), 0), 2) AS obtained_score,
                COUNT(response_id) * 2 AS total_score,
                ROUND(
                    CASE
                        WHEN COUNT(response_id) = 0 THEN 0
                        ELSE (COALESCE(SUM(score), 0) / (COUNT(response_id) * 2)) * 100
                    END,
                    2
                ) AS score_percent
            FROM assessment_response
            GROUP BY assessment_id
        ) rs
            ON rs.assessment_id = a.assessment_id
        WHERE a.fac_id_fk = ?
        ORDER BY a.assessment_id DESC
    ";

    $stmt = $con->prepare($sql);

    if (!$stmt) {
        Response::serverError('Prepare failed: ' . $con->error);
    }

    $stmt->bind_param('iii', $facId, $facId, $facId);
    $stmt->execute();

    $result = $stmt->get_result();
    $assessments = [];

    $summary = [
        'total' => 0,
        'active' => 0,
        'completed' => 0,
        'cancelled' => 0,
        'average_score' => 0
    ];

    $scoreTotal = 0;
    $scoreCount = 0;

    while ($row = $result->fetch_assoc()) {
        $status = strtoupper((string)($row['status'] ?? ''));
        $score = (float)($row['score_percent'] ?? 0);

        $summary['total']++;

        if ($status === 'ACTIVE') {
            $summary['active']++;
        } elseif ($status === 'COMPLETED') {
            $summary['completed']++;
        } elseif ($status === 'CANCELLED') {
            $summary['cancelled']++;
        }

        if ((int)$row['answered_checkpoints'] > 0) {
            $scoreTotal += $score;
            $scoreCount++;
        }

        $assessments[] = [
            'assessment_id' => (int)$row['assessment_id'],
            'assessment_name' => $row['assessment_name'],
            'framework_code' => $row['framework_code'],
            'fac_id' => (int)$row['fac_id_fk'],
            'start_date' => $row['start_date'],
            'end_date' => $row['end_date'],
            'status' => $status,
            'created_by' => (int)$row['created_by'],
            'created_on' => $row['created_on'],
            'updated_on' => $row['updated_on'],
            'completed_on' => $row['completed_on'],
            'cancelled_on' => $row['cancelled_on'],
            'active_departments' => (int)$row['active_departments'],
            'started_departments' => (int)$row['started_departments'],
            'completed_departments' => (int)$row['completed_departments'],
            'answered_checkpoints' => (int)$row['answered_checkpoints'],
            'obtained_score' => (float)$row['obtained_score'],
            'total_score' => (int)$row['total_score'],
            'score_percent' => $score
        ];
    }

    $summary['average_score'] = $scoreCount > 0
        ? round($scoreTotal / $scoreCount, 2)
        : 0;

    Response::success(
        'Assessment list fetched successfully',
        [
            'summary' => $summary,
            'assessments' => $assessments
        ]
    );

} catch (Throwable $e) {

    Response::serverError($e->getMessage());
}
