<?php
//session_start();
include(__DIR__ . "/../../assets/conn/db.php");

/*
|--------------------------------------------------------------------------
| Security Check (optional but recommended)
|--------------------------------------------------------------------------
*/
//if (!isset($_SESSION['user_id'])) {
  //  die("Unauthorized access");
//}

/*
|--------------------------------------------------------------------------
| Set Headers for CSV Download
|--------------------------------------------------------------------------
*/
$filename = "user_activity_log_" . date("Y-m-d_H-i-s") . ".csv";

header("Content-Type: text/csv; charset=utf-8");
header("Content-Disposition: attachment; filename=$filename");

/*
|--------------------------------------------------------------------------
| Open Output Stream
|--------------------------------------------------------------------------
*/
$output = fopen("php://output", "w");

/*
|--------------------------------------------------------------------------
| CSV Header Row
|--------------------------------------------------------------------------
*/
fputcsv($output, [
    'ID',
    'User ID',
    'Username',
    'Action',
    'Module',
    'Description',
    'Reference ID',
    'IP Address',
    'Created At'
]);

/*
|--------------------------------------------------------------------------
| Fetch Data
|--------------------------------------------------------------------------
*/
$sql = "
    SELECT 
        id,
        user_id,
        username,
        action,
        module,
        description,
        reference_id,
        ip_address,
        created_at
    FROM user_activity_log
    ORDER BY created_at DESC
";

$result = mysqli_query($con, $sql);

if (!$result) {
    fputcsv($output, ["Error fetching data"]);
    fclose($output);
    exit;
}

/*
|--------------------------------------------------------------------------
| Write Data Rows
|--------------------------------------------------------------------------
*/
while ($row = mysqli_fetch_assoc($result)) {
    fputcsv($output, [
        $row['id'],
        $row['user_id'],
        $row['username'],
        $row['action'],
        $row['module'],
        $row['description'],
        $row['reference_id'],
        $row['ip_address'],
        $row['created_at']
    ]);
}

/*
|--------------------------------------------------------------------------
| Close Output
|--------------------------------------------------------------------------
*/
fclose($output);
exit;
