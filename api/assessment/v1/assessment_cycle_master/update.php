<?php
require_once dirname(__DIR__, 3) . "/_bootstrap.php";

$id = (int)($_GET['id'] ?? 0);
$in = json_decode(file_get_contents("php://input"), true);

if ($id <= 0) {
    respond(["status"=>"error","message"=>"Invalid id"], 400);
}

$fields = [];
$params = [];
$types  = "";

foreach (['ass_name','f_date','t_date','idst_ass_fk'] as $f) {
    if (isset($in[$f])) {
        $fields[] = "$f = ?";
        $params[] = $in[$f];
        $types .= is_int($in[$f]) ? "i" : "s";
    }
}

if (!$fields) {
    respond(["status"=>"error","message"=>"Nothing to update"], 400);
}

$sql = "UPDATE assessment_desc SET ".implode(",", $fields)." WHERE id=?";
$params[] = $id;
$types .= "i";

$stmt = $con->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();

respond(["status"=>"success"]);
