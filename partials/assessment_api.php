<?php
include("conn.php");
session_start();

$action = $_POST['action'] ?? '';

if($action == "fetchChecklist"){
    $fid = $_SESSION['u_facilityid'];
    $fac_type = $_SESSION['facilty_type'];
    $dept_id = $_SESSION['dept_id1'];
    $period = $_SESSION['assperiod'];
    $concernId = $_POST['concernId'];
    $standardId = $_POST['standardId'];
    $method = $_POST['method'];

    $query = "CALL get_assessment($fid,$fac_type,$concernId,$standardId,$period,$dept_id,'$method','M','C','Means')";
    $result = $con->query($query);

    echo '<table class="table table-bordered"><thead>
        <tr><th>Ref.No</th><th>Measurable Element</th><th>Checkpoint</th><th>Means of Verification</th><th>Compliance</th></tr>
        </thead><tbody>';

    while($row = mysqli_fetch_assoc($result)){
        echo "<tr>
            <td>{$row['csqa_reference_id']}</td>
            <td>{$row['M']}</td>
            <td>{$row['C']}</td>
            <td>{$row['Means']}</td>
            <td>
                <input type='radio' name='compliance_{$row['csqa_id']}' value='0'> 0
                <input type='radio' name='compliance_{$row['csqa_id']}' value='1'> 1
                <input type='radio' name='compliance_{$row['csqa_id']}' value='2'> 2
                <button class='btn btn-sm btn-success saveCompliance' data-id='{$row['csqa_id']}'>Save</button>
            </td>
        </tr>";
    }

    echo "</tbody></table>";
    mysqli_free_result($result);
    $con->next_result();
}

if($action == "saveCompliance"){
    $fid = $_SESSION['u_facilityid'];
    $uid = $_SESSION['userid'];
    $period = $_SESSION['assperiod'];
    $dept_id = $_SESSION['dept_id1'];

    $csqa_id = $_POST['csqa_id'];
    $compliance = $_POST['compliance'];

    $query = "UPDATE chk_list_assessment SET ass_compliance=$compliance 
              WHERE csqa_id_fk=$csqa_id AND fac_id_fk=$fid AND user_id=$uid AND ass_period_id=$period";

    if($con->query($query)){
        echo json_encode(["status" => "success", "message" => "Compliance saved."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to save compliance."]);
    }
}
?>
