<?php
include(__DIR__ . "/../../assets/conn/db.php");
include(__DIR__ . "/../../assets/conn/session.php");
include(__DIR__ . "/../../assets/helpers/audit_logger.php");
$fac_id = $_SESSION['u_facilityid'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_assessor'])) {
    $ass_name = trim($_POST['ass_name']);

    if (!empty($ass_name) && $fac_id) {
        // Reset current_assment to 0 for all previous records of this facility
        $resetQuery = "UPDATE assessment_desc SET current_assment = 0 WHERE fac_id_fk = ?";
        $stmtReset = mysqli_prepare($con, $resetQuery);
        mysqli_stmt_bind_param($stmtReset, "i", $fac_id);
        mysqli_stmt_execute($stmtReset);
        mysqli_stmt_close($stmtReset);

        // Insert new assessment cycle
        $insertQuery = "INSERT INTO assessment_desc (idst_ass_fk,ass_name, fac_id_fk, current_assment) VALUES (1,?, ?, 1)";
        $stmtInsert = mysqli_prepare($con, $insertQuery);
        mysqli_stmt_bind_param($stmtInsert, "si", $ass_name, $fac_id);

        if (mysqli_stmt_execute($stmtInsert)) {
            $_SESSION['success'] = "New assessment cycle created successfully.";
              $_SESSION['ass_create_success'] = true;
              auditLog(
    $con,
    'CREATE_ASSESSMENT_success',
    'Assessment Setup',
    'Created new assessment cycle: '.$_POST['ass_name'],
    $_POST['fac_id_fk']
);

        } else {
            $_SESSION['error'] = "Error: Unable to create new assessment cycle.";
            auditLog(
    $con,
    'CREATE_ASSESSMENT_failed',
    'Assessment Setup',
    'Creating new assessment cycle faield: '.$_POST['ass_name'],
    $_POST['fac_id_fk']
);

        }

        mysqli_stmt_close($stmtInsert);
    } else {
        $_SESSION['error'] = "All required data not available (ass_name or fac_id).";
    }


    header("Location: /deptask.php"); // Replace with actual page name
    exit();
}
?>
