<?php
header('Content-Type: application/json');
include(__DIR__ . "/../../assets/conn/db.php");
include(__DIR__ . "/../../assets/conn/session.php");



$query = "SELECT fac_name, fac_type, cert_type, cert_detailscol, lat, longi, score, cert_issue, validity ,dist
          FROM cert_details 
          WHERE lat IS NOT NULL AND longi IS NOT NULL";

$result = mysqli_query($con, $query);

$facilities = [];

while ($row = mysqli_fetch_assoc($result)) {
    $facilities[] = [
        'fac_name'      => $row['fac_name'],
        'fac_type'      => $row['fac_type'],
        'cert_type'     => $row['cert_type'],
        'cert_detailscol' => $row['cert_detailscol'],
        'lat'           => (float) $row['lat'],
        'longi'         => (float) $row['longi'],
        'score'         => isset($row['score']) ? (float) $row['score'] : null,
        'cert_issue'    => $row['cert_issue'],  // keep as string (date)
        'validity'      => $row['validity'],     // keep as string (date)
        'dist'      => $row['dist'] 
    ];
}

echo json_encode($facilities);
?>
