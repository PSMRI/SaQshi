<?php

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../../service/FormulaEngine.php';
require_once __DIR__ . '/../../service/PerformanceService.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

Security::requireMethod('GET');

/**
 * Returns the configured input captions for each KPI/Outcome indicator.
 * The submitted entry stores only numbers, so the captions must come from the
 * performance configuration rather than the formula expression.
 */
function performanceInputLabels(): array
{
    static $labels = null;
    if ($labels !== null) return $labels;

    $labels = [];
    foreach (['KPI' => 'kpi.json', 'OUTCOME' => 'outcome.json'] as $type => $file) {
        $config = json_decode((string)@file_get_contents(__DIR__ . '/../../config/performance/' . $file), true);
        if (!is_array($config)) continue;

        $collect = function ($node) use (&$collect, &$labels, $type): void {
            if (!is_array($node)) return;
            if (isset($node['indicator_id'])) {
                $numerator = '';
                $denominator = '';
                foreach (($node['fields'] ?? []) as $field) {
                    $name = strtolower((string)($field['field_name'] ?? ''));
                    if ($name === 'numerator') $numerator = trim((string)($field['label'] ?? ''));
                    if ($name === 'denominator') $denominator = trim((string)($field['label'] ?? ''));
                }
                $labels[$type . ':' . (int)$node['indicator_id']] = [
                    'numerator' => $numerator,
                    'denominator' => $denominator,
                ];
            }
            foreach ($node as $value) $collect($value);
        };
        $collect($config);
    }
    return $labels;
}

$facilityId = (int)($_GET['fac_id'] ?? 0);
$departmentId = (int)($_GET['dept_id'] ?? 0);
$indicatorType = strtoupper(trim((string)($_GET['indicator_type'] ?? '')));
$indicatorType = in_array($indicatorType, ['KPI', 'OUTCOME'], true) ? $indicatorType : '';
if ($facilityId <= 0) Response::validation(['fac_id' => 'A facility is required.']);

$facilityStmt = $con->prepare('SELECT fac_name, NIN_no FROM facilities WHERE fac_id = ? LIMIT 1');
$facilityStmt->bind_param('i', $facilityId);
$facilityStmt->execute();
$facility = $facilityStmt->get_result()->fetch_assoc() ?: ['fac_name' => 'facility', 'NIN_no' => '-'];
$departmentName = $departmentId > 0 ? PerformanceService::departmentName($departmentId) : 'All departments';

$where = 'pe.fac_id = ?';
$types = 'i';
$params = [$facilityId];
if ($departmentId > 0) {
    $where .= ' AND pe.dept_id = ?';
    $types .= 'i';
    $params[] = $departmentId;
}
if ($indicatorType !== '') {
    $where .= ' AND pe.indicator_type = ?';
    $types .= 's';
    $params[] = $indicatorType;
}
$stmt = $con->prepare("SELECT pe.dept_id, pe.indicator_type, pe.indicator_id, pe.indicator_name, pe.indicator_code, pe.entry_year, pe.entry_month, pe.numerator_value, pe.denominator_value, pe.result_value FROM performance_entries pe WHERE {$where} ORDER BY pe.entry_year, pe.entry_month, pe.indicator_name");
$stmt->bind_param($types, ...$params);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$months = [];
$indicators = [];
$inputLabels = performanceInputLabels();
foreach ($rows as $row) {
    $period = sprintf('%04d-%02d', (int)$row['entry_year'], (int)$row['entry_month']);
    $months[$period] = $period;
    $type = strtoupper((string)$row['indicator_type']);
    $key = $type . '|' . $row['dept_id'] . '|' . $row['indicator_id'];
    if (!isset($indicators[$key])) {
        $configuredLabels = $inputLabels[$type . ':' . (int)$row['indicator_id']] ?? [];
        $inputText = 'Numerator: ' . (($configuredLabels['numerator'] ?? '') ?: 'Numerator')
            . "\n\nDenominator: " . (($configuredLabels['denominator'] ?? '') ?: 'Denominator');
        $indicators[$key] = ['name' => $row['indicator_name'] ?: $row['indicator_code'], 'input_labels' => $inputText, 'values' => []];
    }
    $indicators[$key]['values'][$period] = [$row['numerator_value'], $row['denominator_value'], $row['result_value']];
}
ksort($months);

$book = new Spreadsheet();
$sheet = $book->getActiveSheet();
$reportType = $indicatorType !== '' ? $indicatorType : 'Performance';
$sheet->setTitle($reportType . ' Reports');
$monthCount = count($months);
$lastColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(2 + ($monthCount * 3));
$sheet->mergeCells("A1:{$lastColumn}1")->setCellValue('A1', $reportType . ' Report');
$sheet->setCellValue('A2', 'Facility name:')->setCellValue('B2', $facility['fac_name']);
$sheet->setCellValue('A3', 'NIN no.:')->setCellValue('B3', $facility['NIN_no'] ?: '-');
$sheet->setCellValue('A4', 'Department:')->setCellValue('B4', $departmentName);
$sheet->mergeCells("A6:A8")->setCellValue('A6', 'INDICATOR');
$sheet->mergeCells("B6:B8")->setCellValue('B6', 'NUMERATOR / DENOMINATOR');
$sheet->mergeCells("C6:{$lastColumn}6")->setCellValue('C6', strtoupper($reportType) . ' REPORTS');
$column = 3;
foreach ($months as $period) {
    $endColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($column + 2);
    $startColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($column);
    $sheet->mergeCells("{$startColumn}7:{$endColumn}7")->setCellValue("{$startColumn}7", date('M y', strtotime($period . '-01')));
    foreach (['NUMERATOR', 'DENOMINATOR', 'VALUE'] as $offset => $label) {
        $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($column + $offset) . '8', $label);
    }
    $column += 3;
}
$rowNo = 9;
foreach ($indicators as $indicator) {
    $sheet->setCellValue("A{$rowNo}", $indicator['name']);
    $sheet->setCellValue("B{$rowNo}", $indicator['input_labels']);
    $column = 3;
    foreach ($months as $period) {
        foreach (($indicator['values'][$period] ?? [null, null, null]) as $offset => $value) {
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($column + $offset) . $rowNo, $value);
        }
        $column += 3;
    }
    $rowNo++;
}
$sheet->getStyle("A1:{$lastColumn}1")->getFont()->setBold(true)->setSize(14);
$headerRange = "A6:{$lastColumn}8";
$sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D7E7F9');
$sheet->getStyle($headerRange)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF003B75'));
$sheet->getStyle("A6:{$lastColumn}" . max(8, $rowNo - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFB8C9DC'));
$sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
$sheet->getStyle("C9:{$lastColumn}" . max(9, $rowNo - 1))->getNumberFormat()->setFormatCode('0.0000');
$sheet->getColumnDimension('A')->setWidth(38);
$sheet->getColumnDimension('B')->setWidth(28);
for ($index = 3; $index <= 2 + ($monthCount * 3); $index++) $sheet->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index))->setWidth(15);
$sheet->getStyle("A9:B" . max(9, $rowNo - 1))->getAlignment()->setWrapText(true);
$sheet->freezePane('C9');

$filename = preg_replace('/[^A-Za-z0-9]+/', '_', (string)$facility['fac_name']) . '_' . strtolower($reportType) . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
(new Xlsx($book))->save('php://output');
