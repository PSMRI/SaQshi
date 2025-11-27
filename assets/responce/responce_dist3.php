<?php
include_once("db.php");
include('session.php');
if (!empty($_POST['cid'])) {
    
    $sid = $_POST['bid'];
    $sid1 = $_POST['cid'];
    //$sid2 = $_POST['did'];
  // $dist_id1 = $_SESSION['dist'];
    $query1 = "SELECT fac_name , fac_id  FROM
     facilities where block_id=$sid1  and Health_facilty_type=$sid";
    $result1 = mysqli_query($con, $query1);
    if ($result1->num_rows > 0) {
        echo '<option value="">Select Institute</option>';
        while ($row = mysqli_fetch_assoc($result1)) {
            echo '<option value="' . $row['fac_id'] . '">' . $row['fac_name'] . '</option>';
        }
    }else{
        echo '<option value="0">Institute not mapped</option>';
    }
}
?>
