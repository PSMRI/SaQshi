<?php

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../../service/FormulaEngine.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

Security::requireMethod('GET');

$facilityId = (int)($_GET['fac_id'] ?? 0);
$departmentId = (int)($_GET['dept_id'] ?? 0);
if ($facilityId <= 0) Response::validation(['fac_id' => 'A facility is required.']);

$facilityStmt = $con->prepare('SELECT fac_name FROM facilities WHERE fac_id = ? LIMIT 1');
$facilityStmt->bind_param('i', $facilityId);
$facilityStmt->execute();
$facility = $facilityStmt->get_result()->fetch_assoc() ?: ['fac_name' => 'facility'];

$where = 'pe.fac_id = ?';
$types = 'i';
$params = [$facilityId];
if ($departmentId > 0) {
    $where .= ' AND pe.dept_id = ?';
    $types .= 'i';
    $params[] = $departmentId;
}
$stmt = $con->prepare("SELECT pe.dept_id, pe.indicator_id, pe.indicator_name, pe.indicator_code, pe.formula_id, pe.entry_year, pe.entry_month, pe.numerator_value, pe.denominator_value, pe.result_value FROM performance_entries pe WHERE {$where} ORDER BY pe.entry_year, pe.entry_month, pe.indicator_name");
$stmt->bind_param($types, ...$params);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$months = [];
$indicators = [];
foreach ($rows as $row) {
    $period = sprintf('%04d-%02d', (int)$row['entry_year'], (int)$row['entry_month']);
    $months[$period] = $period;
    $key = $row['dept_id'] . '|' . $row['indicator_id'];
    if (!isset($indicators[$key])) {
        $formula = FormulaEngine::findFormula((int)($row['formula_id'] ?? 0));
        $indicators[$key] = ['name' => $row['indicator_name'] ?: $row['indicator_code'], 'formula' => $formula['source_expression'] ?? $formula['expression'] ?? '', 'values' => []];
    }
    $indicators[$key]['values'][$period] = [$row['numerator_value'], $row['denominator_value'], $row['result_value']];
}
ksort($months);

$book = new Spreadsheet();
$sheet = $book->getActiveSheet();
$sheet->setTitle('Outcome Reports');
$monthCount = count($months);
$lastColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(2 + ($monthCount * 3));
$sheet->mergeCells("A1:A3")->setCellValue('A1', 'INDICATOR');
$sheet->mergeCells("B1:B3")->setCellValue('B1', 'FORMULA');
$sheet->mergeCells("C1:{$lastColumn}1")->setCellValue('C1', 'OUTCOME REPORTS');
$column = 3;
foreach ($months as $period) {
    $endColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($column + 2);
    $startColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($column);
    $sheet->mergeCells("{$startColumn}2:{$endColumn}2")->setCellValue("{$startColumn}2", date('M y', strtotime($period . '-01')));
    foreach (['NUMERATOR', 'DENOMINATOR', 'VALUE'] as $offset => $label) {
        $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($column + $offset) . '3', $label);
    }
    $column += 3;
}
$rowNo = 4;
foreach ($indicators as $indicator) {
    $sheet->setCellValue("A{$rowNo}", $indicator['name']);
    $sheet->setCellValue("B{$rowNo}", $indicator['formula']);
    $column = 3;
    foreach ($months as $period) {
        foreach (($indicator['values'][$period] ?? [null, null, null]) as $offset => $value) {
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($column + $offset) . $rowNo, $value);
        }
        $column += 3;
    }
    $rowNo++;
}
$headerRange = "A1:{$lastColumn}3";
$sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D7E7F9');
$sheet->getStyle($headerRange)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF003B75'));
$sheet->getStyle("A1:{$lastColumn}" . max(3, $rowNo - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFB8C9DC'));
$sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
$sheet->getStyle("C4:{$lastColumn}" . max(4, $rowNo - 1))->getNumberFormat()->setFormatCode('0.0000');
$sheet->getColumnDimension('A')->setWidth(38);
$sheet->getColumnDimension('B')->setWidth(28);
for ($index = 3; $index <= 2 + ($monthCount * 3); $index++) $sheet->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index))->setWidth(15);
$sheet->getStyle("A4:B" . max(4, $rowNo - 1))->getAlignment()->setWrapText(true);
$sheet->freezePane('C4');

$filename = preg_replace('/[^A-Za-z0-9]+/', '_', (string)$facility['fac_name']) . '_outcome_report.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
(new Xlsx($book))->save('php://output');
