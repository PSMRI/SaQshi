<?php

/** Update facility references in a user CSV from the configured facilities table. */
require_once __DIR__ . '/../core/Env.php';

$sourcePath = $argv[1] ?? '';
if ($sourcePath === '' || !is_file($sourcePath)) {
    throw new InvalidArgumentException('Provide an existing user_nin.csv path as the first argument.');
}
$destinationPath = $argv[2] ?? $sourcePath;

$dbKeys = ['DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD', 'DB_CONNECT_TIMEOUT'];
foreach ($dbKeys as $key) putenv($key);
$envPath = dirname(__DIR__, 2) . '/.env';
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

$input = fopen($sourcePath, 'rb');
if ($input === false || ($headers = fgetcsv($input)) === false) {
    throw new RuntimeException('Unable to read CSV headers.');
}
$headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $headers[0]);

$firstColumn = static function (string $name) use ($headers): int {
    $index = array_search($name, $headers, true);
    if ($index === false) throw new RuntimeException("Required column {$name} was not found.");
    return $index;
};
$ninColumn = $firstColumn('NIN_no');
$targetColumns = [
    'fac_id' => $firstColumn('fac_id'),
    'dist_id' => $firstColumn('dist_id'),
    'block_id' => $firstColumn('block_id'),
    'division_id' => $firstColumn('division_id'),
];

$facilities = [];
$result = $con->query('SELECT NIN_no, fac_id, dist_id, block_id, division_id FROM facilities WHERE COALESCE(is_active, 1) = 1');
if (!$result) throw new RuntimeException('Unable to read facilities: ' . $con->error);
while ($facility = $result->fetch_assoc()) {
    $nin = trim((string) ($facility['NIN_no'] ?? ''));
    if ($nin === '') continue;
    $facilities[$nin][] = $facility;
}

$directory = dirname($destinationPath);
$basename = pathinfo($destinationPath, PATHINFO_FILENAME);
$extension = pathinfo($destinationPath, PATHINFO_EXTENSION);
$temporaryPath = $directory . DIRECTORY_SEPARATOR . $basename . '.updating.' . $extension;
$backupPath = dirname($sourcePath) . DIRECTORY_SEPARATOR . pathinfo($sourcePath, PATHINFO_FILENAME) . '.before_facility_update.' . pathinfo($sourcePath, PATHINFO_EXTENSION);
$unmatchedPath = dirname($sourcePath) . DIRECTORY_SEPARATOR . pathinfo($sourcePath, PATHINFO_FILENAME) . '_unmatched.' . pathinfo($sourcePath, PATHINFO_EXTENSION);
$output = fopen($temporaryPath, 'wb');
$unmatched = fopen($unmatchedPath, 'wb');
if ($output === false || $unmatched === false) throw new RuntimeException('Unable to create CSV output files.');
fputcsv($output, $headers);
fputcsv($unmatched, ['row_number', 'u_id', 'u_name', 'nin_no', 'match_status', 'source_fac_id', 'source_dist_id', 'source_block_id', 'source_division_id']);

$rowNumber = 1;
$updated = 0;
$unmatchedCount = 0;
while (($row = fgetcsv($input)) !== false) {
    $rowNumber++;
    $row = array_pad($row, count($headers), '');
    $nin = trim((string) ($row[$ninColumn] ?? ''));
    $matches = $nin === '' ? [] : ($facilities[$nin] ?? []);
    $uniqueMatches = [];
    foreach ($matches as $match) {
        $key = implode('|', [$match['fac_id'], $match['dist_id'], $match['block_id'], $match['division_id']]);
        $uniqueMatches[$key] = $match;
    }
    if (count($uniqueMatches) === 1) {
        $match = reset($uniqueMatches);
        foreach ($targetColumns as $field => $column) $row[$column] = (string) $match[$field];
        $updated++;
    } else {
        $status = $nin === '' ? 'MISSING_NIN' : (count($uniqueMatches) === 0 ? 'NOT_FOUND' : 'AMBIGUOUS_NIN');
        fputcsv($unmatched, [
            $rowNumber,
            $row[$firstColumn('u_id')] ?? '',
            $row[$firstColumn('u_name')] ?? '',
            $nin,
            $status,
            $row[$targetColumns['fac_id']] ?? '',
            $row[$targetColumns['dist_id']] ?? '',
            $row[$targetColumns['block_id']] ?? '',
            $row[$targetColumns['division_id']] ?? '',
        ]);
        $unmatchedCount++;
    }
    fputcsv($output, $row);
}
fclose($input);
fclose($output);
fclose($unmatched);

if ($destinationPath === $sourcePath && !is_file($backupPath) && !copy($sourcePath, $backupPath)) {
    throw new RuntimeException('Unable to create backup before updating the source CSV.');
}
if (!rename($temporaryPath, $destinationPath)) {
    @unlink($temporaryPath);
    throw new RuntimeException('Unable to write the updated CSV.');
}

echo "Updated={$updated}; unmatched={$unmatchedCount}\n";
if ($destinationPath === $sourcePath) echo "Backup={$backupPath}\n";
echo "Updated CSV={$destinationPath}\n";
echo "Unmatched list={$unmatchedPath}\n";
