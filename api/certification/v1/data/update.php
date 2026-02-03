<?php
require_once dirname(__DIR__, 3) . "/_bootstrap.php";
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode([
        "status"  => "error",
        "message" => "PUT required"
    ]);
    exit;
}

$fac_nin = $_GET['fac_nin'] ?? null;
$data = json_decode(file_get_contents("php://input"), true);

if (!$fac_nin) {
    http_response_code(400);
    echo json_encode([
        "status"  => "error",
        "message" => "fac_nin required"
    ]);
    exit;
}

/* =================================================
   1️⃣ Fetch latest certification for fac_nin
================================================= */
$chk = $con->prepare("
    SELECT Cert_status, date_of_ass
    FROM cert_details
    WHERE fac_nin = ?
    ORDER BY date_of_ass DESC
    LIMIT 1
");
$chk->bind_param("i", $fac_nin);
$chk->execute();
$res = $chk->get_result();

if ($res->num_rows === 0) {
    http_response_code(404);
    echo json_encode([
        "status"  => "error",
        "message" => "No certification record found for this NIN"
    ]);
    exit;
}

$current = $res->fetch_assoc();

/* =================================================
   2️⃣ Block update if already Expired
================================================= */
if (strtolower($current['Cert_status']) === 'expired') {
    http_response_code(409);
    echo json_encode([
        "status"  => "error",
        "message" => "Certification already expired. Create a new certification record."
    ]);
    exit;
}

/* =================================================
   3️⃣ Validate required fields
================================================= */
$required = ['cert_type','Cert_status','date_of_ass'];
foreach ($required as $r) {
    if (empty($data[$r])) {
        http_response_code(400);
        echo json_encode([
            "status"  => "error",
            "message" => "Missing field: $r"
        ]);
        exit;
    }
}

/* =================================================
   4️⃣ Auto-calculate validity
================================================= */
$assDate = new DateTime($data['date_of_ass']);
$status  = strtolower($data['Cert_status']);

switch ($status) {
    case 'certified':
        $assDate->modify('+3 years');
        break;

    case 'conditional':
        $assDate->modify('+1 year');
        break;

    case 'provisional':
        $assDate->modify('+6 months');
        break;

    case 'expired':
        // validity = assessment date
        break;

    default:
        http_response_code(400);
        echo json_encode([
            "status"  => "error",
            "message" => "Invalid Cert_status value"
        ]);
        exit;
}

$validity = $assDate->format('Y-m-d');

/* =================================================
   5️⃣ Update latest certification record
================================================= */
$stmt = $con->prepare("
    UPDATE cert_details
    SET
        cert_type        = ?,
        Cert_status      = ?,
        validity         = ?,
        score            = ?,
        cert_issue       = ?,
        cert_detailscol  = ?,
        ass_mod          = ?,
        date_of_ass      = ?
    WHERE fac_nin = ?
    ORDER BY date_of_ass DESC
    LIMIT 1
");

$stmt->bind_param(
    "sssdssssi",
    $data['cert_type'],
    $data['Cert_status'],
    $validity,
    $data['score'],
    $data['cert_issue'],
    $data['cert_detailscol'],
    $data['ass_mod'],
    $data['date_of_ass'],
    $fac_nin
);

$stmt->execute();

echo json_encode([
    "status"   => "success",
    "message"  => "Latest certification updated",
    "validity" => $validity
]);
