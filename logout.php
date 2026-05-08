<?php
/**
 * =====================================================
 * SaQshi Secure Logout
 * logout.php
 * Production + Security Audit Ready
 * =====================================================
 */

/* =====================================================
   SECURITY HEADERS
===================================================== */

header("X-Frame-Options: DENY");

header("X-Content-Type-Options: nosniff");

header("Referrer-Policy: strict-origin");

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

header("Pragma: no-cache");

header("Expires: Sat, 01 Jan 2000 00:00:00 GMT");

/* =====================================================
   SESSION CONFIG
===================================================== */

/*
   MUST MATCH login.php + session.php
*/

//session_name("SAQSHISESSID");

/* =====================================================
   START SESSION SAFELY
===================================================== */

if (session_status() === PHP_SESSION_NONE) {

    session_start();
}

/* =====================================================
   OPTIONAL AUDIT LOG
===================================================== */

$userName =
    $_SESSION['u_name'] ?? 'Unknown';

/* =====================================================
   CLEAR SESSION DATA
===================================================== */

$_SESSION = [];

/* =====================================================
   REMOVE SESSION COOKIE
===================================================== */

if (ini_get("session.use_cookies")) {

    $params =
        session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

/* =====================================================
   DESTROY SESSION
===================================================== */

session_destroy();

/* =====================================================
   CLEAR OUTPUT BUFFER SAFELY
===================================================== */

if (ob_get_length()) {

    ob_end_clean();
}

/* =====================================================
   PREVENT BACK BUTTON CACHE ACCESS
===================================================== */

header("Clear-Site-Data: \"cache\", \"cookies\", \"storage\"");

/* =====================================================
   REDIRECT TO LOGIN
===================================================== */

header("Location: login.php");

exit;
?>