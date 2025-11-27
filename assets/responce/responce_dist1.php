<?php
include_once("db.php");
if (!empty($_POST['cid'])) {
    
   
    $sid12 = $_POST['cid'];
  
    $query1 = "SELECT ass_name,id FROM assessment_desc where fac_id_fk=$sid12";
    $result1 = mysqli_query($con, $query1);
    if ($result1->num_rows > 0) {
           while ($row = mysqli_fetch_assoc($result1)) {
                       echo '<option value="' . $row['id'] . '">' . $row['ass_name'] . '</option>';
        }
    }else{
        echo '<option value="0">Assessment not mapped</option>';
    }
}
?>
