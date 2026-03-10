<?php
include(__DIR__ . "/../../assets/conn/db.php");
include(__DIR__ . "/../../assets/conn/session.php");
if (!empty($_POST["cid"])) {
     //include_once("fac.php");   
  $dist = $_POST["cid"];
                                                                        
                                    //list($dist_id1, $dist_name1) = explode('-', $dist);
                                   // $dist = $dist_id1;
   $query="select block_id,block_name from block_master where dist_id= $dist order by block_name asc ";
   $result = mysqli_query($con, $query);   
   if ($result->num_rows > 0) {
       echo  '<option value="0">-Select Block-</option>';
       
        while ($row = mysqli_fetch_assoc($result)) {
            //echo '<option value="' . $row['block_id'] . '-' . $row['block_name'] . '">' . $row['block_name'] . '</option>';
            echo "<option value='{$row['block_id']}'>{$row['block_name']}</option>";
        }
        mysqli_free_result($result);
                           $con->next_result();
    }elseif($result->num_rows==0){
      
        echo  '<option value="0">-Select-</option>';
        
    }
} else {
    //print_r($mysqli -> error_list);
}  
   
?>

