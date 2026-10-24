<?php

/*! SaQshi Open Source | State Performance Facility List Export */

require_once __DIR__ . '/_bootstrap.php';

Security::requireMethod('GET');

try {
    $category = strtolower(trim((string)($_GET['category'] ?? '')));
    $categories = [
        'submitted' => 'Facilities with submissions',
        'kpi_only' => 'Facilities with KPI only',
        'outcome_only' => 'Facilities with Outcome only',
        'both' => 'Facilities with KPI and Outcome',
        'not_submitted' => 'Not submitted facilities',
    ];
    if (!isset($categories[$category])) {
        Response::validation(['category' => 'A valid performance facility category is required.']);
    }

    $where = ['1=1'];
    $types = '';
    $params = [];
    foreach ([
        'state_code' => 'f.state_code',
        'division' => 'f.division',
        'district' => 'f.Dist_Name',
        'block' => 'f.Block_Name',
        'facility_type' => 'f.Health_facilty_type',
    ] as $key => $column) {
        if (($_GET[$key] ?? '') !== '') {
            $where[] = "{$column} = ?";
            $types .= in_array($key, ['state_code', 'facility_type'], true) ? 'i' : 's';
            $params[] = in_array($key, ['state_code', 'facility_type'], true) ? (int)$_GET[$key] : (string)$_GET[$key];
        }
    }
    $search = trim((string)($_GET['search'] ?? ''));
    if ($search !== '') {
        $where[] = '(f.fac_name LIKE ? OR CAST(f.NIN_no AS CHAR) LIKE ? OR CAST(f.fac_id AS CHAR) LIKE ? OR f.Dist_Name LIKE ? OR f.Block_Name LIKE ?)';
        $like = '%' . $search . '%';
        $types .= 'sssss';
        array_push($params, $like, $like, $like, $like, $like);
    }

    $entryWhere = '';
    if (($_GET['month'] ?? '') !== '') $entryWhere .= ' AND pe.entry_month = ' . (int)$_GET['month'];
    if (($_GET['year'] ?? '') !== '') $entryWhere .= ' AND pe.entry_year = ' . (int)$_GET['year'];
    $having = match ($category) {
        'submitted' => 'COUNT(pe.fac_id) > 0',
        'kpi_only' => "SUM(CASE WHEN pe.indicator_type = 'KPI' THEN 1 ELSE 0 END) > 0 AND SUM(CASE WHEN pe.indicator_type = 'OUTCOME' THEN 1 ELSE 0 END) = 0",
        'outcome_only' => "SUM(CASE WHEN pe.indicator_type = 'KPI' THEN 1 ELSE 0 END) = 0 AND SUM(CASE WHEN pe.indicator_type = 'OUTCOME' THEN 1 ELSE 0 END) > 0",
        'both' => "SUM(CASE WHEN pe.indicator_type = 'KPI' THEN 1 ELSE 0 END) > 0 AND SUM(CASE WHEN pe.indicator_type = 'OUTCOME' THEN 1 ELSE 0 END) > 0",
        default => 'COUNT(pe.fac_id) = 0',
    };

    $sql = "SELECT f.fac_name, f.NIN_no, f.Dist_Name, f.Block_Name, f.Health_facilty_type,
                SUM(CASE WHEN pe.indicator_type = 'KPI' THEN 1 ELSE 0 END) AS kpi_entries,
                SUM(CASE WHEN pe.indicator_type = 'OUTCOME' THEN 1 ELSE 0 END) AS outcome_entries,
                COUNT(pe.fac_id) AS performance_entries,
                MAX(CONCAT(pe.entry_year, '-', LPAD(pe.entry_month, 2, '0'))) AS latest_month
            FROM facilities f
            LEFT JOIN performance_entries pe ON pe.fac_id = f.fac_id{$entryWhere}
            WHERE " . implode(' AND ', $where) . "
            GROUP BY f.fac_id, f.fac_name, f.NIN_no, f.Dist_Name, f.Block_Name, f.Health_facilty_type
            HAVING {$having}
            ORDER BY f.Dist_Name, f.Block_Name, f.fac_name";
    $stmt = $con->prepare($sql);
    if (!$stmt) throw new RuntimeException($con->error);
    if ($types !== '') $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $facilityTypes = [];
    $facilityTypeConfig = json_decode((string)file_get_contents(__DIR__ . '/../../config/masters/facility_types.json'), true);
    foreach (is_array($facilityTypeConfig) ? $facilityTypeConfig : [] as $facilityType) {
        $facilityTypes[(int)($facilityType['fac_type_id'] ?? 0)] = (string)($facilityType['fac'] ?? $facilityType['facilities_type'] ?? '');
    }

    $filename = preg_replace('/[^a-z0-9]+/i', '-', $category) . '-facilities-' . date('Y-m-d') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['Facility', 'NIN', 'District', 'Block', 'Facility type', 'KPI submitted', 'Outcome submitted', 'Latest submitted month'], ',', '"', '');
    foreach ($rows as $row) {
        $facilityTypeId = (int)($row['Health_facilty_type'] ?? 0);
        fputcsv($out, [
            $row['fac_name'],
            $row['NIN_no'],
            $row['Dist_Name'],
            $row['Block_Name'],
            $facilityTypes[$facilityTypeId] ?? ($facilityTypeId > 0 ? 'Facility type ' . $facilityTypeId : 'Unspecified'),
            (int)($row['kpi_entries'] ?? 0) > 0 ? 'Yes' : 'No',
            (int)($row['outcome_entries'] ?? 0) > 0 ? 'Yes' : 'No',
            $row['latest_month'] ?: '-'
        ], ',', '"', '');
    }
    fclose($out);
    exit;
} catch (Throwable $e) {
    Response::serverError($e->getMessage());
}
