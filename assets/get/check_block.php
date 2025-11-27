<?php

include(__DIR__ . "/../../assets/conn/db.php");
include(__DIR__ . "/../../assets/conn/session.php");

// Check if block name and district ID are provided
if (isset($_POST['block_name']) && isset($_POST['district_id'])) {
    $block_name = mysqli_real_escape_string($con, $_POST['block_name']);  // Sanitize the block name
    $district_id = mysqli_real_escape_string($con, $_POST['district_id']);  // Sanitize the district ID

    // Query to check if the block name already exists in the selected district
    $check_query = "SELECT COUNT(*) AS block_count FROM block_master WHERE block_name = '$block_name' AND dist_id = $district_id";
    $check_result = mysqli_query($con, $check_query);
    $row = mysqli_fetch_assoc($check_result);

    // If block exists in the district, return 'exists'
    if ($row['block_count'] > 0) {
        echo 'exists';
    } else {
        echo ''; // If block does not exist, return nothing
    }

    mysqli_free_result($check_result);
}
?>
