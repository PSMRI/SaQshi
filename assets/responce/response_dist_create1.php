<?php
include_once("db.php");

if (!empty($_POST["cid"])) {
    $dist = $_POST["cid"];            

    // Sanitize input (always a good practice)
   

    // Query to get block details
    $query = "SELECT block_id, block_name FROM block_master WHERE dist_id = $dist and block_id not in(select block_id FROM s_user WHERE dist_id = $dist and role_id_fk=8)";
    $result = mysqli_query($con, $query);   

    if ($result && mysqli_num_rows($result) > 0) {
        // If there are blocks, loop through them
        while ($row = mysqli_fetch_assoc($result)) {
            // Properly concatenate the values inside the echo statement
            echo '<option value="' . $row['block_id'] . '">' . $row['block_name'] . '</option>';
        }
        // Free result set after usage
        mysqli_free_result($result);
    } else {
        // If no rows were found
        echo '<option value="0">- Select -</option>';
    }
} else {
    // Optional: Handle case where "cid" is not provided
    echo '<option value="0">- Select1 -</option>';
}
?>
