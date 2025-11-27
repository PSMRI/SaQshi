<?php include("assets/head/h.php"); ?>



<div class="pcoded-main-container">
    <div class="pcoded-content">
        <div class="pagetitle mb-2">
            <h5 class="fw-bold text-primary">
               Password Update/change
            </h5>
        </div>

    <div class="row">
      <div class="col-xl-8">
        <div class="card">
          <div class="card-body pt-3">
            <!-- Bordered Tabs -->
            
            
              <!-- Change Password Form -->
              <form method="POST" action="#">

                <div class="row mb-3">
                  <label for="currentPassword" class="col-md-4 col-lg-3 col-form-label">Current Password</label>
                  <div class="col-md-8 col-lg-9">
                    <input name="password" type="password" class="form-control" id="currentPassword">
                  <?php
                  $uid=$_SESSION['userid'];
                  $fid=$_SESSION['u_facilityid']??0;
                  $query = "select u_password from s_user where  u_id=$uid";
                  $result = mysqli_query($con, $query);
                  $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
                  $oldpss1 = $row['u_password'];
                  $_SESSION['oldpss12']=  $oldpss1;
                  ?>
                  </div>
                </div>

                <div class="row mb-3">
                  <label for="newPassword" class="col-md-4 col-lg-3 col-form-label">New Password</label>
                  <div class="col-md-8 col-lg-9">
                    <input name="newpassword" type="password" class="form-control" id="newPassword">
                  </div>
                </div>

                <div class="row mb-3">
                  <label for="renewPassword" class="col-md-4 col-lg-3 col-form-label">Re-enter New Password</label>
                  <div class="col-md-8 col-lg-9">
                    <input name="renewpassword" type="password" class="form-control" id="renewPassword">
                  </div>
                </div>

                <div class="text-center">
                  <button type="submit" name="postsubmit" class="btn btn-primary">Change Password</button>
                </div>
              </form><!-- End Change Password Form -->
<?php
if(isset($_POST['postsubmit'])) {
$oldpass3=$_SESSION['oldpss12'];
  $oldpass=$_POST["password"];
  $newpass=$_POST["newpassword"];
  $repass=$_POST["renewpassword"];
  if($oldpass3<>$oldpass){
    ?>
  <div class="alert alert-danger bg-danger text-light border-0 alert-dismissible fade show" role="alert">
 Current password donot match..!!!
  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
    <?php
       
  }elseif($newpass<>$repass){
    ?>
  <div class="alert alert-danger bg-danger text-light border-0 alert-dismissible fade show" role="alert">
 New password and Re-type password does not match..!!!
  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
    <?php
  }else{
    $u_sql="update s_user set u_password=$newpass where u_id=$uid";
    $result = mysqli_query($con, $u_sql);
    ?>
    <div class="alert alert-success bg-success text-light border-0 alert-dismissible fade show" role="alert">
                Password Updated.
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
    <?php
  }
}

?>
            </div>

          </div><!-- End Bordered Tabs -->

        </div>
      </div>

    </div>
    
   </div>

    </div>

<!-- ======= Footer ======= -->
<?php include("assets/head/f.php"); ?>