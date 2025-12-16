<?php

include(__DIR__ . "/../../assets/conn/db.php");
include(__DIR__ . "/../../assets/conn/session.php");

/* ================= SECURITY ================= */
if (!isset($_SESSION['userid']) || $_SESSION['userid'] == 0) {
    die("Unauthorized access");
}

/* ================= INPUT ================= */
$user_id     = $_SESSION['userid'];
$user_name   = trim($_POST['user_name'] ?? '');
$user_review = trim($_POST['user_review'] ?? '');
$rating_data = intval($_POST['rating_data'] ?? 0);

/* ================= VALIDATION ================= */
if ($user_name === '' || $user_review === '' || $rating_data < 1 || $rating_data > 5) {
    die("Invalid input");
}

/* ================= INSERT ================= */
$stmt = $con->prepare("
    INSERT INTO review_table
    (user_id, user_name, user_review, user_rating, datetime)
    VALUES (?, ?, ?, ?, NOW())
");

$stmt->bind_param(
    "issi",
    $user_id,
    $user_name,
    $user_review,
    $rating_data
);

if ($stmt->execute()) {
    header("Location: /feedback.php");
    exit;
}

/* ================= ERROR ================= */
echo "Error: " . $stmt->error;

$stmt->close();
$con->close();

?>
