<?php
include("assets/conn/db.php"); // Database connection

header("Content-Type: application/json");

// Query to fetch all facility details with certification info
/*
$query = "SELECT 
    f.lat, 
    f.longit, 
    f.fac_id, 
    f.fac_name, 
    f.dist_id,
    d.Dist_Name, 
    GROUP_CONCAT(DISTINCT c.cert_type ORDER BY c.cert_type SEPARATOR ', ') AS cert_type, 
    GROUP_CONCAT(DISTINCT c.cert_name ORDER BY c.cert_name SEPARATOR ', ') AS cert_name, 
    GROUP_CONCAT(DISTINCT c.cert_status ORDER BY c.cert_status SEPARATOR ', ') AS cert_status
FROM facilities f
JOIN certification_details c ON f.fac_id = c.fac_id
JOIN dist_master d ON f.dist_id = d.Dist_id
GROUP BY f.fac_id, f.dist_id, d.Dist_Name;";
*/
$query = "SELECT 
      -- Assuming `longi` is the correct column; change if necessary
   f.lat, 
    f.longi,
    f.fac_name, 
    f.dist as Dist_Name,
    d.id as dist_id,
   f.cert_type,
   f.cert_detailscol as cert_name
FROM cert_details f,dist_master d where f.dist_id=d.Dist_id";
$result = $con->query($query);

if ($result) {
    $facilities = [];
    while ($row = $result->fetch_assoc()) {
        $facilities[] = $row;
    }
    echo json_encode($facilities);
} else {
    echo json_encode(["error" => "Query execution failed: " . $con->error]);
}

$con->close();
