<?php

require_once dirname(__DIR__) . '/bootstrap.php';

/**
 * Compatibility middleware for legacy route-based APIs.
 * All session handling is delegated to SessionManager so legacy routes share
 * the same Redis-backed cookie and security policy as modern APIs.
 */
class AuthMiddleware {

    /**
     * Handles check processing for this API workflow.
     */
    public static function check() {
        SessionManager::requireLogin();
        return SessionManager::user();
    }

    /**
     * Handles role processing for this API workflow.
     */
    public static function role($allowed_roles = []) {
        $user = self::check();

        if (!in_array($user['role_name'] ?? '', $allowed_roles, true)) {
            echo json_encode([
                "status" => false,
                "message" => "Forbidden - Role not allowed"
            ]);
            exit;
        }

        return $user;
    }
}
