<?php
/** Return checklist progress for every Area of Concern in one department. */
require_once __DIR__ . '/../../auth_api.php';
require_once __DIR__ . '/../../assets/conn/db.php';
require_once __DIR__ . '/../../core/FrameworkEngine.php';
require_once __DIR__ . '/../../core/AssessmentAccess.php';

Security::requireMethod('GET');

try {
    $assessmentId = (int)($_GET['assessment_id'] ?? 0);
    $deptId = (int)($_GET['dept_id'] ?? 0);
    $facId = SessionManager::facilityId();
    if ($assessmentId <= 0 || $deptId <= 0 || $facId <= 0) {
        Response::validation(['assessment_id' => 'Assessment and department are required']);
    }

    AssessmentAccess::requireEditableByCurrentUser($con, $assessmentId, $facId);
    $stmt = $con->prepare('SELECT a.framework_code, f.Health_facilty_type FROM assessment_master a JOIN facilities f ON f.fac_id = a.fac_id_fk WHERE a.assessment_id = ? AND a.fac_id_fk = ? LIMIT 1');
    $stmt->bind_param('ii', $assessmentId, $facId);
    $stmt->execute();
    $assessment = $stmt->get_result()->fetch_assoc();
    if (!$assessment) Response::error('Assessment not found for this facility');

    $stmt = $con->prepare('SELECT checkpoint_id FROM assessment_response WHERE assessment_id = ? AND dept_id = ?');
    $stmt->bind_param('ii', $assessmentId, $deptId);
    $stmt->execute();
    $saved = [];
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) $saved[(int)$row['checkpoint_id']] = true;

    $engine = FrameworkEngine::load($assessment['framework_code'] ?: 'saqshi-nqas');
    $progress = [];
    foreach ($engine->getCheckpoints((int)$assessment['Health_facilty_type'], $deptId) as $checkpoint) {
        $concernId = (int)($checkpoint['_concern_id'] ?? 0);
        $checkpointId = (int)($checkpoint['csqa_id'] ?? 0);
        if ($concernId <= 0 || $checkpointId <= 0) continue;
        if (!isset($progress[$concernId])) $progress[$concernId] = ['concern_id' => $concernId, 'total' => 0, 'answered' => 0, '_seen' => []];
        if (isset($progress[$concernId]['_seen'][$checkpointId])) continue;
        $progress[$concernId]['_seen'][$checkpointId] = true;
        $progress[$concernId]['total']++;
        if (isset($saved[$checkpointId])) $progress[$concernId]['answered']++;
    }

    foreach ($progress as &$row) unset($row['_seen']);
    unset($row);
    Response::success('Checklist concern progress loaded', ['concerns' => array_values($progress)]);
} catch (Throwable $e) {
    Response::serverError($e->getMessage());
}
