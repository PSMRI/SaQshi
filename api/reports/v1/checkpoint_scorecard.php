<?php

/**
 * checkpoint_scorecard.php
 * -------------------------------------------------------
 * Download checkpoint score card in standard Excel format.
 *
 * Method:
 * GET /api/reports/v1/checkpoint_scorecard.php?assessment_id=1
 * -------------------------------------------------------
 */

require_once __DIR__ . '/../../auth_api.php';
require_once __DIR__ . '/../../core/FrameworkEngine.php';
require_once __DIR__ . '/../../core/Crypto.php';
require_once __DIR__ . '/../../assets/conn/db.php';

Security::requireMethod('GET');

/**
 * Handles scorecard normalize processing for this API workflow.
 */
function scorecardNormalize(string $value): string
{
    $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $value = preg_replace('/\s+/u', ' ', $value);
    $value = trim((string)$value);

    return function_exists('mb_strtolower')
        ? mb_strtolower($value, 'UTF-8')
        : strtolower($value);
}

/**
 * Returns the assessment reference column used by department status table.
 */
function scorecardDepartmentStatusColumn(mysqli $con): string
{
    $result = $con->query("
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'assessment_department_status'
          AND COLUMN_NAME = 'assessment_id'
        LIMIT 1
    ");

    return ($result && $result->fetch_assoc()) ? 'assessment_id' : 'ass_period_id';
}

/**
 * Handles scorecard facility processing for this API workflow.
 */
function scorecardFacility(int $facId): array
{
    $facilityJsonPath = __DIR__ . '/../../config/masters/facilities.json';

    if (!file_exists($facilityJsonPath)) {
        return [
            'fac_type_id' => 0,
            'fac_name' => '',
            'facilities_type' => ''
        ];
    }

    $states = json_decode(file_get_contents($facilityJsonPath), true);

    if (!is_array($states)) {
        return [
            'fac_type_id' => 0,
            'fac_name' => '',
            'facilities_type' => ''
        ];
    }

    foreach ($states as $state) {
        foreach (($state['divisions'] ?? []) as $division) {
            foreach (($division['districts'] ?? []) as $district) {
                foreach (($district['blocks'] ?? []) as $block) {
                    foreach (($block['facilities'] ?? []) as $facility) {
                        if ((int)($facility['fac_id'] ?? 0) === $facId) {
                            return [
                                'fac_type_id' => (int)($facility['fac_type_id'] ?? 0),
                                'fac_name' => (string)($facility['fac_name'] ?? ''),
                                'facilities_type' => (string)($facility['facilities_type'] ?? '')
                            ];
                        }
                    }
                }
            }
        }
    }

    return [
        'fac_type_id' => 0,
        'fac_name' => '',
        'facilities_type' => ''
    ];
}

/**
 * Handles scorecard shared strings processing for this API workflow.
 */
function scorecardSharedStrings(ZipArchive $zip): array
{
    $xml = $zip->getFromName('xl/sharedStrings.xml');

    if ($xml === false) {
        return [];
    }

    $sx = simplexml_load_string($xml);

    if (!$sx) {
        return [];
    }

    $strings = [];

    foreach ($sx->si as $si) {
        $text = '';

        if (isset($si->t)) {
            $text = (string)$si->t;
        } else {
            foreach ($si->r as $run) {
                $text .= (string)$run->t;
            }
        }

        $strings[] = $text;
    }

    return $strings;
}

/**
 * Handles scorecard cell text processing for this API workflow.
 */
function scorecardCellText(DOMXPath $xpath, array $sharedStrings, string $cellRef): string
{
    $cell = $xpath->query("//x:c[@r='" . $cellRef . "']")->item(0);

    if (!$cell instanceof DOMElement) {
        return '';
    }

    $type = $cell->getAttribute('t');

    if ($type === 's') {
        $value = $xpath->query('x:v', $cell)->item(0);
        $index = $value ? (int)$value->nodeValue : -1;
        return $sharedStrings[$index] ?? '';
    }

    if ($type === 'inlineStr') {
        $text = $xpath->query('x:is/x:t', $cell)->item(0);
        return $text ? (string)$text->nodeValue : '';
    }

    $value = $xpath->query('x:v', $cell)->item(0);
    return $value ? (string)$value->nodeValue : '';
}

/**
 * Handles scorecard column index processing for this API workflow.
 */
function scorecardColumnIndex(string $cellRef): int
{
    preg_match('/^[A-Z]+/', $cellRef, $matches);
    $letters = $matches[0] ?? '';
    $index = 0;

    foreach (str_split($letters) as $letter) {
        $index = ($index * 26) + (ord($letter) - 64);
    }

    return $index;
}

/**
 * Handles scorecard set cell processing for this API workflow.
 */
function scorecardSetCell(
    DOMDocument $dom,
    DOMXPath $xpath,
    DOMElement $row,
    string $cellRef,
    string|float|int|null $value,
    bool $numeric = false
): void {
    $cell = $xpath->query("x:c[@r='" . $cellRef . "']", $row)->item(0);

    if (!$cell instanceof DOMElement) {
        $cell = $dom->createElementNS(
            'http://schemas.openxmlformats.org/spreadsheetml/2006/main',
            'c'
        );
        $cell->setAttribute('r', $cellRef);

        $newIndex = scorecardColumnIndex($cellRef);
        $inserted = false;

        foreach ($xpath->query('x:c', $row) as $existing) {
            if (scorecardColumnIndex($existing->getAttribute('r')) > $newIndex) {
                $row->insertBefore($cell, $existing);
                $inserted = true;
                break;
            }
        }

        if (!$inserted) {
            $row->appendChild($cell);
        }
    }

    while ($cell->firstChild) {
        $cell->removeChild($cell->firstChild);
    }

    if ($value === null || $value === '') {
        $cell->removeAttribute('t');
        return;
    }

    if ($numeric) {
        $cell->removeAttribute('t');
        $cell->appendChild($dom->createElementNS(
            'http://schemas.openxmlformats.org/spreadsheetml/2006/main',
            'v',
            (string)$value
        ));
        return;
    }

    $cell->setAttribute('t', 'inlineStr');
    $is = $dom->createElementNS(
        'http://schemas.openxmlformats.org/spreadsheetml/2006/main',
        'is'
    );
    $text = $dom->createElementNS(
        'http://schemas.openxmlformats.org/spreadsheetml/2006/main',
        't'
    );
    $text->appendChild($dom->createTextNode((string)$value));
    $is->appendChild($text);
    $cell->appendChild($is);
}

/**
 * Handles scorecard create row processing for this API workflow.
 */
function scorecardCreateRow(DOMDocument $dom, int $rowNo): DOMElement
{
    $row = $dom->createElementNS(
        'http://schemas.openxmlformats.org/spreadsheetml/2006/main',
        'row'
    );
    $row->setAttribute('r', (string)$rowNo);
    return $row;
}

/**
 * Handles scorecard apply style processing for this API workflow.
 */
function scorecardApplyStyle(DOMXPath $xpath, DOMElement $row, string $cellRef, ?string $styleId): void
{
    if ($styleId === null || $styleId === '') {
        return;
    }

    $cell = $xpath->query("x:c[@r='" . $cellRef . "']", $row)->item(0);

    if ($cell instanceof DOMElement) {
        $cell->setAttribute('s', $styleId);
    }
}

/**
 * Handles scorecard template styles processing for this API workflow.
 */
function scorecardTemplateStyles(DOMXPath $xpath, array $rowMap, int $rowNo): array
{
    $styles = [];
    $row = $rowMap[$rowNo] ?? null;

    if (!$row instanceof DOMElement) {
        return $styles;
    }

    foreach ($xpath->query('x:c', $row) as $cell) {
        $ref = $cell->getAttribute('r');
        preg_match('/^[A-Z]+/', $ref, $matches);
        $column = $matches[0] ?? '';

        if ($column !== '') {
            $styles[$column] = $cell->getAttribute('s');
        }
    }

    return $styles;
}

/**
 * Handles scorecard set styled cell processing for this API workflow.
 */
function scorecardSetStyledCell(
    DOMDocument $dom,
    DOMXPath $xpath,
    DOMElement $row,
    int $rowNo,
    string $column,
    string|float|int|null $value,
    bool $numeric,
    array $styles
): void {
    $cellRef = $column . $rowNo;
    scorecardSetCell($dom, $xpath, $row, $cellRef, $value, $numeric);
    scorecardApplyStyle($xpath, $row, $cellRef, $styles[$column] ?? null);
}

/** Creates the bordered assessment-information grid used above every score card. */
function scorecardApplyAssessmentMetadata(
    DOMDocument $dom,
    DOMXPath $xpath,
    array $rowMap,
    array $metadata,
    array $labelStyles,
    array $valueStyles
): void {
    $mergeCells = $xpath->query('//x:mergeCells')->item(0);
    if ($mergeCells instanceof DOMElement) {
        foreach (iterator_to_array($xpath->query('x:mergeCell', $mergeCells)) as $mergeCell) {
            preg_match_all('/\d+/', $mergeCell->getAttribute('ref'), $matches);
            $rows = array_map('intval', $matches[0] ?? []);
            if (!empty($rows) && min($rows) <= 5 && max($rows) >= 3) {
                $mergeCells->removeChild($mergeCell);
            }
        }
    }

    $fields = [
        3 => ['Name of Facility', $metadata['facility'] ?? '-', 'Date of Assessment', $metadata['assessment_date'] ?? '-'],
        4 => ['Name of Assessors', $metadata['assessor'] ?? '-', 'Name of Assessee', $metadata['assessee'] ?? '-'],
        5 => ['Type of Assessment (Internal/ State/External)', $metadata['assessment_type'] ?? '-', 'Action Plan submitted date', $metadata['action_plan_date'] ?? '-'],
    ];
    foreach ($fields as $rowNo => [$leftLabel, $leftValue, $rightLabel, $rightValue]) {
        if (!isset($rowMap[$rowNo])) continue;
        $row = $rowMap[$rowNo];
        // Remove any template content that would otherwise remain inside the
        // newly merged metadata fields (for example Total Marks / Progress).
        foreach (['B', 'D', 'F', 'H', 'I', 'J'] as $column) {
            scorecardSetCell($dom, $xpath, $row, $column . $rowNo, '', false);
        }
        scorecardSetStyledCell($dom, $xpath, $row, $rowNo, 'A', $leftLabel, false, $labelStyles);
        scorecardSetStyledCell($dom, $xpath, $row, $rowNo, 'C', $leftValue, false, $valueStyles);
        scorecardSetStyledCell($dom, $xpath, $row, $rowNo, 'E', $rightLabel, false, $labelStyles);
        scorecardSetStyledCell($dom, $xpath, $row, $rowNo, 'G', $rightValue, false, $valueStyles);
        if ($mergeCells instanceof DOMElement) {
            foreach ([['A', 'B'], ['C', 'D'], ['E', 'F'], ['G', 'J']] as [$startColumn, $endColumn]) {
                $mergeCell = $dom->createElementNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main', 'mergeCell');
                $mergeCell->setAttribute('ref', $startColumn . $rowNo . ':' . $endColumn . $rowNo);
                $mergeCells->appendChild($mergeCell);
            }
        }
    }
}

/**
 * Adds the presentation styles used by the NQAS score-card sections.
 * These are deliberately kept in the workbook stylesheet instead of relying
 * on incidental styles from the detailed checkpoint template.
 */
function scorecardEnsurePresentationStyles(ZipArchive $zip): array
{
    $xml = $zip->getFromName('xl/styles.xml');
    if ($xml === false) {
        throw new RuntimeException('Score card stylesheet not found');
    }

    $dom = new DOMDocument();
    $dom->loadXML($xml);
    $xpath = new DOMXPath($dom);
    $xpath->registerNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
    $fonts = $xpath->query('//x:fonts')->item(0);
    $fills = $xpath->query('//x:fills')->item(0);
    $borders = $xpath->query('//x:borders')->item(0);
    $cellXfs = $xpath->query('//x:cellXfs')->item(0);
    if (!$fonts instanceof DOMElement || !$fills instanceof DOMElement || !$borders instanceof DOMElement || !$cellXfs instanceof DOMElement) {
        throw new RuntimeException('Score card stylesheet is incomplete');
    }

    $appendFont = static function (string $colour, bool $bold = true, int $size = 10) use ($dom, $fonts): int {
        $font = $dom->createElementNS($fonts->namespaceURI, 'font');
        if ($bold) $font->appendChild($dom->createElementNS($fonts->namespaceURI, 'b'));
        $font->appendChild($dom->createElementNS($fonts->namespaceURI, 'sz'))->setAttribute('val', (string)$size);
        $font->appendChild($dom->createElementNS($fonts->namespaceURI, 'color'))->setAttribute('rgb', 'FF' . $colour);
        $font->appendChild($dom->createElementNS($fonts->namespaceURI, 'name'))->setAttribute('val', 'Arial');
        $fonts->appendChild($font);
        $fontCount = $fonts->getElementsByTagNameNS($fonts->namespaceURI, 'font')->length;
        $fonts->setAttribute('count', (string)$fontCount);
        return $fontCount - 1;
    };
    $appendFill = static function (string $colour) use ($dom, $fills): int {
        $fill = $dom->createElementNS($fills->namespaceURI, 'fill');
        $pattern = $dom->createElementNS($fills->namespaceURI, 'patternFill');
        $pattern->setAttribute('patternType', 'solid');
        $foreground = $dom->createElementNS($fills->namespaceURI, 'fgColor');
        $foreground->setAttribute('rgb', 'FF' . $colour);
        $pattern->appendChild($foreground);
        $background = $dom->createElementNS($fills->namespaceURI, 'bgColor');
        $background->setAttribute('indexed', '64');
        $pattern->appendChild($background);
        $fill->appendChild($pattern);
        $fills->appendChild($fill);
        $fillCount = $fills->getElementsByTagNameNS($fills->namespaceURI, 'fill')->length;
        $fills->setAttribute('count', (string)$fillCount);
        return $fillCount - 1;
    };
    $appendBorder = static function (string $style = 'thin') use ($dom, $borders): int {
        $border = $dom->createElementNS($borders->namespaceURI, 'border');
        foreach (['left', 'right', 'top', 'bottom'] as $edge) {
            $edgeNode = $dom->createElementNS($borders->namespaceURI, $edge);
            $edgeNode->setAttribute('style', $style);
            $colour = $dom->createElementNS($borders->namespaceURI, 'color');
            $colour->setAttribute('rgb', 'FF000000');
            $edgeNode->appendChild($colour);
            $border->appendChild($edgeNode);
        }
        $border->appendChild($dom->createElementNS($borders->namespaceURI, 'diagonal'));
        $borders->appendChild($border);
        $borderCount = $borders->getElementsByTagNameNS($borders->namespaceURI, 'border')->length;
        $borders->setAttribute('count', (string)$borderCount);
        return $borderCount - 1;
    };

    $blackBold = $appendFont('000000');
    $black = $appendFont('000000', false);
    $whiteBold = $appendFont('FFFFFF');
    $scoreFont = $appendFont('000000', true, 18);
    $blueScoreFont = $appendFont('0070C0', false, 14);
    $lavender = $appendFill('E4DFEC');
    $red = $appendFill('FF5050');
    $rose = $appendFill('D99694');
    $purple = $appendFill('604A78');
    $scoreGrey = $appendFill('B7B3BF');
    $pink = $appendFill('E6B8B7');
    $yellow = $appendFill('FFFF00');
    $white = $appendFill('FFFFFF');
    $thinBorder = $appendBorder();

    $appendStyle = static function (int $fontId, int $fillId, string $horizontal = 'center', bool $wrap = true) use ($dom, $cellXfs, $thinBorder): int {
        $xf = $dom->createElementNS($cellXfs->namespaceURI, 'xf');
        $xf->setAttribute('numFmtId', '0');
        $xf->setAttribute('fontId', (string)$fontId);
        $xf->setAttribute('fillId', (string)$fillId);
        $xf->setAttribute('borderId', (string)$thinBorder);
        $xf->setAttribute('xfId', '0');
        $xf->setAttribute('applyFont', '1');
        $xf->setAttribute('applyFill', '1');
        $xf->setAttribute('applyBorder', '1');
        $xf->setAttribute('applyAlignment', '1');
        $alignment = $dom->createElementNS($cellXfs->namespaceURI, 'alignment');
        $alignment->setAttribute('horizontal', $horizontal);
        $alignment->setAttribute('vertical', 'center');
        if ($wrap) $alignment->setAttribute('wrapText', '1');
        $xf->appendChild($alignment);
        $cellXfs->appendChild($xf);
        $styleCount = $cellXfs->getElementsByTagNameNS($cellXfs->namespaceURI, 'xf')->length;
        $cellXfs->setAttribute('count', (string)$styleCount);
        return $styleCount - 1;
    };

    $styles = [
        'hospitalTitle' => $appendStyle($whiteBold, $red),
        'departmentHeader' => $appendStyle($blackBold, $white),
        'departmentScore' => $appendStyle($blueScoreFont, $white),
        'hospitalScore' => $appendStyle($scoreFont, $yellow),
        'overallTitle' => $appendStyle($whiteBold, $purple),
        'overallScore' => $appendStyle($blackBold, $scoreGrey),
        'areaTitle' => $appendStyle($blackBold, $lavender),
        'areaLabel' => $appendStyle($blackBold, $pink),
        'areaScore' => $appendStyle($blackBold, $white),
        'standardTitle' => $appendStyle($blackBold, $white),
        'standardArea' => $appendStyle($whiteBold, $scoreGrey),
        'standardDetail' => $appendStyle($blackBold, $yellow, 'left'),
        'metadataLabel' => $appendStyle($blackBold, $lavender, 'left'),
        'metadataValue' => $appendStyle($black, $white, 'left'),
        'detailHeader' => $appendStyle($blackBold, $rose),
        'detailData' => $appendStyle($black, $white, 'left'),
        'bigScore' => $appendStyle($scoreFont, $yellow),
    ];
    $zip->addFromString('xl/styles.xml', $dom->saveXML());
    return $styles;
}

/** Configure one workbook worksheet for every selected department. */
function scorecardConfigureDepartmentSheets(ZipArchive $zip, array $sheetNames): void
{
    if (count($sheetNames) < 2) return;

    $workbook = new DOMDocument();
    $workbook->loadXML((string)$zip->getFromName('xl/workbook.xml'));
    $xpath = new DOMXPath($workbook);
    $xpath->registerNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
    $xpath->registerNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
    $sheets = $xpath->query('//x:sheets')->item(0);
    if (!$sheets instanceof DOMElement) throw new RuntimeException('Score card workbook sheets not found');
    while ($sheets->firstChild) $sheets->removeChild($sheets->firstChild);

    $rels = new DOMDocument();
    $rels->loadXML((string)$zip->getFromName('xl/_rels/workbook.xml.rels'));
    $relsXPath = new DOMXPath($rels);
    $relsXPath->registerNamespace('r', 'http://schemas.openxmlformats.org/package/2006/relationships');
    $existingIds = [];
    foreach ($relsXPath->query('//r:Relationship') as $relationship) $existingIds[$relationship->getAttribute('Id')] = true;

    foreach ($sheetNames as $index => $sheetName) {
        $sheetNumber = $index + 1;
        $relId = 'rId' . $sheetNumber;
        while ($sheetNumber > 1 && isset($existingIds[$relId])) $relId = 'rId' . (++$sheetNumber + 100);
        if ($index > 0) {
            $relationship = $rels->createElementNS('http://schemas.openxmlformats.org/package/2006/relationships', 'Relationship');
            $relationship->setAttribute('Id', $relId);
            $relationship->setAttribute('Type', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet');
            $relationship->setAttribute('Target', 'worksheets/sheet' . ($index + 1) . '.xml');
            $rels->documentElement->appendChild($relationship);
            $existingIds[$relId] = true;
        }
        $sheet = $workbook->createElementNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main', 'sheet');
        $sheet->setAttribute('name', $sheetName);
        $sheet->setAttribute('sheetId', (string)($index + 1));
        $sheet->setAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'r:id', $relId);
        $sheets->appendChild($sheet);
    }
    $zip->addFromString('xl/workbook.xml', $workbook->saveXML());
    $zip->addFromString('xl/_rels/workbook.xml.rels', $rels->saveXML());

    $contentTypes = new DOMDocument();
    $contentTypes->loadXML((string)$zip->getFromName('[Content_Types].xml'));
    for ($sheet = 2; $sheet <= count($sheetNames); $sheet++) {
        $override = $contentTypes->createElementNS('http://schemas.openxmlformats.org/package/2006/content-types', 'Override');
        $override->setAttribute('PartName', '/xl/worksheets/sheet' . $sheet . '.xml');
        $override->setAttribute('ContentType', 'application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml');
        $contentTypes->documentElement->appendChild($override);
    }
    $zip->addFromString('[Content_Types].xml', $contentTypes->saveXML());
}

/** Builds area and standard score summaries from one department's checklist. */
function scorecardCompareStandards(array $left, array $right): int
{
    $leftReference = trim((string)($left['reference'] ?? $left['name'] ?? ''));
    $rightReference = trim((string)($right['reference'] ?? $right['name'] ?? ''));
    $comparison = strnatcasecmp($leftReference, $rightReference);
    if ($comparison !== 0) return $comparison;
    return strnatcasecmp((string)($left['name'] ?? ''), (string)($right['name'] ?? ''));
}

function scorecardDepartmentSummaries(array $items, array $responseMap, int $deptId): array
{
    $areas = [];
    $standards = [];
    $areaKey = null;
    $standardKey = null;

    foreach ($items as $item) {
        if ($item['type'] === 'area') {
            $areaKey = (string)($item['measurable'] ?? 'Area of Concern');
            $areas[$areaKey] ??= ['name' => $areaKey, 'obtained' => 0.0, 'possible' => 0.0];
            $standardKey = null;
            continue;
        }
        if ($item['type'] === 'standard') {
            $standardKey = trim((string)($item['reference'] ?? '') . ' ' . (string)($item['standard'] ?? ''));
            if ($standardKey === '') $standardKey = 'Standard';
            $standards[$standardKey] ??= [
                'name' => $standardKey,
                'reference' => (string)($item['reference'] ?? ''),
                'description' => (string)($item['standard'] ?? ''),
                'area' => $areaKey ?? 'Area of Concern',
                'obtained' => 0.0,
                'possible' => 0.0
            ];
            continue;
        }
        if ($item['type'] !== 'checkpoint') continue;

        $response = $responseMap[$deptId . ':' . (int)($item['checkpoint_id'] ?? 0)] ?? null;
        $obtained = $response && $response['revised_score'] !== null
            ? (float)$response['revised_score']
            : (float)($response['score'] ?? 0);

        if ($areaKey !== null) {
            $areas[$areaKey]['obtained'] += $obtained;
            $areas[$areaKey]['possible'] += 2;
        }
        if ($standardKey !== null) {
            $standards[$standardKey]['obtained'] += $obtained;
            $standards[$standardKey]['possible'] += 2;
        }
    }

    $standardValues = array_values($standards);
    usort($standardValues, 'scorecardCompareStandards');
    return ['areas' => array_values($areas), 'standards' => $standardValues];
}

function scorecardColumnLabel(int $number): string
{
    $label = '';
    while ($number > 0) {
        $number--;
        $label = chr(65 + ($number % 26)) . $label;
        $number = intdiv($number, 26);
    }
    return $label;
}

try {
    $facId = SessionManager::facilityId();
    $userId = SessionManager::userId();

    if ($facId <= 0) {
        Response::error('Facility not assigned to logged-in user');
    }

    if ($userId <= 0) {
        Response::error('User session not found');
    }

    $assessmentId = isset($_GET['assessment_id'])
        ? (int)$_GET['assessment_id']
        : 0;

    if ($assessmentId <= 0) {
        Response::validation([
            'assessment_id' => 'assessment_id is required'
        ]);
    }

    $sqlAssessment = "
        SELECT assessment_id, assessment_name, framework_code, fac_id_fk, start_date, end_date, status
        FROM assessment_master
        WHERE assessment_id = ?
          AND fac_id_fk = ?
        LIMIT 1
    ";

    $stmt = $con->prepare($sqlAssessment);

    if (!$stmt) {
        Response::serverError('Assessment prepare failed: ' . $con->error);
    }

    $stmt->bind_param('ii', $assessmentId, $facId);
    $stmt->execute();
    $assessment = $stmt->get_result()->fetch_assoc();

    if (!$assessment) {
        Response::error('Assessment not found for this facility');
    }

    $facility = scorecardFacility($facId);
    $frameworkCode = $assessment['framework_code'] ?: 'saqshi-nqas';
    $engine = FrameworkEngine::load($frameworkCode);

    $checklistRowsByDepartment = [];
    $exportDepartments = [];

    $activeDeptIds = [];
    $departmentStatusColumn = scorecardDepartmentStatusColumn($con);

    $sqlActiveDept = "
        SELECT dept_id
        FROM assessment_department_status
        WHERE {$departmentStatusColumn} = ?
          AND fac_id_fk = ?
          AND is_active = 1
    ";

    $stmt = $con->prepare($sqlActiveDept);

    if ($stmt) {
        $stmt->bind_param('ii', $assessmentId, $facId);
        $stmt->execute();
        $activeDeptResult = $stmt->get_result();

        while ($row = $activeDeptResult->fetch_assoc()) {
            $activeDeptIds[(int)$row['dept_id']] = true;
        }
    }

    foreach ($engine->getDepartments((int)$facility['fac_type_id']) as $department) {
        $deptId = (int)($department['fac_dept_id'] ?? 0);
        $deptName = (string)($department['dept_name'] ?? '');
        if ($deptId <= 0 || !isset($activeDeptIds[$deptId])) continue;

        $exportDepartments[$deptId] = $deptName ?: ('Department ' . $deptId);
        $checklistRowsByDepartment[$deptId] = [];

        foreach (($department['concerns'] ?? []) as $concern) {
            $concernName = trim((string)(
                ($concern['concern_name'] ?? '') .
                (
                    !empty($concern['concern_des'])
                        ? ': ' . $concern['concern_des']
                        : ''
                )
            ));

            $checklistRowsByDepartment[$deptId][] = [
                'type' => 'area',
                'reference' => '',
                'standard' => '',
                'measurable' => $concernName ?: $deptName,
                'checkpoint' => '',
                'verification' => '',
                'method' => '',
                'checkpoint_id' => 0
            ];

            foreach (($concern['subtypes'] ?? []) as $subtype) {
                $standardRef = (string)($subtype['Reference_No'] ?? '');
                $standardText = (string)($subtype['area_of_con_subtypedeatils'] ?? '');

                if ($standardRef !== '' || $standardText !== '') {
                    $checklistRowsByDepartment[$deptId][] = [
                        'type' => 'standard',
                        'reference' => $standardRef,
                        'standard' => $standardText,
                        'measurable' => '',
                        'checkpoint' => '',
                        'verification' => '',
                        'method' => '',
                        'checkpoint_id' => 0
                    ];
                }

                foreach (($subtype['checkpoints'] ?? []) as $checkpoint) {
                    $checkpointId = (int)($checkpoint['csqa_id'] ?? 0);
                    $method = $checkpoint['Assessment_Method'] ?? '';

                    if (is_array($method)) {
                        $method = implode('/', array_filter(array_map('strval', $method)));
                    }

                    if ($checkpointId <= 0) {
                        continue;
                    }

                    $checklistRowsByDepartment[$deptId][] = [
                        'type' => 'checkpoint',
                        'reference' => (string)($checkpoint['csqa_reference_id'] ?? ''),
                        'standard' => '',
                        'measurable' => (string)($checkpoint['Measurable_Element'] ?? ''),
                        'checkpoint' => (string)($checkpoint['Checkpoint'] ?? ''),
                        'verification' => (string)($checkpoint['Means_of_Verification'] ?? ''),
                        'method' => (string)$method,
                        'checkpoint_id' => $checkpointId
                    ];
                }
            }
        }
    }

    $responseMap = [];

    $sqlResponses = "
        SELECT dept_id, checkpoint_id, response_value, score, remarks
        FROM assessment_response
        WHERE assessment_id = ?
    ";

    $stmt = $con->prepare($sqlResponses);

    if (!$stmt) {
        Response::serverError('Response prepare failed: ' . $con->error);
    }

    $stmt->bind_param('i', $assessmentId);
    $stmt->execute();
    $responses = $stmt->get_result();

    while ($row = $responses->fetch_assoc()) {
        $responseMap[(int)$row['dept_id'] . ':' . (int)$row['checkpoint_id']] = [
            'response_value' => $row['response_value'],
            'score' => (float)$row['score'],
            'remarks' => (string)($row['remarks'] ?? ''),
            'revised_score' => null
        ];
    }

    $legacyTable = $con->query("SHOW TABLES LIKE 'assessment_cycle_response'");

    if ($legacyTable && $legacyTable->num_rows > 0) {
        $sqlLegacyResponses = "
            SELECT dept_id, checkpoint_id, response_value, score, remarks
            FROM assessment_cycle_response
            WHERE cycle_id = ?
        ";

        $stmt = $con->prepare($sqlLegacyResponses);

        if ($stmt) {
            $stmt->bind_param('i', $assessmentId);
            $stmt->execute();
            $legacyResponses = $stmt->get_result();

            while ($row = $legacyResponses->fetch_assoc()) {
                $responseKey = (int)$row['dept_id'] . ':' . (int)$row['checkpoint_id'];

                if (isset($responseMap[$responseKey])) {
                    continue;
                }

                $responseMap[$responseKey] = [
                    'response_value' => $row['response_value'],
                    'score' => (float)$row['score'],
                    'remarks' => (string)($row['remarks'] ?? ''),
                    'revised_score' => null
                ];
            }
        }
    }

    $sqlRevisedScores = "
        SELECT dept_id, checkpoint_id, revised_score, closure_remarks
        FROM assessment_action_plan
        WHERE assessment_id = ?
          AND revised_score IS NOT NULL
    ";

    $stmt = $con->prepare($sqlRevisedScores);

    if ($stmt) {
        $stmt->bind_param('i', $assessmentId);
        $stmt->execute();
        $revisedResult = $stmt->get_result();

        while ($row = $revisedResult->fetch_assoc()) {
            $responseKey = (int)$row['dept_id'] . ':' . (int)$row['checkpoint_id'];

            if (!isset($responseMap[$responseKey])) {
                $responseMap[$responseKey] = [
                    'response_value' => $row['revised_score'],
                    'score' => (float)$row['revised_score'],
                    'remarks' => '',
                    'revised_score' => (float)$row['revised_score']
                ];
                continue;
            }

            $responseMap[$responseKey]['revised_score'] = (float)$row['revised_score'];

            if (!empty($row['closure_remarks'])) {
                $responseMap[$responseKey]['remarks'] = (string)$row['closure_remarks'];
            }
        }
    }

    $assessor = [
        'assessor_name' => '',
        'assessee_name' => '',
        'assessment_date' => '',
        'assessment_type' => ''
    ];
    $assessorByDepartment = [];

    $sqlAssessor = "
        SELECT dept_id, assessor_name, assessee_name, assessment_date, assessment_type
        FROM assessment_assessor_info
        WHERE assessment_id = ?
          AND fac_id_fk = ?
        ORDER BY dept_id ASC, assessment_date DESC, updated_on DESC, info_id DESC
    ";

    $stmt = $con->prepare($sqlAssessor);

    if ($stmt) {
        $stmt->bind_param('ii', $assessmentId, $facId);
        $stmt->execute();
        $assessorResult = $stmt->get_result();
        while ($row = $assessorResult->fetch_assoc()) {
            $departmentAssessor = Crypto::decryptFields($row, ['assessor_name', 'assessee_name']);
            $departmentInfoId = (int)($departmentAssessor['dept_id'] ?? 0);
            if ($assessor['assessment_date'] === '') {
                $assessor = $departmentAssessor;
            }
            if ($departmentInfoId > 0 && !isset($assessorByDepartment[$departmentInfoId])) {
                $assessorByDepartment[$departmentInfoId] = $departmentAssessor;
            }
        }
    }

    // Historic records sometimes store the assessed department in the
    // assessee-name field. A department is not a person, so do not present it
    // under "Name of Assessee" in the report.
    $normalizeAssesseeName = static function (string $value): string {
        $value = preg_replace('/\band\b/i', '', str_replace('&', '', $value));
        return preg_replace('/[^a-z0-9]+/i', '', (string)$value);
    };
    foreach ($assessorByDepartment as $departmentInfoId => &$departmentAssessor) {
        $storedAssesseeKey = $normalizeAssesseeName((string)$departmentAssessor['assessee_name']);
        $departmentKey = $normalizeAssesseeName((string)($exportDepartments[$departmentInfoId] ?? ''));
        if ($storedAssesseeKey !== '' && $storedAssesseeKey === $departmentKey) {
            $departmentAssessor['assessee_name'] = '';
        }
    }
    unset($departmentAssessor);

    $actionPlanSubmittedDate = '';
    $stmt = $con->prepare('SELECT MIN(created_on) AS submitted_on FROM assessment_action_plan WHERE assessment_id = ?');
    if ($stmt) {
        $stmt->bind_param('i', $assessmentId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $actionPlanSubmittedDate = (string)($row['submitted_on'] ?? '');
    }

    $template = __DIR__ . '/../../templates/report_revise_formate.xlsx';

    if (!file_exists($template)) {
        Response::serverError('Score card template not found');
    }

    $tmpDir = dirname(__DIR__, 3) . '/uploads/reports';

    if (!is_dir($tmpDir) && !mkdir($tmpDir, 0775, true)) {
        Response::serverError('Unable to create report folder');
    }

    $tmpFile = $tmpDir . '/scorecard_' . $assessmentId . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.xlsx';

    if (!copy($template, $tmpFile)) {
        Response::serverError('Unable to prepare score card template');
    }

    $zip = new ZipArchive();

    if ($zip->open($tmpFile) !== true) {
        Response::serverError('Unable to open score card workbook');
    }

    $templateSheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');

    if ($templateSheetXml === false) {
        $zip->close();
        Response::serverError('Score card worksheet not found');
    }

    $sharedStrings = scorecardSharedStrings($zip);
    $presentationStyles = scorecardEnsurePresentationStyles($zip);

    // First worksheet: a facility-level score card aggregating all active departments.
    $hospitalDepartments = [];
    $overallAreas = [];
    $overallStandards = [];
    $overallObtained = 0.0;
    $overallPossible = 0.0;
    foreach ($exportDepartments as $deptId => $deptName) {
        $summary = scorecardDepartmentSummaries($checklistRowsByDepartment[$deptId] ?? [], $responseMap, (int)$deptId);
        $obtained = 0.0;
        $possible = 0.0;
        foreach ($summary['areas'] as $area) {
            $obtained += (float)$area['obtained'];
            $possible += (float)$area['possible'];
            $key = (string)$area['name'];
            $overallAreas[$key] ??= ['name' => $key, 'obtained' => 0.0, 'possible' => 0.0];
            $overallAreas[$key]['obtained'] += (float)$area['obtained'];
            $overallAreas[$key]['possible'] += (float)$area['possible'];
        }
        foreach ($summary['standards'] as $standard) {
            $key = (string)($standard['area'] ?? '') . '|' . (string)$standard['name'];
            $overallStandards[$key] ??= [
                'name' => (string)$standard['name'],
                'reference' => (string)($standard['reference'] ?? ''),
                'description' => (string)($standard['description'] ?? ''),
                'area' => (string)($standard['area'] ?? 'Area of Concern'),
                'obtained' => 0.0,
                'possible' => 0.0
            ];
            $overallStandards[$key]['obtained'] += (float)$standard['obtained'];
            $overallStandards[$key]['possible'] += (float)$standard['possible'];
        }
        $hospitalDepartments[] = ['name' => $deptName, 'obtained' => $obtained, 'possible' => $possible];
        $overallObtained += $obtained;
        $overallPossible += $possible;
    }

    $hospitalDom = new DOMDocument();
    $hospitalDom->preserveWhiteSpace = false;
    $hospitalDom->formatOutput = false;
    $hospitalDom->loadXML($templateSheetXml);
    $hospitalXpath = new DOMXPath($hospitalDom);
    $hospitalXpath->registerNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
    $hospitalData = $hospitalXpath->query('//x:sheetData')->item(0);
    if (!$hospitalData instanceof DOMElement) {
        $zip->close();
        Response::serverError('Hospital score card worksheet data not found');
    }
    $hospitalRowMap = [];
    foreach ($hospitalXpath->query('x:row', $hospitalData) as $row) $hospitalRowMap[(int)$row->getAttribute('r')] = $row;
    $hospitalAreaStyles = array_fill_keys(range('A', 'J'), (string)$presentationStyles['hospitalTitle']);
    $hospitalHeaderStyles = array_fill_keys(range('A', 'J'), (string)$presentationStyles['departmentHeader']);
    $hospitalDataStyles = array_fill_keys(range('A', 'J'), (string)$presentationStyles['departmentScore']);
    $hospitalOverallTitleStyles = array_fill_keys(range('A', 'J'), (string)$presentationStyles['overallTitle']);
    $hospitalOverallScoreStyles = array_fill_keys(range('A', 'J'), (string)$presentationStyles['overallScore']);
    $hospitalAreaLabelStyles = array_fill_keys(range('A', 'J'), (string)$presentationStyles['areaLabel']);
    $hospitalAreaScoreStyles = array_fill_keys(range('A', 'J'), (string)$presentationStyles['areaScore']);
    $hospitalStandardTitleStyles = array_fill_keys(range('A', 'J'), (string)$presentationStyles['standardTitle']);
    $hospitalStandardAreaStyles = array_fill_keys(range('A', 'J'), (string)$presentationStyles['standardArea']);
    $hospitalStandardDetailStyles = array_fill_keys(range('A', 'J'), (string)$presentationStyles['standardDetail']);
    $metadataLabelStyles = array_fill_keys(range('A', 'J'), (string)$presentationStyles['metadataLabel']);
    $metadataValueStyles = array_fill_keys(range('A', 'J'), (string)$presentationStyles['metadataValue']);
    scorecardApplyAssessmentMetadata(
        $hospitalDom,
        $hospitalXpath,
        $hospitalRowMap,
        [
            'facility' => $facility['fac_name'] ?: '-',
            'assessment_date' => $assessor['assessment_date'] ?: $assessment['start_date'],
            'assessor' => $assessor['assessor_name'] ?: '-',
            'assessee' => $assessor['assessee_name'] ?: '-',
            'assessment_type' => $assessor['assessment_type'] ?: '-',
            'action_plan_date' => $actionPlanSubmittedDate ?: '-',
        ],
        $metadataLabelStyles,
        $metadataValueStyles
    );
    foreach (['A2', 'G2'] as $cellRef) {
        if (isset($hospitalRowMap[2])) {
            scorecardSetStyledCell($hospitalDom, $hospitalXpath, $hospitalRowMap[2], 2, substr($cellRef, 0, 1), '', false, $metadataValueStyles);
        }
    }
    foreach (iterator_to_array($hospitalXpath->query('x:row', $hospitalData)) as $row) {
        if ((int)$row->getAttribute('r') >= 7) $hospitalData->removeChild($row);
    }
    $hospitalMerges = $hospitalXpath->query('//x:mergeCells')->item(0);
    if ($hospitalMerges instanceof DOMElement) {
        foreach (iterator_to_array($hospitalXpath->query('x:mergeCell', $hospitalMerges)) as $mergeCell) {
            preg_match_all('/\\d+/', $mergeCell->getAttribute('ref'), $matches);
            $rows = array_map('intval', $matches[0] ?? []);
            if (!empty($rows) && max($rows) >= 7) $hospitalMerges->removeChild($mergeCell);
        }
    }
    $hospitalRow = 7;
    // Match the NQAS score-card layout: five department columns beside one
    // prominent facility score panel.
    $departmentColumns = 5;
    $lastDepartmentColumn = scorecardColumnLabel($departmentColumns);
    $titleRow = scorecardCreateRow($hospitalDom, $hospitalRow);
    $titleRow->setAttribute('ht', '24');
    $titleRow->setAttribute('customHeight', '1');
    $hospitalData->appendChild($titleRow);
    scorecardSetStyledCell($hospitalDom, $hospitalXpath, $titleRow, $hospitalRow, 'A', 'Hospital Score Card (Department wise)', false, $hospitalAreaStyles);
    if ($hospitalMerges instanceof DOMElement) {
        $mergeCell = $hospitalDom->createElementNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main', 'mergeCell');
        $mergeCell->setAttribute('ref', 'A' . $hospitalRow . ':J' . $hospitalRow);
        $hospitalMerges->appendChild($mergeCell);
    }
    $hospitalRow++;
    $overallPercent = $overallPossible > 0 ? round(($overallObtained / $overallPossible) * 100, 2) : 0;
    $hospitalGridStart = $hospitalRow;
    foreach (array_chunk($hospitalDepartments, $departmentColumns) as $departmentChunk) {
        $departmentHeader = scorecardCreateRow($hospitalDom, $hospitalRow);
        $departmentHeader->setAttribute('ht', '30');
        $departmentHeader->setAttribute('customHeight', '1');
        $hospitalData->appendChild($departmentHeader);
        foreach ($departmentChunk as $index => $department) {
            scorecardSetStyledCell($hospitalDom, $hospitalXpath, $departmentHeader, $hospitalRow, scorecardColumnLabel($index + 1), $department['name'], false, $hospitalHeaderStyles);
        }
        if (count($departmentChunk) === 1 && $hospitalMerges instanceof DOMElement) {
            $mergeCell = $hospitalDom->createElementNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main', 'mergeCell');
            $mergeCell->setAttribute('ref', 'A' . $hospitalRow . ':E' . $hospitalRow);
            $hospitalMerges->appendChild($mergeCell);
        }
        $hospitalRow++;
        $departmentScores = scorecardCreateRow($hospitalDom, $hospitalRow);
        $departmentScores->setAttribute('ht', '28');
        $departmentScores->setAttribute('customHeight', '1');
        $hospitalData->appendChild($departmentScores);
        foreach ($departmentChunk as $index => $department) {
            $percent = $department['possible'] > 0 ? round(($department['obtained'] / $department['possible']) * 100, 2) : 0;
            scorecardSetStyledCell($hospitalDom, $hospitalXpath, $departmentScores, $hospitalRow, scorecardColumnLabel($index + 1), number_format($percent, 2) . '%', false, $hospitalDataStyles);
        }
        if (count($departmentChunk) === 1 && $hospitalMerges instanceof DOMElement) {
            $mergeCell = $hospitalDom->createElementNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main', 'mergeCell');
            $mergeCell->setAttribute('ref', 'A' . $hospitalRow . ':E' . $hospitalRow);
            $hospitalMerges->appendChild($mergeCell);
        }
        $hospitalRow++;
    }
    $hospitalGridEnd = max($hospitalGridStart, $hospitalRow - 1);
    $hospitalScoreTitleEnd = $hospitalGridStart + max(0, intdiv($hospitalGridEnd - $hospitalGridStart + 1, 2) - 1);
    $hospitalScoreValueStart = $hospitalScoreTitleEnd + 1;
    $hospitalScoreTitle = $hospitalXpath->query("//x:sheetData/x:row[@r='{$hospitalGridStart}']")->item(0);
    $hospitalScoreValue = $hospitalXpath->query("//x:sheetData/x:row[@r='{$hospitalScoreValueStart}']")->item(0);
    $hospitalScoreStyles = array_fill_keys(range('A', 'J'), (string)$presentationStyles['hospitalScore']);
    if ($hospitalScoreTitle instanceof DOMElement) {
        scorecardSetStyledCell($hospitalDom, $hospitalXpath, $hospitalScoreTitle, $hospitalGridStart, 'F', 'Hospital Score', false, $hospitalScoreStyles);
    }
    if ($hospitalScoreValue instanceof DOMElement) {
        scorecardSetStyledCell($hospitalDom, $hospitalXpath, $hospitalScoreValue, $hospitalScoreValueStart, 'F', number_format($overallPercent, 2) . '%', false, $hospitalScoreStyles);
    }
    if ($hospitalMerges instanceof DOMElement) {
        foreach ([
            'F' . $hospitalGridStart . ':J' . $hospitalScoreTitleEnd,
            'F' . $hospitalScoreValueStart . ':J' . $hospitalGridEnd,
        ] as $ref) {
            $mergeCell = $hospitalDom->createElementNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main', 'mergeCell');
            $mergeCell->setAttribute('ref', $ref);
            $hospitalMerges->appendChild($mergeCell);
        }
    }
    $areaTitle = scorecardCreateRow($hospitalDom, $hospitalRow);
    $areaTitle->setAttribute('ht', '24');
    $areaTitle->setAttribute('customHeight', '1');
    $hospitalData->appendChild($areaTitle);
    scorecardSetStyledCell($hospitalDom, $hospitalXpath, $areaTitle, $hospitalRow, 'A', 'AREA OF CONCERN WISE', false, $hospitalAreaStyles);
    if ($hospitalMerges instanceof DOMElement) {
        $mergeCell = $hospitalDom->createElementNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main', 'mergeCell');
        $mergeCell->setAttribute('ref', 'A' . $hospitalRow . ':J' . $hospitalRow);
        $hospitalMerges->appendChild($mergeCell);
    }
    $hospitalRow++;
    foreach (array_chunk(array_values($overallAreas), 2) as $areaPair) {
        $areaRow = scorecardCreateRow($hospitalDom, $hospitalRow);
        $areaRow->setAttribute('ht', '28');
        $areaRow->setAttribute('customHeight', '1');
        $hospitalData->appendChild($areaRow);
        foreach ($areaPair as $index => $area) {
            $labelColumn = $index === 0 ? 'A' : 'F';
            $labelEnd = $index === 0 ? 'C' : 'H';
            $scoreColumn = $index === 0 ? 'D' : 'I';
            $scoreEnd = $index === 0 ? 'E' : 'J';
            $percent = (float)$area['possible'] > 0 ? round(((float)$area['obtained'] / (float)$area['possible']) * 100, 2) : 0;
            scorecardSetStyledCell($hospitalDom, $hospitalXpath, $areaRow, $hospitalRow, $labelColumn, $area['name'], false, $hospitalAreaLabelStyles);
            scorecardSetStyledCell($hospitalDom, $hospitalXpath, $areaRow, $hospitalRow, $scoreColumn, number_format($percent, 2) . '%', false, $hospitalAreaScoreStyles);
            if ($hospitalMerges instanceof DOMElement) {
                foreach ([
                    $labelColumn . $hospitalRow . ':' . $labelEnd . $hospitalRow,
                    $scoreColumn . $hospitalRow . ':' . $scoreEnd . $hospitalRow,
                ] as $ref) {
                    $mergeCell = $hospitalDom->createElementNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main', 'mergeCell');
                    $mergeCell->setAttribute('ref', $ref);
                    $hospitalMerges->appendChild($mergeCell);
                }
            }
        }
        $hospitalRow++;
    }

    $standardTitle = scorecardCreateRow($hospitalDom, $hospitalRow);
    $standardTitle->setAttribute('ht', '24');
    $standardTitle->setAttribute('customHeight', '1');
    $hospitalData->appendChild($standardTitle);
    scorecardSetStyledCell($hospitalDom, $hospitalXpath, $standardTitle, $hospitalRow, 'A', 'Standard wise Score Card', false, $hospitalStandardTitleStyles);
    scorecardSetStyledCell($hospitalDom, $hospitalXpath, $standardTitle, $hospitalRow, 'G', 'Standard wise Score', false, $hospitalStandardTitleStyles);
    if ($hospitalMerges instanceof DOMElement) {
        $mergeCell = $hospitalDom->createElementNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main', 'mergeCell');
        $mergeCell->setAttribute('ref', 'A' . $hospitalRow . ':F' . $hospitalRow);
        $hospitalMerges->appendChild($mergeCell);
    }
    $hospitalRow++;
    $standardsByArea = [];
    foreach ($overallStandards as $standard) $standardsByArea[(string)$standard['area']][] = $standard;
    foreach ($standardsByArea as $areaName => $standards) {
        usort($standards, 'scorecardCompareStandards');
        $standardAreaTitle = scorecardCreateRow($hospitalDom, $hospitalRow);
        $standardAreaTitle->setAttribute('ht', '24');
        $standardAreaTitle->setAttribute('customHeight', '1');
        $hospitalData->appendChild($standardAreaTitle);
        scorecardSetStyledCell($hospitalDom, $hospitalXpath, $standardAreaTitle, $hospitalRow, 'A', 'Area of Concern - ' . $areaName, false, $hospitalStandardAreaStyles);
        if ($hospitalMerges instanceof DOMElement) {
            $mergeCell = $hospitalDom->createElementNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main', 'mergeCell');
            $mergeCell->setAttribute('ref', 'A' . $hospitalRow . ':G' . $hospitalRow);
            $hospitalMerges->appendChild($mergeCell);
        }
        $hospitalRow++;
        foreach ($standards as $standard) {
            $standardRow = scorecardCreateRow($hospitalDom, $hospitalRow);
            $standardRow->setAttribute('ht', '30');
            $standardRow->setAttribute('customHeight', '1');
            $hospitalData->appendChild($standardRow);
            $percent = (float)$standard['possible'] > 0 ? round(((float)$standard['obtained'] / (float)$standard['possible']) * 100, 2) : 0;
            scorecardSetStyledCell($hospitalDom, $hospitalXpath, $standardRow, $hospitalRow, 'A', $standard['reference'] ?: $standard['name'], false, $hospitalStandardDetailStyles);
            scorecardSetStyledCell($hospitalDom, $hospitalXpath, $standardRow, $hospitalRow, 'B', $standard['description'] ?: $standard['name'], false, $hospitalStandardDetailStyles);
            scorecardSetStyledCell($hospitalDom, $hospitalXpath, $standardRow, $hospitalRow, 'G', number_format($percent, 2) . '%', false, $hospitalAreaScoreStyles);
            if ($hospitalMerges instanceof DOMElement) {
                $mergeCell = $hospitalDom->createElementNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main', 'mergeCell');
                $mergeCell->setAttribute('ref', 'B' . $hospitalRow . ':F' . $hospitalRow);
                $hospitalMerges->appendChild($mergeCell);
            }
            $hospitalRow++;
        }
    }
    if ($hospitalMerges instanceof DOMElement) $hospitalMerges->setAttribute('count', (string)$hospitalXpath->query('x:mergeCell', $hospitalMerges)->length);
    $hospitalDimension = $hospitalXpath->query('//x:dimension')->item(0);
    if ($hospitalDimension instanceof DOMElement) $hospitalDimension->setAttribute('ref', 'A1:J' . max(10, $hospitalRow - 1));
    $zip->addFromString('xl/worksheets/sheet1.xml', $hospitalDom->saveXML());

    $sheetNames = ['Hospital Score Card'];

    foreach ($exportDepartments as $deptId => $deptName) {
        $sheetNumber = count($sheetNames) + 1;
        $sheetPath = 'xl/worksheets/sheet' . $sheetNumber . '.xml';
        if ($sheetNumber > 1) $zip->addFromString($sheetPath, $templateSheetXml);
        $sheetXml = $zip->getFromName($sheetPath);
        $sheetName = preg_replace('/[\\\\\\/\\?\\*\\[\\]:]/', ' ', trim($deptName)) ?: ('Department ' . $deptId);
        $sheetName = function_exists('mb_substr') ? mb_substr($sheetName, 0, 31, 'UTF-8') : substr($sheetName, 0, 31);
        if (in_array($sheetName, $sheetNames, true)) $sheetName = 'Department ' . $deptId;
        $sheetNames[] = $sheetName;

    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = false;
    $dom->loadXML($sheetXml);

    $xpath = new DOMXPath($dom);
    $xpath->registerNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

    $sheetData = $xpath->query('//x:sheetData')->item(0);

    if (!$sheetData instanceof DOMElement) {
        $zip->close();
        Response::serverError('Score card worksheet data not found');
    }

    $rowMap = [];

    foreach ($xpath->query('x:row', $sheetData) as $row) {
        $rowMap[(int)$row->getAttribute('r')] = $row;
    }

    $allColumns = range('A', 'J');
    $areaStyles = array_fill_keys($allColumns, (string)$presentationStyles['areaTitle']);
    $areaLabelStyles = array_fill_keys($allColumns, (string)$presentationStyles['areaLabel']);
    $areaScoreStyles = array_fill_keys($allColumns, (string)$presentationStyles['areaScore']);
    $standardStyles = array_fill_keys($allColumns, (string)$presentationStyles['detailHeader']);
    $standardDetailStyles = array_fill_keys($allColumns, (string)$presentationStyles['standardDetail']);
    $dataStyles = array_fill_keys($allColumns, (string)$presentationStyles['detailData']);
    $bigScoreStyles = array_fill_keys($allColumns, (string)$presentationStyles['bigScore']);
    $departmentAssessor = $assessorByDepartment[(int)$deptId] ?? $assessor;
    scorecardApplyAssessmentMetadata(
        $dom,
        $xpath,
        $rowMap,
        [
            'facility' => $facility['fac_name'] ?: '-',
            'assessment_date' => $departmentAssessor['assessment_date'] ?: $assessment['start_date'],
            'assessor' => $departmentAssessor['assessor_name'] ?: '-',
            'assessee' => $departmentAssessor['assessee_name'] ?: '-',
            'assessment_type' => $departmentAssessor['assessment_type'] ?: '-',
            'action_plan_date' => $actionPlanSubmittedDate ?: '-',
        ],
        array_fill_keys($allColumns, (string)$presentationStyles['metadataLabel']),
        array_fill_keys($allColumns, (string)$presentationStyles['metadataValue'])
    );

    foreach (iterator_to_array($xpath->query('x:row', $sheetData)) as $row) {
        if ((int)$row->getAttribute('r') >= 7) {
            $sheetData->removeChild($row);
        }
    }

    $mergeCells = $xpath->query('//x:mergeCells')->item(0);

    if ($mergeCells instanceof DOMElement) {
        foreach (iterator_to_array($xpath->query('x:mergeCell', $mergeCells)) as $mergeCell) {
            $ref = $mergeCell->getAttribute('ref');
            preg_match_all('/\d+/', $ref, $matches);
            $rows = array_map('intval', $matches[0] ?? []);

            if (!empty($rows) && max($rows) >= 7) {
                $mergeCells->removeChild($mergeCell);
            }
        }
    }

    $departmentItems = $checklistRowsByDepartment[$deptId] ?? [];
    $summaries = scorecardDepartmentSummaries($departmentItems, $responseMap, (int)$deptId);
    $departmentObtained = 0.0;
    $departmentPossible = 0.0;
    foreach ($summaries['areas'] as $area) {
        $departmentObtained += (float)$area['obtained'];
        $departmentPossible += (float)$area['possible'];
    }
    $departmentPercent = $departmentPossible > 0 ? round(($departmentObtained / $departmentPossible) * 100, 2) : 0;

    $rowNo = 7;
    $summaryTitle = scorecardCreateRow($dom, $rowNo);
    $summaryTitle->setAttribute('ht', '24');
    $summaryTitle->setAttribute('customHeight', '1');
    $sheetData->appendChild($summaryTitle);
    scorecardSetStyledCell($dom, $xpath, $summaryTitle, $rowNo, 'A', 'Area of Concern-wise Score', false, $areaStyles);
    scorecardSetStyledCell($dom, $xpath, $summaryTitle, $rowNo, 'F', $deptName . ' Score', false, $areaStyles);
    foreach (['A7:E7', 'F7:J7'] as $ref) {
        $mergeCell = $dom->createElementNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main', 'mergeCell');
        $mergeCell->setAttribute('ref', $ref);
        $mergeCells?->appendChild($mergeCell);
    }
    $rowNo++;

    $firstAreaSummaryRow = null;
    foreach ($summaries['areas'] as $index => $area) {
        $summaryRow = scorecardCreateRow($dom, $rowNo);
        $summaryRow->setAttribute('ht', '28');
        $summaryRow->setAttribute('customHeight', '1');
        $sheetData->appendChild($summaryRow);
        if ($firstAreaSummaryRow === null) $firstAreaSummaryRow = $summaryRow;
        $percent = (float)$area['possible'] > 0 ? round(((float)$area['obtained'] / (float)$area['possible']) * 100, 2) : 0;
        scorecardSetStyledCell($dom, $xpath, $summaryRow, $rowNo, 'A', chr(65 + $index), false, $areaLabelStyles);
        scorecardSetStyledCell($dom, $xpath, $summaryRow, $rowNo, 'B', $area['name'], false, $areaLabelStyles);
        scorecardSetStyledCell($dom, $xpath, $summaryRow, $rowNo, 'C', number_format($percent, 2) . '%', false, $areaScoreStyles);
        if ($mergeCells instanceof DOMElement) {
            $mergeCell = $dom->createElementNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main', 'mergeCell');
            $mergeCell->setAttribute('ref', 'C' . $rowNo . ':E' . $rowNo);
            $mergeCells->appendChild($mergeCell);
        }
        $rowNo++;
    }
    $areaScoreStart = 8;
    $areaScoreEnd = max($areaScoreStart, $rowNo - 1);
    if ($mergeCells instanceof DOMElement) {
        $mergeCell = $dom->createElementNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main', 'mergeCell');
        $mergeCell->setAttribute('ref', 'F' . $areaScoreStart . ':J' . $areaScoreEnd);
        $mergeCells->appendChild($mergeCell);
    }
    // The area grid shares one prominent, merged department score value.
    if ($firstAreaSummaryRow instanceof DOMElement) {
        scorecardSetStyledCell($dom, $xpath, $firstAreaSummaryRow, $areaScoreStart, 'F', number_format($departmentPercent, 2) . '%', false, $bigScoreStyles);
    }

    $standardTitle = scorecardCreateRow($dom, $rowNo);
    $standardTitle->setAttribute('ht', '24');
    $standardTitle->setAttribute('customHeight', '1');
    $sheetData->appendChild($standardTitle);
    scorecardSetStyledCell($dom, $xpath, $standardTitle, $rowNo, 'A', 'Standard-wise Score', false, $areaStyles);
    $mergeCell = $dom->createElementNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main', 'mergeCell');
    $mergeCell->setAttribute('ref', 'A' . $rowNo . ':J' . $rowNo);
    $mergeCells?->appendChild($mergeCell);
    $rowNo++;

    foreach ($summaries['standards'] as $index => $standard) {
        $standardRow = scorecardCreateRow($dom, $rowNo);
        $standardRow->setAttribute('ht', '30');
        $standardRow->setAttribute('customHeight', '1');
        $sheetData->appendChild($standardRow);
        $percent = (float)$standard['possible'] > 0 ? round(((float)$standard['obtained'] / (float)$standard['possible']) * 100, 2) : 0;
        scorecardSetStyledCell($dom, $xpath, $standardRow, $rowNo, 'A', $standard['reference'] ?: ($index + 1), false, $standardDetailStyles);
        scorecardSetStyledCell($dom, $xpath, $standardRow, $rowNo, 'B', $standard['description'] ?: $standard['name'], false, $standardDetailStyles);
        scorecardSetStyledCell($dom, $xpath, $standardRow, $rowNo, 'C', number_format($percent, 2) . '%', false, $areaScoreStyles);
        $rowNo++;
    }

    $detailTitle = scorecardCreateRow($dom, $rowNo);
    $sheetData->appendChild($detailTitle);
    scorecardSetStyledCell($dom, $xpath, $detailTitle, $rowNo, 'A', 'Checkpoint-wise Details', false, $areaStyles);
    $mergeCell = $dom->createElementNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main', 'mergeCell');
    $mergeCell->setAttribute('ref', 'A' . $rowNo . ':J' . $rowNo);
    $mergeCells?->appendChild($mergeCell);
    $rowNo++;

    $detailHeader = scorecardCreateRow($dom, $rowNo);
    $sheetData->appendChild($detailHeader);
    foreach (['A' => 'Reference No', 'B' => 'Measurable Elements', 'C' => 'Checkpoints', 'D' => 'Means of Verification', 'E' => 'Assessment Method', 'F' => 'User Compliance', 'G' => 'Revised Score', 'H' => 'Action Plan'] as $column => $label) {
        scorecardSetStyledCell($dom, $xpath, $detailHeader, $rowNo, $column, $label, false, $standardStyles);
    }
    $rowNo++;

    foreach ($departmentItems as $item) {
        $row = scorecardCreateRow($dom, $rowNo);
        $sheetData->appendChild($row);

        if ($item['type'] === 'area') {
            foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'] as $column) {
                scorecardSetStyledCell(
                    $dom,
                    $xpath,
                    $row,
                    $rowNo,
                    $column,
                    $column === 'A' ? $item['measurable'] : '',
                    false,
                    $areaStyles
                );
            }

            if ($mergeCells instanceof DOMElement) {
                $mergeCell = $dom->createElementNS(
                    'http://schemas.openxmlformats.org/spreadsheetml/2006/main',
                    'mergeCell'
                );
                $mergeCell->setAttribute('ref', 'A' . $rowNo . ':H' . $rowNo);
                $mergeCells->appendChild($mergeCell);
            }

            $rowNo++;
            continue;
        }

        if ($item['type'] === 'standard') {
            scorecardSetStyledCell($dom, $xpath, $row, $rowNo, 'A', $item['reference'], false, $standardStyles);
            scorecardSetStyledCell($dom, $xpath, $row, $rowNo, 'B', $item['standard'], false, $standardStyles);
            scorecardSetStyledCell($dom, $xpath, $row, $rowNo, 'C', '', false, $standardStyles);
            scorecardSetStyledCell($dom, $xpath, $row, $rowNo, 'D', '', false, $standardStyles);
            scorecardSetStyledCell($dom, $xpath, $row, $rowNo, 'E', '', false, $standardStyles);
            scorecardSetStyledCell($dom, $xpath, $row, $rowNo, 'F', '', false, $standardStyles);
            scorecardSetStyledCell($dom, $xpath, $row, $rowNo, 'G', '', false, $standardStyles);
            scorecardSetStyledCell($dom, $xpath, $row, $rowNo, 'H', '', false, $standardStyles);

            if ($mergeCells instanceof DOMElement) {
                $mergeCell = $dom->createElementNS(
                    'http://schemas.openxmlformats.org/spreadsheetml/2006/main',
                    'mergeCell'
                );
                $mergeCell->setAttribute('ref', 'B' . $rowNo . ':H' . $rowNo);
                $mergeCells->appendChild($mergeCell);
            }

            $rowNo++;
            continue;
        }

        $response = $responseMap[$deptId . ':' . (int)$item['checkpoint_id']] ?? null;
        $userScore = null;
        $revisedScore = null;

        if ($response) {
            $rawScore = $response['response_value'];
            $userScore = is_numeric($rawScore)
                ? (int)$rawScore
                : (int)$response['score'];

            if ($response['revised_score'] !== null && is_numeric($response['revised_score'])) {
                $revisedScore = (int)$response['revised_score'];
            }
        }

        $remarks = $response ? (string)$response['remarks'] : '';

        scorecardSetStyledCell($dom, $xpath, $row, $rowNo, 'A', $item['reference'], false, $dataStyles);
        scorecardSetStyledCell($dom, $xpath, $row, $rowNo, 'B', $item['measurable'], false, $dataStyles);
        scorecardSetStyledCell($dom, $xpath, $row, $rowNo, 'C', $item['checkpoint'], false, $dataStyles);
        scorecardSetStyledCell($dom, $xpath, $row, $rowNo, 'D', $item['verification'], false, $dataStyles);
        scorecardSetStyledCell($dom, $xpath, $row, $rowNo, 'E', $item['method'], false, $dataStyles);
        scorecardSetStyledCell($dom, $xpath, $row, $rowNo, 'F', $userScore, true, $dataStyles);
        scorecardSetStyledCell($dom, $xpath, $row, $rowNo, 'G', $revisedScore, true, $dataStyles);
        scorecardSetStyledCell($dom, $xpath, $row, $rowNo, 'H', $remarks, false, $dataStyles);

        $rowNo++;
    }

    if ($mergeCells instanceof DOMElement) {
        $mergeCells->setAttribute('count', (string)$xpath->query('x:mergeCell', $mergeCells)->length);
    }

    $dimension = $xpath->query('//x:dimension')->item(0);

    if ($dimension instanceof DOMElement) {
        $dimension->setAttribute('ref', 'A1:K' . max(10, $rowNo - 1));
    }

        $zip->addFromString($sheetPath, $dom->saveXML());
    }

    scorecardConfigureDepartmentSheets($zip, $sheetNames);
    $zip->close();

    $filename = 'checkpoint_scorecard_assessment_' . $assessmentId . '.xlsx';

    if (!headers_sent()) {
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($tmpFile));
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    }

    readfile($tmpFile);
    @unlink($tmpFile);
    exit;

} catch (Throwable $e) {
    Response::serverError($e->getMessage());
}
