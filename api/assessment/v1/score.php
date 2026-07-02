<?php

/**
 * score.php
 * -------------------------------------------------------
 * Calculate assessment score.
 *
 * Supports:
 * - Full assessment score
 * - Department-wise score
 *
 * Original score:
 * - assessment_response.score
 *
 * Improved score:
 * - assessment_action_plan.revised_score if available
 * - otherwise original score
 *
 * Simplified design:
 * - responses are stored by assessment_id in assessment_response
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

    $assessmentId = isset($_GET['assessment_id'])
        ? (int)$_GET['assessment_id']
        : 0;

    $deptId = isset($_GET['dept_id'])
        ? (int)$_GET['dept_id']
        : 0;

    if ($assessmentId <= 0) {
        Response::validation([
            'assessment_id' => 'assessment_id is required'
        ]);
    }

    /*
     * 1. Validate assessment belongs to facility
     */
    $sqlAssessment = "
        SELECT
            assessment_id,
            assessment_name,
            framework_code,
            fac_id_fk,
            start_date,
            end_date,
            status
        FROM assessment_master
        WHERE assessment_id = ?
          AND fac_id_fk = ?
        LIMIT 1
    ";

    $stmt = $con->prepare($sqlAssessment);

    if (!$stmt) {
        Response::serverError('Assessment prepare failed: ' . $con->error);
    }

    $stmt->bind_param('ii', $assessmentId, $facId);
    $stmt->execute();

    $assessment = $stmt->get_result()->fetch_assoc();

    if (!$assessment) {
        Response::error('Assessment not found for this facility');
    }

    $cycleId = $assessmentId;

    /*
     * 2. Department-wise score
     */
    if ($deptId > 0) {

        $sqlDept = "
            SELECT
                id,
                dept_id,
                status,
                is_active
            FROM assessment_department
            WHERE assessment_id = ?
              AND fac_id_fk = ?
              AND dept_id = ?
              AND is_active = 1
            LIMIT 1
        ";

        $stmt = $con->prepare($sqlDept);

        if (!$stmt) {
            Response::serverError('Department prepare failed: ' . $con->error);
        }

        $stmt->bind_param('iii', $assessmentId, $facId, $deptId);
        $stmt->execute();

        $dept = $stmt->get_result()->fetch_assoc();

        if (!$dept) {
            Response::error('Department not activated for this assessment');
        }

        /*
         * Revised score comes from completed action plan.
         * If revised_score is null, original score is used.
         */
        $sqlScore = "
            SELECT
                COUNT(r.response_id) AS answered_checkpoints,

                COALESCE(SUM(r.score), 0) AS original_obtained_score,

                COALESCE(
                    SUM(
                        CASE
                            WHEN ap.revised_score IS NOT NULL
                            THEN ap.revised_score
                            ELSE r.score
                        END
                    ),
                    0
                ) AS improved_obtained_score,

                COUNT(r.response_id) * 2 AS total_score,

                ROUND(
                    CASE
                        WHEN COUNT(r.response_id) = 0 THEN 0
                        ELSE (COALESCE(SUM(r.score), 0) / (COUNT(r.response_id) * 2)) * 100
                    END,
                    2
                ) AS original_percentage,

                ROUND(
                    CASE
                        WHEN COUNT(r.response_id) = 0 THEN 0
                        ELSE (
                            COALESCE(
                                SUM(
                                    CASE
                                        WHEN ap.revised_score IS NOT NULL
                                        THEN ap.revised_score
                                        ELSE r.score
                                    END
                                ),
                                0
                            ) / (COUNT(r.response_id) * 2)
                        ) * 100
                    END,
                    2
                ) AS improved_percentage,

                SUM(
                    CASE
                        WHEN ap.revised_score IS NOT NULL THEN 1
                        ELSE 0
                    END
                ) AS revised_checkpoints

            FROM assessment_response r

            LEFT JOIN assessment_action_plan ap
                ON ap.assessment_id = r.assessment_id
               AND ap.dept_id = r.dept_id
               AND ap.checkpoint_id = r.checkpoint_id

            WHERE r.assessment_id = ?
              AND r.dept_id = ?
        ";

        $stmt = $con->prepare($sqlScore);

        if (!$stmt) {
            Response::serverError('Score prepare failed: ' . $con->error);
        }

        $stmt->bind_param('ii', $cycleId, $deptId);
        $stmt->execute();

        $score = $stmt->get_result()->fetch_assoc();

        $originalPercentage = (float)($score['original_percentage'] ?? 0);
        $improvedPercentage = (float)($score['improved_percentage'] ?? 0);

        Response::success(
            'Department score calculated successfully',
            [
                'assessment' => [
                    'assessment_id' => (int)$assessment['assessment_id'],
                    'assessment_name' => $assessment['assessment_name'],
                    'framework_code' => $assessment['framework_code'],
                    'status' => $assessment['status']
                ],
                'scope' => 'DEPARTMENT',
                'dept_id' => $deptId,
                'department_status' => $dept['status'],

                'score' => [
                    'answered_checkpoints' => (int)($score['answered_checkpoints'] ?? 0),
                    'revised_checkpoints' => (int)($score['revised_checkpoints'] ?? 0),

                    'original' => [
                        'obtained_score' => (float)($score['original_obtained_score'] ?? 0),
                        'total_score' => (float)($score['total_score'] ?? 0),
                        'percentage' => $originalPercentage
                    ],

                    'improved' => [
                        'obtained_score' => (float)($score['improved_obtained_score'] ?? 0),
                        'total_score' => (float)($score['total_score'] ?? 0),
                        'percentage' => $improvedPercentage
                    ],

                    'improvement' => [
                        'score_gain' =>
                            (float)($score['improved_obtained_score'] ?? 0)
                            - (float)($score['original_obtained_score'] ?? 0),

                        'percentage_gain' =>
                            round($improvedPercentage - $originalPercentage, 2)
                    ]
                ]
            ]
        );
    }

    /*
     * 3. Full assessment score
     */
    $sqlSummary = "
        SELECT
            d.dept_id,
            d.status AS department_status,

            COUNT(r.response_id) AS answered_checkpoints,

            COALESCE(SUM(r.score), 0) AS original_obtained_score,

            COALESCE(
                SUM(
                    CASE
                        WHEN ap.revised_score IS NOT NULL
                        THEN ap.revised_score
                        ELSE r.score
                    END
                ),
                0
            ) AS improved_obtained_score,

            COUNT(r.response_id) * 2 AS total_score,

            ROUND(
                CASE
                    WHEN COUNT(r.response_id) = 0 THEN 0
                    ELSE (COALESCE(SUM(r.score), 0) / (COUNT(r.response_id) * 2)) * 100
                END,
                2
            ) AS original_percentage,

            ROUND(
                CASE
                    WHEN COUNT(r.response_id) = 0 THEN 0
                    ELSE (
                        COALESCE(
                            SUM(
                                CASE
                                    WHEN ap.revised_score IS NOT NULL
                                    THEN ap.revised_score
                                    ELSE r.score
                                END
                            ),
                            0
                        ) / (COUNT(r.response_id) * 2)
                    ) * 100
                END,
                2
            ) AS improved_percentage,

            SUM(
                CASE
                    WHEN ap.revised_score IS NOT NULL THEN 1
                    ELSE 0
                END
            ) AS revised_checkpoints

        FROM assessment_department d

        LEFT JOIN assessment_response r
            ON r.assessment_id = d.assessment_id
           AND r.dept_id = d.dept_id

        LEFT JOIN assessment_action_plan ap
            ON ap.assessment_id = d.assessment_id
           AND ap.dept_id = d.dept_id
           AND ap.checkpoint_id = r.checkpoint_id

        WHERE d.assessment_id = ?
          AND d.fac_id_fk = ?
          AND d.is_active = 1

        GROUP BY
            d.dept_id,
            d.status

        ORDER BY d.dept_id
    ";

    $stmt = $con->prepare($sqlSummary);

    if (!$stmt) {
        Response::serverError('Assessment score prepare failed: ' . $con->error);
    }

    $stmt->bind_param('ii', $assessmentId, $facId);
    $stmt->execute();

    $result = $stmt->get_result();

    $departments = [];

    $totalAnswered = 0;
    $totalRevised = 0;

    $totalOriginalObtained = 0;
    $totalImprovedObtained = 0;
    $totalScore = 0;

    while ($row = $result->fetch_assoc()) {

        $answered = (int)$row['answered_checkpoints'];
        $revised = (int)$row['revised_checkpoints'];

        $originalObtained = (float)$row['original_obtained_score'];
        $improvedObtained = (float)$row['improved_obtained_score'];
        $possible = (float)$row['total_score'];

        $originalPercentage = (float)$row['original_percentage'];
        $improvedPercentage = (float)$row['improved_percentage'];

        $departments[] = [
            'dept_id' => (int)$row['dept_id'],
            'department_status' => $row['department_status'],

            'answered_checkpoints' => $answered,
            'revised_checkpoints' => $revised,

            'original' => [
                'obtained_score' => $originalObtained,
                'total_score' => $possible,
                'percentage' => $originalPercentage
            ],

            'improved' => [
                'obtained_score' => $improvedObtained,
                'total_score' => $possible,
                'percentage' => $improvedPercentage
            ],

            'improvement' => [
                'score_gain' => round($improvedObtained - $originalObtained, 2),
                'percentage_gain' => round($improvedPercentage - $originalPercentage, 2)
            ]
        ];

        $totalAnswered += $answered;
        $totalRevised += $revised;

        $totalOriginalObtained += $originalObtained;
        $totalImprovedObtained += $improvedObtained;
        $totalScore += $possible;
    }

    $overallOriginalPercentage = $totalScore > 0
        ? round(($totalOriginalObtained / $totalScore) * 100, 2)
        : 0;

    $overallImprovedPercentage = $totalScore > 0
        ? round(($totalImprovedObtained / $totalScore) * 100, 2)
        : 0;

    Response::success(
        'Assessment score calculated successfully',
        [
            'assessment' => [
                'assessment_id' => (int)$assessment['assessment_id'],
                'assessment_name' => $assessment['assessment_name'],
                'framework_code' => $assessment['framework_code'],
                'status' => $assessment['status']
            ],

            'scope' => 'ASSESSMENT',

            'overall_score' => [
                'answered_checkpoints' => $totalAnswered,
                'revised_checkpoints' => $totalRevised,

                'original' => [
                    'obtained_score' => $totalOriginalObtained,
                    'total_score' => $totalScore,
                    'percentage' => $overallOriginalPercentage
                ],

                'improved' => [
                    'obtained_score' => $totalImprovedObtained,
                    'total_score' => $totalScore,
                    'percentage' => $overallImprovedPercentage
                ],

                'improvement' => [
                    'score_gain' => round($totalImprovedObtained - $totalOriginalObtained, 2),
                    'percentage_gain' => round($overallImprovedPercentage - $overallOriginalPercentage, 2)
                ]
            ],

            'departments' => $departments
        ]
    );

} catch (Throwable $e) {

    Response::serverError($e->getMessage());
}