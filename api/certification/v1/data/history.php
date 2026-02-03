<?php
require_once dirname(__DIR__, 3) . "/_bootstrap.php";
header("Content-Type: application/json");

/*
 Certification History API
 Returns full certification history for a facility (by fac_nin)
*/

$fac_nin = $_GET['fac_nin'] ?? null;

if (!$fac_nin) {
    http_response_code(400);
    echo json_encode([
        "status"  => "error",
        "message" => "fac_nin is required"
    ]);
    exit;
}

$stmt = $con->prepare("
    SELECT
        id,
        fac_nin,
        fac_name,
        fac_type,
        cert_type,
        Cert_status,
        cert_issue,
        cert_detailscol,
        validity,
        score,
        ass_mod,
        date_of_ass,
        dist,
        block,
        dist_id,
        block_id,
        lat,
        longi
    FROM cert_details
    WHERE fac_nin = ?
    ORDER BY date_of_ass DESC
");

$stmt->bind_param("i", $fac_nin);
$stmt->execute();
$res = $stmt->get_result();

$data = $res->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    "status" => "success",
    "count"  => count($data),
    "data"   => $data
]);
