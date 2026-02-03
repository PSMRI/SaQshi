<?php
require_once dirname(__DIR__, 3) . "/_bootstrap.php";

header("Content-Type: application/json; charset=UTF-8");

/*
|--------------------------------------------------------------------------
| Latest Certification per Facility (State Dashboard)
| Rule:
| - One latest record per facility (by validity)
| - Expiry calculated dynamically
|--------------------------------------------------------------------------
*/

$sql = "
SELECT c.*
FROM cert_details c
INNER JOIN (
    SELECT fac_nin, MAX(validity) AS latest_validity
    FROM cert_details
    GROUP BY fac_nin
) t
  ON c.fac_nin = t.fac_nin
 AND c.validity = t.latest_validity
WHERE c.lat IS NOT NULL
  AND c.longi IS NOT NULL
";

$res = mysqli_query($con, $sql);

$data = [];
$today = new DateTime('today');

while ($r = mysqli_fetch_assoc($res)) {

    $isExpired = false;
    if (!empty($r['validity'])) {
        try {
            $validity = new DateTime($r['validity']);
            $isExpired = $validity < $today;
        } catch (Exception $e) {
            $isExpired = false;
        }
    }

    $data[] = [
        /* ---------- Identity ---------- */
        "fac_nin"    => $r['fac_nin'],
        "fac_name"   => $r['fac_name'],
        "fac_type"   => $r['fac_type'],
        "district"   => $r['dist'],

        /* ---------- Certification ---------- */
        "cert_type"   => $r['cert_type'],
        "cert_status" => $r['Cert_status'],
        "cert_detail" => $r['cert_detailscol'],
        "cert_issue"  => $r['cert_issue'],
        "validity"    => $r['validity'],
        "score"       => $r['score'] !== null ? (float)$r['score'] : null,
        "is_expired"  => $isExpired,

        /* ---------- Assessment ---------- */
        "ass_mode"    => $r['ass_mod'],
        "ass_date"    => $r['date_of_ass'],

        /* ---------- Geo ---------- */
        "lat" => (float)$r['lat'],
        "lng" => (float)$r['longi']
    ];
}

echo json_encode([
    "status" => "success",
    "count"  => count($data),
    "data"   => $data
], JSON_UNESCAPED_UNICODE);
