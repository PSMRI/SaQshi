<?php
/**
 * =====================================================
 * SaQshi Security Layer
 * security.php
 * Production
 * =====================================================
 */

/* =====================================================
   PREVENT MULTIPLE LOAD
===================================================== */

if (defined('SAQSHI_SECURITY_LOADED')) {
    return;
}

define('SAQSHI_SECURITY_LOADED', true);

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

header(
    "Content-Security-Policy: " .
    "default-src 'self'; " .
    "script-src 'self' 'unsafe-inline' 'unsafe-eval' " .
    "https://cdnjs.cloudflare.com " .
    "https://cdn.jsdelivr.net " .
    "https://code.jquery.com; " .
    "style-src 'self' 'unsafe-inline' " .
    "https://cdn.jsdelivr.net " .
    "https://fonts.googleapis.com; " .
    "font-src 'self' " .
    "https://cdn.jsdelivr.net " .
    "https://fonts.gstatic.com data:; " .
    "img-src 'self' data: blob:; " .
    "connect-src 'self'; " .
    "frame-ancestors 'none'; " .
    "object-src 'none'; " .
    "base-uri 'self';"
);

header(
    "Cache-Control: no-store, no-cache, must-revalidate, max-age=0"
);

header("Pragma: no-cache");

header("Expires: Sat, 01 Jan 2000 00:00:00 GMT");

/* =====================================================
   PHP ERROR SETTINGS
===================================================== */

ini_set('display_errors', 0);

ini_set('log_errors', 1);

error_reporting(E_ALL);

/* =====================================================
   CSRF TOKEN
===================================================== */

if (
    session_status() === PHP_SESSION_ACTIVE &&
    empty($_SESSION['csrf_token'])
) {

    if (empty($_SESSION['csrf_token'])) {

    $_SESSION['csrf_token'] =
        bin2hex(random_bytes(32));
}
}
/* =====================================================
   CSRF HELPER
===================================================== */

if (!function_exists('csrf')) {

    function csrf()
    {
        return '
        <input type="hidden"
               name="csrf_token"
               value="' .
               $_SESSION['csrf_token'] .
               '">';
    }
}
/* =====================================================
   CSRF VALIDATION
===================================================== */

if (
    session_status() === PHP_SESSION_ACTIVE &&
    $_SERVER['REQUEST_METHOD'] === 'POST'
) {

    if (
        empty($_POST['csrf_token']) ||
        !hash_equals(
            $_SESSION['csrf_token'],
            $_POST['csrf_token']
        )
    ) {

        http_response_code(403);

        exit("Invalid CSRF Token");
    }
}

/* =====================================================
   SAFE OUTPUT FUNCTION
===================================================== */

function e($string)
{
    return htmlspecialchars(
        (string)($string ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

/* =====================================================
   SAFE INPUT FUNCTION
===================================================== */

if (!function_exists('cleanInput')) {

    function cleanInput($data)
    {
        return trim(
            htmlspecialchars(
                (string)$data,
                ENT_QUOTES,
                'UTF-8'
            )
        );
    }
}

/* =====================================================
   SAFE INTEGER
===================================================== */

if (!function_exists('cleanInt')) {

    function cleanInt($value)
    {
        return (int)$value;
    }
}

/* =====================================================
   SAFE REDIRECT
===================================================== */

if (!function_exists('safeRedirect')) {

    function safeRedirect($url)
    {
        header("Location: " . $url);

        exit;
    }
}

/* =====================================================
   ROLE AUTHORIZATION
===================================================== */

if (!function_exists('requireRole')) {

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
}

/* =====================================================
   AJAX VALIDATION
===================================================== */

if (!function_exists('requireAjax')) {

    function requireAjax()
    {

        if (
            strtolower(
                $_SERVER['HTTP_X_REQUESTED_WITH']
                ?? ''
            ) !== 'xmlhttprequest'
        ) {

            http_response_code(403);

            exit("Invalid Request");
        }
    }
}

/* =====================================================
   SIMPLE RATE LIMIT
===================================================== */

if (session_status() === PHP_SESSION_ACTIVE) {

    if (!isset($_SESSION['rate_limit'])) {

        $_SESSION['rate_limit'] = [];
    }

    $currentMinute =
        floor(time() / 60);

    if (
        !isset(
            $_SESSION['rate_limit'][$currentMinute]
        )
    ) {

        $_SESSION['rate_limit']
            = [$currentMinute => 1];

    } else {

        $_SESSION['rate_limit'][$currentMinute]++;
    }

    if (
        $_SESSION['rate_limit'][$currentMinute]
        > 300
    ) {

        http_response_code(429);

        exit("Too many requests");
    }
}

/* =====================================================
   CLICKJACKING FRAME BREAK
===================================================== */

echo '
<script>
if (window.top !== window.self) {
    window.top.location = window.self.location;
}
</script>
';

/* =====================================================
   SECURITY READY
===================================================== */
?>