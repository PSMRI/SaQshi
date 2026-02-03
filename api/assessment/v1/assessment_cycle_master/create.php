<?php
require_once dirname(__DIR__, 3) . "/_bootstrap.php";

$in = json_decode(file_get_contents("php://input"), true);

$ass = trim($in['ass_name'] ?? '');
$fac = (int)($in['fac_id_fk'] ?? 0);

if (!$ass || !$fac) {
    respond(["status"=>"error","message"=>"Invalid payload"], 400);
}

$con->begin_transaction();

try {
    /* Only one current assessment per facility */
    if (!empty($in['current_assment'])) {
        $stmt = $con->prepare("
            UPDATE assessment_desc
            SET current_assment = 0
            WHERE fac_id_fk = ?
        ");
        $stmt->bind_param("i", $fac);
        $stmt->execute();
    }

    $stmt = $con->prepare("
        INSERT INTO assessment_desc
        (ass_name, idst_ass_fk, f_date, t_date, u_id_fk, fac_id_fk, current_assment)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "sissiii",
        $ass,
        $in['idst_ass_fk'],
        $in['f_date'],
        $in['t_date'],
        $in['u_id_fk'],
        $fac,
        $in['current_assment']
    );

    $stmt->execute();
    $con->commit();

    respond(["status"=>"success","id"=>$con->insert_id]);

} catch (Throwable $e) {
    $con->rollback();
    respond(["status"=>"error","message"=>$e->getMessage()], 500);
}
