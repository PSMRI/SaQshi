<?php
/**
 * =====================================================
 * SaQshi Secure Database Connection
 * Legacy Compatible + Security Audit Ready
 * =====================================================
 */

/* =====================================================
   PREVENT MULTIPLE LOAD
===================================================== */

if (defined('SAQSHI_DB_LOADED')) {
    return;
}

define('SAQSHI_DB_LOADED', true);

/* =====================================================
   MYSQLI ERROR MODE
===================================================== */

mysqli_report(MYSQLI_REPORT_OFF);

/* =====================================================
   LOAD ENV FILE
===================================================== */

$envPath =
    dirname(__DIR__, 2) . '/.env';

if (!file_exists($envPath)) {

    error_log(".env file missing");

    http_response_code(500);

    exit("Configuration error.");
}

$env = parse_ini_file($envPath);

if (!$env) {

    error_log("Unable to load .env");

    http_response_code(500);

    exit("Configuration error.");
}

/* =====================================================
   DATABASE CONFIG
===================================================== */

$db_host =
    trim($env['DB_HOST'] ?? '');

$db_port =
    (int)($env['DB_PORT'] ?? 3306);

$db_name =
    trim($env['DB_NAME'] ?? '');

$db_user =
    trim($env['DB_USER'] ?? '');

$db_pass =
    trim($env['DB_PASS'] ?? '');

/* =====================================================
   VALIDATE CONFIG
===================================================== */

if (
    empty($db_host) ||
    empty($db_name) ||
    empty($db_user)
) {

    error_log(
        "Missing database configuration"
    );

    http_response_code(500);

    exit("Database configuration error.");
}

/* =====================================================
   CREATE MYSQLI CONNECTION
===================================================== */

/** @var mysqli $con */

$con = mysqli_init();

/* =====================================================
   MYSQL OPTIONS
===================================================== */

mysqli_options(
    $con,
    MYSQLI_OPT_CONNECT_TIMEOUT,
    10
);

/* =====================================================
   CONNECT DATABASE
===================================================== */

$connected = @mysqli_real_connect(
    $con,
    $db_host,
    $db_user,
    $db_pass,
    $db_name,
    $db_port
);

/* =====================================================
   CONNECTION FAILED
===================================================== */

if (!$connected) {

    error_log(
        "DB Connection Failed: " .
        mysqli_connect_error()
    );

    http_response_code(500);

    exit("Database unavailable.");
}

/* =====================================================
   CHARACTER SET
===================================================== */

mysqli_set_charset(
    $con,
    "utf8mb4"
);

/* =====================================================
   SQL MODE
===================================================== */

mysqli_query(
    $con,
    "SET SESSION sql_mode =
    'STRICT_TRANS_TABLES,
    ERROR_FOR_DIVISION_BY_ZERO,
    NO_ENGINE_SUBSTITUTION'"
);

/* =====================================================
   TIMEZONE
===================================================== */

mysqli_query(
    $con,
    "SET time_zone = '+05:30'"
);

/* =====================================================
   UTF8 COLLATION
===================================================== */

mysqli_query(
    $con,
    "SET NAMES utf8mb4
    COLLATE utf8mb4_unicode_ci"
);

/* =====================================================
   GLOBAL COMPATIBILITY
===================================================== */

/*
   Makes $con available everywhere
*/

$GLOBALS['con'] = $con;

/* =====================================================
   OPTIONAL HELPER FUNCTIONS
===================================================== */

if (!function_exists('dbQuery')) {

    function dbQuery($query)
    {
        global $con;

        return mysqli_query(
            $con,
            $query
        );
    }
}

if (!function_exists('dbEscape')) {

    function dbEscape($value)
    {
        global $con;

        return mysqli_real_escape_string(
            $con,
            $value
        );
    }
}

/* =====================================================
   DATABASE READY
===================================================== */
?>