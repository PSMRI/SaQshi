<?php
include(__DIR__ . "/../../assets/conn/db.php");
include(__DIR__ . "/../../assets/conn/session.php");

/* ----------------------------------------------------
   READ POST INPUTS (SAFE)
---------------------------------------------------- */
$concern  = intval($_POST['Concern'] ?? 0);
$standard = intval($_POST['category'] ?? 0);
$method   = trim($_POST['Assessment_Method'] ?? "");

/* ----------------------------------------------------
   BASIC VALIDATION
---------------------------------------------------- */
if ($concern === 0) {
    echo "<div class='alert alert-danger'>Please select Area of Concern.</div>";
    exit;
}

if ($standard === -1 || $standard === "") {
    echo "<div class='alert alert-danger'>Please select a Standard.</div>";
    exit;
}

/* ----------------------------------------------------
   IF STANDARD = 0 → All Completed Case
---------------------------------------------------- */
if ($standard == 0) {
    echo "<div class='alert alert-success fw-bold text-center'>
            ✔ All checkpoints under this Area of Concern are already completed!
          </div>";
    exit;
}

/* ----------------------------------------------------
   SESSION VARIABLES FOR PROCEDURE
---------------------------------------------------- */
$fid  = intval($_SESSION['u_facilityid'] ?? 0);
$fty  = intval($_SESSION['facilty_type'] ?? 0);
$peri = intval($_SESSION['assperiod'] ?? 0);
$dep  = intval($_SESSION['dept_id1'] ?? 0);

if ($fid == 0 || $fty == 0 || $peri == 0 || $dep == 0) {
    echo "<div class='alert alert-danger'>
            Missing facility or assessment session data. Please reselect department.
          </div>";
    exit;
}

/* ----------------------------------------------------
   LANGUAGE COLUMNS
---------------------------------------------------- */
$m_col  = mysqli_real_escape_string($con, $_SESSION['M']);
$c_col  = mysqli_real_escape_string($con, $_SESSION['C']);
$me_col = mysqli_real_escape_string($con, $_SESSION['Means']);

/* ----------------------------------------------------
   CLEAR PREVIOUS MULTI-RESULTS (IMPORTANT!)
---------------------------------------------------- */
while ($con->more_results() && $con->next_result()) { /* clean buffer */ }

/* ----------------------------------------------------
   BUILD PROCEDURE CALL
---------------------------------------------------- */
$sql = "
    CALL get_assessment(
        $fid,
        $fty,
        $concern,
        $standard,
        $peri,
        $dep,
        '$method',
        '$m_col',
        '$c_col',
        '$me_col'
    )
";

/* ----------------------------------------------------
   EXECUTE PROCEDURE
---------------------------------------------------- */
$res = $con->query($sql);

if (!$res) {
    echo "<div class='alert alert-danger'>
            <b>SQL Error:</b> " . htmlspecialchars($con->error) . "
          </div>";
    exit;
}

/* ----------------------------------------------------
   COLLECT RESULTS
---------------------------------------------------- */
$list = [];
while ($r = mysqli_fetch_assoc($res)) {
    $list[] = $r;
}

mysqli_free_result($res);
$con->next_result();

/* ----------------------------------------------------
   IF RESULTS NOT FOUND
---------------------------------------------------- */
if (empty($list)) {
    echo "<div class='alert alert-warning fw-bold text-center'>
            ⚠ No checklist items found for selected filters.<br>
            Try choosing a different Assessment Method.
          </div>";
    exit;
}

/* ----------------------------------------------------
   STORE IN SESSION FOR assessment_engine
---------------------------------------------------- */
$_SESSION['LIST'] = $list;
$_SESSION['IDX']  = 0;

/* ----------------------------------------------------
   LOAD FIRST QUESTION VIEW
---------------------------------------------------- */
include "compliance_view.php";
?>
