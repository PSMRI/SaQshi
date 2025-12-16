<?php
include(__DIR__ . "/../../assets/conn/db.php");
include(__DIR__ . "/../../assets/conn/session.php");

$userId  = $_SESSION['userid'] ?? 0;
$userRole = $_SESSION['userrole'] ?? 0;

// Admin does not get feedback notifications
if ($userId == 0 || $userRole == 9) {
    echo json_encode(['count' => 0]);
    exit;
}

$stmt = $con->prepare("
    SELECT COUNT(*) 
    FROM review_replies rr
    JOIN review_table rt ON rt.review_id = rr.review_id
    WHERE rt.user_id = ?
      AND rr.reply_by = 'admin'
      AND rr.is_read = 0
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($count);
$stmt->fetch();
$stmt->close();

echo json_encode(['count' => (int)$count]);
