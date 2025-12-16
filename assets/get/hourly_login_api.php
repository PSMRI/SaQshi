<?php
include(__DIR__ . "/../../assets/conn/db.php");
include(__DIR__ . "/../../assets/conn/session.php");

$date = $_GET['date'] ?? date('Y-m-d');

$stmt = $con->prepare("
    SELECT HOUR(login_time) h, COUNT(*) total
    FROM login_log
    WHERE DATE(login_time)=?
    GROUP BY h ORDER BY h
");
$stmt->bind_param("s",$date);
$stmt->execute();
$res = $stmt->get_result();

$data=array_fill(0,24,0);
while($r=$res->fetch_assoc()){
    $data[(int)$r['h']] = (int)$r['total'];
}

echo json_encode([
    'labels'=>array_map(fn($h)=>sprintf('%02d:00',$h),array_keys($data)),
    'values'=>array_values($data)
]);
