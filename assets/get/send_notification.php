<?php
include(__DIR__ . "/../../assets/conn/db.php");
include(__DIR__ . "/../../assets/conn/session.php");

if (isset($_POST['submit'])) {
    $facility_id = (int)$_POST['facility_id'];
    $message = mysqli_real_escape_string($con, $_POST['message']);

    // If All Facilities → save as NULL
    $facility_id_sql = ($facility_id == 0) ? "NULL" : $facility_id;

    $query = "INSERT INTO facility_notifications (facility_id, message) VALUES ($facility_id_sql, '$message')";
    if (mysqli_query($con, $query)) {
        echo "<div class='alert alert-success'>Notification sent successfully!</div>";
    } else {
        echo "<div class='alert alert-danger'>Error sending notification.</div>";
    }
}
?>
