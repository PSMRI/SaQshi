<?php
require_once dirname(__DIR__, 3) . "/_bootstrap.php";

$csqa = (int)($_GET['csqa_id'] ?? 0);
if ($csqa <= 0) {
    respond(["status"=>"error","message"=>"Invalid csqa_id"], 400);
}

$sql = "
SELECT
    csqa_id,
    Checkpoint,
    Assessment_Method,
    Means_of_Verification
FROM concern_subtype_chklist
WHERE csqa_id = ?
";

$stmt = $con->prepare($sql);
$stmt->bind_param("i", $csqa);
$stmt->execute();

$row = $stmt->get_result()->fetch_assoc();
respond(["status"=>"success","data"=>$row]);
