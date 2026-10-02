<?php
/** Resumable status and reset for Excel checklist imports. */
require_once __DIR__ . '/../../auth_api.php';
require_once __DIR__ . '/../../assets/conn/db.php';
require_once __DIR__ . '/../../core/FrameworkEngine.php';
require_once __DIR__ . '/../../core/AssessmentAccess.php';
Security::requireMethod($_SERVER['REQUEST_METHOD'] === 'GET' ? 'GET' : 'POST');
try {
    $input = $_SERVER['REQUEST_METHOD'] === 'GET' ? $_GET : Security::jsonInput();
    $assessmentId=(int)($input['assessment_id']??0); $deptId=(int)($input['dept_id']??0); $facId=SessionManager::facilityId();
    if(!$assessmentId||!$deptId||!$facId) Response::validation(['assessment_id'=>'Assessment and department are required']);
    AssessmentAccess::requireEditableByCurrentUser($con,$assessmentId,$facId);
    $con->query("CREATE TABLE IF NOT EXISTS assessment_excel_import_staging (assessment_id INT NOT NULL, dept_id INT NOT NULL, fac_id INT NOT NULL, file_name VARCHAR(255) NOT NULL, responses_json LONGTEXT NOT NULL, updated_by INT NOT NULL, updated_on TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY (assessment_id, dept_id))");
    if(($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($input['action'] ?? '') === 'delete'){
        $table=$con->query("SHOW TABLES LIKE 'assessment_action_plan'");
        if($table && $table->num_rows){
            $q=$con->prepare('SELECT COUNT(*) total, SUM(CASE WHEN UPPER(COALESCE(status,\'\')) IN (\'COMPLETED\',\'CLOSED\') THEN 1 ELSE 0 END) closed FROM assessment_action_plan WHERE assessment_id=? AND dept_id=?');$q->bind_param('ii',$assessmentId,$deptId);$q->execute();$plans=$q->get_result()->fetch_assoc()?:[];
            if((int)($plans['total']??0)>0){
                Response::error((int)($plans['closed']??0)>0 ? 'Uploaded checklist cannot be deleted because Gap Closure has been completed.' : 'Uploaded checklist cannot be deleted because an Action Plan has already been created.');
            }
        }
        $con->begin_transaction();
        foreach(['assessment_response_evidence','assessment_response_field_index'] as $table){$q=$con->prepare("DELETE FROM {$table} WHERE assessment_id=? AND dept_id=?"); if($q){$q->bind_param('ii',$assessmentId,$deptId);$q->execute();}}
        $q=$con->prepare('DELETE FROM assessment_response WHERE assessment_id=? AND dept_id=?');$q->bind_param('ii',$assessmentId,$deptId);$q->execute();$deleted=$q->affected_rows;
        $q=$con->prepare('DELETE FROM assessment_excel_import_staging WHERE assessment_id=? AND dept_id=? AND fac_id=?');$q->bind_param('iii',$assessmentId,$deptId,$facId);$q->execute();
        $q=$con->prepare("UPDATE assessment_department SET status='IN_PROGRESS', completed_on=NULL, current_checkpoint_id=NULL WHERE assessment_id=? AND fac_id_fk=? AND dept_id=? AND is_active=1");$q->bind_param('iii',$assessmentId,$facId,$deptId);$q->execute();
        $con->commit(); Response::success('Uploaded checklist responses deleted',['deleted_count'=>$deleted]);
    }
    $q=$con->prepare('SELECT a.framework_code,f.Health_facilty_type FROM assessment_master a JOIN facilities f ON f.fac_id=a.fac_id_fk WHERE a.assessment_id=? AND a.fac_id_fk=? AND a.status=\'ACTIVE\'');$q->bind_param('ii',$assessmentId,$facId);$q->execute();$row=$q->get_result()->fetch_assoc();if(!$row)Response::error('Active assessment not found');
    $engine=FrameworkEngine::load($row['framework_code']?:'saqshi-nqas');$total=count($engine->getCheckpoints((int)$row['Health_facilty_type'],$deptId));$q=$con->prepare('SELECT COUNT(DISTINCT checkpoint_id) total FROM assessment_response WHERE assessment_id=? AND dept_id=?');$q->bind_param('ii',$assessmentId,$deptId);$q->execute();$saved=(int)($q->get_result()->fetch_assoc()['total']??0);
    $q=$con->prepare('SELECT DISTINCT checkpoint_id FROM assessment_response WHERE assessment_id=? AND dept_id=?');$q->bind_param('ii',$assessmentId,$deptId);$q->execute();$ids=[];$result=$q->get_result();while($item=$result->fetch_assoc())$ids[]=(int)$item['checkpoint_id'];
    $q=$con->prepare('SELECT file_name,responses_json FROM assessment_excel_import_staging WHERE assessment_id=? AND dept_id=? AND fac_id=?');$q->bind_param('iii',$assessmentId,$deptId,$facId);$q->execute();$stage=$q->get_result()->fetch_assoc()?:[];$staged=json_decode((string)($stage['responses_json']??'[]'),true);if(!is_array($staged))$staged=[];
    Response::success('Excel import progress loaded',['saved_count'=>$saved,'total_count'=>$total,'remaining_count'=>max(0,$total-$saved),'saved_checkpoint_ids'=>$ids,'staged_file_name'=>$stage['file_name']??'','staged_responses'=>$staged]);
}catch(Throwable $e){if(isset($con))try{$con->rollback();}catch(Throwable $ignored){} Response::serverError($e->getMessage());}
