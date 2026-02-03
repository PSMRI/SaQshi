<?php
require_once dirname(__DIR__, 4) . "/_bootstrap.php";
header("Content-Type: application/json");

/*
 Supported queries:
 - facility_id
 - nin_no
 - state_id
 - division_id
 - district_id
 - block_id / blockid
*/

$facility_id = $_GET['facility_id'] ?? null;
$nin_no      = $_GET['nin_no'] ?? null;

$state_id    = $_GET['state_id'] ?? null;
$division_id = $_GET['division_id'] ?? null;
$district_id = $_GET['district_id'] ?? null;

/* accept both block_id and blockid */
$block_id    = $_GET['block_id'] ?? ($_GET['blockid'] ?? null);

/* =====================================================
   1️⃣ SINGLE FACILITY BY ID
===================================================== */
if ($facility_id) {

    $stmt = $con->prepare("
        SELECT fac_id, fac_name, nin_no, Health_facilty_type,
               state_id, division_id, dist_id, block_id,
               lat, longit, is_active
        FROM facilities
        WHERE fac_id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $facility_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        http_response_code(404);
        echo json_encode(["status"=>"error","message"=>"Facility not found"]);
        exit;
    }

    echo json_encode([
        "status" => "success",
        "data"   => $res->fetch_assoc()
    ]);
    exit;
}

/* =====================================================
   2️⃣ SINGLE FACILITY BY NIN
===================================================== */
if ($nin_no) {

    $stmt = $con->prepare("
        SELECT fac_id, fac_name, nin_no, Health_facilty_type,
               state_id, division_id, dist_id, block_id,
               lat, longit, is_active
        FROM facilities
        WHERE nin_no = ?
        LIMIT 1
    ");
    $stmt->bind_param("s", $nin_no);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        http_response_code(404);
        echo json_encode(["status"=>"error","message"=>"Facility not found"]);
        exit;
    }

    echo json_encode([
        "status" => "success",
        "data"   => $res->fetch_assoc()
    ]);
    exit;
}

/* =====================================================
   3️⃣ FACILITY LIST (HIERARCHY FILTERS)
===================================================== */

$sql = "
    SELECT fac_id, fac_name, nin_no, Health_facilty_type,
           state_id, division_id, dist_id, block_id, is_active
    FROM facilities
    WHERE 1=1
";

$params = [];
$types  = "";

if ($state_id) {
    $sql .= " AND state_id = ?";
    $params[] = $state_id;
    $types   .= "i";
}

if ($division_id) {
    $sql .= " AND division_id = ?";
    $params[] = $division_id;
    $types   .= "i";
}

if ($district_id) {
    $sql .= " AND dist_id = ?";
    $params[] = $district_id;
    $types   .= "i";
}

if ($block_id) {
    $sql .= " AND block_id = ?";
    $params[] = $block_id;
    $types   .= "i";
}

/* optional: show only active facilities */
// $sql .= " AND is_active = 1";

$sql .= " ORDER BY fac_name";

$stmt = $con->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$res = $stmt->get_result();

$data = [];
while ($row = $res->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode([
    "status" => "success",
    "count"  => count($data),
    "data"   => $data
]);
