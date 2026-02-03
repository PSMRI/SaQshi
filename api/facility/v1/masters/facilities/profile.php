<?php
require_once dirname(__DIR__, 4) . "/_bootstrap.php";
header("Content-Type: application/json");

$facility_id = $_GET['facility_id'] ?? null;

if (!$facility_id) {
  http_response_code(400);
  echo json_encode(["status"=>"error","message"=>"Facility ID required"]);
  exit;
}

$stmt = $con->prepare("
  SELECT *
  FROM facilities
  WHERE fac_id = ?
");

$stmt->bind_param("i", $facility_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
  http_response_code(404);
  echo json_encode(["status"=>"error","message"=>"Facility not found"]);
  exit;
}

echo json_encode([
  "status"=>"success",
  "data"=>$result->fetch_assoc()
]);
