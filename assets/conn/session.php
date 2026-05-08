<?php
/**
 * =====================================================
 * SaQshi Secure Session Management
 * session.php
 * Production + Security Audit Ready
 * =====================================================
 */

require_once __DIR__ . '/db.php';

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

/*
=====================================================
 PRACTICAL CSP FOR SAQSHI
=====================================================
*/

header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://code.jquery.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; font-src 'self' https://cdn.jsdelivr.net https://fonts.gstatic.com data:; img-src 'self' data: blob:; connect-src 'self'; frame-ancestors 'none'; object-src 'none'; base-uri 'self';");

/* =====================================================
   DISABLE CACHE
===================================================== */

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

header("Pragma: no-cache");

header("Expires: Sat, 01 Jan 2000 00:00:00 GMT");

/* =====================================================
   SESSION SECURITY SETTINGS
===================================================== */

ini_set('session.use_only_cookies', 1);

ini_set('session.cookie_httponly', 1);

ini_set(
    'session.cookie_secure',
    isset($_SERVER['HTTPS']) ? 1 : 0
);

ini_set('session.use_strict_mode', 1);

ini_set('session.cookie_samesite', 'Lax');

/* =====================================================
   SESSION COOKIE PARAMS
===================================================== */

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax'
]);

/* =====================================================
   IMPORTANT:
   MUST MATCH login.php
===================================================== */

//session_name("SAQSHISESSID");

/* =====================================================
   REDIS SESSION STORAGE
===================================================== */

ini_set('session.save_handler', 'redis');

ini_set(
    'session.save_path',
    'tcp://127.0.0.1:6379?database=2&prefix=saqshi_sess_&timeout=2&read_timeout=2'
);

/* =====================================================
   START SESSION SAFELY
===================================================== */

if (!@session_start()) {

    ini_set('session.save_handler', 'files');

    session_start();
}

/* =====================================================
   AUTH CHECK
===================================================== */

if (
    empty($_SESSION['userid']) ||
    empty($_SESSION['u_name'])
) {

    session_unset();

    session_destroy();

    header("Location: login.php");

    exit;
}

/* =====================================================
   SESSION TIMEOUT
===================================================== */

$SESSION_TIMEOUT = 1800;

if (
    isset($_SESSION['LAST_ACTIVITY']) &&
    (
        time() - $_SESSION['LAST_ACTIVITY']
    ) > $SESSION_TIMEOUT
) {

    session_unset();

    session_destroy();

    header("Location: login.php");

    exit;
}

$_SESSION['LAST_ACTIVITY'] = time();

/* =====================================================
   SESSION FINGERPRINT VALIDATION
===================================================== */

/*
   Prevents:
   - Session Hijacking
   - Cookie Theft
*/

$currentIP =
    $_SERVER['REMOTE_ADDR'] ?? '';

$currentUA = hash(
    'sha256',
    $_SERVER['HTTP_USER_AGENT'] ?? ''
);

/* =====================================================
   VALIDATE USER IP
===================================================== */

if (
    !empty($_SESSION['ip']) &&
    $_SESSION['ip'] !== $currentIP
) {

    session_unset();

    session_destroy();

    header("Location: login.php");

    exit;
}

/* =====================================================
   VALIDATE USER AGENT
===================================================== */

if (
    !empty($_SESSION['user_agent']) &&
    $_SESSION['user_agent'] !== $currentUA
) {

    session_unset();

    session_destroy();

    header("Location: login.php");

    exit;
}

/* =====================================================
   VERIFY USER EXISTS IN DATABASE
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

    header("Location: login.php");

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
   INVALID USER / SESSION TAMPERING
===================================================== */

if ($result->num_rows !== 1) {

    session_unset();

    session_destroy();

    header("Location: login.php");

    exit;
}

/* =====================================================
   VALID AUTHENTICATED USER
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

/*
   Prevents session fixation
*/

if (
    empty($_SESSION['session_regenerated'])
) {

    session_regenerate_id(true);

    $_SESSION['session_regenerated'] =
        time();
}

/*
   Regenerate every 15 minutes
*/

elseif (
    (
        time() -
        $_SESSION['session_regenerated']
    ) > 900
) {

    session_regenerate_id(true);

    $_SESSION['session_regenerated'] =
        time();
}

/* =====================================================
   OPTIONAL:
   ROLE AUTHORIZATION HELPER
===================================================== */

function requireRole($roles = [])
{
    if (
        empty($_SESSION['userrole']) ||
        !in_array(
            $_SESSION['userrole'],
            $roles
        )
    ) {

        http_response_code(403);

        exit("Unauthorized Access");
    }
}

/* =====================================================
   OPTIONAL:
   SAFE REDIRECT HELPER
===================================================== */

function safeRedirect($url)
{
    header("Location: " . $url);

    exit;
}

/* =====================================================
   SESSION VALID BEYOND THIS POINT
===================================================== */