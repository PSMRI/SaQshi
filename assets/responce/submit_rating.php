<?php

include ("../conn/db.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user_name = trim($_POST['user_name']);
    $user_review = trim($_POST['user_review']);
    $rating_data = intval($_POST['rating_data']);

    $stmt = $con->prepare("INSERT INTO review_table (user_name, user_review, user_rating, datetime) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("ssi", $user_name, $user_review, $rating_data);

    if ($stmt->execute()) {
        header("Location: /feedback.php");
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $con->close();
}
?>
