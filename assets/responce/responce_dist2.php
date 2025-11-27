<?php
include_once("db.php");
include('session.php');
if (!empty($_POST['cid'])) {    
    
    $sid1 = $_POST['cid'];
    
    $query1 = "SELECT distinct Block_Name , block_id  FROM
     facilities where dist_id=$sid1";
    $result1 = mysqli_query($con, $query1);
    if ($result1->num_rows > 0) {
       
        while ($row = mysqli_fetch_assoc($result1)) {
            echo '<option value="' . $row['block_id'] . '">' . $row['Block_Name'] . '</option>';
        }
    }else{
        echo '<option value="0">Block not mapped</option>';
    }
}
?>
