<?php
require_once dirname(__DIR__, 3) . "/_bootstrap.php";
header("Content-Type: application/json");

$type = $_GET['type'] ?? null;
$mode = $_GET['mode'] ?? 'latest';

if (!$type) {
    http_response_code(400);
    echo json_encode([
        "status"  => "error",
        "message" => "type parameter required"
    ]);
    exit;
}

/* =========================================================
   Latest record per facility
========================================================= */
$latestFacilityJoin = "
JOIN (
    SELECT fac_nin, MAX(date_of_ass) AS latest_ass
    FROM cert_details
    GROUP BY fac_nin
) latest
  ON cd.fac_nin = latest.fac_nin
 AND cd.date_of_ass = latest.latest_ass
";

/* =========================================================
   STATUS DERIVED BY RULE (NO validity COLUMN)
========================================================= */
$statusCase = "
CASE
  WHEN (
    CASE
      WHEN UPPER(TRIM(cd.Cert_status)) = 'Certified'
           THEN DATE_ADD(cd.date_of_ass, INTERVAL 3 YEAR)
      WHEN UPPER(TRIM(cd.Cert_status)) IN (
           'CERTIFIED WITH CONDITION',
           'CERTIFIED WITH CONDITIONS',
           'CONDITIONAL',
           'Conditional'
      )
           THEN DATE_ADD(cd.date_of_ass, INTERVAL 1 YEAR)
      ELSE cd.date_of_ass
    END
  ) < CURDATE()
  THEN 'EXPIRED'
  WHEN UPPER(TRIM(cd.Cert_status)) = 'Certified'
       THEN 'CERTIFIED'
  WHEN UPPER(TRIM(cd.Cert_status)) IN (
       'CERTIFIED WITH CONDITION',
       'CERTIFIED WITH CONDITIONS',
       'CONDITIONAL',
       'Conditional'
  )
       THEN 'CONDITIONAL'
  ELSE 'EXPIRED'
END
";

/* =========================================================
   DISTRICT LEADERBOARD
========================================================= */
if ($type === 'district_leaderboard') {

    if ($mode === 'history') {

        $sql = "
        SELECT
            cd.dist_id,
            cd.dist AS district_name,
            SUM($statusCase = 'Certified')   AS certified,
            SUM($statusCase = 'Conditional') AS conditional,
            SUM($statusCase = 'EXPIRED')     AS expired
        FROM cert_details cd
        GROUP BY cd.dist_id, cd.dist
        ORDER BY certified DESC
        ";

        $data = $con->query($sql)->fetch_all(MYSQLI_ASSOC);

        echo json_encode([
            "status" => "success",
            "type"   => $type,
            "mode"   => "history",
            "data"   => $data
        ]);
        exit;
    }

    $sql = "
    SELECT
        cd.dist_id,
        cd.dist AS district_name,
        COUNT(DISTINCT cd.fac_nin) AS total_facilities,
        SUM($statusCase = 'Certified')   AS certified,
        SUM($statusCase = 'Conditional') AS conditional,
        SUM($statusCase = 'EXPIRED')     AS expired
    FROM cert_details cd
    $latestFacilityJoin
    GROUP BY cd.dist_id, cd.dist
    ORDER BY certified DESC
    ";

    $data = $con->query($sql)->fetch_all(MYSQLI_ASSOC);

    echo json_encode([
        "status" => "success",
        "type"   => $type,
        "mode"   => "latest",
        "data"   => $data
    ]);
    exit;
}

/* =========================================================
   STATE LEADERBOARD
========================================================= */
if ($type === 'state_leaderboard') {

    $join = ($mode === 'latest') ? $latestFacilityJoin : "";

    $sql = "
    SELECT
        cd.dist AS state_name,
        COUNT(DISTINCT cd.fac_nin) AS total_facilities,
        SUM($statusCase = 'Certified')   AS certified,
        SUM($statusCase = 'Conditional') AS conditional,
        SUM($statusCase = 'EXPIRED')     AS expired
    FROM cert_details cd
    $join
    GROUP BY cd.dist
    ORDER BY certified DESC
    ";

    $data = $con->query($sql)->fetch_all(MYSQLI_ASSOC);

    echo json_encode([
        "status" => "success",
        "type"   => $type,
        "mode"   => $mode,
        "data"   => $data
    ]);
    exit;
}

/* =========================================================
   CERT TYPE SUMMARY
========================================================= */
if ($type === 'cert_type_summary') {

    $join = ($mode === 'latest') ? $latestFacilityJoin : "";

    $sql = "
    SELECT
        cd.cert_type,
        COUNT(DISTINCT cd.fac_nin) AS total_facilities,
        SUM($statusCase = 'Certified')   AS certified,
        SUM($statusCase = 'Conditional') AS conditional,
        SUM($statusCase = 'EXPIRED')     AS expired
    FROM cert_details cd
    $join
    GROUP BY cd.cert_type
    ORDER BY total_facilities DESC
    ";

    $data = $con->query($sql)->fetch_all(MYSQLI_ASSOC);

    echo json_encode([
        "status" => "success",
        "type"   => $type,
        "mode"   => $mode,
        "data"   => $data
    ]);
    exit;
}

/* =========================================================
   INVALID TYPE
========================================================= */
http_response_code(400);
echo json_encode([
    "status"  => "error",
    "message" => "Invalid type value"
]);
