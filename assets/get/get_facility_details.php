<?php
include(__DIR__ . "/../../assets/conn/db.php");
include(__DIR__ . "/../../assets/conn/session.php");

// Ensure the facility ID is provided via POST
if (isset($_POST['facility_id'])) {
    $facility_id = $_POST['facility_id']; // Get the facility ID from the POST data
    // Query the database for the facility details
    $query = "SELECT fac_id, dist_id, block_id, nin_no, Health_facilty_type FROM facilities WHERE fac_id = ?";
    $stmt = $con->prepare($query);
    $stmt->bind_param('i', $facility_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if data is found
    if ($result && $result->num_rows > 0) {
        $data = $result->fetch_assoc();

        // Return the data as JSON
        echo json_encode($data);
    } else {
        // If no data is found, return an error message as JSON
        echo json_encode(["error" => "No data found"]);
    }

    $stmt->close();
} else {
    // If no facility_id is provided, return an error message as JSON
    echo json_encode(["error" => "No facility ID provided"]);
}
?>
