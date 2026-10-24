<?php

/*!
 * ==========================================================
 * SaQshi Open Source
 * State Indicator Analytics API
 * indicator_analytics.php
 * Version 1.1.0 | Updated 2026-07-13
 * ==========================================================
 */

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../../service/StateIndicatorAnalyticsService.php';

Security::requireMethod('GET');

try {
    if (($_GET['download'] ?? '') === 'multiple_issue_facilities') {
        $responseTable = $con->query("SHOW TABLES LIKE 'assessment_response'")->num_rows > 0
            ? 'assessment_response'
            : ($con->query("SHOW TABLES LIKE 'assessment_cycle_response'")->num_rows > 0 ? 'assessment_cycle_response' : '');
        if ($responseTable === '') {
            Response::serverError('Assessment response data is not available.');
        }
        $assessmentColumn = $con->query("SHOW COLUMNS FROM {$responseTable} LIKE 'assessment_id'")->num_rows > 0 ? 'assessment_id' : 'cycle_id';
        $conditions = ['r.score <= 0'];
        foreach (['state_code' => 'f.state_code', 'division' => 'f.division', 'district' => 'f.Dist_Name', 'block' => 'f.Block_Name', 'facility_type' => 'f.Health_facilty_type'] as $filter => $column) {
            if (($_GET[$filter] ?? '') === '') continue;
            $value = in_array($filter, ['state_code', 'facility_type'], true) ? (string)(int)$_GET[$filter] : "'" . $con->real_escape_string((string)$_GET[$filter]) . "'";
            $conditions[] = "{$column} = {$value}";
        }
        $search = trim((string)($_GET['search'] ?? ''));
        if ($search !== '') {
            $like = "'%" . $con->real_escape_string($search) . "%";
            $conditions[] = "(f.fac_name LIKE {$like} OR CAST(f.NIN_no AS CHAR) LIKE {$like} OR f.Dist_Name LIKE {$like} OR f.Block_Name LIKE {$like})";
        }
        $rows = $con->query("SELECT a.assessment_id, a.fac_id_fk, a.framework_code, r.checkpoint_id, f.fac_name, f.NIN_no, f.Dist_Name, f.Block_Name, f.Health_facilty_type FROM {$responseTable} r INNER JOIN assessment_master a ON a.assessment_id = r.{$assessmentColumn} INNER JOIN (SELECT fac_id_fk, MAX(assessment_id) AS assessment_id FROM assessment_master GROUP BY fac_id_fk) latest ON latest.assessment_id = a.assessment_id INNER JOIN facilities f ON f.fac_id = a.fac_id_fk WHERE " . implode(' AND ', $conditions) . ' ORDER BY f.Dist_Name, f.Block_Name, f.fac_name, r.checkpoint_id');
        if (!$rows) {
            throw new RuntimeException($con->error);
        }
        $issues = [];
        $collect = function (array $node, array $context = []) use (&$collect, &$issues): void {
            $context['department'] = trim((string)($node['dept_name'] ?? $node['department_name'] ?? $context['department'] ?? ''));
            $context['area'] = trim((string)($node['concern_name'] ?? $context['area'] ?? ''));
            if (isset($node['csqa_id']) || isset($node['checkpoint_id'])) {
                $id = (string)($node['csqa_id'] ?? $node['checkpoint_id']);
                $issues[$id] = ['name' => (string)($node['Checkpoint'] ?? $node['checkpoint'] ?? ('Checkpoint ' . $id)), 'department' => $context['department'], 'area' => $context['area']];
            }
            foreach ($node as $value) if (is_array($value)) $collect($value, $context);
        };
        foreach (glob(__DIR__ . '/../../config/frameworks/*.json') ?: [] as $path) {
            $config = json_decode((string)file_get_contents($path), true);
            if (is_array($config)) $collect($config);
        }
        $types = [];
        foreach ([__DIR__ . '/../../config/masters/facility_types_health.json', __DIR__ . '/../../config/masters/facility_types.json'] as $path) {
            $config = is_file($path) ? json_decode((string)file_get_contents($path), true) : [];
            foreach (is_array($config) ? $config : [] as $type) $types[(int)($type['fac_type_id'] ?? $type['facility_type_id'] ?? 0)] = (string)($type['facilities_type'] ?? $type['fac_type_name'] ?? '');
        }
        $facilities = [];
        while ($row = $rows->fetch_assoc()) {
            $id = (int)$row['fac_id_fk'];
            $facilities[$id] ??= ['row' => $row, 'issues' => [], 'departments' => [], 'areas' => []];
            $issue = $issues[(string)$row['checkpoint_id']] ?? ['name' => 'Checkpoint ' . $row['checkpoint_id'], 'department' => '', 'area' => ''];
            $facilities[$id]['issues'][(string)$row['checkpoint_id']] = $issue['name'];
            if ($issue['department'] !== '') $facilities[$id]['departments'][$issue['department']] = $issue['department'];
            if ($issue['area'] !== '') $facilities[$id]['areas'][$issue['area']] = $issue['area'];
        }
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="facilities-with-multiple-score-zero-issues-' . date('Ymd-His') . '.csv"');
        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($out, ['Facility Name', 'NIN', 'District', 'Block', 'Facility Type', 'Latest Assessment ID', 'Score-0 Issue Count', 'Score-0 Issues', 'Departments / Packages', 'Areas of Concern']);
        foreach ($facilities as $facility) {
            if (count($facility['issues']) < 2) continue;
            $row = $facility['row'];
            fputcsv($out, [$row['fac_name'], $row['NIN_no'], $row['Dist_Name'], $row['Block_Name'], $types[(int)$row['Health_facilty_type']] ?? ('Facility type ' . $row['Health_facilty_type']), $row['assessment_id'], count($facility['issues']), implode(' | ', $facility['issues']), implode(' | ', $facility['departments']), implode(' | ', $facility['areas'])]);
        }
        fclose($out);
        exit;
    }
    if (in_array(($_GET['download'] ?? ''), ['zero_facilities', 'low_score_facilities'], true)) {
        StateIndicatorAnalyticsService::streamZeroFacilityList($con, (int)($_GET['checkpoint_id'] ?? 0), $_GET);
    }
    if (($_GET['download'] ?? '') === 'issue_list') {
        StateIndicatorAnalyticsService::streamDetailedIssueList($con, $_GET);
    }
    if (($_GET['download'] ?? '') === 'capacity_building_roadmap') {
        StateIndicatorAnalyticsService::streamCapacityBuildingRoadmap($con, $_GET);
    }

    Response::success('State indicator analytics loaded', StateIndicatorAnalyticsService::analytics($con, $_GET));
} catch (Throwable $e) {
    Response::serverError($e->getMessage());
}
