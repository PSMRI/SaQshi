<?php
/**
 * =====================================================
 * SaQshi Secure Session Manager
 * session.php
 * Production + Security Audit Ready
 * =====================================================
 */

/* =====================================================
   PREVENT MULTIPLE LOAD
===================================================== */

if (defined('SAQSHI_SESSION_LOADED')) {
    return;
}

define('SAQSHI_SESSION_LOADED', true);

/* =====================================================
   SESSION SECURITY SETTINGS
===================================================== */

ini_set('session.use_only_cookies', 1);

ini_set('session.cookie_httponly', 1);

$isHttps =
(
    !empty($_SERVER['HTTPS']) &&
    $_SERVER['HTTPS'] !== 'off'
)
||
(
    ($_SERVER['SERVER_PORT'] ?? 80) == 443
);

ini_set('session.use_strict_mode', 1);

ini_set(
    'session.cookie_samesite',
    'Strict'
);

/* =====================================================
   SESSION COOKIE CONFIG
===================================================== */

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Strict'
]);

/* =====================================================
   SESSION NAME
===================================================== */

//session_name("SAQSHISESSID");

/* =====================================================
   REDIS SESSION STORAGE
===================================================== */

ini_set(
    'session.save_handler',
    'redis'
);

ini_set(
    'session.save_path',
    'tcp://127.0.0.1:6379?database=2&prefix=saqshi_sess_&timeout=2&read_timeout=2'
);
ini_set(
    'session.gc_maxlifetime',
    1800
);

/* =====================================================
   START SESSION
===================================================== */

if (
    session_status() === PHP_SESSION_NONE
) {

    if (!@session_start()) {

        /*
        =====================================================
        FALLBACK TO FILE SESSION
        =====================================================
        */

        ini_set(
            'session.save_handler',
            'files'
        );

        session_start();
    }
}

/* =====================================================
   DATABASE
===================================================== */

require_once(
    __DIR__ . '/db.php'
);

/* =====================================================
   AUTH CHECK
===================================================== */

if (
    empty($_SESSION['userid']) ||
    empty($_SESSION['u_name'])
) {

    session_unset();

    session_destroy();

    header(
        "Location: login.php"
    );

    exit;
}

/* =====================================================
   SESSION TIMEOUT
===================================================== */

$SESSION_TIMEOUT = 1800;

if (
    isset($_SESSION['LAST_ACTIVITY']) &&
    (
        time() -
        $_SESSION['LAST_ACTIVITY']
    ) > $SESSION_TIMEOUT
) {

    session_unset();

    session_destroy();

    header(
        "Location: login.php?timeout=1"
    );

    exit;
}

$_SESSION['LAST_ACTIVITY'] = time();

/* =====================================================
   SESSION FINGERPRINT
===================================================== */

$currentIP =
    $_SERVER['REMOTE_ADDR']
    ?? '';

$currentUA =
    hash(
        'sha256',
        $_SERVER['HTTP_USER_AGENT']
        ?? ''
    );

/* =====================================================
   VALIDATE IP
===================================================== */

if (
    !empty($_SESSION['ip']) &&
    $_SESSION['ip'] !== $currentIP
) {

    session_unset();

    session_destroy();

    header(
        "Location: login.php?security=ip"
    );

    exit;
}

/* =====================================================
   VALIDATE USER AGENT
===================================================== */

if (
    !empty($_SESSION['user_agent']) &&
    $_SESSION['user_agent']
    !== $currentUA
) {

    session_unset();

    session_destroy();

    header(
        "Location: login.php?security=ua"
    );

    exit;
}

/* =====================================================
   VERIFY USER EXISTS
===================================================== */

$sql = "
    SELECT
        u_id,
        u_name,
        role_id_fk,
        is_active
    FROM s_user
    WHERE u_id = ?
    AND u_name = ?
    AND is_active = 1
    LIMIT 1
";

$stmt = $con->prepare($sql);

/* =====================================================
   DATABASE ERROR
===================================================== */

if (!$stmt) {

    error_log(
        "Session DB Prepare Failed: " .
        $con->error
    );

    session_unset();

    session_destroy();

    header(
        "Location: login.php"
    );

    exit;
}

/* =====================================================
   EXECUTE QUERY
===================================================== */

$stmt->bind_param(
    "is",
    $_SESSION['userid'],
    $_SESSION['u_name']
);

$stmt->execute();

$result = $stmt->get_result();

/* =====================================================
   INVALID SESSION USER
===================================================== */

if (
    $result->num_rows !== 1
) {

    session_unset();

    session_destroy();

    header(
        "Location: login.php"
    );

    exit;
}

/* =====================================================
   VALID USER
===================================================== */

$userData =
    $result->fetch_assoc();

$login_session =
    $userData['u_name'];

$_SESSION['userid'] =
    (int)$userData['u_id'];

$_SESSION['userrole'] =
    (int)$userData['role_id_fk'];

$stmt->close();

/* =====================================================
   PERIODIC SESSION REGENERATION
===================================================== */

if (
    empty($_SESSION['session_regenerated'])
) {

    session_regenerate_id(true);

    $_SESSION['session_regenerated']
        = time();
}

elseif (
    (
        time() -
        $_SESSION['session_regenerated']
    ) > 900
) {

    session_regenerate_id(true);

    $_SESSION['session_regenerated']
        = time();
}

/* =====================================================
   SESSION READY
===================================================== */
?>