<?php
if (isset($_POST['postsubmit'])) {
  $_SESSION['Cn'] = $_POST['Concern'] ?? 0;
  $_SESSION['cy'] = $_POST['category'] ?? 0;
  $_SESSION['Assessment_Method'] = $_POST['Assessment_Method'] ?? '';
  $_SESSION['xx'] = $_SESSION['Cn'];
  $_SESSION['xxx'] = $_SESSION['Assessment_Method'];

  $M = $_SESSION['M'];
  $C = $_SESSION['C'];
  $Means = $_SESSION['Means'];
  $p = $_SESSION['assperiod'];
  $F = $_SESSION['dept_id1'];
  $Fa = $_SESSION['facilty_type'];
  $Co = $_SESSION['Cn'];
  $ca = $_SESSION['cy'];
  $fid = $_SESSION['u_facilityid'];
  $ASSm = $_SESSION['Assessment_Method'];

  if ($Co == 0 || $ca == "") {
    echo '<div class="alert alert-danger">Kindly Select Area of Concern/Standard.</div>';
  } else {
    $_SESSION['q1'] = "CALL get_assessment($fid,$Fa,$Co,$ca,$p,$F,'$ASSm','$M','$C','$Means')";
    $query = $con->query($_SESSION['q1']);

    while ($row = mysqli_fetch_array($query)) {
      include("compliance_form_row.php");
    }
    mysqli_free_result($query);
    $con->next_result();

    // Count display
     $_SESSION['getcount'] = "CALL get_assessment_count($fid,$Fa,$Co,$ca,$F,$p)";
    $getcount = "CALL get_assessment_count($fid,$Fa,$Co,$ca,$F,$p)";
    $queryb = $con->query($getcount);
    if ($queryb && $countRow = mysqli_fetch_assoc($queryb)) {
      echo '<div class="mt-3">';
      echo '<span class="badge badge-success">Area of Concern: ' . $countRow['c'] . ' ' . $countRow['id1'] . '/' . $countRow['id2'] . '</span> ';
      echo '<span class="badge badge-success">Standard: ' . $countRow['c2'] . ' ' . $countRow['id3'] . '/' . $countRow['id4'] . '</span>';
      echo '</div>';
    }
    mysqli_free_result($queryb);
    $con->next_result();
  }

} elseif (isset($_POST['postsubmit1'])) {
  if ($_POST['randcheck'] != ($_SESSION['rand'] ?? 0)) {
    echo '<div class="alert alert-danger">Duplicate submission blocked!</div>';
    return;
  }

  // Declare all session-based variables
  $facid = $_SESSION['u_facilityid'];
  $uid = $_SESSION['userid'];
  $assperiod = $_SESSION['assperiod'];
  $fd = $_SESSION['dept_id1'];
  $Fa = $_SESSION['facilty_type'];
  $Co = $_SESSION['Cn'];
  $ca = $_SESSION['cy'];
  $p = $_SESSION['assperiod'];
  $F = $_SESSION['dept_id1'];
  $fid = $_SESSION['u_facilityid'];

  $ass_compliance = $_POST['f'];
  $csqa_id = $_POST['csqa_id'];

  $sql = "SELECT area_of_con_id_fk, c_subtype_id_fk FROM concern_subtype_chklist WHERE csqa_id = $csqa_id";
  $row = mysqli_fetch_assoc(mysqli_query($con, $sql));

  $insert = "CALL in_assessment($facid,$fd,{$row['c_subtype_id_fk']},{$row['area_of_con_id_fk']},$csqa_id,$ass_compliance,$assperiod,$uid)";
  $res = $con->query($insert);

  if ($res) {
   
echo '<div class="alert alert-success alert-dismissible fade show auto-dismiss" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> Compliance Added!
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>'; 
    // Load next record
    $query = $con->query($_SESSION['q1']);
    if ($query && mysqli_num_rows($query) > 0) {
      while ($row = mysqli_fetch_assoc($query)) {
        $con->next_result();

        $check = "SELECT 1 FROM chk_list_assessment 
                  WHERE fac_id_fk = $facid AND user_id = $uid AND ass_period_id = $assperiod 
                  AND csqa_id_fk = {$row['csqa_id']}";

        $checkRes = mysqli_query($con, $check);

        if (mysqli_num_rows($checkRes) === 0) {
          include("compliance_form_row.php");
          break;
        }
      }
      mysqli_free_result($query);
      $con->next_result();
    } else {
      echo '<div class="alert alert-warning alert-dismissible fade show auto-dismiss" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>All checklist items completed.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>';       
    }

    //  Make sure the connection is ready for next stored proc call
    $con->next_result();

    //  Display updated count
    //$getcount = "CALL get_assessment_count($fid,$Fa,$Co,$ca,$F,$p)";
    $queryb = $con->query($_SESSION['getcount']);
    if ($queryb && $countRow = mysqli_fetch_assoc($queryb)) {
      echo '<div class="mt-3">';
      echo '<span class="badge badge-success">Area of Concern: ' . $countRow['c'] . ' ' . $countRow['id1'] . '/' . $countRow['id2'] . '</span> ';
      echo '<span class="badge badge-success">Standard: ' . $countRow['c2'] . ' ' . $countRow['id3'] . '/' . $countRow['id4'] . '</span>';
      echo '</div>';
    }
    mysqli_free_result($queryb);
    $con->next_result();

  } else {
    echo '<div class="alert alert-danger alert-dismissible fade show auto-dismiss" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>Insert Failed!
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>';   
      }
}elseif (isset($_POST['edit'])) {
  $csqid = $_POST['edit'];
  $editQ = "SELECT * FROM concern_subtype_chklist WHERE csqa_id=$csqid";
  $editQ1 = $con->query($editQ);
  while ($row = mysqli_fetch_array($editQ1)) {
    include("partials/edit_compliance_form.php");
  }
  mysqli_free_result($editQ1);
  $con->next_result();
   $queryb = $con->query($_SESSION['getcount']);
    if ($queryb && $countRow = mysqli_fetch_assoc($queryb)) {
      echo '<div class="mt-3">';
      echo '<span class="badge badge-success">Area of Concern: ' . $countRow['c'] . ' ' . $countRow['id1'] . '/' . $countRow['id2'] . '</span> ';
      echo '<span class="badge badge-success">Standard: ' . $countRow['c2'] . ' ' . $countRow['id3'] . '/' . $countRow['id4'] . '</span>';
      echo '</div>';
    }
    mysqli_free_result($queryb);
    $con->next_result();
} elseif (isset($_POST['update'])) {
  $facid = $_SESSION['u_facilityid'];
  $uid = $_SESSION['userid'];
  $assperiod = $_SESSION['assperiod'];
  $csqa_id = $_POST['csqa_id_u'];
  $compl = $_POST['f'];
  $fd = $_SESSION['dept_id1'];

  $update = "UPDATE chk_list_assessment SET ass_compliance=$compl WHERE csqa_id_fk=$csqa_id AND fac_id_fk=$facid AND fac_dept_id_fk=$fd AND ass_period_id=$assperiod AND user_id=$uid";
  $res = $con->query($update);
  if ($res && $con->affected_rows > 0) {
echo '<div class="alert alert-success alert-dismissible fade show auto-dismiss" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> Compliance Updated!
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>';
} else {
  echo '<div class="alert alert-danger alert-dismissible fade show auto-dismiss" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> Update Failed! Record not found or already same value.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>';  
}


  // After update, load next unanswered record
  $query = $con->query($_SESSION['q1']);
  if ($query && mysqli_num_rows($query) > 0) {
    while ($row = mysqli_fetch_assoc($query)) {
      $con->next_result();

      $check = "SELECT 1 FROM chk_list_assessment 
                WHERE fac_id_fk = $facid AND user_id = $uid AND ass_period_id = $assperiod 
                AND csqa_id_fk = {$row['csqa_id']}";

      $checkRes = mysqli_query($con, $check);

      if (mysqli_num_rows($checkRes) === 0) {
        include("partials/compliance_form_row.php");
        break;
      }
    }
    mysqli_free_result($query);
    $con->next_result();

    $con->next_result();
     $queryb = $con->query($_SESSION['getcount']);
    if ($queryb && $countRow = mysqli_fetch_assoc($queryb)) {
      echo '<div class="mt-3">';
      echo '<span class="badge badge-success">Area of Concern: ' . $countRow['c'] . ' ' . $countRow['id1'] . '/' . $countRow['id2'] . '</span> ';
      echo '<span class="badge badge-success">Standard: ' . $countRow['c2'] . ' ' . $countRow['id3'] . '/' . $countRow['id4'] . '</span>';
      echo '</div>';
    }
    mysqli_free_result($queryb);
    $con->next_result();
  }
}
?>
<script>
  // Auto-dismiss alert after 3 seconds
  setTimeout(function () {
    let alertEl = document.querySelector('.auto-dismiss');
    if (alertEl) {
      alertEl.classList.remove('show'); // triggers fade
      alertEl.classList.add('fade');
      setTimeout(() => alertEl.remove(), 300); // removes from DOM after fade
    }
  }, 3000); // 3 seconds
</script>