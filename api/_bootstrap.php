<?php
/*************************************************
 * SaQshi API Bootstrap
 * Version : v1
 * Purpose : Common headers, DB, auth & helpers
 *************************************************/

/* ------------------------------------------------
   RESPONSE HEADERS
------------------------------------------------ */
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Authorization, Content-Type, X-API-KEY");

/* Handle CORS preflight */
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/* ------------------------------------------------
   DATABASE CONNECTION
------------------------------------------------ */
$dbPath = dirname(__DIR__, 1) . "/../../../assets/conn/db.php";
if (!file_exists($dbPath)) {
    http_response_code(500);
    echo json_encode([
        "status"  => "error",
        "message" => "Database configuration not found"
    ]);
    exit;
}
require_once $dbPath;

/* ------------------------------------------------
   SIMPLE API AUTH (PHASE 1 – OPTIONAL)
------------------------------------------------ */
/*
$headers = getallheaders();
$apiKey  = $headers['X-API-KEY'] ?? '';

if ($apiKey !== 'SAQSHI_PUBLIC_API_KEY') {
    respondError("Unauthorized", 401);
}
*/

/* ------------------------------------------------
   COMMON RESPONSE HELPERS
------------------------------------------------ */
function respond(array $data, int $status = 200)
{
    http_response_code($status);
    header("Content-Type: application/json");
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function respondSuccess($data = [], string $message = "OK") {
    respond([
        "status"  => "success",
        "message" => $message,
        "count"   => is_array($data) ? count($data) : 1,
        "data"    => $data
    ]);
}

function respondError(string $message, int $code = 400) {
    respond([
        "status"  => "error",
        "message" => $message
    ], $code);
}

/* ------------------------------------------------
   PARAMETER HELPERS
------------------------------------------------ */
function requireInt(string $key): int {
    $val = (int)($_GET[$key] ?? $_POST[$key] ?? 0);
    if ($val <= 0) {
        respondError("$key is required");
    }
    return $val;
}

function getString(string $key, string $default = ""): string {
    return trim($_GET[$key] ?? $_POST[$key] ?? $default);
}

/* ------------------------------------------------
   DB FETCH HELPERS
------------------------------------------------ */
function fetchAll(mysqli $con, string $sql): array {
    $res = $con->query($sql);
    if (!$res) {
        respondError($con->error, 500);
    }

    $rows = [];
    while ($row = $res->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

function fetchOne(mysqli $con, string $sql): array {
    $res = $con->query($sql);
    if (!$res || $res->num_rows === 0) {
        respondError("Record not found", 404);
    }
    return $res->fetch_assoc();
}

/* -----------------------------------------------
 CERTIFICATION VALIDITY RULE 
 ----------------------------------------------------*/
function calculateValidity(string $certStatus, string $assessmentDate): string
{
    $dt = new DateTime($assessmentDate);

    switch (strtolower(trim($certStatus))) {

        case 'certified':
            $dt->modify('+3 years');
            break;

        case 'conditional':
        case 'certified with condition':
        case 'certified with conditions':
            $dt->modify('+1 year');
            break;

        case 'expired':
            // validity = assessment date itself
            break;

        default:
            throw new Exception("Invalid Cert_status: $certStatus");
    }

    return $dt->format('Y-m-d');
}