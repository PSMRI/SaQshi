<?php

/**
 * =====================================================
 * SaQshi Secure Audit Logger
 * =====================================================
 */

if (!function_exists('auditLog')) {

function auditLog(
    $con,
    $action,
    $module,
    $description = '',
    $ref_id = null,
    $fallbackUsername = null
) {

    /* =====================================================
       USER DETAILS
    ===================================================== */

    $user_id =
        $_SESSION['userid'] ?? null;

    $username =
        $_SESSION['u_name']
        ?? $fallbackUsername
        ?? 'guest';

    /* =====================================================
       SAFE REQUEST DETAILS
    ===================================================== */

    $ip =
        $_SERVER['HTTP_X_FORWARDED_FOR']
        ?? $_SERVER['REMOTE_ADDR']
        ?? '';

    $ip = substr($ip, 0, 45);

    $agent = substr(
        $_SERVER['HTTP_USER_AGENT']
        ?? '',
        0,
        500
    );

    /* =====================================================
       SANITIZE
    ===================================================== */

    $action =
        substr(strip_tags($action), 0, 100);

    $module =
        substr(strip_tags($module), 0, 100);

    $username =
        substr(strip_tags($username), 0, 100);

    $description =
        substr(
            strip_tags($description),
            0,
            1000
        );

    /* =====================================================
       PREPARE QUERY
    ===================================================== */

    $stmt = $con->prepare("
        INSERT INTO user_activity_log
        (
            user_id,
            username,
            action,
            module,
            description,
            reference_id,
            ip_address,
            user_agent,
            created_at
        )
        VALUES
        (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    if (!$stmt) {

        error_log(
            "AUDIT PREPARE ERROR: " .
            $con->error
        );

        return false;
    }

    /* =====================================================
       BIND
    ===================================================== */

    $uid = $user_id;

    $ref = $ref_id;

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

    /* =====================================================
       EXECUTE
    ===================================================== */

    if (!$stmt->execute()) {

        error_log(
            "AUDIT EXEC ERROR: " .
            $stmt->error
        );

        $stmt->close();

        return false;
    }

    $stmt->close();

    return true;
}

}
?>