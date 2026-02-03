<?php
require_once dirname(__DIR__, 3) . "/_bootstrap.php";
header("Content-Type: application/json");

/*
 Rules:
 1) fac_nin ONLY  -> single latest certification
 2) Any filter(s) -> list
*/

$fac_nin     = $_GET['fac_nin'] ?? null;
$dist_id     = $_GET['dist_id'] ?? null;
$block_id    = $_GET['block_id'] ?? null;
$cert_type   = $_GET['cert_type'] ?? null;
$cert_status = $_GET['Cert_status'] ?? null;

/* =================================================
   1️⃣ SINGLE LATEST RECORD (fac_nin ONLY)
================================================= */
if ($fac_nin && !$dist_id && !$block_id && !$cert_type && !$cert_status) {

    $stmt = $con->prepare("
        SELECT *
        FROM cert_details
        WHERE fac_nin = ?
        ORDER BY date_of_ass DESC
        LIMIT 1
    ");
    $stmt->bind_param("i", $fac_nin);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        http_response_code(404);
        echo json_encode([
            "status"  => "error",
            "message" => "No certification found for this NIN"
        ]);
        exit;
    }

    echo json_encode([
        "status" => "success",
        "data"   => $res->fetch_assoc()
    ]);
    exit;
}

/* =================================================
   2️⃣ FILTERED LIST
================================================= */
$sql = "SELECT * FROM cert_details WHERE 1=1";
$params = [];
$types  = "";

if ($fac_nin) {
    $sql .= " AND fac_nin = ?";
    $params[] = $fac_nin;
    $types   .= "i";
}

if ($dist_id) {
    $sql .= " AND dist_id = ?";
    $params[] = $dist_id;
    $types   .= "i";
}

if ($block_id) {
    $sql .= " AND block_id = ?";
    $params[] = $block_id;
    $types   .= "i";
}

if ($cert_type) {
    $sql .= " AND cert_type = ?";
    $params[] = $cert_type;
    $types   .= "s";
}

if ($cert_status) {
    $sql .= " AND Cert_status = ?";
    $params[] = $cert_status;
    $types   .= "s";
}

$sql .= " ORDER BY date_of_ass DESC";

$stmt = $con->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$res = $stmt->get_result();

$data = $res->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    "status" => "success",
    "count"  => count($data),
    "data"   => $data
]);
