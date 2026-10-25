<?php

require_once __DIR__ . '/../../auth_api.php';
require_once __DIR__ . '/../../assets/conn/db.php';
require_once __DIR__ . '/../../service/PerformanceService.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

Security::requireMethod('GET');
$facId = SessionManager::facilityId();
$data = PerformanceService::dashboard($con, $facId, ['trend_type' => 'OUTCOME', 'all_indicators' => 1]);
$groups = [];
foreach ($data['indicator_trends']['OUTCOME'] as $item) {
    $id = (int)$item['department_id'];
    if ($id <= 0) continue;
    $groups[$id]['name'] = $item['department_name'] ?: 'Department ' . $id;
    $groups[$id]['series'][] = $item;
}
if (!$groups) {
    http_response_code(404);
    exit('No outcome trend data available.');
}

$book = new Spreadsheet();
$usedTitles = [];
foreach ($groups as $group) {
    $sheet = count($usedTitles) ? $book->createSheet() : $book->getActiveSheet();
    $base = trim(preg_replace('/[\\\\\/?*\[\]:]/u', ' ', $group['name']), " '\t\n\r\0\x0B");
    $base = mb_substr($base ?: 'Department', 0, 31);
    $title = $base;
    for ($suffix = 2; in_array(mb_strtolower($title), $usedTitles, true); $suffix++) {
        $tail = ' (' . $suffix . ')';
        $title = mb_substr($base, 0, 31 - mb_strlen($tail)) . $tail;
    }
    $usedTitles[] = mb_strtolower($title);
    $sheet->setTitle($title);
    $text = static function ($cell, $value) use ($sheet): void {
        $sheet->setCellValueExplicit($cell, (string)$value, DataType::TYPE_STRING);
    };
    $periods = [];
    foreach ($group['series'] as $item) foreach ($item['points'] as $point) $periods[$point['period']] = true;
    $periods = array_keys($periods);
    sort($periods);
    $text('A1', 'Facility Name'); $text('B1', $data['facility']['fac_name'] ?? '-');
    $text('C1', 'Report Time'); $text('D1', date('Y-m-d H:i:s'));
    $text('E1', 'Department'); $text('F1', $group['name']);
    $text('A2', 'Facility NIN No'); $text('B2', $data['facility']['nin_no'] ?? $data['facility']['NIN_no'] ?? $data['facility']['fac_nin'] ?? '-');
    $text('A3', 'Report Type'); $text('B3', 'OUTCOME Performance Trend');
    foreach (['Indicator Name', 'Formula', 'Numerator label', 'Denominator label'] as $i => $label) {
        $column = Coordinate::stringFromColumnIndex($i + 1);
        $text($column . '5', $label);
        $sheet->mergeCells($column . '5:' . $column . '6');
    }
    foreach ($periods as $index => $period) {
        $start = 5 + $index * 3;
        $first = Coordinate::stringFromColumnIndex($start);
        $last = Coordinate::stringFromColumnIndex($start + 2);
        $text($first . '5', date('M Y', strtotime($period . '-01')));
        $sheet->mergeCells($first . '5:' . $last . '5');
        foreach (['Numerator', 'Denominator', 'Value'] as $offset => $label) $text(Coordinate::stringFromColumnIndex($start + $offset) . '6', $label);
    }
    foreach ($group['series'] as $index => $item) {
        $row = $index + 7;
        foreach (['indicator_name', 'formula', 'numerator_label', 'denominator_label'] as $i => $key) $text(Coordinate::stringFromColumnIndex($i + 1) . $row, $item[$key] ?? '');
        $points = [];
        foreach ($item['points'] as $point) $points[$point['period']] = $point;
        foreach ($periods as $i => $period) {
            if (!isset($points[$period])) continue;
            foreach (['numerator', 'denominator', 'result'] as $offset => $key) {
                if (($points[$period][$key] ?? null) !== null) $sheet->setCellValue(Coordinate::stringFromColumnIndex(5 + $i * 3 + $offset) . $row, $points[$period][$key]);
            }
        }
    }
    $lastColumn = Coordinate::stringFromColumnIndex(4 + count($periods) * 3);
    $lastRow = 6 + count($group['series']);
    $sheet->getStyle('A1:' . $lastColumn . $lastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('000000');
    foreach (['A1', 'C1', 'E1', 'A2', 'A3'] as $cell) {
        $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('B8C9E6');
        $sheet->getStyle($cell)->getFont()->setBold(true);
    }
    foreach (['B1', 'D1', 'F1', 'B2'] as $cell) {
        $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F4E3D1');
    }
    $sheet->getStyle('B3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D6D900');
    $sheet->getStyle('B3')->getFont()->setBold(true);
    $sheet->getStyle('A5:' . $lastColumn . '5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('156799');
    $sheet->getStyle('A6:' . $lastColumn . '6')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('86A86F');
    $sheet->getStyle('A5:' . $lastColumn . '6')->getFont()->setBold(true);
    $sheet->getStyle('A5:' . $lastColumn . '6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
    $sheet->getStyle('A7:D' . $lastRow)->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
    $sheet->getStyle('A7:A' . $lastRow)->getFont()->setBold(true);
    $sheet->getStyle('E7:' . $lastColumn . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    foreach (['A' => 45, 'B' => 24, 'C' => 38, 'D' => 38] as $column => $width) $sheet->getColumnDimension($column)->setWidth($width);
    for ($i = 5; $i <= 4 + count($periods) * 3; $i++) $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setWidth(16);
    $sheet->freezePane('E7');
}
$book->setActiveSheetIndex(0);
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="outcome_trends_by_department.xlsx"');
(new Xlsx($book))->save('php://output');
exit;
