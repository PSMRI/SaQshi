<?php
include(__DIR__ . "/../../assets/conn/db.php");
include(__DIR__ . "/../../assets/conn/session.php");

$action = $_POST['action'] ?? '';

$list = $_SESSION['LIST'] ?? [];
$idx  = intval($_SESSION['IDX'] ?? 0);

// Safety check
if (empty($list)) {
    echo "<div class='alert alert-danger'>Checklist not loaded.</div>";
    exit;
}

// Total questions
$total = count($list);
$message = "";

/* =========================================================================
   SAVE (INSERT or UPDATE)
=========================================================================== */
if ($action == "save") {

    $csqa_id = intval($_POST['csqa_id'] ?? 0);
    $comp    = intval($_POST['f'] ?? -1);

    if ($csqa_id <= 0 || $comp < 0) {
        $_SESSION['MSG'] = "Invalid submission!";
        include "compliance_view.php";
        exit;
    }

    $fid       = intval($_SESSION['u_facilityid']);
    $fd        = intval($_SESSION['dept_id1']);
    $assperiod = intval($_SESSION['assperiod']);
    $uid       = intval($_SESSION['userid']);

    /* --------------------------------------------------------------
       1. CHECK IF DATA EXISTS
    -------------------------------------------------------------- */
    $chk = $con->query("
        SELECT ass_id 
        FROM chk_list_assessment
        WHERE csqa_id_fk = $csqa_id
          AND fac_id_fk = $fid
          AND fac_dept_id_fk = $fd
          AND ass_period_id = $assperiod
          AND user_id = $uid
        LIMIT 1
    ");

    $exists = ($chk && $chk->num_rows > 0);

    /* --------------------------------------------------------------
       2A. UPDATE EXISTING RECORD
    -------------------------------------------------------------- */
   if ($exists) {

    // Common WHERE filter
    $where = "
        csqa_id_fk = $csqa_id
        AND fac_id_fk = $fid
        AND fac_dept_id_fk = $fd
        AND ass_period_id = $assperiod
        AND user_id = $uid
    ";

    // 1️⃣ Update main table
    $upd1 = "
        UPDATE chk_list_assessment
        SET ass_compliance = $comp
        WHERE $where
    ";

    // 2️⃣ Update second table
    $upd2 = "
        UPDATE chk_list_assessment_1
        SET ass_compliance = $comp
        WHERE $where
    ";

    $ok1 = $con->query($upd1);
    $ok2 = $con->query($upd2);

    if ($ok1 && $ok2) {
        $message = "✔ Updated Successfully..!";
    } else {

        $error1 = $con->error;
        $error2 = $con->error;

        $message = "❌ Update Failed!<br>
                    Main Table Error: $error1<br>
                    Second Table Error: $error2";
    }
}

    /* --------------------------------------------------------------
       2B. INSERT NEW RECORD
    -------------------------------------------------------------- */
    else {
        $q = $con->query("
            SELECT c_subtype_id_fk, area_of_con_id_fk 
            FROM concern_subtype_chklist 
            WHERE csqa_id = $csqa_id
        ");

        if (!$q || $q->num_rows == 0) {
            $_SESSION['MSG'] = "Invalid CSQA Mapping!";
            include "compliance_view.php";
            exit;
        }

        $info = $q->fetch_assoc();

        $proc = "
            CALL in_assessment(
                $fid,
                $fd,
                {$info['c_subtype_id_fk']},
                {$info['area_of_con_id_fk']},
                $csqa_id,
                $comp,
                $assperiod,
                $uid
            )
        ";

        if ($con->query($proc)) {
            $message = "✔ Saved Successfully!";
        } else {
            $message = "❌ Save Failed!";
        }

        $con->next_result();
    }

    /* --------------------------------------------------------------
       CHECK IF LAST QUESTION → SHOW COMPLETION MESSAGE
    -------------------------------------------------------------- */
    if ($idx >= $total - 1) {

        $_SESSION['MSG'] = "🎉 Assessment Completed! All $total checkpoints completed.";

        // Stay on last question
        $_SESSION['IDX'] = $idx;

        include "compliance_view.php";
        exit;
    }

    // If not last → move to next
    $idx++;
    $_SESSION['MSG'] = $message;
}


/* =========================================================================
   SKIP BUTTON
=========================================================================== */
elseif ($action == "skip") {

    if ($idx >= $total - 1) {

        $_SESSION['MSG'] = "🎉All $total checkpoints skipped.";

        // Do not move further
        $_SESSION['IDX'] = $idx;

        include "compliance_view.php";
        exit;
    }

    $idx++;
    $_SESSION['MSG'] = "⏭ Skipped → Next Question";
}


/* =========================================================================
   BACK BUTTON
=========================================================================== */
elseif ($action == "back") {

    if ($idx > 0) {
        $idx--;
        $_SESSION['MSG'] = "⬅ Moved Back";
    }
}


/* =========================================================================
   NEXT BUTTON (If you want to keep it)
=========================================================================== */
elseif ($action == "next") {

    if ($idx < $total - 1) {
        $idx++;
        $_SESSION['MSG'] = "➡ Next Question";
    }
}


/* =========================================================================
   SAVE UPDATED INDEX
=========================================================================== */
$_SESSION['IDX'] = $idx;

/* =========================================================================
   LOAD VIEW
=========================================================================== */
include "compliance_view.php";
?>
