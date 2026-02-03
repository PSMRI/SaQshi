<?php
require_once dirname(__DIR__, 4) . "/_bootstrap.php";
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
  http_response_code(405);
  echo json_encode(["status"=>"error","message"=>"Method not allowed"]);
  exit;
}

$facility_id = $_GET['facility_id'] ?? null;
$data = json_decode(file_get_contents("php://input"), true);

if (!$facility_id) {
  http_response_code(400);
  echo json_encode(["status"=>"error","message"=>"Facility ID required"]);
  exit;
}

/* Check existence */
$chk = $con->prepare("SELECT fac_id FROM facilities WHERE fac_id = ?");
$chk->bind_param("i", $facility_id);
$chk->execute();
$chk->store_result();

if ($chk->num_rows === 0) {
  http_response_code(404);
  echo json_encode(["status"=>"error","message"=>"Facility not found"]);
  exit;
}

/* Update */
$stmt = $con->prepare("
  UPDATE facilities SET
    fac_name = ?,
    Health_facilty_type = ?,
    lat = ?,
    longit = ?,
    is_active = ?
  WHERE fac_id = ?
");

$stmt->bind_param(
  "sissii",
  $data['facility_name'],
  $data['facility_type'],
  $data['latitude'],
  $data['longitude'],
  $data['is_active'],
  $facility_id
);

$stmt->execute();

echo json_encode([
  "status"=>"success",
  "message"=>"Facility updated successfully"
]);
