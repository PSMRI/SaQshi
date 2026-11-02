<?php

require_once __DIR__ . '/../core/Crypto.php';
require_once __DIR__ . '/../core/SessionManager.php';

/**
 * Provides auth service behavior for SaQshi API workflows.
 */
class AuthService {

    /**
     * Handles login processing for this API workflow.
     */
    public static function login($conn, $username, $password) {

        $sql = "SELECT u.*, r.role_name
                FROM s_user u
                LEFT JOIN u_role r ON u.role_id_fk = r.role_id
                WHERE u.u_name = ? AND u.is_active = 1";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();

        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if (!$user) {
            return ["status" => false, "message" => "User not found or inactive"];
        }

        // If password is hashed
        if (!password_verify($password, $user['u_password'])) {
            return ["status" => false, "message" => "Invalid password"];
        }

        $user = Crypto::decryptFields($user, [
            'f_name',
            'm_name',
            'l_name',
            'mail_id',
            'mob_no'
        ]);

        // If plain password (temporary fallback)
        // if ($password !== $user['u_password']) { ... }

        // Legacy callers now create the same centralized session as all modern
        // authentication endpoints. Do not introduce a second $_SESSION[user]
        // format with a different lifetime or storage handler.
        SessionManager::login($user);

        return [
            "status" => true,
            "message" => "Login successful",
            "data" => SessionManager::user()
        ];
    }

    /**
     * Handles validate processing for this API workflow.
     */
    public static function validate() {
        return SessionManager::isLoggedIn() ? SessionManager::user() : null;
    }

    /**
     * Handles logout processing for this API workflow.
     */
    public static function logout() {
        SessionManager::logout();
        return true;
    }
}
