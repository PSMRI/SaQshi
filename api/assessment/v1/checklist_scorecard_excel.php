<?php
/** Download an upload-compatible checklist in Score Card report layout. */
require_once __DIR__ . '/../../auth_api.php';
require_once __DIR__ . '/../../assets/conn/db.php';
require_once __DIR__ . '/../../core/FrameworkEngine.php';
require_once __DIR__ . '/../../core/AssessmentAccess.php';
require_once dirname(__DIR__, 3) . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

Security::requireMethod('GET');

try {
    $assessmentId = (int)($_GET['assessment_id'] ?? 0);
    $deptId = (int)($_GET['dept_id'] ?? 0);
    $facId = SessionManager::facilityId();
    $userId = SessionManager::userId();
    if ($assessmentId <= 0 || $deptId <= 0 || $facId <= 0 || $userId <= 0) Response::validation(['assessment_id' => 'Assessment and department are required']);

    AssessmentAccess::requireEditableByCurrentUser($con, $assessmentId, $facId);
    $stmt = $con->prepare("SELECT a.framework_code, f.Health_facilty_type FROM assessment_master a JOIN facilities f ON f.fac_id = a.fac_id_fk WHERE a.assessment_id = ? AND a.fac_id_fk = ? AND a.status = 'ACTIVE'");
    $stmt->bind_param('ii', $assessmentId, $facId);
    $stmt->execute();
    $assessment = $stmt->get_result()->fetch_assoc();
    if (!$assessment) Response::error('Active assessment not found');

    $stmt = $con->prepare('SELECT status FROM assessment_department WHERE assessment_id = ? AND fac_id_fk = ? AND dept_id = ? AND is_active = 1');
    $stmt->bind_param('iii', $assessmentId, $facId, $deptId);
    $stmt->execute();
    $department = $stmt->get_result()->fetch_assoc();
    if (!$department || ($department['status'] ?? '') === 'COMPLETED') Response::error('Department is not available for import');

    $stmt = $con->prepare('SELECT info_id FROM assessment_assessor_info WHERE assessment_id = ? AND fac_id_fk = ? AND dept_id = ?');
    $stmt->bind_param('iii', $assessmentId, $facId, $deptId);
    $stmt->execute();
    if (!$stmt->get_result()->fetch_assoc()) Response::error('Save assessor information before downloading or uploading a checklist');

    $engine = FrameworkEngine::load($assessment['framework_code'] ?: 'saqshi-nqas');
    $checkpoints = $engine->getCheckpoints((int)$assessment['Health_facilty_type'], $deptId);
    $saved = [];
    $stmt = $con->prepare('SELECT checkpoint_id, response_value FROM assessment_response WHERE assessment_id = ? AND dept_id = ?');
    $stmt->bind_param('ii', $assessmentId, $deptId);
    $stmt->execute();
    $savedResult = $stmt->get_result();
    while ($row = $savedResult->fetch_assoc()) $saved[(int)$row['checkpoint_id']] = (string)$row['response_value'];

    $book = new Spreadsheet();
    $meta = $book->getActiveSheet();
    $meta->setTitle('Metadata');
    foreach ([['A1', 'Assessment ID'], ['B1', $assessmentId], ['A2', 'Department ID'], ['B2', $deptId], ['A3', 'Framework'], ['B3', $assessment['framework_code']], ['A5', 'Instructions'], ['B5', 'This workbook mirrors the Score Card checklist. Enter or update only User Compliance: 0 = Non Compliant, 1 = Partially Compliant, 2 = Fully Compliant.']] as [$cell, $value]) $meta->setCellValue($cell, $value);
    $meta->getStyle('A1:A5')->getFont()->setBold(true);
    $meta->getStyle('A1:A5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('B8C9E6');
    $meta->getStyle('A1:B5')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    $meta->getStyle('B5')->getAlignment()->setWrapText(true);
    $meta->getColumnDimension('A')->setWidth(18);
    $meta->getColumnDimension('B')->setWidth(105);

    $sheet = $book->createSheet();
    $sheet->setTitle('Checklist');
    $sheet->mergeCells('A1:F1')->setCellValue('A1', 'Checkpoint-wise Details');
    foreach (['A2' => 'Reference No', 'B2' => 'Measurable Elements', 'C2' => 'Checkpoints', 'D2' => 'Means of Verification', 'E2' => 'Assessment Method', 'F2' => 'User Compliance', 'G2' => 'Checkpoint ID'] as $cell => $value) $sheet->setCellValue($cell, $value);

    $rowNo = 3;
    $lastConcern = null;
    $lastStandard = null;
    foreach ($checkpoints as $checkpoint) {
        $concern = trim((string)($checkpoint['_concern_name'] ?? ''));
        $reference = trim((string)($checkpoint['_reference_no'] ?? ''));
        $standard = trim((string)($checkpoint['_subtype_name'] ?? ''));
        if ($concern !== '' && $concern !== $lastConcern) {
            $sheet->mergeCells("A{$rowNo}:F{$rowNo}")->setCellValue("A{$rowNo}", 'Area of Concern - ' . $concern);
            $sheet->getStyle("A{$rowNo}:F{$rowNo}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('B7B7C8');
            $sheet->getStyle("A{$rowNo}:F{$rowNo}")->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
            $sheet->getStyle("A{$rowNo}:F{$rowNo}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $lastConcern = $concern;
            $lastStandard = null;
            $rowNo++;
        }
        $standardKey = $reference . '|' . $standard;
        if ($standard !== '' && $standardKey !== $lastStandard) {
            $standardTitle = preg_match('/^standard\b/i', $reference) ? trim($reference . ' ' . $standard) : trim('Standard ' . $reference . ' ' . $standard);
            $sheet->mergeCells("A{$rowNo}:F{$rowNo}")->setCellValue("A{$rowNo}", $standardTitle);
            $sheet->getStyle("A{$rowNo}:F{$rowNo}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('156799');
            $sheet->getStyle("A{$rowNo}:F{$rowNo}")->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
            $sheet->getStyle("A{$rowNo}:F{$rowNo}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $lastStandard = $standardKey;
            $rowNo++;
        }
        $checkpointId = (int)($checkpoint['csqa_id'] ?? 0);
        $method = $checkpoint['Assessment_Method'] ?? '';
        if (is_array($method)) $method = implode('/', array_filter(array_map('strval', $method)));
        foreach ([
            'A' => (string)($checkpoint['csqa_reference_id'] ?? ''),
            'B' => (string)($checkpoint['Measurable_Element'] ?? ''),
            'C' => (string)($checkpoint['Checkpoint'] ?? ''),
            'D' => (string)($checkpoint['Means_of_Verification'] ?? ''),
            'E' => (string)$method,
            'F' => $saved[$checkpointId] ?? '',
            'G' => $checkpointId
        ] as $column => $value) $sheet->setCellValue($column . $rowNo, $value);
        $sheet->getStyle("F{$rowNo}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF2CC');
        $rowNo++;
    }

    $lastRow = $rowNo - 1;
    $validation = $sheet->getDataValidation('F3');
    $validation->setType(DataValidation::TYPE_LIST)->setFormula1('"0,1,2"')->setAllowBlank(false)->setShowDropDown(true);
    $sheet->setDataValidation("F3:F{$lastRow}", $validation);
    $sheet->getStyle('A1:F1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('156799');
    $sheet->getStyle('A1:F1')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
    $sheet->getStyle('A2:G2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('86A86F');
    $sheet->getStyle('A2:G2')->getFont()->setBold(true);
    $sheet->getStyle("A2:G{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('B8C9DC');
    $sheet->getStyle("A1:G{$lastRow}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
    $sheet->getStyle('A1:G2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
    $sheet->getStyle("F3:F{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    foreach (['A' => 19, 'B' => 43, 'C' => 58, 'D' => 58, 'E' => 20, 'F' => 18] as $column => $width) $sheet->getColumnDimension($column)->setWidth($width);
    $sheet->getColumnDimension('G')->setVisible(false);
    $sheet->getRowDimension(1)->setRowHeight(28);
    $sheet->getRowDimension(2)->setRowHeight(32);
    $sheet->setAutoFilter("A2:F{$lastRow}");
    $sheet->freezePane('A3');

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="checklist_scorecard_' . $assessmentId . '_' . $deptId . '.xlsx"');
    (new Xlsx($book))->save('php://output');
    exit;
} catch (Throwable $e) {
    Response::serverError($e->getMessage());
}
