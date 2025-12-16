<?php
include(__DIR__ . "/../../assets/conn/db.php");
include(__DIR__ . "/../../assets/conn/session.php");

date_default_timezone_set('Asia/Kolkata');

$date = $_GET['date'] ?? date('Y-m-d');

/* Prepare 24 hours in IST */
$hours = [];
for ($i = 0; $i < 24; $i++) {
    $label = date("g A", strtotime("$i:00")); // 12-hour AM/PM
    $hours[$label] = 0;
}

/* Fetch login data */
$q = mysqli_query($con,"
    SELECT HOUR(login_time) h, COUNT(*) c
    FROM login_log
    WHERE DATE(login_time) = '$date'
    GROUP BY HOUR(login_time)
");

while ($r = mysqli_fetch_assoc($q)) {
    $label = date("g A", strtotime($r['h'].":00"));
    $hours[$label] = (int)$r['c'];
}

/* JSON output */
echo json_encode([
    'labels' => array_keys($hours),
    'values' => array_values($hours)
]);