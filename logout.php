<?php
// Start the session (must be done before modifying session variables)
include('assets/conn/session.php');
// Regenerate session ID to prevent session fixation attacks
session_regenerate_id();
// Clear all session variables
session_unset();
// Destroy the session data
session_destroy();
// Clean (erase) the output buffer if any exists (useful if output buffering was enabled)
ob_end_clean();
// Redirect the user to login1.php after session is destroyed
header("Location: start.php");
exit(); // Ensure no further code is executed after the redirect
?>
