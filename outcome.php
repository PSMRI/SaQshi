<?php
/***************************************************
   OUTCOME PAGE (Store Images As-Is — No Conversion)
   Mapping:
   neu_val  = numerator
   deno_val = denominator
   values_in = result
***************************************************/

// DB
include("assets/conn/db.php");

/***************************************************
 1) Department Selection — Normal POST
***************************************************/
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['department_id'])) {
    session_start();
    $_SESSION['dept_id1']   = $_POST['department_id'];
    $_SESSION['dept_name1'] = $_POST['department_name'];
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

/***************************************************
 2) AJAX — Save Single Indicator Card
***************************************************/
if ($_SERVER["REQUEST_METHOD"] === "POST" && ( $_POST['action'] ?? "" ) === "save_outcome") {

    session_start();
    header('Content-Type: application/json');

    // Validate required session keys
    $need = ['u_facilityid','dept_id1','new_date1','assperiod','f_type_id'];
    foreach ($need as $k){
        if(empty($_SESSION[$k])){
            echo json_encode(["status"=>"error","msg"=>"Session expired. Reload!"]);
            exit;
        }
    }

    $fac_id  = $_SESSION['u_facilityid'];
    $dept_id = $_SESSION['dept_id1'];
    $date1   = $_SESSION['new_date1'];
    $period  = $_SESSION['assperiod'];
    $ftype   = $_SESSION['f_type_id'];

    // Inputs
    $indicator = intval($_POST['indicator_id']);
    $num       = ($_POST['numerator']   !== "") ? floatval($_POST['numerator'])   : null;
    $den       = ($_POST['denominator'] !== "") ? floatval($_POST['denominator']) : null;
    $res       = ($_POST['result_value'] !== "") ? $_POST['result_value'] : null;
    $opt       = trim($_POST['selected_option']);

    // Remove % if script printed
    if(is_string($res)) $res = str_replace('%','',$res);
    if(is_numeric($res)) $res = floatval($res);

    // Auto calculate if missing
    if ($res === null && $num !== null && $den !== null && $den != 0) {
        $res = round($num / $den, 2);
    }

    if(!is_numeric($res)){
        echo json_encode(["status"=>"error","msg"=>"Invalid result value"]);
        exit;
    }

    /***************************************************
      Check existing entry
    ***************************************************/
    $chk = $con->prepare("
      SELECT outcome_id_values,
      (SELECT file_path FROM outcome_value_files 
       WHERE outcome_value_id=outcome_id_values LIMIT 1) AS old_file
      FROM outcome_values_in
      WHERE month_in=? AND institute_id=? AND dept_id=? AND id_out_hwc=?
      LIMIT 1");
    $chk->bind_param("siii",$date1,$fac_id,$dept_id,$indicator);
    $chk->execute();
    $exist=$chk->get_result()->fetch_assoc();
    $chk->close();

    $oid      = 0;
    $old_file = $exist['old_file'] ?? null;

    /***************************************************
      INSERT or UPDATE outcome_values_in
    ***************************************************/
    if($exist){

        $oid=$exist['outcome_id_values'];

        $u=$con->prepare("
            UPDATE outcome_values_in
            SET values_in=?, neu_val=?, deno_val=?
            WHERE outcome_id_values=?");
        $u->bind_param("dddi",$res,$num,$den,$oid);
        $u->execute();
        $u->close();

    } else {

        // Call Stored Procedure
        $i=$con->prepare("CALL insert_outcome_values(?,?,?,?,?,?,?,?)");
        $i->bind_param(
            "idsiiidd",
            $indicator,
            $res,
            $date1,
            $fac_id,
            $dept_id,
            $period,
            $den,    // deno_val
            $num     // neu_val
        );
        $i->execute();
        $i->close();

        // fetch inserted id
        $g=$con->prepare("
            SELECT outcome_id_values 
            FROM outcome_values_in
            WHERE month_in=? AND institute_id=? AND dept_id=? AND id_out_hwc=?
            ORDER BY outcome_id_values DESC LIMIT 1");
        $g->bind_param("siii",$date1,$fac_id,$dept_id,$indicator);
        $g->execute();
        $gx=$g->get_result()->fetch_assoc();
        $g->close();

        if(!$gx){
            echo json_encode(["status"=>"error","msg"=>"Insert fetch failed"]);
            exit;
        }
        $oid=$gx['outcome_id_values'];
    }

    /***************************************************
      FILE UPLOAD (Store Original File as-is)
      Supports Camera, Gallery, PDF
    ***************************************************/
    $store = $old_file;

    if(isset($_FILES['evidence_file']) && $_FILES['evidence_file']['error']==0){

        $tmp  = $_FILES['evidence_file']['tmp_name'];
        $name = $_FILES['evidence_file']['name'];
        $size = $_FILES['evidence_file']['size'];
        $ext  = strtolower(pathinfo($name,PATHINFO_EXTENSION));

        $allowed = ['pdf','jpg','jpeg','png','webp','heif','heic','bmp','gif','tiff'];
        if(!in_array($ext,$allowed)){
            echo json_encode(["status"=>"error","msg"=>"Invalid file type!"]);
            exit;
        }

        if($size > 10*1024*1024){
            echo json_encode(["status"=>"error","msg"=>"File must be < 10MB"]);
            exit;
        }

        // Folder
        $folder="uploads/outcome/$fac_id/$date1/";
        if(!is_dir($folder)) mkdir($folder,0777,true);

        // Keep original filename
        $filePath=$folder.$name;

        // Remove old file
        if($old_file && file_exists($old_file)) unlink($old_file);

        move_uploaded_file($tmp,$filePath);

        $store=$filePath;
    }

    /***************************************************
      Insert/Update outcome_value_files
    ***************************************************/
    $q=$con->prepare("SELECT id FROM outcome_value_files WHERE outcome_value_id=? LIMIT 1");
    $q->bind_param("i",$oid);
    $q->execute();
    $have=$q->get_result()->fetch_assoc();
    $q->close();

    if($have){
        $u=$con->prepare("UPDATE outcome_value_files 
                          SET selected_option=?,file_path=? 
                          WHERE outcome_value_id=?");
        $u->bind_param("ssi",$opt,$store,$oid);
        $u->execute();
        $u->close();
    } else {
        $i=$con->prepare("INSERT INTO outcome_value_files 
                        (outcome_value_id,selected_option,file_path) 
                        VALUES (?,?,?)");
        $i->bind_param("iss",$oid,$opt,$store);
        $i->execute();
        $i->close();
    }

    echo json_encode(["status"=>"success"]);
    exit;
}

/***************************************************
 3) PAGE UI
***************************************************/
include("assets/head/h.php");

$showDept = empty($_SESSION['dept_id1']);
$deptName = $_SESSION['dept_name1'] ?? "";
?>

<style>
.upload-progress{height:3px;width:0;background:green;transition:.2s;}
.upload-container{display:none;background:#e9f7e9;margin-top:4px;}
.saved-border{border:2px solid #28a745!important;}
.small-file-link{font-size:12px;margin-top:4px;}
</style>

<div class="pcoded-main-container">
<div class="pcoded-content">

<h5 class="fw-bold text-primary mb-2">
 <i class="bi bi-bar-chart-fill me-2"></i>
 Outcome Indicators <?= $deptName?"for ".htmlspecialchars($deptName):"" ?>
 <button class="btn btn-sm btn-link text-warning"
  data-bs-toggle="modal" data-bs-target="#deptModal">Change Department</button>
</h5>

<div class="card mb-3"><div class="card-body">
<form method="post">
<label class="fw-bold">Month</label>
<input type="month" name="date1" class="form-control"
 value="<?= $_POST['date1'] ?? date('Y-m'); ?>"
 min="<?= date('Y-m',strtotime('-6 months')); ?>"
 max="<?= date('Y-m'); ?>" required>
<button class="btn btn-primary mt-2" name="submit1">Fill Data</button>
</form>
</div></div>

<?php
/***************************************************
 Load Indicator Cards
***************************************************/
if(isset($_POST['submit1'])){
    if(empty($_SESSION['dept_id1'])){
        echo "<div class='alert alert-warning'>Please select department.</div>";
    } else {

        $newDate=date('Y-m',strtotime($_POST['date1']));
        $_SESSION['new_date1']=$newDate;

        if($newDate=='1970-01'){
            echo "<div class='alert alert-danger'>Invalid Month!</div>";
        } else {

            $fac=$_SESSION['u_facilityid'];
            $did=$_SESSION['dept_id1'];
            $ft=$_SESSION['f_type_id'];

            $q=$con->prepare("
              SELECT id_out_hwc,out_come_hwcindi,num,deno
              FROM out_come_dh
              WHERE out_come_hwc_factype=? AND out_come_dept=?");
            $q->bind_param("ii",$ft,$did);
            $q->execute();
            $set=$q->get_result();

            $total=$set->num_rows;
            $i=0;

            echo "<form>";

            while($r=$set->fetch_assoc()){ $i++;

                $sv=$con->prepare("
                  SELECT outcome_id_values,values_in,neu_val,deno_val
                  FROM outcome_values_in
                  WHERE month_in=? AND institute_id=? AND dept_id=? AND id_out_hwc=?
                  LIMIT 1");
                $sv->bind_param("siii",$newDate,$fac,$did,$r['id_out_hwc']);
                $sv->execute();
                $sd=$sv->get_result()->fetch_assoc();
                $sv->close();

                $numVal=$sd['neu_val']  ?? "";
                $denVal=$sd['deno_val'] ?? "";
                $resVal=$sd['values_in'] ?? "";

                $opt="";
                $filehtml="";
                if($sd){
                    $mid=$sd['outcome_id_values'];
                    $ff=$con->prepare("SELECT selected_option,file_path 
                                       FROM outcome_value_files 
                                       WHERE outcome_value_id=?");
                    $ff->bind_param("i",$mid);
                    $ff->execute();
                    $fx=$ff->get_result()->fetch_assoc();
                    $ff->close();

                    if($fx){
                        $opt=$fx['selected_option'];
                        if($fx['file_path']){
                            $filehtml="<div class='small-file-link'>
 Evidence: ".basename($fx['file_path'])."
 <br><a href='{$fx['file_path']}' target='_blank'>View File</a>
</div>";
                        }
                    }
                }

                $readonly = ($r['deno']=="N/A")?"readonly":"";
                $cls = $sd?"saved-border":"";

                echo "
<div class='card mb-3 $cls' id='card$i' style='".($i>1?"display:none":"")."'>
<div class='card-body'>

<h6 class='text-primary'>
 <i class='bi bi-check2-circle me-2'></i>{$r['out_come_hwcindi']}
</h6>

<div class='alert alert-info small'>
<b>Expected:</b> Num <b>{$r['num']}</b>,
Den <b>{$r['deno']}</b>
</div>

<div class='row g-2 mb-2'>
 <div class='col-md-4'>
  <input type='number' class='form-control'
         id='input".($i*2-1)."'
         value='$numVal'
         placeholder='Numerator'
         oninput='calculateResult($i)'
         step='any'>
 </div>

 <div class='col-md-4'>
  <input type='number' class='form-control'
         id='input".($i*2)."'
         value='$denVal'
         placeholder='Denominator'
         oninput='calculateResult($i)'
         step='any' $readonly>
 </div>

 <div class='col-md-4'>
  <input type='text' class='form-control'
         id='result$i'
         value='$resVal'
         readonly placeholder='Result'>
 </div>
</div>

<label class='fw-bold'>Source of verification</label>
<select id='opt$i' class='form-control mb-2'>
<option value=''>-- Select --</option>
<option ".($opt=="Achieved"?"selected":"").">Achieved</option>
<option ".($opt=="Partially Achieved"?"selected":"").">Partially Achieved</option>
<option ".($opt=="Not Achieved"?"selected":"").">Not Achieved</option>
</select>

<label class='fw-bold'>Upload File / Camera Photo</label>
<input type='file' id='file$i' class='form-control'
 accept='image/*,.pdf' capture='environment'>

<div class='upload-container' id='box$i'>
 <div class='upload-progress' id='bar$i'></div>
</div>

$filehtml

<input type='hidden' id='ind$i' value='{$r['id_out_hwc']}'>

<div class='d-flex justify-content-between mt-3'>
".($i>1?
"<button class='btn btn-secondary btn-sm' onclick='back($i)'>
 <i class=\"bi bi-arrow-left\"></i> Back</button>" : "<div></div>")."

<button class='btn btn-success btn-sm'
 onclick='saveNext($i,$total)'>".($i==$total?"Finish":"Next")."
 <i class='bi bi-arrow-right'></i></button>
</div>

</div></div>
                ";
            }

            echo "</form>";

            // load dept-wise calc
            if ($ft == 3)
                echo "<script src='assets/calculationjs/departmentphc{$did}.js?v=".time()."'></script>";
            elseif ($ft == 9)
                echo "<script src='assets/calculationjs/departmentaphc{$did}.js?v=".time()."'></script>";
            elseif ($ft == 8 || $ft == 4)
                echo "<script src='assets/calculationjs/departmenthwc2{$did}.js?v=".time()."'></script>";
            elseif ($ft == 1)
                echo "<script src='assets/calculationjs/chc{$did}.js?v=".time()."'></script>";
            else
                echo "<script src='assets/calculationjs/department{$did}.js?v=".time()."'></script>";

            $q->close();
        }
    }
}
?>
</div>
</div>

<!-- Department Modal -->
<div class="modal fade" id="deptModal">
<div class="modal-dialog modal-dialog-centered">
<form method="post">
<div class="modal-content">

<div class="modal-header">
 <h5>Select Department</h5>
</div>

<div class="modal-body">
<select id="depSel" name="department_id" class="form-control" required>
<option value="">-- Select --</option>
<?php
$ft=$_SESSION['f_type_id']??0;
$fac=$_SESSION['u_facilityid']??0;
$as=$_SESSION['assperiod']??0;

$s=$con->prepare("
SELECT DISTINCT a.fac_dept_id_fk,b.dept_name
FROM concern_subtype_chklist a
JOIN fac_department b ON a.fac_dept_id_fk=b.fac_dept_id
WHERE a.fac_type_id_fk=?
AND a.fac_dept_id_fk IN(
  SELECT fac_dept_id FROM fac_dept_map
  WHERE fac_id=? AND acc_id=?
)");
$s->bind_param("iii",$ft,$fac,$as);
$s->execute();
$zz=$s->get_result();
while($d=$zz->fetch_assoc()){
 echo "<option value='{$d['fac_dept_id_fk']}' data-name='{$d['dept_name']}'>
       {$d['dept_name']}
      </option>";
}
$s->close();
?>
</select>

<input type="hidden" name="department_name" id="depName">
</div>

<div class="modal-footer">
<button type="submit" class="btn btn-primary">Continue</button>
</div>

</div>
</form>
</div>
</div>

<?php include("assets/head/f.php"); ?>

<script>
document.getElementById('depSel')?.addEventListener('change',()=>{
 let nm=document.querySelector('#depSel option:checked').dataset.name;
 document.getElementById('depName').value=nm;
});

// Auto modal
<?php if($showDept): ?>
window.onload=()=>{
 let m=new bootstrap.Modal(document.getElementById('deptModal'),{
   backdrop:'static',keyboard:false
 });
 m.show();
}
<?php endif; ?>
/*************************************************
 IMAGE COMPRESSOR (Target size in KB)
**************************************************/

// read file to base64
function readAsDataURL(file){
    return new Promise((resolve,reject)=>{
        const r=new FileReader();
        r.onload=()=>resolve(r.result);
        r.onerror=reject;
        r.readAsDataURL(file);
    });
}

// Convert dataURL back to File
function dataURLtoFile(dataURL, filename){
    const arr=dataURL.split(",");
    const mime=arr[0].match(/:(.*?);/)[1];
    const bstr=atob(arr[1]);
    let n=bstr.length;
    const u8arr=new Uint8Array(n);
    while(n--) u8arr[n]=bstr.charCodeAt(n);
    return new File([u8arr], filename, {type:mime});
}

// Detect EXIF orientation (only JPEG)
async function getOrientation(file) {
    return new Promise(resolve => {

        // Non-JPEG Images don’t have EXIF Orientation
        if (!file.type.includes("jpeg") && !file.type.includes("jpg")) {
            resolve(-1);
            return;
        }

        const reader = new FileReader();
        reader.onload = (event) => {

            const arrayBuffer = event.target.result;
            const view = new DataView(arrayBuffer);

            // sanity check
            if (view.byteLength < 4) {
                resolve(-1);
                return;
            }

            let offset = 2;
            let length = view.byteLength;

            try {

                while (offset + 1 < length) {
                    // EXIF segment marker
                    if (view.getUint16(offset, false) === 0xFFE1) {

                        // ensure enough bytes remain
                        if (offset + 10 > length) break;

                        offset += 2;
                        const exifHeader = view.getUint32(offset, false);
                        if (exifHeader !== 0x45786966) break; // "Exif"

                        const little = view.getUint16(offset + 6, false) === 0x4949;
                        const firstIFD = view.getUint32(offset + 10, little);

                        if (offset + firstIFD > length) break;

                        offset += firstIFD + 6;
                        const tags = view.getUint16(offset, little);
                        offset += 2;

                        for (let i = 0; i < tags; i++) {
                            const tagOffset = offset + (i * 12);

                            if (tagOffset + 10 > length) break;

                            if (view.getUint16(tagOffset, little) === 0x0112) {
                                resolve(view.getUint16(tagOffset + 8, little));
                                return;
                            }
                        }
                    }
                    offset++;
                }

            } catch (err) {
                // fail silently
            }

            resolve(-1);
        };

        reader.onerror = () => resolve(-1);
        reader.readAsArrayBuffer(file);
    });
}


// Apply EXIF orientation to canvas
function applyOrientation(canvas, ctx, orientation){
    const w=canvas.width;
    const h=canvas.height;
    switch(orientation){
        case 2: ctx.translate(w,0); ctx.scale(-1,1); break;
        case 3: ctx.translate(w,h); ctx.rotate(Math.PI); break;
        case 4: ctx.translate(0,h); ctx.scale(1,-1); break;
        case 5: ctx.rotate(0.5*Math.PI); ctx.scale(1,-1); break;
        case 6: ctx.rotate(0.5*Math.PI); ctx.translate(0,-h); break;
        case 7: ctx.rotate(0.5*Math.PI); ctx.translate(w,-h); ctx.scale(-1,1); break;
        case 8: ctx.rotate(-0.5*Math.PI); ctx.translate(-w,0); break;
    }
}

/*************************************************
 MAIN COMPRESSOR
 targetKB → final maximum size
**************************************************/
async function compressImage(file, targetKB){

    if(file.type=="application/pdf") return file; // skip

    let base64 = await readAsDataURL(file);
    let img = new Image();
    img.src = base64;
    await new Promise(res=>img.onload=res);

    const orient = await getOrientation(file);

    let w = img.width;
    let h = img.height;

    // HARD LIMIT photo resolution (optional)
    const MAX_SIDE = 2000;
    if(w > MAX_SIDE || h > MAX_SIDE){
        if(w > h){
            h = Math.round(h * (MAX_SIDE / w));
            w = MAX_SIDE;
        } else {
            w = Math.round(w * (MAX_SIDE / h));
            h = MAX_SIDE;
        }
    }

    let canvas=document.createElement("canvas");
    canvas.width=w;
    canvas.height=h;

    const ctx=canvas.getContext("2d");

    applyOrientation(canvas, ctx, orient);
    ctx.drawImage(img,0,0,w,h);

    let quality = 0.85;
    let result;
    let sizeKB=99999;

    // retry compress until <= target
    while(quality > 0.25){
        result = canvas.toDataURL("image/jpeg", quality);
        sizeKB = Math.round((result.length * 3 / 4) / 1024);
        if(sizeKB <= targetKB) break;
        quality -= 0.05;
    }

    return dataURLtoFile(result, file.name.replace(/\.[^.]+$/, ".jpg"));
}

async function saveNext(i,total){
  event.preventDefault();

  let n=document.getElementById('input'+(i*2-1)).value;
  let dEl=document.getElementById('input'+(i*2));
  let d=dEl.hasAttribute('readonly') ? "" : dEl.value;
  let r=document.getElementById('result'+i).value;
  let id=document.getElementById('ind'+i).value;
  let o=document.getElementById('opt'+i).value;
  let f=document.getElementById('file'+i);

  if(!n){alert("Enter numerator");return;}
  if(!dEl.hasAttribute('readonly') && !d){alert("Enter denominator");return;}
  if(!o){alert("Select Source of Verification");return;}

  let box=document.getElementById('box'+i);
  let bar=document.getElementById('bar'+i);

  let finalFile=null;

  if(f.files.length>0){
    // 🔥 300KB target
    finalFile = await compressImage(f.files[0], 300);
  }

  let fd=new FormData();
  fd.append('action','save_outcome');
  fd.append('indicator_id',id);
  fd.append('numerator',n);
  fd.append('denominator',d);
  fd.append('result_value',r);
  fd.append('selected_option',o);

  if(finalFile) fd.append('evidence_file',finalFile);

  let xhr=new XMLHttpRequest();
  xhr.open("POST","");

  if(finalFile){
    box.style.display="block";
    xhr.upload.onprogress=(e)=>{
      if(e.lengthComputable) bar.style.width=((e.loaded/e.total)*100)+"%";
    }
  }

  xhr.onload=()=>{
    let res={};
    try{res=JSON.parse(xhr.responseText)}catch(err){
      alert("Invalid JSON Response!\nServer Output:\n"+xhr.responseText);
      return;
    }

    if(res.status=="success"){
        if(i<total){
            document.getElementById('card'+i).style.display="none";
            document.getElementById('card'+(i+1)).style.display="block";
        } else {
            alert("All Indicators Saved Successfully!");
        }
    } else {
        alert(res.msg);
    }
  }

  xhr.send(fd);
}


function back(i){
 event.preventDefault();
 document.getElementById('card'+i).style.display="none";
 document.getElementById('card'+(i-1)).style.display="block";
}
</script>
