<?php
require_once dirname(__DIR__, 3) . "/_bootstrap.php";

$where = [];
$params = [];
$types = "";

if (!empty($_GET['id'])) {
    $where[] = "id = ?";
    $params[] = (int)$_GET['id'];
    $types .= "i";
}

if (!empty($_GET['fac_id'])) {
    $where[] = "fac_id_fk = ?";
    $params[] = (int)$_GET['fac_id'];
    $types .= "i";
}

if (isset($_GET['current'])) {
    $where[] = "current_assment = 1";
}

$sql = "SELECT * FROM assessment_desc";
if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$stmt = $con->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();

$res = $stmt->get_result();
respond([
    "status"=>"success",
    "data"=>$res->fetch_all(MYSQLI_ASSOC)
]);
