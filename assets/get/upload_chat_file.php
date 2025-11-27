<?php
include(__DIR__ . "/../../assets/conn/db.php");
include(__DIR__ . "/../../assets/conn/session.php");

/* ============================================================
    BASIC VALIDATION
============================================================ */
if (!isset($_FILES['chat_file'])) {
    echo "NO_FILE";
    exit;
}

$sender   = intval($_POST['sender'] ?? 0);
$receiver = intval($_POST['receiver'] ?? 0);

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

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
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
$query = "
    INSERT INTO facility_chat_messages
    (sender_facility_id, receiver_facility_id, message_text, is_active, is_read)
    VALUES ($sender, $receiver, '$dbFileUrl', 1, 0)
";

if (!mysqli_query($con, $query)) {
    echo "DB_ERROR";
    exit;
}

/* SUCCESS */
echo "OK";
exit;

?>
