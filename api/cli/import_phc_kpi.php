<?php

declare(strict_types=1);

/** Import PHC KPI records into performance_entries. */

$csvPath = $argv[1] ?? '';
if ($csvPath === '' || !is_file($csvPath)) {
    fwrite(STDERR, "Usage: php api/cli/import_phc_kpi.php <phckpi.csv> [--dry-run]\n");
    exit(1);
}

$dryRun = in_array('--dry-run', $argv, true);
$skipMissingFacilities = in_array('--skip-missing-facilities', $argv, true);
$deleteAllZeroImported = in_array('--delete-all-zero-imported', $argv, true);
$root = dirname(__DIR__, 2);

// The CLI can inherit blank DB_* variables. Load the repository .env explicitly.
foreach (file($root . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
    $line = trim($line);
    if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
    [$key, $value] = explode('=', $line, 2);
    putenv(trim($key) . '=' . trim($value, " \t\n\r\0\x0B\"'"));
}

require_once $root . '/api/bootstrap.php';
require_once $root . '/api/assets/conn/db.php';

$config = json_decode((string)file_get_contents($root . '/api/config/performance/kpi.json'), true, 512, JSON_THROW_ON_ERROR);
$indicators = [];
$collect = static function ($node) use (&$collect, &$indicators): void {
    if (!is_array($node)) return;
    if (isset($node['indicator_id'])) {
        $id = (int)$node['indicator_id'];
        if ($id > 0) $indicators[$id] = [
            'code' => (string)($node['indicator_code'] ?? ''),
            'name' => (string)($node['indicator_name'] ?? ''),
            'formula_id' => (int)($node['formula_id'] ?? 0),
        ];
    }
    foreach ($node as $value) $collect($value);
};
$collect($config);

$handle = fopen($csvPath, 'rb');
$headers = fgetcsv($handle, 0, ',', '"', '\\');
$required = ['indicator_id', 'result_value', 'date', 'fac_id', 'numerator_value', 'denominator_value'];
if (!$headers || array_diff($required, $headers)) throw new RuntimeException('CSV headers are invalid.');
$indexes = array_flip($headers);

$records = [];
$skipped = [];
while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
    $source = array_combine($headers, array_pad($row, count($headers), ''));
    $indicatorId = (int)$source['indicator_id'];
    if (!isset($indicators[$indicatorId])) { $skipped[] = $indicatorId; continue; }
    if (!preg_match('/^(\d{4})[\/-](\d{1,2})$/', trim((string)$source['date']), $period)) {
        throw new RuntimeException('Invalid period: ' . $source['date']);
    }
    $key = implode(':', [(int)$source['fac_id'], $indicatorId, (int)$period[1], (int)$period[2]]);
    $numeric = static function ($value): float {
        $value = trim((string)$value);
        return is_numeric($value) ? (float)$value : 0.0;
    };
    // The table stores numerator/denominator as NOT NULL decimals; non-numeric source values become 0.
    $records[$key] = [
        'fac_id' => (int)$source['fac_id'], 'indicator_id' => $indicatorId,
        'month' => (int)$period[2], 'year' => (int)$period[1],
        'numerator' => $numeric($source['numerator_value']),
        'denominator' => $numeric($source['denominator_value']),
        'result' => $numeric($source['result_value']),
        ...$indicators[$indicatorId],
    ];
}
fclose($handle);

if ($skipped) throw new RuntimeException('Unmapped KPI IDs: ' . implode(', ', array_unique($skipped)));

$facilityIds = array_values(array_unique(array_map(static fn(array $record): int => $record['fac_id'], $records)));
$facilityPlaceholders = implode(',', array_fill(0, count($facilityIds), '?'));
$facilityStmt = $con->prepare("SELECT fac_id FROM facilities WHERE fac_id IN ({$facilityPlaceholders})");
if (!$facilityStmt) throw new RuntimeException($con->error);
$facilityStmt->bind_param(str_repeat('i', count($facilityIds)), ...$facilityIds);
$facilityStmt->execute();
$foundFacilityIds = array_map('intval', array_column($facilityStmt->get_result()->fetch_all(MYSQLI_ASSOC), 'fac_id'));
$missingFacilityIds = array_values(array_diff($facilityIds, $foundFacilityIds));
$skippedMissingRecords = 0;
if ($missingFacilityIds) {
    if (!$skipMissingFacilities) throw new RuntimeException('CSV facility IDs missing from facilities: ' . implode(', ', $missingFacilityIds));
    $records = array_filter($records, static function (array $record) use ($missingFacilityIds, &$skippedMissingRecords): bool {
        if (in_array($record['fac_id'], $missingFacilityIds, true)) {
            $skippedMissingRecords++;
            return false;
        }
        return true;
    });
}

if ($dryRun) {
    echo json_encode(['target_records' => count($records), 'skipped_missing_facility_records' => $skippedMissingRecords, 'dry_run' => true], JSON_PRETTY_PRINT) . PHP_EOL;
    exit(0);
}

if ($deleteAllZeroImported) {
    $zeroRecords = array_filter($records, static fn(array $record): bool => $record['numerator'] == 0.0 && $record['denominator'] == 0.0 && $record['result'] == 0.0);
    $delete = $con->prepare("DELETE FROM performance_entries WHERE fac_id = ? AND dept_id = 0 AND indicator_type = 'KPI' AND indicator_id = ? AND entry_month = ? AND entry_year = ? AND numerator_value = 0 AND denominator_value = 0 AND result_value = 0");
    if (!$delete) throw new RuntimeException($con->error);
    $deleted = 0;
    $con->begin_transaction();
    try {
        foreach ($zeroRecords as $record) {
            $delete->bind_param('iiii', $record['fac_id'], $record['indicator_id'], $record['month'], $record['year']);
            if (!$delete->execute()) throw new RuntimeException($delete->error);
            $deleted += $delete->affected_rows;
        }
        $con->commit();
    } catch (Throwable $error) {
        $con->rollback();
        throw $error;
    }
    echo json_encode(['deleted_all_zero_imported_kpi_records' => $deleted, 'source_zero_records' => count($zeroRecords), 'skipped_missing_facility_records' => $skippedMissingRecords], JSON_PRETTY_PRINT) . PHP_EOL;
    exit(0);
}

$sql = 'INSERT INTO performance_entries (fac_id, dept_id, indicator_type, indicator_id, indicator_code, indicator_name, entry_month, entry_year, numerator_value, denominator_value, result_value, formula_id, remarks) VALUES (?, 0, \'KPI\', ?, ?, ?, ?, ?, ?, ?, ?, ?, \'Imported from phckpi.csv\') ON DUPLICATE KEY UPDATE indicator_code = VALUES(indicator_code), indicator_name = VALUES(indicator_name), numerator_value = VALUES(numerator_value), denominator_value = VALUES(denominator_value), result_value = VALUES(result_value), formula_id = VALUES(formula_id), remarks = VALUES(remarks)';
$stmt = $con->prepare($sql);
if (!$stmt) throw new RuntimeException($con->error);
$con->begin_transaction();
try {
    foreach ($records as $record) {
        $stmt->bind_param('iissiidddi', $record['fac_id'], $record['indicator_id'], $record['code'], $record['name'], $record['month'], $record['year'], $record['numerator'], $record['denominator'], $record['result'], $record['formula_id']);
        if (!$stmt->execute()) throw new RuntimeException($stmt->error);
    }
    $con->commit();
} catch (Throwable $error) {
    $con->rollback();
    throw $error;
}

echo json_encode(['imported_target_records' => count($records), 'skipped_missing_facility_records' => $skippedMissingRecords, 'indicator_type' => 'KPI'], JSON_PRETTY_PRINT) . PHP_EOL;
