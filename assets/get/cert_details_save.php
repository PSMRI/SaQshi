<?php
include(__DIR__ . "/../../assets/conn/db.php");
include(__DIR__ . "/../../assets/conn/session.php");

// Get data from form
$fac_name   = $_POST['fac_name'] ?? '';
$cert_type  = $_POST['cert_type'] ?? '';
$cert_issue = $_POST['cert_issue'] ?? '';
$validity   = '';
$score      = $_POST['score'] ?? null;
$lat   = isset($_POST['lat']) ? floatval($_POST['lat']) : null;
$longi = isset($_POST['longi']) ? floatval($_POST['longi']) : null;
$dist       = $_POST['dist'] ?? null;
$dist_name  = '';

// Get district name from DB
if ($dist !== null) {
    $stmt = $con->prepare("SELECT Dist_name FROM dist_master WHERE Dist_id = ?");
    $stmt->bind_param("i", $dist);
    $stmt->execute();
    $stmt->bind_result($dist_name);
    $stmt->fetch();
    $stmt->close();
}
$block      = $_POST['block'] ?? null;
$fac_type   = $_POST['fac_type'] ?? '';

if ($cert_issue !== '') {
    // Calculate validity as 1 year after issue date
    $validity = date('Y-m-d', strtotime($cert_issue . ' +1 year'));
}

// INSERT new record
$stmt = $con->prepare("INSERT INTO cert_details 
    (dist_id,dist,block, fac_name, fac_type, cert_type, cert_issue, validity, score, lat, longi) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$stmt->bind_param(
    "isisssssdds",
    $dist,
    $dist_name,
    $block,
    $fac_name,
    $fac_type,
    $cert_type,
    $cert_issue,
    $validity,
    $score,
    $lat,
    $longi
);

$stmt->execute();
$stmt->close();

$con->close();
header("Location: /cert.php?status=success");
exit;

