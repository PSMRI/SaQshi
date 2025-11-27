<?php
// DB connection - replace with your DB details
include("assets/conn/db.php");
// Get form data
$name = $con->real_escape_string($_POST['name']);
$email = $con->real_escape_string($_POST['email']);
$phone = $con->real_escape_string($_POST['phone']);
$message = $con->real_escape_string($_POST['message']);

// Insert into DB
$sql = "INSERT INTO demo_requests (name, email, phone, message, submitted_on)
        VALUES ('$name', '$email', '$phone', '$message', NOW())";

if ($con->query($sql) === TRUE) {
    echo "<script>alert('Thank you! Your request has been submitted.'); window.location.href='start.php';</script>";
} else {
    echo "Error: " . $sql . "<br>" . $con->error;
}

$con->close();
?>
