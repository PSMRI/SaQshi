<?php
include(__DIR__ . "/../../assets/conn/db.php");
include(__DIR__ . "/../../assets/conn/session.php");

if (isset($_POST['nin_no'])) {
    $nin = mysqli_real_escape_string($con, $_POST['nin_no']);

    // Check if the NIN exists in the facilities table
    $query = "SELECT COUNT(*) AS nin_count FROM facilities WHERE NIN_no = '$nin'";
    $result = mysqli_query($con, $query);
    $row = mysqli_fetch_assoc($result);

    if ($row['nin_count'] > 0) {
        echo 'exists';  // NIN number exists
    } else {
        echo '';  // NIN number does not exist
    }

    mysqli_free_result($result);
}
?>
