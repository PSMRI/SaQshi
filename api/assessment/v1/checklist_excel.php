<?php
/** Download and validate offline checklist Excel workbooks. */
require_once __DIR__ . '/../../auth_api.php';
require_once __DIR__ . '/../../assets/conn/db.php';
require_once __DIR__ . '/../../core/FrameworkEngine.php';
require_once __DIR__ . '/../../core/AssessmentAccess.php';
require_once dirname(__DIR__, 3) . '/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

function excelContext(mysqli $con, int $assessmentId, int $deptId): array {
    $facId=SessionManager::facilityId(); $userId=SessionManager::userId();
    if($facId<=0||$userId<=0||$assessmentId<=0||$deptId<=0) Response::validation(['assessment_id'=>'Assessment and department are required']);
    AssessmentAccess::requireEditableByCurrentUser($con,$assessmentId,$facId);
    $q=$con->prepare("SELECT a.framework_code,f.Health_facilty_type FROM assessment_master a JOIN facilities f ON f.fac_id=a.fac_id_fk WHERE a.assessment_id=? AND a.fac_id_fk=? AND a.status='ACTIVE'");$q->bind_param('ii',$assessmentId,$facId);$q->execute();$row=$q->get_result()->fetch_assoc();if(!$row) Response::error('Active assessment not found');
    $q=$con->prepare("SELECT status FROM assessment_department WHERE assessment_id=? AND fac_id_fk=? AND dept_id=? AND is_active=1");$q->bind_param('iii',$assessmentId,$facId,$deptId);$q->execute();$d=$q->get_result()->fetch_assoc();if(!$d||$d['status']==='COMPLETED') Response::error('Department is not available for import');
    $q=$con->prepare('SELECT info_id FROM assessment_assessor_info WHERE assessment_id=? AND fac_id_fk=? AND dept_id=?');$q->bind_param('iii',$assessmentId,$facId,$deptId);$q->execute();if(!$q->get_result()->fetch_assoc()) Response::error('Save assessor information before downloading or uploading a checklist');
    return [$row,(int)$facId];
}
Security::requireMethod($_SERVER['REQUEST_METHOD']==='GET'?'GET':'POST');
try { $aid=(int)($_REQUEST['assessment_id']??0);$did=(int)($_REQUEST['dept_id']??0);[$ctx,$facId] = excelContext($con,$aid,$did);$engine=FrameworkEngine::load($ctx['framework_code']?:'saqshi-nqas');$valid=[];foreach($engine->getCheckpoints((int)$ctx['Health_facilty_type'],$did) as $c)$valid[(int)($c['csqa_id']??0)]=$c;
if($_SERVER['REQUEST_METHOD']==='GET'){ $book=new Spreadsheet();$meta=$book->getActiveSheet();$meta->setTitle('Metadata');foreach([['A1','Assessment ID'],['B1',$aid],['A2','Department ID'],['B2',$did],['A3','Framework'],['B3',$ctx['framework_code']],['A5','Instructions'],['B5','Enter compliance only in the yellow cells: 0 = Non Compliant, 1 = Partially Compliant, 2 = Fully Compliant.']] as [$cell,$value])$meta->setCellValue($cell,$value);$sheet=$book->createSheet();$sheet->setTitle('Checklist');foreach(['A1'=>'Area of Concern','B1'=>'Reference_No','C1'=>'area_of_con_subtypedeatils','D1'=>'csqa_reference_id','E1'=>'Means_of_Verification','F1'=>'Measurable_Element','G1'=>'Checkpoint','H1'=>'Assessment_Method','I1'=>'Compliance (0/1/2)'] as $cell=>$value)$sheet->setCellValue($cell,$value);$r=2;foreach($valid as $id=>$c){foreach(['A'=>$c['_concern_name']??'','B'=>$c['_reference_no']??'','C'=>$c['_subtype_name']??'','D'=>$id,'E'=>$c['Means_of_Verification']??'','F'=>$c['Measurable_Element']??'','G'=>$c['Checkpoint']??'','H'=>$c['Assessment_Method']??'','I'=>''] as $column=>$value)$sheet->setCellValue($column.$r,$value);$sheet->getStyle('I'.$r)->getFill()->setFillType('solid')->getStartColor()->setRGB('FFF2CC');$r++;}$validation=$sheet->getDataValidation('I2');$validation->setType(DataValidation::TYPE_LIST)->setFormula1('"0,1,2"')->setAllowBlank(false)->setShowDropDown(true);$sheet->setDataValidation('I2:I'.($r-1),$validation);foreach(['A'=>24,'B'=>18,'C'=>34,'D'=>18,'E'=>28,'F'=>35,'G'=>70,'H'=>22,'I'=>22] as $column=>$width)$sheet->getColumnDimension($column)->setWidth($width);$sheet->freezePane('A2');header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="checklist_'.$aid.'_'.$did.'.xlsx"');(new Xlsx($book))->save('php://output');exit; }
$file=$_FILES['file']??null;if(!$file||$file['error']!==UPLOAD_ERR_OK)Response::validation(['file'=>'Upload a valid Excel file']);
// PhpSpreadsheet 5 still uses PHP's deprecated alphabetic string increment while
// reading column dimensions. It is a harmless library compatibility notice, not
// an invalid workbook, so do not let the application's strict error handler turn
// it into a failed import.
$previousErrorReporting=error_reporting();
error_reporting($previousErrorReporting & ~E_DEPRECATED);
try{$book=IOFactory::load($file['tmp_name']);}finally{error_reporting($previousErrorReporting);}
$meta=$book->getSheetByName('Metadata');$sheet=$book->getSheetByName('Checklist');if(!$meta||!$sheet|| (int)$meta->getCell('B1')->getValue()!==$aid || (int)$meta->getCell('B2')->getValue()!==$did)Response::error('Workbook metadata does not match this assessment and department');$headers=[];foreach(range('A','I') as $column)$headers[strtolower(trim((string)$sheet->getCell($column.'1')->getValue()))]=$column;$idColumn=$headers['csqa_reference_id']??$headers['checkpoint id']??null;$complianceColumn=$headers['compliance (0/1/2)']??null;if(!$idColumn||!$complianceColumn)Response::validation(['file'=>'Checklist headers are invalid. Download a new sample Excel file.']);$rows=[];$seen=[];$errors=[];foreach($sheet->getRowIterator(2) as $row){$id=(int)$sheet->getCell($idColumn.$row->getRowIndex())->getValue();$value=$sheet->getCell($complianceColumn.$row->getRowIndex())->getValue();if($id===0&&$value==='')continue;if(!isset($valid[$id]))$errors[]="Row {$row->getRowIndex()}: invalid checkpoint ID";elseif(isset($seen[$id]))$errors[]="Row {$row->getRowIndex()}: duplicate checkpoint ID";elseif(!in_array((string)$value,['0','1','2'],true))$errors[]="Row {$row->getRowIndex()}: compliance must be 0, 1 or 2";else{$seen[$id]=true;$checkpoint=$valid[$id];$rows[]=['checkpoint_id'=>$id,'response_value'=>(string)$value,'area_of_concern'=>$checkpoint['_concern_name']??'','reference_no'=>$checkpoint['_reference_no']??'','subtype_details'=>$checkpoint['_subtype_name']??'','csqa_reference_id'=>$checkpoint['csqa_reference_id']??$id,'means_of_verification'=>$checkpoint['Means_of_Verification']??'','measurable_element'=>$checkpoint['Measurable_Element']??'','checkpoint'=>$checkpoint['Checkpoint']??'','assessment_method'=>$checkpoint['Assessment_Method']??''];}}
if(!$errors){
    $con->query("CREATE TABLE IF NOT EXISTS assessment_excel_import_staging (assessment_id INT NOT NULL, dept_id INT NOT NULL, fac_id INT NOT NULL, file_name VARCHAR(255) NOT NULL, responses_json LONGTEXT NOT NULL, updated_by INT NOT NULL, updated_on TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY (assessment_id, dept_id))");
    $payload=json_encode($rows,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);$name=basename((string)($file['name']??'checklist.xlsx'));
    $stage=$con->prepare('INSERT INTO assessment_excel_import_staging (assessment_id,dept_id,fac_id,file_name,responses_json,updated_by) VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE fac_id=VALUES(fac_id),file_name=VALUES(file_name),responses_json=VALUES(responses_json),updated_by=VALUES(updated_by)');$userId=SessionManager::userId();$stage->bind_param('iiissi',$aid,$did,$facId,$name,$payload,$userId);$stage->execute();
}
Response::success('Import preview generated',['valid'=>!$errors,'errors'=>$errors,'responses'=>$rows,'assessment_id'=>$aid,'dept_id'=>$did]);
}catch(Throwable $e){Response::serverError($e->getMessage());}
