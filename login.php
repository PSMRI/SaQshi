<?php
ob_start();

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

set_exception_handler(function ($e) {

        header("Location: server_error.php");
   exit();
   

});

set_error_handler(function ($severity, $message, $file, $line) {
    error_log("LOGIN ERROR: " . $message . " in " . $file . " on line " . $line);
    return true;
});
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

if (
    empty($_SERVER['HTTPS']) ||
    $_SERVER['HTTPS'] === 'off'
) {
    header(
        "Location: https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
        true,
        301
    );
    exit;
}

header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin");
header("Permissions-Policy: geolocation=()");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self'; object-src 'none'; base-uri 'self'; frame-ancestors 'none';");

ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict'
]);

session_start();

// Reuse the nonce-based CSP and response hardening for the public login page.
// This layer does not perform authorization; it is safe before authentication.
require_once __DIR__ . '/assets/security/security.php';

$error = '';
$con = null;

require_once("assets/conn/db.php");

if (file_exists("assets/helpers/audit_logger.php")) {
    require_once("assets/helpers/audit_logger.php");
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}



function cleanInput($data)
{
    return trim(htmlspecialchars($data, ENT_QUOTES, 'UTF-8'));
}

function getClientIP()
{
    return $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
}

function auditSafe($con, $action, $module, $message, $username = null)
{
    if (function_exists('auditLog')) {
        auditLog($con, $action, $module, $message, null, $username);
    }
}

function safePrepare($con, $sql)
{
    $stmt = $con->prepare($sql);

    if (!$stmt) {
        error_log("LOGIN PREPARE FAILED: " . $con->error);
        header("Location: login.php?error=server");
        exit;
    }

    return $stmt;
}


$ip_address = getClientIP();

if (isset($_GET['error']) && $_GET['error'] === 'server') {
    $error = "Unable to process login request at the moment. Please try again after some time.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $con instanceof mysqli) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$con) {
        $error = "Unable to process login request at the moment. Please try again after some time.";
    }
    usleep(random_int(300000, 800000));

    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        $error = "Invalid request. Please refresh the page and try again.";
    } elseif (!isset($_POST['robot_check'])) {

        $error = "Please verify that you are not a robot.";
    } else {

        // $_SESSION['robot_token'] = bin2hex(random_bytes(32));

        $myusername = cleanInput($_POST['myusername'] ?? '');
        $mypassword = trim($_POST['mypassword'] ?? '');
        $_SESSION['lang'] = (int)($_POST['lang'] ?? 5);

        if (empty($myusername) || empty($mypassword)) {
            $error = "Invalid username or password.";
        } else {

            $lockStmt = safePrepare($con, "
                SELECT COUNT(*) total
                FROM login_attempts
                WHERE (username = ? OR ip_address = ?)
                AND status = 'FAILED'
                AND attempt_time >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)
            ");

            $lockStmt->bind_param("ss", $myusername, $ip_address);
            $lockStmt->execute();
            $lock = $lockStmt->get_result()->fetch_assoc();
            $lockStmt->close();

            if ((int)$lock['total'] >= 5) {
                header("Location: account_locked.php?wait=15");
                exit;
            }

            $stmt = safePrepare($con, "
                SELECT
                    u_id,
                    u_name,
                    u_password,
                    role_id_fk,
                    dist_id,
                    fac_id_fk,
                    dept_id,
                    assessment_id,
                    is_active,
                    block_id
                FROM s_user
                WHERE u_name = ?
                AND is_active = 1
                LIMIT 1
            ");

            $stmt->bind_param("s", $myusername);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows === 1) {

                $row = $result->fetch_assoc();

                $userid = (int)$row['u_id'];
                $userrole = (int)$row['role_id_fk'];
                $district_id = (int)$row['dist_id'];
                $stored_hash = trim($row['u_password']);

                $isValid = false;

                if (password_verify($mypassword, $stored_hash)) {
                    $isValid = true;
                } elseif (hash_equals($stored_hash, $mypassword)) {
                    $isValid = true;

                    $newHash = password_hash($mypassword, PASSWORD_DEFAULT);

                    $update = safePrepare($con, "
                        UPDATE s_user
                        SET u_password = ?
                        WHERE u_id = ?
                    ");

                    $update->bind_param("si", $newHash, $userid);
                    $update->execute();
                    $update->close();
                }

                if ($isValid) {

                    $clearAttempts = safePrepare($con, "
                        DELETE FROM login_attempts
                        WHERE username = ?
                        AND ip_address = ?
                    ");

                    $clearAttempts->bind_param("ss", $myusername, $ip_address);
                    $clearAttempts->execute();
                    $clearAttempts->close();

                    session_regenerate_id(true);

                    $_SESSION['userid'] = $userid;
                    $_SESSION['u_name'] = $myusername;
                    $_SESSION['userrole'] = $userrole;
                    $_SESSION['urole'] = $userrole;
                    $_SESSION['dist'] = $district_id;
                    $_SESSION['login_time'] = date('d M Y h:i A');
                    $_SESSION['ip'] = getClientIP();
                    $_SESSION['user_agent'] = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                    $logSuccess = safePrepare($con, "
                        INSERT INTO login_attempts
                        (username, ip_address, attempt_time, status)
                        VALUES (?, ?, NOW(), 'SUCCESS')
                    ");

                    $logSuccess->bind_param("ss", $myusername, $ip_address);
                    $logSuccess->execute();
                    $logSuccess->close();

                    auditSafe($con, 'LOGIN_SUCCESS', 'Authentication', 'User logged in successfully', $myusername);

                    switch ($userrole) {

                        case 1:
                        case 2:
                        case 3:
                        case 6:

                            $stmt2 = safePrepare($con, "
                                SELECT a.fac_id_fk, b.NIN_no, a.assessment_id, a.dist_id,
                                       b.Health_facilty_type, b.fac_name, c.fac
                                FROM s_user AS a
                                JOIN facilities AS b ON a.fac_id_fk = b.fac_id
                                JOIN facilities_type AS c ON b.Health_facilty_type = c.fac_type_id
                                WHERE a.u_id = ?
                                AND a.is_active = 1
                            ");

                            $stmt2->bind_param("i", $userid);
                            $stmt2->execute();
                            $data = $stmt2->get_result()->fetch_assoc();

                            $_SESSION['u_facilityid'] = $data['fac_id_fk'];
                            $_SESSION['f_type_id'] = $data['Health_facilty_type'];
                            $_SESSION['facilty_type'] = $data['Health_facilty_type'];
                            $_SESSION['assperiod'] = $data['assessment_id'];
                            $_SESSION['facname'] = $data['fac_name'];
                            $_SESSION['factypename'] = $data['fac'];
                            $_SESSION['factynin'] = $data['NIN_no'];

                            header("Location: index.php");
                            exit;

                        case 4:

                            $stmt2 = safePrepare($con, "
                                SELECT a.Dist_id, b.Dist_name
                                FROM facilities AS a
                                JOIN dist_master AS b ON a.dist_id = b.Dist_id
                                WHERE a.Dist_id = (
                                    SELECT dist_id FROM s_user WHERE u_id = ?
                                )
                            ");

                            $stmt2->bind_param("i", $userid);
                            $stmt2->execute();
                            $data = $stmt2->get_result()->fetch_assoc();

                            $_SESSION['div_id'] = $data['Dist_id'];
                            $_SESSION['div_name'] = $data['Dist_name'];

                            header("Location: distdash.php");
                            exit;

                        case 5:

                            $stmt2 = safePrepare($con, "
                                SELECT a.division_id, b.division_name
                                FROM facilities AS a
                                JOIN division AS b ON a.division_id = b.iddivision
                                WHERE a.division_id = (
                                    SELECT division_id FROM s_user WHERE u_id = ?
                                )
                            ");

                            $stmt2->bind_param("i", $userid);
                            $stmt2->execute();
                            $data = $stmt2->get_result()->fetch_assoc();

                            $_SESSION['div_id'] = $data['division_id'];
                            $_SESSION['div_name'] = $data['division_name'];

                            header("Location: regdash.php");
                            exit;

                        case 8:

                            $_SESSION['block_id'] = $row['block_id'];

                            $stmt2 = safePrepare($con, "
                                SELECT block_name
                                FROM block_master
                                WHERE block_id = ?
                            ");

                            $stmt2->bind_param("i", $_SESSION['block_id']);
                            $stmt2->execute();
                            $data = $stmt2->get_result()->fetch_assoc();

                            $_SESSION['block_name'] = $data['block_name'];

                            header("Location: bdash.php");
                            exit;

                        case 9:

                            $_SESSION['u_facilityid'] = 0;

                            header("Location: sdashcount.php");
                            exit;

                        default:

                            session_destroy();
                            $error = "Unauthorized role.";
                    }
                } else {

                    $failLog = safePrepare($con, "
                        INSERT INTO login_attempts
                        (username, ip_address, attempt_time, status)
                        VALUES (?, ?, NOW(), 'FAILED')
                    ");

                    $failLog->bind_param("ss", $myusername, $ip_address);
                    $failLog->execute();
                    $failLog->close();

                    sleep(2);

                    auditSafe($con, 'LOGIN_FAILED', 'Authentication', 'Invalid password', $myusername);

                    $error = "Invalid username or password.";
                }
            } else {

                $failLog = safePrepare($con, "
                    INSERT INTO login_attempts
                    (username, ip_address, attempt_time, status)
                    VALUES (?, ?, NOW(), 'FAILED')
                ");

                $failLog->bind_param("ss", $myusername, $ip_address);
                $failLog->execute();
                $failLog->close();

                auditSafe($con, 'LOGIN_FAILED', 'Authentication', 'Invalid username', $myusername);

                $error = "Invalid username or password.";
            }

            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SaQshi</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

    <div class="auth-wrapper">

        <div class="auth-content text-center">

            <div class="card borderless">

                <div class="row align-items-center">

                    <div class="col-md-12">

                        <?php if (!empty($error)): ?>

                            <div class="alert alert-danger">
                                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                            </div>

                        <?php endif; ?>

                        <form method="POST" autocomplete="off">

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

                            <div class="card-body">

                                <h3>SaQshi</h3>

                                <hr>

                                <input
                                    type="text"
                                    name="myusername"
                                    class="form-control mb-3"
                                    placeholder="Username"
                                    required
                                    maxlength="100"
                                    autocomplete="off">

                                <input
                                    type="password"
                                    name="mypassword"
                                    class="form-control mb-4"
                                    placeholder="Password"
                                    required
                                    maxlength="100"
                                    autocomplete="new-password">

                                <select name="lang" class="form-control mb-4">
                                    <option value="5">English</option>
                                </select>

                                <div class="form-group mb-4"
                                    style="border:1px solid #dcdcdc;
            padding:15px;
            border-radius:5px;
            background:#fafafa;">

                                    <label
                                        style="display:flex;
               align-items:center;
               gap:10px;
               cursor:pointer;
               margin:0;">

                                        <input
                                            type="checkbox"
                                            name="robot_check"
                                            value="1"
                                            required>

                                        <span>I am not a robot</span>

                                    </label>

                                </div>

                                <button
                                    type="submit"
                                    class="btn btn-primary btn-block mb-4">

                                    Login

                                </button>

                                <p class="text-muted mb-0">
                                    <?= date('Y'); ?> Piramal Swasthya. All Rights Reserved.
                                </p>

                            </div>

                        </form>

                    </div>

                </div>

            </div>

        </div>

    </div>



</body>

</html>
