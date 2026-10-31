<?php

declare(strict_types=1);

/**
 * Imports the Bhavya facility master CSV without changing an existing facility.
 * Existing records are identified exclusively by Facility NIN Number / NIN_no.
 *
 * Usage:
 *   php api/cli/import_bhavya_facilities.php <csv-path> --dry-run
 *   php api/cli/import_bhavya_facilities.php <csv-path> --import
 */

$csvPath = $argv[1] ?? '';
$dryRun = in_array('--dry-run', $argv, true);
$apply = in_array('--import', $argv, true);
if ($csvPath === '' || !is_file($csvPath) || !is_readable($csvPath) || ($dryRun === $apply)) {
    fwrite(STDERR, "Usage: php api/cli/import_bhavya_facilities.php <csv-path> --dry-run|--import\n");
    exit(1);
}

$root = dirname(__DIR__, 2);
$dbKeys = ['DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD', 'DB_CONNECT_TIMEOUT'];
foreach ($dbKeys as $key) putenv($key);
foreach (file($root . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
    $line = trim($line);
    if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
    [$key, $value] = explode('=', $line, 2);
    $key = trim($key);
    if (!in_array($key, $dbKeys, true)) continue;
    putenv($key . '=' . trim($value, " \t\n\r\0\x0B\"'"));
}
require_once $root . '/api/assets/conn/db.php';

$normaliseHeader = static function (string $value): string {
    $value = preg_replace('/^\xEF\xBB\xBF/', '', $value) ?? $value;
    $value = str_replace("\xC2\xA0", ' ', $value);
    return strtolower(trim(preg_replace('/\s+/', ' ', $value) ?? $value));
};
$headers = [
    'state code', 'state', 'dist_id', 'dist_name', 'division_id', 'division_name',
    'block_id', 'block_name', 'facilities_type', 'fac_type_id', 'fac_name',
    'facility nin number', 'latitude', 'longitude'
];
$numeric = static function (?string $value, string $field, int $line): int {
    $value = trim((string)$value);
    if ($value === '' || !preg_match('/^\d+$/', $value)) throw new RuntimeException("Line {$line}: {$field} must be a whole number.");
    return (int)$value;
};
$coordinate = static function (?string $value, string $field, int $line, float $min, float $max): ?float {
    $value = trim((string)$value);
    if ($value === '') return null;
    if (!is_numeric($value) || (float)$value < $min || (float)$value > $max) throw new RuntimeException("Line {$line}: {$field} is invalid.");
    return (float)$value;
};

$handle = fopen($csvPath, 'rb');
$sourceHeaders = fgetcsv($handle);
if (!$sourceHeaders || array_map($normaliseHeader, $sourceHeaders) !== $headers) {
    throw new RuntimeException('CSV headers do not match the Bhavya facility-master format.');
}

$records = [];
$duplicateSourceNins = 0;
$missingOrInvalidNins = 0;
$line = 1;
while (($row = fgetcsv($handle)) !== false) {
    $line++;
    if ($row === [null] || $row === []) continue;
    if (count($row) !== count($headers)) throw new RuntimeException("Line {$line}: expected " . count($headers) . ' columns.');
    $data = array_combine($headers, $row);
    $nin = trim((string)$data['facility nin number']);
    // A missing NIN cannot be checked against the facility master safely, so
    // it is deliberately excluded rather than risking a duplicate facility.
    if ($nin === '' || !preg_match('/^\d{1,19}$/', $nin)) {
        $missingOrInvalidNins++;
        continue;
    }
    if (isset($records[$nin])) {
        $duplicateSourceNins++;
        continue;
    }
    $stateCode = $numeric($data['state code'], 'State Code', $line);
    $records[$nin] = [
        'state_name' => trim((string)$data['state']),
        'dist_name' => trim((string)$data['dist_name']),
        'block_name' => trim((string)$data['block_name']),
        'fac_name' => trim((string)$data['fac_name']),
        'fac_type_id' => $numeric($data['fac_type_id'], 'fac_type_id', $line),
        'block_id' => $numeric($data['block_id'], 'block_id', $line),
        'dist_id' => $numeric($data['dist_id'], 'dist_id', $line),
        'state_code' => $stateCode,
        'state_id' => $stateCode,
        'division_id' => $numeric($data['division_id'], 'division_id', $line),
        'division' => trim((string)$data['division_name']),
        'nin' => $nin,
        'lat' => $coordinate($data['latitude'], 'Latitude', $line, -90, 90),
        'longit' => $coordinate($data['longitude'], 'Longitude', $line, -180, 180)
    ];
}
fclose($handle);

$existingCheck = $con->prepare('SELECT 1 FROM facilities WHERE NIN_no = ? LIMIT 1');
$existingNins = [];
foreach ($records as $nin => $_record) {
    $existingCheck->bind_param('s', $nin);
    $existingCheck->execute();
    if ($existingCheck->get_result()->fetch_row()) $existingNins[] = $nin;
}
$newRecords = array_diff_key($records, array_flip($existingNins));
$summary = [
    'database' => (string)(getenv('DB_DATABASE') ?: ''),
    'source_unique_nins' => count($records),
    'duplicate_source_nins_skipped' => $duplicateSourceNins,
    'missing_or_invalid_nins_skipped' => $missingOrInvalidNins,
    'already_existing_nins_skipped' => count($existingNins),
    'new_facilities_to_insert' => count($newRecords),
    'mode' => $dryRun ? 'dry-run' : 'import'
];
if ($dryRun) {
    echo json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit(0);
}

$insert = $con->prepare('INSERT IGNORE INTO facilities (state_name, Dist_Name, Block_Name, fac_name, Health_facilty_type, block_id, dist_id, state_code, division_id, division, NIN_no, lat, longit, is_active, state_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)');
$inserted = 0;
$racedExisting = 0;
$con->begin_transaction();
try {
    foreach ($newRecords as $record) {
        $insert->bind_param(
            'ssssiiiiissddi',
            $record['state_name'], $record['dist_name'], $record['block_name'], $record['fac_name'],
            $record['fac_type_id'], $record['block_id'], $record['dist_id'], $record['state_code'],
            $record['division_id'], $record['division'], $record['nin'], $record['lat'], $record['longit'], $record['state_id']
        );
        if (!$insert->execute()) throw new RuntimeException($insert->error);
        if ($insert->affected_rows === 1) $inserted++;
        else $racedExisting++;
    }
    $con->commit();
} catch (Throwable $error) {
    $con->rollback();
    throw $error;
}

$summary['inserted'] = $inserted;
$summary['concurrent_or_unique_skipped'] = $racedExisting;
echo json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
