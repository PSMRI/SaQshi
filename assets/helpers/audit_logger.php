<?php
function auditLog($con, $action, $module, $description = '', $ref_id = null, $fallbackUsername = null) {

    // Safe defaults
    $user_id  = $_SESSION['userid'] ?? null;
    $username = $_SESSION['u_name'] ?? $fallbackUsername ?? 'guest';

    $ip    = $_SERVER['REMOTE_ADDR'] ?? '';
    $agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    // Prepare
    $stmt = $con->prepare("
        INSERT INTO user_activity_log
        (user_id, username, action, module, description, reference_id, ip_address, user_agent, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    if (!$stmt) {
        error_log("AUDIT PREPARE ERROR: " . $con->error);
        return false;
    }

    // IMPORTANT: assign variables explicitly
    $uid  = $user_id;
    $ref  = $ref_id;

    // Bind (i = int, s = string)
    $stmt->bind_param(
        "isssssss",
        $uid,
        $username,
        $action,
        $module,
        $description,
        $ref,
        $ip,
        $agent
    );

    if (!$stmt->execute()) {
        error_log("AUDIT EXEC ERROR: " . $stmt->error);
        return false;
    }

    return true;
}
