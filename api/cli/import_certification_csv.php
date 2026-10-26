<?php

declare(strict_types=1);

/** Import a legacy certification CSV into the selected SaQshi database. */

$csvPath = $argv[1] ?? '';
$dryRun = in_array('--dry-run', $argv, true);
$keepNinMatched = in_array('--keep-nin-matched', $argv, true);
if ($csvPath === '' || !is_file($csvPath) || !is_readable($csvPath)) {
    fwrite(STDERR, "Usage: php api/cli/import_certification_csv.php <certification.csv> [--dry-run]\n");
    exit(1);
}

$root = dirname(__DIR__, 2);
foreach (file($root . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
    $line = trim($line);
    if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
    [$key, $value] = explode('=', $line, 2);
    putenv(trim($key) . '=' . trim($value, " \t\n\r\0\x0B\"'"));
}
putenv('DB_DATABASE=saqshi_bh');

require_once $root . '/api/bootstrap.php';
require_once $root . '/api/assets/conn/db.php';

$expected = ['id', 'dist', 'block', 'fac_name', 'fac_type', 'cert_type', 'cert_detailscol', 'cert_issue', 'validity', 'score', 'lat', 'longi', 'dist_id', 'block_id', 'fac_id', 'Cert_status', 'ass_mod', 'date_of_ass', 'fac_nin', 'state_id'];
$handle = fopen($csvPath, 'rb');
$headers = fgetcsv($handle, 0, ',', '"', '\\');
if ($headers !== $expected) {
    throw new RuntimeException('CSV header does not match the expected certification import format.');
}

$date = static function (?string $value, bool $required = false): ?string {
    $value = trim((string)$value);
    if ($value === '' || strtoupper($value) === 'NULL') {
        if ($required) throw new RuntimeException('Required date is missing.');
        return null;
    }
    $candidate = substr($value, 0, 10);
    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $candidate);
    if (!$parsed || $parsed->format('Y-m-d') !== $candidate) throw new RuntimeException("Invalid date: {$value}");
    return $candidate;
};
$nullableInt = static fn(?string $value): ?int => trim((string)$value) === '' || strtoupper(trim((string)$value)) === 'NULL' ? null : (int)$value;
$nullableNumber = static fn(?string $value): ?float => trim((string)$value) === '' || strtoupper(trim((string)$value)) === 'NULL' ? null : (float)$value;

$records = [];
$line = 1;
while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
    $line++;
    if (count($row) !== count($expected)) throw new RuntimeException("CSV row {$line} has an incorrect column count.");
    $source = array_combine($expected, $row);
    $id = $nullableInt($source['id']);
    if (!$id || trim((string)$source['cert_type']) === '' || trim((string)$source['Cert_status']) === '' || trim((string)$source['ass_mod']) === '') {
        throw new RuntimeException("CSV row {$line} is missing a required certification value.");
    }
    $certIssue = $date($source['cert_issue']);
    $dateOfAssessment = $date($source['date_of_ass']) ?? $certIssue;
    if ($dateOfAssessment === null) {
        throw new RuntimeException("CSV row {$line} is missing both assessment and certification issue dates.");
    }
    $records[$id] = [
        $id, $source['dist'] ?: null, $source['block'] ?: null, $source['fac_name'] ?: null, $source['fac_type'] ?: null,
        $source['cert_type'], $source['cert_detailscol'] ?: null, null, $certIssue, $date($source['validity']),
        $nullableNumber($source['score']), $nullableNumber($source['lat']), $nullableNumber($source['longi']),
        $nullableInt($source['dist_id']), $nullableInt($source['block_id']), $nullableInt($source['fac_id']), $source['Cert_status'],
        $source['ass_mod'], $dateOfAssessment, $nullableInt($source['fac_nin']), $nullableInt($source['state_id'])
    ];
}
fclose($handle);

$table = $con->query("SHOW TABLES LIKE 'cert_details'");
if (!$table || $table->num_rows === 0) throw new RuntimeException('Target table saqshi_bh.cert_details does not exist.');
$existing = $con->query('SELECT COUNT(*) AS total FROM cert_details')->fetch_assoc();
$ids = implode(',', array_map('intval', array_keys($records)));
$collisions = $con->query("SELECT COUNT(*) AS total FROM cert_details WHERE id IN ({$ids})")->fetch_assoc();
$facilityMatches = $con->query("SELECT
    COUNT(DISTINCT CASE WHEN by_id.fac_id IS NOT NULL THEN c.id END) AS matched_by_facility_id,
    COUNT(DISTINCT CASE WHEN by_nin.fac_id IS NOT NULL THEN c.id END) AS matched_by_nin,
    COUNT(DISTINCT CASE WHEN by_id.fac_id IS NOT NULL OR by_nin.fac_id IS NOT NULL THEN c.id END) AS matched_by_either,
    COUNT(DISTINCT CASE WHEN by_id.fac_id IS NULL AND by_nin.fac_id IS NULL THEN c.id END) AS missing_from_facility_master
    FROM cert_details c
    LEFT JOIN facilities by_id ON by_id.fac_id = c.fac_id
    LEFT JOIN facilities by_nin ON by_nin.NIN_no = c.fac_nin
    WHERE c.id IN ({$ids})")->fetch_assoc();

if ($keepNinMatched) {
    $matched = (int)$facilityMatches['matched_by_nin'];
    $removed = count($records) - $matched;
    if ($dryRun) {
        echo json_encode(['database' => 'saqshi_bh', 'records_to_keep_by_nin' => $matched, 'records_to_update_with_master_facility_id' => $matched, 'records_to_remove_without_nin_match' => $removed, 'mode' => 'dry-run'], JSON_PRETTY_PRINT) . PHP_EOL;
        exit(0);
    }
    $con->begin_transaction();
    try {
        $update = $con->query("UPDATE cert_details c INNER JOIN facilities f ON f.NIN_no = c.fac_nin SET c.fac_id = f.fac_id WHERE c.id IN ({$ids})");
        if (!$update) throw new RuntimeException($con->error);
        $delete = $con->query("DELETE c FROM cert_details c LEFT JOIN facilities f ON f.NIN_no = c.fac_nin WHERE c.id IN ({$ids}) AND f.fac_id IS NULL");
        if (!$delete) throw new RuntimeException($con->error);
        $updated = $con->affected_rows;
        $con->commit();
    } catch (Throwable $error) {
        $con->rollback();
        throw $error;
    }
    echo json_encode(['database' => 'saqshi_bh', 'kept_by_nin' => $matched, 'facility_ids_updated' => $matched, 'removed_without_nin_match' => $removed, 'mode' => 'reconciled'], JSON_PRETTY_PRINT) . PHP_EOL;
    exit(0);
}

if ($dryRun) {
    echo json_encode(['database' => 'saqshi_bh', 'source_rows' => count($records), 'existing_target_rows' => (int)$existing['total'], 'source_id_collisions' => (int)$collisions['total'], 'matched_by_facility_id' => (int)$facilityMatches['matched_by_facility_id'], 'matched_by_nin' => (int)$facilityMatches['matched_by_nin'], 'matched_by_either' => (int)$facilityMatches['matched_by_either'], 'missing_from_facility_master' => (int)$facilityMatches['missing_from_facility_master'], 'mode' => 'dry-run'], JSON_PRETTY_PRINT) . PHP_EOL;
    exit(0);
}

$sql = 'INSERT INTO cert_details (id, dist, block, fac_name, fac_type, cert_type, cert_detailscol, applied_date, cert_issue, validity, score, lat, longi, dist_id, block_id, fac_id, Cert_status, ass_mod, date_of_ass, fac_nin, state_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE dist=VALUES(dist), block=VALUES(block), fac_name=VALUES(fac_name), fac_type=VALUES(fac_type), cert_type=VALUES(cert_type), cert_detailscol=VALUES(cert_detailscol), applied_date=VALUES(applied_date), cert_issue=VALUES(cert_issue), validity=VALUES(validity), score=VALUES(score), lat=VALUES(lat), longi=VALUES(longi), dist_id=VALUES(dist_id), block_id=VALUES(block_id), fac_id=VALUES(fac_id), Cert_status=VALUES(Cert_status), ass_mod=VALUES(ass_mod), date_of_ass=VALUES(date_of_ass), fac_nin=VALUES(fac_nin), state_id=VALUES(state_id)';
$statement = $con->prepare($sql);
if (!$statement) throw new RuntimeException($con->error);
$con->begin_transaction();
try {
    foreach ($records as $record) {
        $statement->bind_param(str_repeat('s', count($record)), ...$record);
        if (!$statement->execute()) throw new RuntimeException($statement->error);
    }
    $con->commit();
} catch (Throwable $error) {
    $con->rollback();
    throw $error;
}
echo json_encode(['database' => 'saqshi_bh', 'imported_or_updated' => count($records), 'source_id_collisions_updated' => (int)$collisions['total'], 'mode' => 'import'], JSON_PRETTY_PRINT) . PHP_EOL;
