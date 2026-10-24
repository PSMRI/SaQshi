<?php

/**
 * Export the configured database's facilities table in the nested master-file
 * format used by api/config/masters/facilities.json.
 */
require_once __DIR__ . '/../core/Env.php';

// The CLI environment may expose blank DB_* variables. Clear only those
// values before loading the project's .env so this export uses its settings.
$envPath = dirname(__DIR__, 2) . '/.env';
$dbKeys = ['DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD', 'DB_CONNECT_TIMEOUT'];
foreach ($dbKeys as $key) {
    putenv($key);
}
foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) continue;
    [$key, $value] = explode('=', $line, 2);
    $key = trim($key);
    if (!in_array($key, $dbKeys, true)) continue;
    $value = trim($value);
    if ((str_starts_with($value, '"') && str_ends_with($value, '"')) || (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
        $value = substr($value, 1, -1);
    }
    putenv($key . '=' . $value);
}
require_once __DIR__ . '/../assets/conn/db.php';

$outputPath = $argv[1] ?? (__DIR__ . '/../config/masters/facilities_bh.json');
$typePath = __DIR__ . '/../config/masters/facility_types_health.json';
$types = is_file($typePath) ? json_decode((string) file_get_contents($typePath), true) : [];
$typeNames = [];
foreach (is_array($types) ? $types : [] as $type) {
    $typeNames[(string) ($type['fac_type_id'] ?? '')] = (string) ($type['facilities_type'] ?? '');
}

$sql = 'SELECT fac_id, state_id, state_name, division_id, division, dist_id, Dist_Name,
               block_id, Block_Name, NIN_no, fac_name, Health_facilty_type, lat, longit
        FROM facilities
        ORDER BY state_id, division_id, dist_id, block_id, fac_id';
$result = $con->query($sql);
if (!$result) {
    throw new RuntimeException('Unable to read facilities: ' . $con->error);
}

$states = [];
while ($row = $result->fetch_assoc()) {
    $stateId = (int) ($row['state_id'] ?? 0);
    $divisionId = (int) ($row['division_id'] ?? 0);
    $districtId = (int) ($row['dist_id'] ?? 0);
    $blockId = (int) ($row['block_id'] ?? 0);
    $typeValue = trim((string) ($row['Health_facilty_type'] ?? ''));
    $typeId = ctype_digit($typeValue) ? (int) $typeValue : 0;
    $typeName = $typeNames[$typeValue] ?? $typeValue;

    $states[$stateId] ??= [
        'state_id' => $stateId,
        'state_name' => (string) ($row['state_name'] ?? ''),
        'divisions' => [],
    ];
    $states[$stateId]['divisions'][$divisionId] ??= [
        'division_id' => $divisionId,
        'division_name' => (string) ($row['division'] ?? ''),
        'districts' => [],
    ];
    $states[$stateId]['divisions'][$divisionId]['districts'][$districtId] ??= [
        'dist_id' => $districtId,
        'dist_name' => (string) ($row['Dist_Name'] ?? ''),
        'blocks' => [],
    ];
    $states[$stateId]['divisions'][$divisionId]['districts'][$districtId]['blocks'][$blockId] ??= [
        'block_id' => $blockId,
        'block_name' => (string) ($row['Block_Name'] ?? ''),
        'facilities' => [],
    ];

    $facility = [
        'nin_no' => (string) ($row['NIN_no'] ?? ''),
        'fac_id' => (int) $row['fac_id'],
        'fac_name' => (string) ($row['fac_name'] ?? ''),
        'fac_type_id' => $typeId,
        'facilities_type' => $typeName,
    ];
    if ($row['lat'] !== null && $row['lat'] !== '') $facility['latitude'] = (float) $row['lat'];
    if ($row['longit'] !== null && $row['longit'] !== '') $facility['longitude'] = (float) $row['longit'];
    $states[$stateId]['divisions'][$divisionId]['districts'][$districtId]['blocks'][$blockId]['facilities'][] = $facility;
}

$normalize = static function (array $state): array {
    $state['divisions'] = array_map(static function (array $division): array {
        $division['districts'] = array_map(static function (array $district): array {
            $district['blocks'] = array_values($district['blocks']);
            return $district;
        }, $division['districts']);
        $division['districts'] = array_values($division['districts']);
        return $division;
    }, $state['divisions']);
    $state['divisions'] = array_values($state['divisions']);
    return $state;
};
$payload = array_values(array_map($normalize, $states));
$json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($json === false || file_put_contents($outputPath, $json . PHP_EOL) === false) {
    throw new RuntimeException('Unable to write ' . $outputPath);
}

echo 'Exported ' . $result->num_rows . ' facilities to ' . $outputPath . PHP_EOL;
