<?php
require_once dirname(__DIR__, 4) . "/_bootstrap.php";
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status"=>"error","message"=>"Method not allowed"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$required = [
  'state_id','division_id','district_id',
  'block_id','facility_type','facility_name','nin_no'
];

foreach ($required as $field) {
  if (empty($data[$field])) {
    http_response_code(400);
    echo json_encode(["status"=>"error","message"=>"Missing required field: $field"]);
    exit;
  }
}

/* Check duplicate NIN */
$chk = $con->prepare("SELECT fac_id FROM facilities WHERE nin_no = ?");
$chk->bind_param("s", $data['nin_no']);
$chk->execute();
$chk->store_result();

if ($chk->num_rows > 0) {
  http_response_code(409);
  echo json_encode(["status"=>"error","message"=>"Facility with this NIN already exists"]);
  exit;
}

/* Insert facility */
$stmt = $con->prepare("
  INSERT INTO facilities
  (state_id, division_id, dist_id, block_id,
   Health_facilty_type, fac_name, nin_no,
   latitude, longitude, is_active)
  VALUES (?,?,?,?,?,?,?,?,?,?)
");

$is_active = $data['is_active'] ?? 1;
$lat = $data['latitude'] ?? null;
$lng = $data['longitude'] ?? null;

$stmt->bind_param(
  "iiiiissssi",
  $data['state_id'],
  $data['division_id'],
  $data['district_id'],
  $data['block_id'],
  $data['facility_type'],
  $data['facility_name'],
  $data['nin_no'],
  $lat,
  $lng,
  $is_active
);

$stmt->execute();

http_response_code(201);
echo json_encode([
  "status"=>"success",
  "message"=>"Facility created successfully",
  "data"=>[
    "facility_id"=>$stmt->insert_id,
    "facility_name"=>$data['facility_name'],
    "nin_no"=>$data['nin_no']
  ]
]);
