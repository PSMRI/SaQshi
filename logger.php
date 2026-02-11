<?php
function logActivity($action, $module, $description = '', $reference_id = null) {
    global $conn; // mysqli / PDO connection

    $user_id   = $_SESSION['user']['id'] ?? null;
    $username  = $_SESSION['user']['username'] ?? 'guest';
    $ip        = $_SERVER['REMOTE_ADDR'] ?? '';
    $agent     = $_SERVER['HTTP_USER_AGENT'] ?? '';

    $stmt = $conn->prepare("
        INSERT INTO user_activity_log
        (user_id, username, action, module, description, reference_id, ip_address, user_agent)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "isssssss",
        $user_id,
        $username,
        $action,
        $module,
        $description,
        $reference_id,
        $ip,
        $agent
    );

    $stmt->execute();
}
