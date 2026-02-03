<?php
require_once dirname(__DIR__, 3) . "/_bootstrap.php";
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        "status"  => "error",
        "message" => "POST required"
    ]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

/*
 Required fields (fac_nin is PRIMARY)
*/
$required = [
    'fac_nin',
    'fac_name',
    'fac_type',
    'cert_type',
    'Cert_status',
    'date_of_ass'
];

foreach ($required as $r) {
    if (!isset($data[$r]) || $data[$r] === '') {
        http_response_code(400);
        echo json_encode([
            "status"  => "error",
            "message" => "Missing field: $r"
        ]);
        exit;
    }
}

/*
 Optional fields
*/
$dist             = $data['dist'] ?? null;
$block            = $data['block'] ?? null;
$cert_detailscol  = $data['cert_detailscol'] ?? null;
$cert_issue       = $data['cert_issue'] ?? null;
$validity         = $data['validity'] ?? null;
$score            = $data['score'] ?? null;
$lat              = $data['lat'] ?? null;
$longi            = $data['longi'] ?? null;
$dist_id          = $data['dist_id'] ?? null;
$block_id         = $data['block_id'] ?? null;
$fac_id           = $data['fac_id'] ?? null;   // OPTIONAL
$ass_mod          = $data['ass_mod'] ?? null;
$state_id          = $data['state_id'] ?? null;

/*
 INSERT certification record
*/
$stmt = $con->prepare("
    INSERT INTO cert_details
    (dist, block, fac_name, fac_type, cert_type, cert_detailscol,
     cert_issue, validity, score, lat, longi,
     dist_id, block_id, fac_id, fac_nin,
     Cert_status, ass_mod, date_of_ass,state_id)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
");

$stmt->bind_param(
    "ssssssssddiiiiisssi",
    $dist,
    $block,
    $data['fac_name'],
    $data['fac_type'],
    $data['cert_type'],
    $cert_detailscol,
    $cert_issue,
    $validity,
    $score,
    $lat,
    $longi,
    $dist_id,
    $block_id,
    $fac_id,
    $data['fac_nin'],
    $data['Cert_status'],
    $ass_mod,
    $data['date_of_ass'],
    $data['state_id']
);

$stmt->execute();

echo json_encode([
    "status"  => "success",
    "message" => "Certification record created",
    "id"      => $stmt->insert_id
]);
