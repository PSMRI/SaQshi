<?php
include_once("db.php");
if (!empty($_POST['cid'])) {
    
   
    $sid12 = $_POST['cid'];
  $query1="select fac_dept_id,dept_name from fac_department where fac_dept_id in (select  distinct fac_dept_id_fk   from chk_list_assessment
where fac_id_fk=$sid12 and ( moic_remarcks='Non achievable' and DQA_remarcks is null))";
    //$query1 = "select fac_dept_id,dept_name from fac_department where fac_dept_id in (select fac_dept_id from fac_dept_map where fac_id=$sid12)";
    $result1 = mysqli_query($con, $query1);
    if ($result1->num_rows > 0) {
           while ($row = mysqli_fetch_assoc($result1)) {
                       echo '<option value="' . $row['fac_dept_id'] . '">' . $row['dept_name'] . '</option>';
        }
    }else{
        echo '<option value="0">Dep.not mapped..!</option>';
    }
}
?>
