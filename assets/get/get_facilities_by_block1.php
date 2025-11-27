<?php
include(__DIR__ . "/../../assets/conn/db.php");
include(__DIR__ . "/../../assets/conn/session.php");

if (isset($_POST['distid']) && isset($_POST['block_id'])) {
    $dist_id1 = $_POST['distid'];
    $block_id1 = $_POST['block_id'];

    // Query to get facilities based on district and block
    $query = "SELECT fac_id, dist_id, block_id, fac_name, nin_no 
FROM facilities 
WHERE dist_id = $dist_id1 
  AND block_id = $block_id1
  ; ";
    $result = mysqli_query($con, $query);

    if (mysqli_num_rows($result) > 0) {
        // Output the facility options
        echo '<option value="">- Select Facility -</option>';
        while ($row = mysqli_fetch_array($result)) {
            echo '<option value="' . $row['fac_id'] . '">' . $row['fac_name'] . '</option>';
        }
    } else {
        echo '<option value="">No facilities found</option>';
    }
}
?>
