<?php
//session_start();
//include("assets/conn/db.php");
include("assets/head/h.php");

/* SESSION VALUES */

$fac   = $_SESSION['u_facilityid'];
$dept  = $_SESSION['dept_id1'];
$month = $_SESSION['new_date1']."-01";

/* CHECK IF DOCUMENTS ALREADY EXIST */

$doc = $con->prepare("
SELECT patient_satisfaction,
       client_satisfaction,
       quality_meeting,
       outcome_excel
FROM outcome_documents
WHERE institute_id=? AND dept_id=? AND month=?
");

$doc->bind_param("iis",$fac,$dept,$month);
$doc->execute();
$res = $doc->get_result();
$existing = $res->fetch_assoc();


/* ================================
   FORM SUBMISSION
================================ */

if($_SERVER["REQUEST_METHOD"]=="POST"){

$dir = "uploads/outcome_documents/$fac/$month/";

if(!is_dir($dir)){
mkdir($dir,0777,true);
}

/* FILE UPLOAD FUNCTION */

function uploadDoc($file,$name,$dir,$oldFile=null){

if(empty($file['name'])) return $oldFile;

$ext = strtolower(pathinfo($file['name'],PATHINFO_EXTENSION));

$allowed=['pdf','doc','docx','xls','xlsx','jpg','jpeg','png','webp'];

if(!in_array($ext,$allowed)){
return $oldFile;
}

/* DELETE OLD FILE */

if($oldFile && file_exists($oldFile)){
unlink($oldFile);
}

/* NEW FILE NAME */

$path = $dir.$name."_".time().".".$ext;

move_uploaded_file($file['tmp_name'],$path);

return $path;

}


/* PROCESS FILES */

$pss = uploadDoc($_FILES['pss'],"patient_satisfaction",$dir,$existing['patient_satisfaction'] ?? null);

$css = uploadDoc($_FILES['css'],"client_satisfaction",$dir,$existing['client_satisfaction'] ?? null);

$qcm = uploadDoc($_FILES['qcm'],"quality_circle_meeting",$dir,$existing['quality_meeting'] ?? null);

$oes = uploadDoc($_FILES['oes'],"outcome_excel",$dir,$existing['outcome_excel'] ?? null);


/* UPDATE OR INSERT */

if($existing){

$stmt=$con->prepare("

UPDATE outcome_documents
SET patient_satisfaction=?,
    client_satisfaction=?,
    quality_meeting=?,
    outcome_excel=?
WHERE institute_id=? AND dept_id=? AND month=?

");

$stmt->bind_param(
"ssssiis",
$pss,
$css,
$qcm,
$oes,
$fac,
$dept,
$month
);

}else{

$stmt=$con->prepare("

INSERT INTO outcome_documents
(institute_id,dept_id,month,
patient_satisfaction,
client_satisfaction,
quality_meeting,
outcome_excel)

VALUES (?,?,?,?,?,?,?)

");

$stmt->bind_param(
"iisssss",
$fac,
$dept,
$month,
$pss,
$css,
$qcm,
$oes
);

}

$stmt->execute();

echo "<script>
alert('Documents saved successfully');
window.location='outcome.php';
</script>";

exit;

}
?>

<div class="pcoded-main-container">
<div class="pcoded-content">

<div class="card">
<div class="card-body">

<h4 class="text-danger mb-3">
Final Submission – Mandatory Documents
</h4>

<form method="post" enctype="multipart/form-data">

<div class="row g-3">


<!-- PATIENT SATISFACTION -->

<div class="col-md-6">

<label class="fw-bold">
Patient Satisfaction Score
</label>

<?php if(!empty($existing['patient_satisfaction'])): ?>

<div class="mb-2">
<a href="<?= $existing['patient_satisfaction'] ?>" target="_blank" class="text-success">
View Uploaded File
</a>
</div>

<?php endif; ?>

<input type="file"
name="pss"
class="form-control"
accept=".pdf,.doc,.docx,.xls,.xlsx,image/*">

</div>



<!-- CLIENT SATISFACTION -->

<div class="col-md-6">

<label class="fw-bold">
Client Satisfaction Score
</label>

<?php if(!empty($existing['client_satisfaction'])): ?>

<div class="mb-2">
<a href="<?= $existing['client_satisfaction'] ?>" target="_blank" class="text-success">
View Uploaded File
</a>
</div>

<?php endif; ?>

<input type="file"
name="css"
class="form-control"
accept=".pdf,.doc,.docx,.xls,.xlsx,image/*">

</div>



<!-- QUALITY MEETING -->

<div class="col-md-6">

<label class="fw-bold">
Proceedings of Quality Circle Meeting
</label>

<?php if(!empty($existing['quality_meeting'])): ?>

<div class="mb-2">
<a href="<?= $existing['quality_meeting'] ?>" target="_blank" class="text-success">
View Uploaded File
</a>
</div>

<?php endif; ?>

<input type="file"
name="qcm"
class="form-control"
accept=".pdf,.doc,.docx,.xls,.xlsx,image/*">

</div>



<!-- OUTCOME EXCEL -->

<div class="col-md-6">

<label class="fw-bold">
Outcome Excel Sheet
</label>

<?php if(!empty($existing['outcome_excel'])): ?>

<div class="mb-2">
<a href="<?= $existing['outcome_excel'] ?>" target="_blank" class="text-success">
View Uploaded File
</a>
</div>

<?php endif; ?>

<input type="file"
name="oes"
class="form-control"
accept=".pdf,.doc,.docx,.xls,.xlsx,image/*">

</div>

</div>

<hr>

<button class="btn btn-success mt-3">

<?= $existing ? "Update Documents" : "Submit Final Outcome" ?>

</button>

</form>

</div>
</div>

</div>
</div>

<?php include("assets/head/f.php"); ?>