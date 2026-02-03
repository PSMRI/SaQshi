<?php
require_once dirname(__DIR__, 3) . "/_bootstrap.php";

$res = $con->query("CALL state_dash_count");

$row = $res->fetch_assoc();

$data = [
    ["type"=>"DH","total"=>$row['DH'],"completed"=>$row['DHcomp']],
    ["type"=>"SH","total"=>$row['SH'],"completed"=>$row['SHcomp']],
    ["type"=>"CHC","total"=>$row['CHC'],"completed"=>$row['CHCcomp']],
    ["type"=>"PHC","total"=>$row['PHC'],"completed"=>$row['PHCcomp']],
    ["type"=>"UPHC","total"=>$row['UPHC'],"completed"=>$row['UPHCcomp']],
    ["type"=>"AAMSC","total"=>$row['AAMSC'],"completed"=>$row['AAMSCcomp']]
];

$con->next_result();

respond(["status"=>"success","data"=>$data]);
