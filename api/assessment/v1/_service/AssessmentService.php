<?php

/**
 * AssessmentService.php
 * ---------------------
 * Core business logic for SaQshi Assessment Service
 * (DPG-ready, stateless, DB-driven)
 */

class AssessmentService
{
    private mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    /* =====================================================
       1️⃣ SAVE / UPDATE ASSESSMENT RESPONSE
    ===================================================== */
    public function saveResponse(array $data): array
    {
        $this->db->begin_transaction();

        try {
            /* Check existing response */
            $sqlCheck = "
            SELECT ass_id
            FROM chk_list_assessment
            WHERE fac_id_fk = ?
              AND fac_dept_id_fk = ?
              AND ass_period_id = ?
              AND csqa_id_fk = ?
            LIMIT 1
        ";

            $stmt = $this->db->prepare($sqlCheck);
            $stmt->bind_param(
                "iiii",
                $data['fac_id'],
                $data['fac_dept_id'],
                $data['ass_period'],
                $data['csqa_id']
            );
            $stmt->execute();
            $res = $stmt->get_result();

            /* UPDATE */
            if ($res && $res->num_rows > 0) {

                $sqlUpd = "
                UPDATE chk_list_assessment
                SET ass_compliance = ?, user_id = ?
                WHERE fac_id_fk = ?
                  AND fac_dept_id_fk = ?
                  AND ass_period_id = ?
                  AND csqa_id_fk = ?
            ";

                $stmt = $this->db->prepare($sqlUpd);
                $stmt->bind_param(
                    "iiiiii",
                    $data['compliance'],
                    $data['user_id'],
                    $data['fac_id'],
                    $data['fac_dept_id'],
                    $data['ass_period'],
                    $data['csqa_id']
                );
                $stmt->execute();

                $this->db->commit();

                return $this->success("Response updated", [
                    "action" => "updated"
                ]);
            }

            /* INSERT via stored procedure */
            $sqlMap = "
            SELECT c_subtype_id_fk, area_of_con_id_fk
            FROM concern_subtype_chklist
            WHERE csqa_id = ?
            LIMIT 1
        ";

            $stmt = $this->db->prepare($sqlMap);
            $stmt->bind_param("i", $data['csqa_id']);
            $stmt->execute();
            $map = $stmt->get_result()->fetch_assoc();

            if (!$map) {
                throw new Exception("Invalid csqa_id");
            }

            $sqlProc = "CALL in_assessment(?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->db->prepare($sqlProc);
            $stmt->bind_param(
                "iiiiiiii",
                $data['fac_id'],
                $data['fac_dept_id'],
                $map['c_subtype_id_fk'],
                $map['area_of_con_id_fk'],
                $data['csqa_id'],
                $data['compliance'],
                $data['ass_period'],
                $data['user_id']
            );
            $stmt->execute();

            while ($this->db->more_results() && $this->db->next_result()) {
            }

            $this->db->commit();

            return $this->success("Response inserted", [
                "action" => "inserted"
            ]);
        } catch (Throwable $e) {

            $this->db->rollback();

            return $this->error("Save failed: " . $e->getMessage());
        }
    }


    /* =====================================================
       2️⃣ RESUME ASSESSMENT (NEXT PENDING CHECKPOINT)
    ===================================================== */
    public function resumeAssessment(int $facId, int $deptId, int $assPeriod): array
{
    $sql = "
        SELECT
            c.csqa_id,
            c.c_subtype_id_fk AS subtype_id
        FROM concern_subtype_chklist c
        LEFT JOIN chk_list_assessment a
          ON a.csqa_id_fk = c.csqa_id
         AND a.fac_id_fk = ?
         AND a.fac_dept_id_fk = ?
         AND a.ass_period_id = ?
        WHERE a.ass_id IS NULL
        ORDER BY c.c_subtype_id_fk, c.csqa_id
        LIMIT 1
    ";

    $stmt = $this->db->prepare($sql);
    $stmt->bind_param("iii", $facId, $deptId, $assPeriod);
    $stmt->execute();

    $res = $stmt->get_result();
    $row = $res->fetch_assoc();

    /* No pending checklist → completed */
    if (!$row) {
        return $this->success("Assessment completed", [
            "completed" => true
        ]);
    }

    return $this->success("Resume assessment", [
        "completed"  => false,
        "subtype_id" => (int)$row['subtype_id'],
        "csqa_id"    => (int)$row['csqa_id']
    ]);
}


    /* =====================================================
   3️⃣ GET ASSESSMENT PROGRESS
===================================================== */
public function getProgress(int $facId, int $assPeriod): array
{
    $sql = "
        SELECT
            completed,
            total_checkpoints,
            progress_percent
        FROM vw_assessment_progress
        WHERE fac_id_fk = ?
          AND ass_period_id = ?
        LIMIT 1
    ";

    $stmt = $this->db->prepare($sql);
    if (!$stmt) {
        return $this->error("Prepare failed");
    }

    $stmt->bind_param("ii", $facId, $assPeriod);
    $stmt->execute();

    $res = $stmt->get_result();
    $row = $res->fetch_assoc();

    /* No progress yet */
    if (!$row) {
        return $this->success("No progress yet", [
            "completed"         => 0,
            "total_checkpoints" => 0,
            "progress_percent"  => 0
        ]);
    }

    /* Normalized output */
    return $this->success("Progress fetched", [
        "completed"         => (int)$row['completed'],
        "total_checkpoints" => (int)$row['total_checkpoints'],
        "progress_percent"  => (float)$row['progress_percent']
    ]);
}

    /*==================================*/

    /* =====================================================
   4️⃣ SUBTYPE-WISE PROGRESS (AREA OF CONCERN)
===================================================== */
public function getSubtypeProgress(
    int $facId,
    int $deptId,
    int $assPeriod,
    int $facilityType,
    int $concernId
): array {

    $sql = "
        SELECT 
            s.c_subtype_id,
            COUNT(DISTINCT c.csqa_id)    AS total_cp,
            COUNT(DISTINCT a.csqa_id_fk) AS filled_cp
        FROM area_of_concern_subtype s
        JOIN concern_subtype_chklist c
          ON c.c_subtype_id_fk = s.c_subtype_id
        LEFT JOIN chk_list_assessment a
          ON a.csqa_id_fk = c.csqa_id
         AND a.fac_id_fk = ?
         AND a.fac_dept_id_fk = ?
         AND a.ass_period_id = ?
        WHERE s.area_of_con_id = ?
          AND s.fac_type_id = ?
        GROUP BY s.c_subtype_id
        ORDER BY s.c_subtype_id
    ";

    $stmt = $this->db->prepare($sql);
    $stmt->bind_param(
        "iiiii",
        $facId,
        $deptId,
        $assPeriod,
        $concernId,
        $facilityType
    );
    $stmt->execute();

    $res = $stmt->get_result();

    $data = [];
    while ($r = $res->fetch_assoc()) {

        $total = (int)$r['total_cp'];
        $done  = (int)$r['filled_cp'];

        $data[] = [
            "subtype_id" => (int)$r['c_subtype_id'],
            "total"      => $total,
            "completed"  => $done,
            "percent"    => $total > 0
                ? round(($done / $total) * 100, 1)
                : 0
        ];
    }

    return $this->success("Subtype progress fetched", $data);
}


    /* =====================================================
       UTILITIES
    ===================================================== */
    private function success(string $message, array $data = []): array
    {
        return [
            "status" => "success",
            "message" => $message,
            "data" => $data
        ];
    }

    private function error(string $message): array
    {
        return [
            "status" => "error",
            "message" => $message
        ];
    }
    /* =====================================================
   5️⃣ MARK ASSESSMENT COMPLETE
===================================================== */
public function completeAssessment(
    int $facId,
    int $deptId,
    int $assPeriod,
    int $userId
): array {

    try {
        $sql = "
            INSERT INTO assessment_completion
                (fac_id_fk, fac_dept_id_fk, ass_period_id, completed_by)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                completed_on = CURRENT_TIMESTAMP,
                completed_by = VALUES(completed_by)
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iiii", $facId, $deptId, $assPeriod, $userId);
        $stmt->execute();

        return $this->success("Assessment marked complete", [
            "completed" => true
        ]);

    } catch (Throwable $e) {
        return $this->error("Completion failed: " . $e->getMessage());
    }
}

}
