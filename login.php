<?php

/*
=====================================================
 SAQSHI ENTERPRISE SECURE LOGIN SYSTEM
 SECURITY AUDIT READY VERSION
=====================================================
*/

/* =====================================================
   OUTPUT BUFFER
===================================================== */
ob_start();

/* =====================================================
   FORCE HTTPS
===================================================== */
if (
    empty($_SERVER['HTTPS']) ||
    $_SERVER['HTTPS'] === 'off'
) {

    $redirect =
        'https://' .
        $_SERVER['HTTP_HOST'] .
        $_SERVER['REQUEST_URI'];

    header("Location: " . $redirect, true, 301);
    exit;
}

/* =====================================================
   SECURITY HEADERS
===================================================== */
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin");
header("Permissions-Policy: geolocation=()");
header("Cross-Origin-Opener-Policy: same-origin");
header("Cross-Origin-Resource-Policy: same-origin");
header("X-Permitted-Cross-Domain-Policies: none");

header(
    "Strict-Transport-Security: max-age=31536000; includeSubDomains"
);
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self'; object-src 'none'; base-uri 'self'; frame-ancestors 'none';");

/* =====================================================
   ERROR SETTINGS
===================================================== */
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

/* =====================================================
   SESSION SECURITY
===================================================== */
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_httponly', 1);

ini_set(
    'session.cookie_secure',
    isset($_SERVER['HTTPS']) ? 1 : 0
);

ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Strict'
]);

//session_name("SAQSHISESSID");

session_start();

/* =====================================================
   SESSION TIMEOUT
===================================================== */
$session_timeout = 1800;

if (
    isset($_SESSION['LAST_ACTIVITY']) &&
    (time() - $_SESSION['LAST_ACTIVITY']) > $session_timeout
) {

    session_unset();
    session_destroy();

    header("Location: login.php");
    exit;
}

$_SESSION['LAST_ACTIVITY'] = time();

/* =====================================================
   DATABASE
===================================================== */
require_once("assets/conn/db.php");

/* =====================================================
   AUDIT LOGGER
===================================================== */
if (file_exists("assets/helpers/audit_logger.php")) {
    require_once("assets/helpers/audit_logger.php");
}

/* =====================================================
   CSRF TOKEN
===================================================== */
if (empty($_SESSION['csrf_token'])) {

    $_SESSION['csrf_token'] =
        bin2hex(random_bytes(32));
}

/* =====================================================
   HELPER FUNCTIONS
===================================================== */

function getClientIP()
{
    return $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
}

function auditSafe(
    $con,
    $action,
    $module,
    $message,
    $username = null
) {

    if (function_exists('auditLog')) {

        auditLog(
            $con,
            $action,
            $module,
            $message,
            null,
            $username
        );
    }
}

function cleanInput($data)
{
    return trim(htmlspecialchars($data, ENT_QUOTES, 'UTF-8'));
}


$ip_address = getClientIP();

$error = '';

/* =====================================================
   PROCESS LOGIN
===================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    /* =====================================================
   CUSTOM CAPTCHA VALIDATION
===================================================== */

    $robotToken =
        $_POST['robot_token'] ?? '';

    if (
        empty($robotToken) ||
        !hash_equals(
            $_SESSION['robot_token'],
            $robotToken
        )
    ) {

        $error =
            "Please verify that you are not a robot.";
    } else {

        /* Regenerate token */
        $_SESSION['robot_token'] =
            bin2hex(random_bytes(32));
    }
    /* =====================================================
       RANDOM DELAY
    ===================================================== */
    usleep(random_int(300000, 800000));

    /* =====================================================
       CSRF VALIDATION
    ===================================================== */
    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals(
            $_SESSION['csrf_token'],
            $_POST['csrf_token']
        )
    ) {

        http_response_code(403);
        die("Invalid request");
    }

    /* =====================================================
       INPUT VALIDATION
    ===================================================== */
    $myusername = cleanInput(
        $_POST['myusername'] ?? ''
    );

    $mypassword = trim(
        $_POST['mypassword'] ?? ''
    );

    $_SESSION['lang'] =
        (int)($_POST['lang'] ?? 5);

    if (
        empty($myusername) ||
        empty($mypassword)
    ) {

        $error = "Invalid username or password.";
    } else {

        /* =====================================================
           CHECK FAILED LOGIN ATTEMPTS
        ===================================================== */

        $lockQuery = $con->prepare("
            SELECT COUNT(*) AS total
            FROM login_attempts
            WHERE ip_address = ?
            AND attempt_time > (NOW() - INTERVAL 15 MINUTE)
            AND status = 'FAILED'
        ");

        $lockQuery->bind_param(
            "s",
            $ip_address
        );

        $lockQuery->execute();

        $lockResult =
            $lockQuery->get_result()->fetch_assoc();

        if ($lockResult['total'] >= 5) {

            http_response_code(429);

            die("Too many failed login attempts. Try again after 15 minutes.");
        }

        /* =====================================================
           USER FETCH
        ===================================================== */

        $stmt = $con->prepare("
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

        if (!$stmt) {

            error_log($con->error);

            die("Server error");
        }

        $stmt->bind_param(
            "s",
            $myusername
        );

        $stmt->execute();

        $result = $stmt->get_result();

        /* =====================================================
           USER FOUND
        ===================================================== */

        if (
            $result &&
            $result->num_rows === 1
        ) {

            $row = $result->fetch_assoc();

            $userid        = (int)$row['u_id'];
            $userrole      = (int)$row['role_id_fk'];
            $district_id   = (int)$row['dist_id'];
            $stored_hash   = trim($row['u_password']);

            $isValid = false;

            /* =====================================================
               PASSWORD VERIFY
            ===================================================== */

            if (
                password_verify(
                    $mypassword,
                    $stored_hash
                )
            ) {

                $isValid = true;

                /* =====================================================
                   PASSWORD REHASH
                ===================================================== */

                if (
                    password_needs_rehash(
                        $stored_hash,
                        PASSWORD_DEFAULT
                    )
                ) {

                    $newHash = password_hash(
                        $mypassword,
                        PASSWORD_DEFAULT
                    );

                    $rehash = $con->prepare("
                        UPDATE s_user
                        SET u_password = ?
                        WHERE u_id = ?
                    ");

                    $rehash->bind_param(
                        "si",
                        $newHash,
                        $userid
                    );

                    $rehash->execute();
                }
            }

            /* =====================================================
               LEGACY PASSWORD SUPPORT
            ===================================================== */ elseif (
                hash_equals(
                    $stored_hash,
                    $mypassword
                )
            ) {

                $isValid = true;

                $newHash = password_hash(
                    $mypassword,
                    PASSWORD_DEFAULT
                );

                $update = $con->prepare("
                    UPDATE s_user
                    SET u_password = ?
                    WHERE u_id = ?
                ");

                $update->bind_param(
                    "si",
                    $newHash,
                    $userid
                );

                $update->execute();
            }

            /* =====================================================
               LOGIN SUCCESS
            ===================================================== */

            if ($isValid) {

                /* =====================================================
                   DELETE OLD FAILED ATTEMPTS
                ===================================================== */

                $clearAttempts = $con->prepare("
                    DELETE FROM login_attempts
                    WHERE ip_address = ?
                ");

                $clearAttempts->bind_param(
                    "s",
                    $ip_address
                );

                $clearAttempts->execute();

                /* =====================================================
                   SESSION REGENERATION
                ===================================================== */

                session_regenerate_id(true);

                $_SESSION['userid']        = $userid;
                $_SESSION['u_name']        = $myusername;
                $_SESSION['userrole']      = $userrole;
                $_SESSION['urole']         = $userrole;
                $_SESSION['dist']          = $district_id;

                $_SESSION['login_time'] =
                    date('d M Y h:i A');

                $_SESSION['ip'] =
                    getClientIP();

                $_SESSION['user_agent'] =
                    hash(
                        'sha256',
                        $_SERVER['HTTP_USER_AGENT'] ?? ''
                    );

                /* =====================================================
                   NEW CSRF TOKEN
                ===================================================== */

                $_SESSION['csrf_token'] =
                    bin2hex(random_bytes(32));

                /* =====================================================
                   LOGIN SUCCESS LOG
                ===================================================== */

                $logSuccess = $con->prepare("
                    INSERT INTO login_attempts
                    (
                        username,
                        ip_address,
                        attempt_time,
                        status
                    )
                    VALUES (?, ?, NOW(), 'SUCCESS')
                ");

                $logSuccess->bind_param(
                    "ss",
                    $myusername,
                    $ip_address
                );

                $logSuccess->execute();

                auditSafe(
                    $con,
                    'LOGIN_SUCCESS',
                    'Authentication',
                    'User logged in successfully',
                    $myusername
                );

                /* =====================================================
                   ROLE REDIRECTION
                ===================================================== */

                switch ($userrole) {

                    case 1:
                    case 2:
                    case 3:
                    case 6:

                        $stmt2 = $con->prepare("
                    SELECT a.fac_id_fk, b.NIN_no, a.assessment_id, a.dist_id,
                           b.Health_facilty_type, b.fac_name, c.fac
                    FROM s_user AS a 
                    JOIN facilities AS b ON a.fac_id_fk = b.fac_id
                    JOIN facilities_type AS c ON b.Health_facilty_type = c.fac_type_id
                    WHERE a.u_id = ? AND a.is_active = 1
                ");
                        $stmt2->bind_param("i", $userid);
                        $stmt2->execute();
                        $data = $stmt2->get_result()->fetch_assoc();

                        $_SESSION['u_facilityid']   = $data['fac_id_fk'];
                        $_SESSION['f_type_id']      = $data['Health_facilty_type'];
                        $_SESSION['facilty_type']   = $data['Health_facilty_type'];  // Required!!
                        $_SESSION['assperiod']      = $data['assessment_id'];
                        $_SESSION['facname']        = $data['fac_name'];
                        $_SESSION['factypename']    = $data['fac'];
                        $_SESSION['factynin']       = $data['NIN_no'];

                        header("location:index.php");
                        exit;

                    case 4:

                        $stmt2 = $con->prepare("
                    SELECT a.Dist_id, b.Dist_name 
                    FROM facilities AS a 
                    JOIN dist_master AS b ON a.dist_id = b.Dist_id 
                    WHERE a.Dist_id = (SELECT dist_id FROM s_user WHERE u_id = ?)
                ");
                        $stmt2->bind_param("i", $userid);
                        $stmt2->execute();
                        $data = $stmt2->get_result()->fetch_assoc();
                        $_SESSION['div_id'] = $data['Dist_id'];
                        $_SESSION['div_name'] = $data['Dist_name'];
                        header("location:distdash.php");
                        exit;

                    case 5:

                        $stmt2 = $con->prepare("
                    SELECT a.division_id, b.division_name 
                    FROM facilities AS a 
                    JOIN division AS b ON a.division_id = b.iddivision 
                    WHERE a.division_id = (SELECT division_id FROM s_user WHERE u_id = ?)
                ");
                        $stmt2->bind_param("i", $userid);
                        $stmt2->execute();
                        $data = $stmt2->get_result()->fetch_assoc();
                        $_SESSION['div_id'] = $data['division_id'];
                        $_SESSION['div_name'] = $data['division_name'];
                        header("location:regdash.php");
                        exit;

                    case 8:

                        $_SESSION['block_id'] = $row['block_id'];
                        $stmt2 = $con->prepare("SELECT block_name FROM block_master WHERE block_id = ?");
                        $stmt2->bind_param("i", $_SESSION['block_id']);
                        $stmt2->execute();
                        $data = $stmt2->get_result()->fetch_assoc();
                        $_SESSION['block_name'] = $data['block_name'];
                        header("location:bdash.php");
                        exit;

                    case 9:

                        $_SESSION['u_facilityid'] = 0;
                        header("location:sdash.php");
                        exit;

                    default:

                        session_destroy();

                        die("Unauthorized role");
                }
            } else {

                /* =====================================================
                   FAILED LOGIN LOG
                ===================================================== */

                $failLog = $con->prepare("
                    INSERT INTO login_attempts
                    (
                        username,
                        ip_address,
                        attempt_time,
                        status
                    )
                    VALUES (?, ?, NOW(), 'FAILED')
                ");

                $failLog->bind_param(
                    "ss",
                    $myusername,
                    $ip_address
                );

                $failLog->execute();

                auditSafe(
                    $con,
                    'LOGIN_FAILED',
                    'Authentication',
                    'Invalid password',
                    $myusername
                );

                $error =
                    "Invalid username or password.";
            }
        } else {

            /* =====================================================
               INVALID USER
            ===================================================== */

            $failLog = $con->prepare("
                INSERT INTO login_attempts
                (
                    username,
                    ip_address,
                    attempt_time,
                    status
                )
                VALUES (?, ?, NOW(), 'FAILED')
            ");

            $failLog->bind_param(
                "ss",
                $myusername,
                $ip_address
            );

            $failLog->execute();

            auditSafe(
                $con,
                'LOGIN_FAILED',
                'Authentication',
                'Invalid username',
                $myusername
            );

            $error =
                "Invalid username or password.";
        }
    }
}
/* =====================================================
   CUSTOM CHECKBOX CAPTCHA
===================================================== */

if (empty($_SESSION['robot_token'])) {

    $_SESSION['robot_token'] =
        bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>SaQshi</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css">

</head>

<body>

    <div class="auth-wrapper">

        <div class="auth-content text-center">

            <div class="card borderless">

                <div class="row align-items-center">

                    <div class="col-md-12">

                        <?php if (!empty($error)): ?>

                            <div class="alert alert-danger">

                                <?= htmlspecialchars(
                                    $error,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>

                            </div>

                        <?php endif; ?>

                        <form
                            method="POST"
                            autocomplete="off">

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">

                            <div class="card-body">

                                <h3>
                                    SaQshi
                                </h3>

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

                                <select
                                    name="lang"
                                    class="form-control mb-4">

                                    <option value="5">
                                        English
                                    </option>

                                </select>
                                <div
                                    class="form-group mb-4"
                                    style="
        border:1px solid #dcdcdc;
        padding:15px;
        border-radius:5px;
        background:#fafafa;
    ">

                                    <label
                                        style="
            display:flex;
            align-items:center;
            gap:10px;
            cursor:pointer;
            margin:0;
        ">

                                        <input
                                            type="checkbox"
                                            id="robotCheck"
                                            required>

                                        <span>
                                            I am not a robot
                                        </span>

                                    </label>

                                    <input
                                        type="hidden"
                                        name="robot_token"
                                        id="robot_token">

                                </div>
                                <button
                                    type="submit"
                                    class="btn btn-primary btn-block mb-4">

                                    Login

                                </button>

                                <p class="text-muted mb-0">

                                    <?= date('Y'); ?>

                                    Piramal Swasthya.
                                    All Rights Reserved.

                                </p>

                            </div>

                        </form>

                    </div>

                </div>

            </div>

        </div>

    </div>
    <script>
        document
            .getElementById('robotCheck')
            .addEventListener('change', function() {

                if (this.checked) {

                    document
                        .getElementById('robot_token')
                        .value =
                        '<?= $_SESSION['robot_token']; ?>';

                } else {

                    document
                        .getElementById('robot_token')
                        .value = '';
                }
            });
    </script>
</body>

</html>