<?php

/*! SaQshi Open Source | Current Month Activity Export | Version 1.1.0 */

require_once __DIR__ . '/_bootstrap.php';

Security::requireMethod('GET');

try {
    $activity = strtolower(trim((string)($_GET['activity'] ?? '')));
    $allowed = ['assessment_started', 'assessment_completed', 'assessment_in_progress', 'kpi_submitted', 'outcome_submitted', 'action_plans_updated'];
    if (!in_array($activity, $allowed, true)) {
        throw new InvalidArgumentException('Unknown current month activity export.');
    }

    $month = max(1, min(12, (int)($_GET['month'] ?? date('n'))));
    $year = max(2000, (int)($_GET['year'] ?? date('Y')));
    $masterPath = dirname(__DIR__, 2) . '/config/masters/facilities.json';
    $master = is_file($masterPath) ? json_decode((string)file_get_contents($masterPath), true) : [];
    $facilities = [];
    foreach ((array)$master as $state) {
        foreach ((array)($state['divisions'] ?? []) as $division) {
            foreach ((array)($division['districts'] ?? []) as $district) {
                foreach ((array)($district['blocks'] ?? []) as $block) {
                    foreach ((array)($block['facilities'] ?? []) as $facility) {
                        $facId = (int)($facility['fac_id'] ?? 0);
                        if ($facId > 0) {
                            $facilities[$facId] = [
                                'nin' => (string)($facility['nin_no'] ?? $facility['NIN_no'] ?? ''),
                                'name' => (string)($facility['fac_name'] ?? ''),
                                'district' => (string)($district['dist_name'] ?? $district['district_name'] ?? ''),
                                'block' => (string)($block['block_name'] ?? ''),
                                'type' => (string)($facility['facilities_type'] ?? '')
                            ];
                        }
                    }
                }
            }
        }
    }

    $activityLabel = ucwords(str_replace('_', ' ', $activity));
    if (in_array($activity, ['assessment_started', 'assessment_completed', 'assessment_in_progress'], true)) {
        $dateColumn = $activity === 'assessment_completed' ? 'completed_on' : 'start_date';
        $statusSql = $activity === 'assessment_in_progress'
            ? "AND UPPER(COALESCE(status, '')) IN ('ACTIVE', 'IN_PROGRESS')"
            : ($activity === 'assessment_completed' ? "AND UPPER(COALESCE(status, '')) = 'COMPLETED'" : "AND UPPER(COALESCE(status, '')) <> 'PENDING'");
        $sql = "SELECT fac_id_fk AS facility_id, assessment_id, assessment_name, {$dateColumn} AS activity_date, status FROM assessment_master WHERE MONTH({$dateColumn}) = ? AND YEAR({$dateColumn}) = ? {$statusSql} ORDER BY assessment_id DESC";
    } elseif (in_array($activity, ['kpi_submitted', 'outcome_submitted'], true)) {
        $indicatorType = $activity === 'kpi_submitted' ? 'KPI' : 'OUTCOME';
        $sql = "SELECT fac_id AS facility_id, '' AS assessment_id, '' AS assessment_name, CONCAT(entry_year, '-', LPAD(entry_month, 2, '0')) AS activity_date, indicator_type AS status FROM performance_entries WHERE indicator_type = '{$indicatorType}' AND entry_month = ? AND entry_year = ? GROUP BY fac_id, entry_year, entry_month, indicator_type ORDER BY fac_id";
    } else {
        $sql = "SELECT a.fac_id_fk AS facility_id, a.assessment_id, a.assessment_name, ap.updated_on AS activity_date, ap.status FROM assessment_action_plan ap LEFT JOIN assessment_master a ON a.assessment_id = ap.assessment_id WHERE MONTH(ap.updated_on) = ? AND YEAR(ap.updated_on) = ? ORDER BY ap.updated_on DESC";
    }

    $stmt = $con->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException('Unable to prepare the activity export: ' . $con->error);
    }
    $stmt->bind_param('ii', $month, $year);
    $stmt->execute();
    $result = $stmt->get_result();

    $filename = 'saqshi-current-month-' . str_replace('_', '-', $activity) . '-' . sprintf('%04d-%02d', $year, $month) . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
    // PHP 8.4+ emits a deprecation warning when the CSV escape parameter is
    // omitted. The application's error handler turns warnings into a 500.
    fputcsv($out, ['Facility ID', 'NIN', 'Facility', 'District', 'Block', 'Facility Type', 'Assessment ID', 'Assessment Name', 'Activity', 'Activity Date', 'Status'], ',', '"', '');
    while ($row = $result->fetch_assoc()) {
        $facility = $facilities[(int)($row['facility_id'] ?? 0)] ?? [];
        fputcsv($out, [
            $row['facility_id'] ?? '', $facility['nin'] ?? '', $facility['name'] ?? '', $facility['district'] ?? '',
            $facility['block'] ?? '', $facility['type'] ?? '', $row['assessment_id'] ?? '', $row['assessment_name'] ?? '',
            $activityLabel, $row['activity_date'] ?? '', $row['status'] ?? ''
        ], ',', '"', '');
    }
    $stmt->close();
    fclose($out);
} catch (Throwable $e) {
    if (class_exists('Event')) {
        Event::dispatch('state.current_month_activity_export.failed', [
            'activity' => $activity ?? null,
            'month' => $month ?? null,
            'year' => $year ?? null,
            'error' => $e->getMessage(),
            'source' => basename($e->getFile()) . ':' . $e->getLine()
        ]);
    }
    Response::serverError($e->getMessage());
}
