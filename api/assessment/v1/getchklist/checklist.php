<?php
require_once dirname(__DIR__, 3) . "/_bootstrap.php";

/* ---------------------------------------
   READ & VALIDATE INPUTS
--------------------------------------- */
$fty    = (int)($_GET['facility_type'] ?? 0);
$dept   = (int)($_GET['department_id'] ?? 0);
$conc   = (int)($_GET['concern_id'] ?? 0);
$sub    = (int)($_GET['subtype_id'] ?? 0);
$method = trim($_GET['method'] ?? '');

/* ---------------------------------------
   BASIC VALIDATION
--------------------------------------- */
if (!$fty || !$dept || !$conc || !$sub) {
    respond([
        "status"  => "error",
        "message" => "Missing required parameters"
    ], 400);
}

/* ---------------------------------------
   CLEAR PREVIOUS RESULTS (IMPORTANT)
--------------------------------------- */
while ($con->more_results() && $con->next_result()) {}

/* ---------------------------------------
   CALL API PROCEDURE
--------------------------------------- */
$sql = "
    CALL get_assessment_api(
        $fty,
        $dept,
        $conc,
        $sub,
        '".mysqli_real_escape_string($con, $method)."'
    )
";

$res = $con->query($sql);

if (!$res) {
    respond([
        "status"  => "error",
        "message" => $con->error
    ], 500);
}

/* ---------------------------------------
   BUILD RESPONSE
--------------------------------------- */
$data = [];

while ($r = $res->fetch_assoc()) {
    $data[] = [
        "csqa_id"        => (int)$r['csqa_id'],
        "subtype_id"     => (int)$r['c_subtype_id_fk'],
        "standard"       => $r['c_subtype_Reference_No_fk'],
        "reference_no"   => $r['csqa_reference_id'],
        "method"         => $r['Assessment_Method'],
        "measurable"     => $r['M'],
        "checkpoint"     => $r['C'],
        "means"          => $r['Means']
    ];
}

respond([
    "status" => "success",
    "count"  => count($data),
    "data"   => $data
]);
