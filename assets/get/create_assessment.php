<?php
include(__DIR__ . "/../../assets/conn/db.php");
include(__DIR__ . "/../../assets/conn/session.php");

$fac_id = $_SESSION['u_facilityid'] ?? null;
$user_id = $_SESSION['user_id'] ?? 0; // logged-in user

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_assessor'])) {

    $ass_name = trim($_POST['ass_name']);
    $f_date   = $_POST['f_date'] ?? null;   // start date
    $t_date   = $_POST['t_date'] ?? null;   // end date

    if (!empty($ass_name) && $fac_id && $f_date && $t_date) {

        // ==========================
        // VALIDATION — End date limit
        // ==========================
        $today = date("Y-m-d");
        $maxEnd = date("Y-m-d", strtotime("+1 month"));

        if ($f_date != $today) {
            $_SESSION['error'] = "Start date must be today's date.";
            header("Location: /deptask.php");
            exit();
        }

        if ($t_date < $today || $t_date > $maxEnd) {
            $_SESSION['error'] = "End date must be between today and next 1 month.";
            header("Location: /deptask.php");
            exit();
        }

        // ==========================
        // RESET previous assessments
        // ==========================
        $resetQuery = "UPDATE assessment_desc SET current_assment = 0 WHERE fac_id_fk = ?";
        $stmtReset = mysqli_prepare($con, $resetQuery);
        mysqli_stmt_bind_param($stmtReset, "i", $fac_id);
        mysqli_stmt_execute($stmtReset);
        mysqli_stmt_close($stmtReset);

        // ==========================
        // INSERT new assessment cycle
        // ==========================
        $insertQuery = "
            INSERT INTO assessment_desc 
            (idst_ass_fk, ass_name, f_date, t_date, u_id_fk, fac_id_fk, current_assment)
            VALUES (1, ?, ?, ?, ?, ?, 1)
        ";
        $stmtInsert = mysqli_prepare($con, $insertQuery);
        mysqli_stmt_bind_param($stmtInsert, "sssii", $ass_name, $f_date, $t_date, $user_id, $fac_id);

        if (mysqli_stmt_execute($stmtInsert)) {
            $_SESSION['success'] = "New assessment cycle created successfully.";
            $_SESSION['ass_create_success'] = true;
        } else {
            $_SESSION['error'] = "Error: Unable to create new assessment cycle.";
        }

        mysqli_stmt_close($stmtInsert);

    } else {
        $_SESSION['error'] = "All required fields (Assessment name, dates, facility) must be filled.";
    }

    header("Location: /deptask.php");
    exit();
}
?>
