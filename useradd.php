<?php
include("assets/head/h.php");

/* ======================================================
   HELPER: SHOW USER LIST
====================================================== */
function displayUserTable($fac_id, $con){
    $res = mysqli_query($con,"
        SELECT u_name, f_name 
        FROM s_user 
        WHERE fac_id_fk = $fac_id
    ");

    if($res && mysqli_num_rows($res)){
        echo "<table class='table table-bordered mt-3'>
                <thead>
                    <tr><th>User ID</th><th>Name</th></tr>
                </thead>
                <tbody>";
        while($r = mysqli_fetch_assoc($res)){
            echo "<tr>
                    <td>".htmlspecialchars($r['u_name'])."</td>
                    <td>".htmlspecialchars($r['f_name'])."</td>
                  </tr>";
        }
        echo "</tbody></table>";
    }
}
?>

<div class="pcoded-main-container">
<div class="pcoded-content">

<div class="pagetitle mb-3">
    <h5 class="fw-bold text-primary">User Creation</h5>
</div>

<!-- ======================================================
     SINGLE FACILITY USER CREATION
====================================================== -->
<div class="card">
<div class="card-body">

<form method="POST">

<!-- District -->
<div class="row mb-3">
<label class="col-md-3 col-form-label">District</label>
<div class="col-md-9">
<select class="form-control" id="District1" required>
<option value="">- Select District -</option>
<?php
$res = mysqli_query($con,"SELECT dist_id, Dist_name FROM dist_master");
while($r=mysqli_fetch_assoc($res)){
    echo "<option value='{$r['dist_id']}'>{$r['Dist_name']}</option>";
}
?>
</select>
</div>
</div>

<!-- Block -->
<div class="row mb-3">
<label class="col-md-3 col-form-label">Health Block</label>
<div class="col-md-9">
<select class="form-control" id="block" required>
<option value="">- Select Block -</option>
</select>
</div>
</div>

<!-- Facility -->
<div class="row mb-3">
<label class="col-md-3 col-form-label">Facility</label>
<div class="col-md-9">
<select class="form-control" id="facility" required>
<option value="">- Select Facility -</option>
</select>
</div>
</div>

<!-- Hidden -->
<input type="hidden" name="fac_id" id="fac_id">
<input type="hidden" name="dist_id" id="dist_id">
<input type="hidden" name="block_id" id="block_id">
<input type="hidden" name="nin_no" id="nin_no">
<input type="hidden" name="facility_type" id="facility_type">

<div class="text-center">
<button type="submit"
        name="create_user"
        id="createBtn"
        class="btn btn-primary"
        disabled>
    Create User
</button>
</div>

</form>


<?php
/* ======================================================
   SINGLE USER CREATE
====================================================== */
if(isset($_POST['create_user'])){

    $fac_id        = (int)$_POST['fac_id'];
    $dist_id       = (int)$_POST['dist_id'];
    $block_id      = (int)$_POST['block_id'];
    $facility_type = (int)$_POST['facility_type'];

    // BIGINT SAFE (DO NOT CAST TO INT)
    $nin = trim($_POST['nin_no'] ?? '');

    /* ===== VALIDATION ===== */
    if($fac_id <= 0 || $dist_id <= 0 || $block_id <= 0){
        echo "<div class='alert alert-danger mt-3'>Invalid selection.</div>";
    }
    elseif($nin === '' || !ctype_digit($nin)){
        echo "<div class='alert alert-danger mt-3'>
                Invalid NIN. Facility NIN is missing or incorrect.
              </div>";
    }
    else {

        /* Get Division */
        $stmt = $con->prepare("
            SELECT division_id 
            FROM dist_master 
            WHERE dist_id = ?
        ");
        $stmt->bind_param("i",$dist_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $division = ($res && $res->num_rows)
            ? (int)$res->fetch_assoc()['division_id']
            : 0;
        $stmt->close();

        /* CALL PROCEDURE */
        $stmt = $con->prepare("CALL facility_user_create(?,?,?,?,?,?)");
        $stmt->bind_param(
            "siiiii",
            $nin,
            $fac_id,
            $dist_id,
            $block_id,
            $facility_type,
            $division
        );

        if($stmt->execute()){
            $res = $stmt->get_result();
            $row = $res ? $res->fetch_assoc() : [];
            $stmt->close();
            $con->next_result();

            if(isset($row['user_created'])){
                echo "<div class='alert alert-success mt-3'>
                        <strong>User Created</strong><br>
                        User ID: {$row['user_created']}<br>
                        Default Password: 12345
                      </div>";
            }
            elseif(isset($row['message'])){
                echo "<div class='alert alert-warning mt-3'>{$row['message']}</div>";
            }

            displayUserTable($fac_id,$con);
        }
        else {
            echo "<div class='alert alert-danger mt-3'>
                    ".$stmt->error."
                  </div>";
        }
    }
}
?>

</div>
</div>

<!-- ======================================================
     BULK USER CREATION
====================================================== -->
<div class="card mt-4">
<div class="card-body">

<form method="POST">
<div class="row mb-3">
<label class="col-md-3 col-form-label">District (Bulk)</label>
<div class="col-md-9">
<select class="form-control" name="bulk_dist" required>
<option value="">- Select District -</option>
<?php
$res=mysqli_query($con,"SELECT dist_id,Dist_name FROM dist_master");
while($r=mysqli_fetch_assoc($res)){
    echo "<option value='{$r['dist_id']}'>{$r['Dist_name']}</option>";
}
?>
</select>
</div>
</div>

<div class="text-center">
<button type="submit" name="bulk_create" class="btn btn-success">
Create Users for All Facilities
</button>
</div>
</form>

<?php
/* ======================================================
   BULK USER CREATE
====================================================== */
if(isset($_POST['bulk_create'])){

    $dist_id = (int)$_POST['bulk_dist'];

    $divRes = mysqli_query($con,"
        SELECT division_id 
        FROM dist_master 
        WHERE dist_id = $dist_id
    ");
    $division = ($divRes && mysqli_num_rows($divRes))
        ? (int)mysqli_fetch_assoc($divRes)['division_id']
        : 0;

    $res = mysqli_query($con,"
        SELECT fac_id, block_id, nin_no, Health_facilty_type
        FROM facilities
        WHERE dist_id = $dist_id
          AND fac_id NOT IN (
              SELECT fac_id_fk FROM s_user where fac_id_fk is not null
          )
    ");

    echo "<table class='table table-bordered mt-3'>
          <thead>
          <tr>
            <th>Facility ID</th>
            <th>User ID</th>
            <th>Status</th>
          </tr>
          </thead><tbody>";

    while($f=mysqli_fetch_assoc($res)){

        $nin = trim($f['nin_no']);

        if($nin === '' || !ctype_digit($nin)){
            echo "<tr>
                    <td>{$f['fac_id']}</td>
                    <td>--</td>
                    <td>Skipped (Invalid NIN)</td>
                  </tr>";
            continue;
        }

        $stmt = $con->prepare("CALL facility_user_create(?,?,?,?,?,?)");
        $stmt->bind_param(
            "siiiii",
            $nin,
            $f['fac_id'],
            $dist_id,
            $f['block_id'],
            $f['Health_facilty_type'],
            $division
        );

        if($stmt->execute()){
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            $con->next_result();

            echo "<tr>
                    <td>{$f['fac_id']}</td>
                    <td>".($row['user_created'] ?? '--')."</td>
                    <td>".($row['message'] ?? 'Created')."</td>
                  </tr>";
        }
    }
    echo "</tbody></table>";
}
?>

</div>
</div>

</div>
</div>

<!-- ================= AJAX ================= -->
<script>
$("#District1").change(function(){
    $.post("assets/get/response_dist_create.php",{cid:this.value},d=>{
        $("#block").html(d);
        $("#facility").html("<option value=''>- Select Facility -</option>");
    });
});

$("#block").change(function(){
    $.post("assets/get/get_facilities_by_block.php",{
        distid:$("#District1").val(),
        block_id:this.value
    },d=>{
        $("#facility").html(d);
    });
});

$("#facility").change(function () {

    $("#createBtn").prop("disabled", true); // block submit immediately

    $.ajax({
        method: "POST",
        url: "assets/get/get_facility_details.php",
        data: { facility_id: this.value },
        dataType: "json",
        success: function (d) {

            $("#fac_id").val(d.fac_id || '');
            $("#dist_id").val(d.dist_id || '');
            $("#block_id").val(d.block_id || '');
            $("#nin_no").val(d.nin_no || '');
            $("#facility_type").val(d.Health_facilty_type || '');

            // ✅ Enable only if valid NIN exists
            if (d.nin_no && d.nin_no !== "0") {
                $("#createBtn").prop("disabled", false);
            } else {
                $("#createBtn").prop("disabled", true);
                alert("Selected facility does not have a valid NIN");
            }
        },
        error: function () {
            $("#createBtn").prop("disabled", true);
            alert("Failed to load facility details. Please retry.");
        }
    });
});
</script>
<script>
/* ================= DISTRICT ================= */
$("#District1").change(function(){

    let distId   = $(this).val();
    let distName = $("#District1 option:selected").text();

    $("#dbg_district").text(distId + " - " + distName);
    $("#dbg_block").text("--");
    $("#dbg_facility").text("--");

    $.post(
        "assets/get/response_dist_create.php",
        { cid: distId },
        function(data){
            $("#block").html(data);
            $("#facility").html("<option value=''>- Select Facility -</option>");
        }
    );
});

/* ================= BLOCK ================= */
$("#block").change(function(){

    let blockId   = $(this).val();
    let blockName = $("#block option:selected").text();

    $("#dbg_block").text(blockId + " - " + blockName);
    $("#dbg_facility").text("--");

    $.post(
        "assets/get/get_facilities_by_block.php",
        {
            distid: $("#District1").val(),
            block_id: blockId
        },
        function(data){
            $("#facility").html(data);
        }
    );
});

/* ================= FACILITY ================= */
$("#facility").change(function(){

    let facId   = $(this).val();
    let facName = $("#facility option:selected").text();

    $("#dbg_facility").text(facId + " - " + facName);

    $.post(
        "assets/get/get_facility_details.php",
        { facility_id: facId },
        function(d){

            // Populate hidden fields
            $("#fac_id").val(d.fac_id);
            $("#dist_id").val(d.dist_id);
            $("#block_id").val(d.block_id);
            $("#nin_no").val(d.nin_no);
            $("#facility_type").val(d.Health_facilty_type);

            // Debug panel
            $("#dbg_fac_id").text(d.fac_id);
            $("#dbg_nin").text(d.nin_no ? d.nin_no : "❌ Missing");
            $("#dbg_type").text(d.Health_facilty_type);

            // Safety check
            if(!d.nin_no || d.nin_no === "0"){
                $("#createBtn").prop("disabled", true);
                alert("⚠ Facility has no valid NIN. User cannot be created.");
            } else {
                $("#createBtn").prop("disabled", false);
            }

        },
        "json"
    );
});
</script>

<?php include("assets/head/f.php"); ?>
