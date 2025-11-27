<?php
include(__DIR__ . "/../../assets/conn/db.php");
include(__DIR__ . "/../../assets/conn/session.php");
$file_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($file_id <= 0) {
    die("Invalid file ID.");
}

// Get file info from DB
$stmt = $con->prepare("SELECT file_path, file_name FROM files WHERE id = ?");
$stmt->bind_param("i", $file_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    die("File not found in database.");
}

$row = $res->fetch_assoc();


// Construct absolute path from download.php in assets/get/
$filePath = realpath(__DIR__ . "/../../" . $row['file_path']);  // from assets/get/, one level up to assets/ then follow path

if (!$filePath || !file_exists($filePath)) {
    die("❌ File missing from server: " . __DIR__ . "/../../" . $row['file_path']);
}

// Update download count
$update = $con->prepare("UPDATE files SET download_count = download_count + 1 WHERE id = ?");
$update->bind_param("i", $file_id);
$update->execute();

// Serve the file
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
header('Content-Length: ' . filesize($filePath));
readfile($filePath);
exit;
?>