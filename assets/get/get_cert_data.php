<?php
header('Content-Type: application/json');
include(__DIR__ . "/../../assets/conn/db.php");
include(__DIR__ . "/../../assets/conn/session.php");

$dist = $_SESSION['block_id'];
$query = "SELECT c.*
FROM cert_details c
INNER JOIN (
    SELECT fac_name, MAX(validity) AS latest_validity
    FROM cert_details
    GROUP BY fac_name
) t
ON c.fac_name = t.fac_name 
AND c.validity = t.latest_validity
WHERE c.lat IS NOT NULL 
  AND c.longi IS NOT NULL and c.block_id=$dist;";

$result = mysqli_query($con, $query);

$facilities = [];

while ($row = mysqli_fetch_assoc($result)) {
    $facilities[] = [
        'fac_name'        => $row['fac_name'],
        'fac_type'        => $row['fac_type'],
        'cert_type'       => $row['cert_type'],
        'cert_detailscol' => $row['cert_detailscol'],

        'lat'             => (float)$row['lat'],
        'longi'           => (float)$row['longi'],
        'score'           => isset($row['score']) ? (float)$row['score'] : null,

        'cert_issue'      => $row['cert_issue'],
        'validity'        => $row['validity'],

        'dist'            => $row['dist'],
        'Cert_status'     => $row['Cert_status'],
        'ass_mod'         => $row['ass_mod'],
        'date_of_ass'     => $row['date_of_ass']
    ];
}

echo json_encode($facilities);
?>
