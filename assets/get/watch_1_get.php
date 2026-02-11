<?php
ini_set('display_errors', 0);     // hide warnings from output
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

require_once __DIR__ . "/../../assets/conn/db.php";

$data = [];

/* ---- DB CONNECTION CHECK ---- */
if (!isset($con) || !$con) {
    echo json_encode([
        "data" => [],
        "error" => "Database connection failed"
    ]);
    exit;
}

/* ---- QUERY ---- */
$sql = "
    SELECT id, user_id, username, action, module,
           description, reference_id, ip_address,
           DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') AS created_at
    FROM user_activity_log
    ORDER BY created_at DESC
    LIMIT 500
";

$res = mysqli_query($con, $sql);

/* ---- QUERY FAILURE CHECK ---- */
if (!$res) {
    echo json_encode([
        "data" => [],
        "error" => mysqli_error($con)
    ]);
    exit;
}

$sr = 1;
while ($row = mysqli_fetch_assoc($res)) {

    $data[] = [
        $sr++,
        htmlspecialchars($row['username']) .
            "<br><small class='text-muted'>User ID: " . (int)$row['user_id'] . "</small>",
        htmlspecialchars($row['action']),
        htmlspecialchars($row['module']),
        htmlspecialchars($row['description']),
        htmlspecialchars($row['reference_id']),
        htmlspecialchars($row['ip_address']),
        $row['created_at']
    ];
}

echo json_encode([
    "data" => $data
], JSON_UNESCAPED_UNICODE);
exit;
