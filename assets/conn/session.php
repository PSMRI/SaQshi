<?php
/**
 * session.php
 * Central authentication & session validation file
 * SaQshi – Production Safe
 */

require_once __DIR__ . '/db.php';

/* -------------------------------------------------
   SESSION CONFIG (Redis with safe fallback)
-------------------------------------------------- */

ini_set('session.save_handler', 'redis');
ini_set(
    'session.save_path',
    'tcp://127.0.0.1:6379?database=2&prefix=saqshi_sess_&timeout=2&read_timeout=2'
);

// Start session safely (fallback to files if Redis fails)
if (!@session_start()) {
    ini_set('session.save_handler', 'files');
    session_start();
}

/* -------------------------------------------------
   AUTH CHECK
-------------------------------------------------- */

// User must be logged in
if (empty($_SESSION['u_name'])) {
    header("Location: 404.php");
    exit;
}

$userName = $_SESSION['u_name'];

/* -------------------------------------------------
   VERIFY USER EXISTS IN DATABASE
-------------------------------------------------- */

$sql = "SELECT u_name FROM s_user WHERE u_name = ? LIMIT 1";
$stmt = $con->prepare($sql);

if (!$stmt) {
    error_log("Session DB prepare failed: " . $con->error);
    session_unset();
    session_destroy();
    header("Location: 404.php");
    exit;
}

$stmt->bind_param("s", $userName);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    // Session tampered or user removed
    session_unset();
    session_destroy();
    header("Location: 404.php");
    exit;
}

// Valid authenticated user
$login_session = $userName;

$stmt->close();

/* -------------------------------------------------
   OPTIONAL: SESSION TIMEOUT (RECOMMENDED)
-------------------------------------------------- */

$SESSION_TIMEOUT = 1800; // 30 minutes

if (isset($_SESSION['LAST_ACTIVITY']) &&
    (time() - $_SESSION['LAST_ACTIVITY']) > $SESSION_TIMEOUT
) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}

$_SESSION['LAST_ACTIVITY'] = time();

/* -------------------------------------------------
   SESSION IS VALID BEYOND THIS POINT
-------------------------------------------------- */
