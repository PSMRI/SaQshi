<?php
require_once dirname(__DIR__, 3) . "/_bootstrap.php";
header("Content-Type: application/json");

/*
 Dashboard Summary API
 - Uses ONLY latest certification per (fac_nin + cert_type)
 - Normalizes inconsistent Cert_status values
*/

$dist_id   = $_GET['dist_id'] ?? null;
$block_id  = $_GET['block_id'] ?? null;
$cert_type = $_GET['cert_type'] ?? null;

/*
 Core query with strict normalization
*/
$sql = "
SELECT
    CASE
        WHEN UPPER(TRIM(cd.Cert_status)) = 'CERTIFIED'
            THEN 'Certified'

        WHEN UPPER(TRIM(cd.Cert_status)) IN (
            'CERTIFIED WITH CONDITION',
            'CERTIFIED WITH CONDITIONS',
            'CERTIFIED WITH CONDITIONAL',
            'CONDITIONAL'
        )
            THEN 'Conditional'

        WHEN UPPER(TRIM(cd.Cert_status)) = 'PROVISIONAL'
            THEN 'Provisional'

        WHEN UPPER(TRIM(cd.Cert_status)) = 'EXPIRED'
            THEN 'Expired'

        ELSE 'Other'
    END AS norm_status,
    COUNT(*) AS total
FROM cert_details cd
JOIN (
    SELECT fac_nin, cert_type, MAX(date_of_ass) AS latest_ass
    FROM cert_details
    GROUP BY fac_nin, cert_type
) latest
    ON cd.fac_nin   = latest.fac_nin
   AND cd.cert_type = latest.cert_type
   AND cd.date_of_ass = latest.latest_ass
WHERE 1=1
";

$params = [];
$types  = "";

/* Optional filters */
if ($dist_id) {
    $sql .= " AND cd.dist_id = ?";
    $params[] = $dist_id;
    $types   .= "i";
}

if ($block_id) {
    $sql .= " AND cd.block_id = ?";
    $params[] = $block_id;
    $types   .= "i";
}

if ($cert_type) {
    $sql .= " AND cd.cert_type = ?";
    $params[] = $cert_type;
    $types   .= "s";
}

$sql .= " GROUP BY norm_status";

/* Execute */
$stmt = $con->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$res = $stmt->get_result();

/* Canonical dashboard output */
$summary = [
    "Certified"   => 0,
    "Conditional" => 0,
    "Provisional" => 0,
    "Expired"     => 0
];

while ($row = $res->fetch_assoc()) {
    if (isset($summary[$row['norm_status']])) {
        $summary[$row['norm_status']] = (int)$row['total'];
    }
}

echo json_encode([
    "status" => "success",
    "data"   => $summary
]);
