<?php

/*! SaQshi Open Source | Facility Indicator Comparison API */

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../../core/FrameworkEngine.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

Security::requireMethod('GET');

try {
    $facilityId = (int)($_GET['facility_id'] ?? 0);
    if ($facilityId <= 0) {
        Response::validation(['facility_id' => 'Facility is required.']);
    }

    $assessmentRows = [];
    $statement = $con->prepare("SELECT a.assessment_id, a.assessment_name, a.framework_code, f.Health_facilty_type, f.fac_name, f.NIN_no
        FROM assessment_master a
        INNER JOIN facilities f ON f.fac_id = a.fac_id_fk
        WHERE a.fac_id_fk = ? AND UPPER(COALESCE(a.status, '')) = 'COMPLETED'
        ORDER BY COALESCE(a.completed_on, a.assessment_id), a.assessment_id");
    $statement->bind_param('i', $facilityId);
    $statement->execute();
    $result = $statement->get_result();
    while ($row = $result->fetch_assoc()) $assessmentRows[] = $row;

    if (count($assessmentRows) < 2) {
        Response::validation(['assessment' => 'At least two completed assessments are required for indicator comparison.']);
    }

    $assessmentIds = array_map(static fn(array $row): int => (int)$row['assessment_id'], $assessmentRows);
    $placeholders = implode(',', array_fill(0, count($assessmentIds), '?'));
    $responseStatement = $con->prepare("SELECT assessment_id, dept_id, checkpoint_id, COALESCE(SUM(score), 0) AS score
        FROM assessment_response
        WHERE assessment_id IN ({$placeholders})
        GROUP BY assessment_id, dept_id, checkpoint_id");
    $responseStatement->bind_param(str_repeat('i', count($assessmentIds)), ...$assessmentIds);
    $responseStatement->execute();
    $responseResult = $responseStatement->get_result();

    $assessmentById = [];
    $engines = [];
    foreach ($assessmentRows as $row) {
        $assessmentId = (int)$row['assessment_id'];
        $assessmentById[$assessmentId] = $row;
        $framework = (string)($row['framework_code'] ?: 'saqshi-nqas');
        if (!isset($engines[$framework])) $engines[$framework] = FrameworkEngine::load($framework);
    }

    $indicators = [];
    $checkpointMaps = [];
    while ($response = $responseResult->fetch_assoc()) {
        $assessmentId = (int)$response['assessment_id'];
        $assessment = $assessmentById[$assessmentId] ?? null;
        if (!$assessment) continue;
        $framework = (string)($assessment['framework_code'] ?: 'saqshi-nqas');
        $engine = $engines[$framework];
        $facilityTypeId = (int)$assessment['Health_facilty_type'];
        $departmentId = (int)$response['dept_id'];
        $checkpointMapKey = $framework . ':' . $facilityTypeId . ':' . $departmentId;
        if (!isset($checkpointMaps[$checkpointMapKey])) {
            $checkpointMaps[$checkpointMapKey] = [];
            foreach ($engine->getCheckpoints($facilityTypeId, $departmentId) as $configuredCheckpoint) {
                $checkpointMaps[$checkpointMapKey][(int)($configuredCheckpoint['csqa_id'] ?? 0)] = $configuredCheckpoint;
            }
        }
        $checkpoint = $checkpointMaps[$checkpointMapKey][(int)$response['checkpoint_id']] ?? $engine->getCheckpointById((int)$response['checkpoint_id']) ?? [];
        $department = $engine->getDepartmentById($facilityTypeId, $departmentId) ?? [];
        $key = (int)$response['dept_id'] . ':' . (int)$response['checkpoint_id'];
        if (!isset($indicators[$key])) {
            $indicators[$key] = [
                'department' => (string)($department['dept_name'] ?? $department['department_name'] ?? ('Department ' . $response['dept_id'])),
                'area_of_concern' => (string)($checkpoint['_concern_name'] ?? ''),
                'reference_no' => (string)($checkpoint['csqa_reference_id'] ?? $checkpoint['_reference_no'] ?? ''),
                'measurable_element' => (string)($checkpoint['Measurable_Element'] ?? ''),
                'indicator' => (string)($checkpoint['Checkpoint'] ?? ('Checkpoint ' . $response['checkpoint_id'])),
                'means_of_verification' => (string)($checkpoint['Means_of_Verification'] ?? ''),
                'assessment_method' => is_array($checkpoint['Assessment_Method'] ?? null)
                    ? implode('/', array_filter(array_map('strval', $checkpoint['Assessment_Method'])))
                    : (string)($checkpoint['Assessment_Method'] ?? ''),
                'scores' => []
            ];
        }
        $indicators[$key]['scores'][$assessmentId] = (float)$response['score'];
    }

    $assessmentPayload = array_map(static fn(array $row): array => ['assessment_id' => (int)$row['assessment_id'], 'assessment_name' => (string)$row['assessment_name']], $assessmentRows);
    $indicatorPayload = array_values($indicators);

    if (!empty($_GET['download'])) {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Indicator Comparison');
        $columns = ['Department', 'Area of Concern', 'Reference No', 'Measurable Elements', 'Checkpoint', 'Means of Verification', 'Assessment Method'];
        foreach ($assessmentPayload as $index => $assessment) {
            $number = $index + 1;
            $suffix = ($number % 100 >= 11 && $number % 100 <= 13) ? 'th' : ([1 => 'st', 2 => 'nd', 3 => 'rd'][$number % 10] ?? 'th');
            $columns[] = $number . $suffix . ' assessment: ' . $assessment['assessment_name'];
        }
        $lastColumn = Coordinate::stringFromColumnIndex(count($columns));
        $sheet->mergeCells("A1:{$lastColumn}1")->setCellValue('A1', 'National Quality Assurance Standards - Indicator Comparison Scorecard');
        $sheet->mergeCells("A2:{$lastColumn}2")->setCellValue('A2', 'Facility: ' . ($assessmentRows[0]['fac_name'] ?? '') . ' | NIN: ' . ($assessmentRows[0]['NIN_no'] ?? ''));
        // Avoid Worksheet::fromArray(): older bundled PhpSpreadsheet versions
        // use PHP's deprecated alphabetic string increment internally.
        $writeRow = static function (array $values, int $row) use ($sheet): void {
            foreach ($values as $columnIndex => $value) {
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($columnIndex + 1) . $row, $value);
            }
        };
        $writeRow($columns, 4);
        $rowNo = 5;
        foreach ($indicatorPayload as $indicator) {
            $values = [$indicator['department'], $indicator['area_of_concern'], $indicator['reference_no'], $indicator['measurable_element'], $indicator['indicator'], $indicator['means_of_verification'], $indicator['assessment_method']];
            foreach ($assessmentPayload as $assessment) $values[] = $indicator['scores'][$assessment['assessment_id']] ?? 'Not assessed';
            $writeRow($values, $rowNo++);
        }
        $sheet->getStyle("A1:{$lastColumn}1")->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle("A1:{$lastColumn}1")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('156799');
        $sheet->getStyle("A4:{$lastColumn}4")->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle("A4:{$lastColumn}4")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('86A86F');
        $sheet->getStyle("A4:{$lastColumn}" . max(4, $rowNo - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle("A1:{$lastColumn}" . max(4, $rowNo - 1))->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
        foreach (range(1, count($columns)) as $index) $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($index))->setWidth($index <= 7 ? 24 : 18);
        $filename = preg_replace('/[^A-Za-z0-9]+/', '_', (string)($assessmentRows[0]['fac_name'] ?? 'facility')) . '_indicator_scorecard_comparison.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        (new Xlsx($spreadsheet))->save('php://output');
        exit;
    }

    Response::success('Indicator comparison loaded', [
        'assessments' => $assessmentPayload,
        'indicators' => $indicatorPayload
    ]);
} catch (Throwable $e) {
    Response::serverError($e->getMessage());
}
