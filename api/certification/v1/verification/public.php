<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");

require_once dirname(__DIR__, 3) . "/_bootstrap.php";

/* ----------------------------------------------------
   INPUT
---------------------------------------------------- */
$fac_nin = $_GET['fac_nin'] ?? '';

if (empty($fac_nin)) {
    respond([
        "status"  => "error",
        "message" => "Facility NIN is required"
    ], 400);
}

/* ----------------------------------------------------
   FETCH LATEST CERTIFICATION
---------------------------------------------------- */
$sql = "
    SELECT 
        fac_nin,
        fac_name,
        cert_type,
        Cert_status,
        date_of_ass,
        score
    FROM cert_details
    WHERE fac_nin = ?
    ORDER BY date_of_ass DESC
    LIMIT 1
";

$stmt = $con->prepare($sql);
$stmt->bind_param("s", $fac_nin);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    respond([
        "status"  => "not_found",
        "message" => "No certification record found for this facility"
    ], 404);
}

$row = $res->fetch_assoc();

/* ----------------------------------------------------
   CALCULATE EXPIRY & VALIDITY
---------------------------------------------------- */
$assessmentDate = new DateTime($row['date_of_ass']);
$today          = new DateTime();

$validityYears = 0;

if ($row['Cert_status'] === "Certified") {
    $validityYears = 3;
} elseif ($row['Cert_status'] === "Conditional") {
    $validityYears = 1;
}

$expiryDate = clone $assessmentDate;
$expiryDate->modify("+{$validityYears} years");

$isValid = (
    $row['Cert_status'] !== "Expired" &&
    $today <= $expiryDate
);

/* ----------------------------------------------------
   RESPONSE
---------------------------------------------------- */
respond([
    "status" => "success",
    "data" => [
        "facility_nin"     => $row['fac_nin'],
        "facility_name"    => $row['fac_name'],
        "certification" => [
            "type"          => $row['cert_type'],
            "status"        => $row['Cert_status'],
            "score"         => $row['score'],
            "assessment_on" => $assessmentDate->format("Y-m-d"),
            "valid_till"    => $expiryDate->format("Y-m-d"),
            "is_valid"      => $isValid ? true : false
        ]
    ]
]);
