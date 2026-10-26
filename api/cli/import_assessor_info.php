<?php

/** Import assessor details from a migration CSV into assessment_assessor_info. */

require_once __DIR__ . '/../assets/conn/db.php';

$args = $argv;
array_shift($args);
$import = in_array('--import', $args, true);
$fileIndex = array_search('--file', $args, true);
$file = $fileIndex !== false ? (string)($args[$fileIndex + 1] ?? '') : '';

if ($file === '' || !is_file($file)) {
    fwrite(STDERR, "Usage: php api/cli/import_assessor_info.php --file <csv-path> [--import]\n");
    exit(1);
}

$handle = fopen($file, 'rb');
$header = fgetcsv($handle);
if (!$header) throw new RuntimeException('CSV header is missing.');
$header = array_map(static fn($value) => trim((string)$value), $header);
$required = ['assessment_id', 'fac_id_fk', 'assessment_date', 'assessment_type'];
foreach ($required as $column) {
    if (!in_array($column, $header, true)) throw new RuntimeException("CSV column is missing: {$column}");
}

$assessmentIds = [];
$result = $con->query('SELECT assessment_id FROM assessment_master');
while ($row = $result->fetch_assoc()) $assessmentIds[(int)$row['assessment_id']] = true;

$columns = [
    'assessment_id', 'fac_id_fk', 'dept_id', 'assessment_date', 'assessment_type',
    'assessor_name', 'assessor_designation', 'assessor_mobile', 'assessor_email',
    'assessee_name', 'assessee_designation', 'assessee_mobile', 'assessee_email',
    'teacher_code', 'head_master_name', 'subject_name', 'class_section', 'remarks'
];
$updateColumns = array_filter($columns, static fn($column) => !in_array($column, ['assessment_id', 'dept_id'], true));
$sql = 'INSERT INTO assessment_assessor_info (' . implode(', ', $columns) . ') VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ')'
    . ' ON DUPLICATE KEY UPDATE ' . implode(', ', array_map(static fn($column) => "{$column} = VALUES({$column})", $updateColumns));
$statement = $con->prepare($sql);
if (!$statement) throw new RuntimeException('Import statement preparation failed: ' . $con->error);

$counts = ['rows' => 0, 'inserted' => 0, 'updated' => 0, 'unchanged' => 0, 'skipped' => 0];
$errors = [];
$seen = [];

if ($import) $con->begin_transaction();
try {
    while (($values = fgetcsv($handle)) !== false) {
        if ($values === [null] || $values === []) continue;
        $counts['rows']++;
        $record = array_combine($header, array_pad($values, count($header), ''));
        $assessmentId = (int)trim((string)($record['assessment_id'] ?? '0'));
        $facilityId = (int)trim((string)($record['fac_id_fk'] ?? '0'));
        $departmentId = (int)trim((string)($record['dept_id'] ?? '0'));
        $assessmentDate = trim((string)($record['assessment_date'] ?? ''));
        $assessmentType = trim((string)($record['assessment_type'] ?? ''));
        $scope = $assessmentId . ':' . $departmentId;

        if ($assessmentId <= 0 || $facilityId <= 0 || $assessmentDate === '' || $assessmentType === '' || !isset($assessmentIds[$assessmentId])) {
            $counts['skipped']++;
            if (count($errors) < 10) $errors[] = "row {$counts['rows']}: missing/unknown assessment, facility, date, or type";
            continue;
        }
        if (isset($seen[$scope])) {
            $counts['skipped']++;
            if (count($errors) < 10) $errors[] = "row {$counts['rows']}: duplicate assessment/department scope {$scope}";
            continue;
        }
        $seen[$scope] = true;

        if (!$import) {
            $counts['inserted']++;
            continue;
        }

        $nullable = static function (array $row, string $key): ?string {
            $value = trim((string)($row[$key] ?? ''));
            return $value === '' || strtoupper($value) === 'NULL' ? null : $value;
        };
        $assessorName = $nullable($record, 'assessor_name');
        $assessorDesignation = $nullable($record, 'assessor_designation');
        $assessorMobile = $nullable($record, 'assessor_mobile');
        $assessorEmail = $nullable($record, 'assessor_email');
        $assesseeName = $nullable($record, 'assessee_name');
        $assesseeDesignation = $nullable($record, 'assessee_designation');
        $assesseeMobile = $nullable($record, 'assessee_mobile');
        $assesseeEmail = $nullable($record, 'assessee_email');
        $teacherCode = $nullable($record, 'teacher_code');
        $headMasterName = $nullable($record, 'head_master_name');
        $subjectName = $nullable($record, 'subject_name');
        $classSection = $nullable($record, 'class_section');
        $remarks = $nullable($record, 'remarks');
        $statement->bind_param(
            'iiisssssssssssssss',
            $assessmentId, $facilityId, $departmentId, $assessmentDate, $assessmentType,
            $assessorName, $assessorDesignation, $assessorMobile, $assessorEmail,
            $assesseeName, $assesseeDesignation, $assesseeMobile, $assesseeEmail,
            $teacherCode, $headMasterName, $subjectName, $classSection, $remarks
        );
        if (!$statement->execute()) throw new RuntimeException("row {$counts['rows']}: " . $statement->error);
        if ($statement->affected_rows === 1) $counts['inserted']++;
        elseif ($statement->affected_rows === 2) $counts['updated']++;
        else $counts['unchanged']++;
    }
    if ($import) $con->commit();
} catch (Throwable $error) {
    if ($import) $con->rollback();
    throw $error;
} finally {
    fclose($handle);
}

echo json_encode($counts + ['mode' => $import ? 'import' : 'dry_run', 'errors' => $errors], JSON_PRETTY_PRINT) . PHP_EOL;
