<?php
include(__DIR__ . "/../../assets/conn/db.php");
include(__DIR__ . "/../../assets/conn/session.php");

$reviewId = (int)$_POST['review_id'];
$replyBy  = $_POST['reply_by'];
$text     = trim($_POST['reply_text']);
$userId   = $_SESSION['userid'] ?? 0;

if (!$reviewId || !$userId || !$text) exit;

$stmt = $con->prepare("
    INSERT INTO review_replies
    (review_id, reply_by, reply_user_id, reply_text, replied_on)
    VALUES (?, ?, ?, ?, NOW())
");
$stmt->bind_param("isis", $reviewId, $replyBy, $userId, $text);
$stmt->execute();