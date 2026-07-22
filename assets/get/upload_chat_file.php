<?php
include(__DIR__ . "/../../assets/conn/db.php");
include(__DIR__ . "/../../assets/conn/session.php");

header('Content-Type: text/plain; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('METHOD_NOT_ALLOWED');
}

if (
    empty($_POST['csrf_token']) ||
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], (string)$_POST['csrf_token'])
) {
    http_response_code(403);
    exit('INVALID_CSRF');
}

/* ============================================================
    BASIC VALIDATION
============================================================ */
if (!isset($_FILES['chat_file'])) {
    echo "NO_FILE";
    exit;
}

$sender   = intval($_POST['sender'] ?? 0);
$receiver = intval($_POST['receiver'] ?? 0);
$sessionFacility = (int)($_SESSION['u_facilityid'] ?? 0);

// A facility user may upload only as that facility.  State-level users use
// facility id 0 and may upload only as the admin/broadcast identity.
if ($sender !== $sessionFacility) {
    http_response_code(403);
    exit('UNAUTHORIZED_SENDER');
}

if ($sender === 0 && $receiver === 0) {
    echo "INVALID";
    exit;
}

/* ============================================================
    FILE PROPERTIES
============================================================ */
$file      = $_FILES['chat_file'];
$fileName  = $file['name'];
$fileSize  = $file['size'];
$fileTmp   = $file['tmp_name'];
$fileError = $file['error'];

if ($fileError !== UPLOAD_ERR_OK) {
    echo "ERROR_" . $fileError;
    exit;
}

/* ============================================================
    SECURITY: ALLOWED EXTENSIONS
============================================================ */
$allowed_ext = [
    "jpg","jpeg","png","gif",
    "pdf","doc","docx","xls","xlsx",
    "ppt","pptx","txt",
    "zip","rar",
    "mp4","mov","avi","mkv"
];

$ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

if (!in_array($ext, $allowed_ext)) {
    echo "INVALID_FILE_TYPE";
    exit;
}

// Extension checks alone can be spoofed.  Validate the content type for
// image uploads and reject executable/script content for all other files.
$detectedType = (new finfo(FILEINFO_MIME_TYPE))->file($fileTmp);
$allowedImageTypes = [
    'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
    'gif' => 'image/gif'
];
if (isset($allowedImageTypes[$ext]) && $detectedType !== $allowedImageTypes[$ext]) {
    exit('INVALID_FILE_CONTENT');
}
if (in_array($detectedType, ['application/x-php', 'text/x-php', 'application/x-httpd-php'], true)) {
    exit('INVALID_FILE_CONTENT');
}

/* ============================================================
    FILE SIZE LIMIT (200 MB recommended)
============================================================ */
$MAX_SIZE = 200 * 1024 * 1024;  // 200 MB

if ($fileSize > $MAX_SIZE) {
    echo "FILE_TOO_LARGE";
    exit;
}

/* ============================================================
    CREATE UPLOAD DIRECTORY IF NOT EXISTS
============================================================ */
$uploadDir = __DIR__ . "/../../assets/chat_files/";

if (!is_dir($uploadDir) && !mkdir($uploadDir, 0750, true)) {
    http_response_code(500);
    exit('UPLOAD_DIRECTORY_ERROR');
}

/* ============================================================
    GENERATE UNIQUE FILE NAME
============================================================ */
$uniqueName = time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;
$targetPath = $uploadDir . $uniqueName;

// This is the path stored in DB
$dbFileUrl = "/assets/chat_files/" . $uniqueName;

/* ============================================================
    MOVE FILE TO SERVER
============================================================ */
if (!move_uploaded_file($fileTmp, $targetPath)) {
    echo "UPLOAD_FAIL";
    exit;
}

/* ============================================================
    STORE FILE MESSAGE IN DATABASE
============================================================ */
$stmt = $con->prepare(
    'INSERT INTO facility_chat_messages
     (sender_facility_id, receiver_facility_id, message_text, is_active, is_read)
     VALUES (?, ?, ?, 1, 0)'
);
if (!$stmt) {
    @unlink($targetPath);
    http_response_code(500);
    exit('DB_ERROR');
}
$stmt->bind_param('iis', $sender, $receiver, $dbFileUrl);
if (!$stmt->execute()) {
    $stmt->close();
    @unlink($targetPath);
    echo "DB_ERROR";
    exit;
}
$stmt->close();

/* SUCCESS */
echo "OK";
exit;

?>
