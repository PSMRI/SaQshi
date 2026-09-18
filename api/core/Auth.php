<?php

/**
 * Auth.php
 * -------------------------------------------------------
 * Centralized authentication service for SaQshi.
 *
 * Uses:
 * - s_user table
 * - u_role table
 * - login_attempts table
 * - SessionManager
 *
 * login_attempts columns:
 * id, username, ip_address, attempt_time, status
 * -------------------------------------------------------
 */

require_once __DIR__ . '/SessionManager.php';
require_once __DIR__ . '/Crypto.php';

/**
 * Provides auth behavior for SaQshi API workflows.
 */
class Auth
{
    private mysqli $db;

    private const MAX_FAILED_ATTEMPTS = 5;
    private const LOCK_MINUTES = 15;

    /**
     * Handles construct processing for this API workflow.
     */
    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    /**
     * Handles login processing for this API workflow.
     */
    public function login(string $username, string $password, bool $rememberMe = false): array
    {
        $username = trim($username);
        // Passwords are opaque secrets. Do not trim or normalize them, or a
        // password accepted and stored at reset time could verify differently
        // at login time.
        $password = (string)$password;

        if ($username === '' || $password === '') {
            return $this->error('Username and password are required');
        }

        if ($this->isLocked($username)) {
            return $this->error('Too many failed login attempts. Please try again later.');
        }

        $user = $this->findUser($username);

        if (
            !$user ||
            (int)($user['is_active'] ?? 0) !== 1 ||
            (array_key_exists('role_status', $user) && (int)($user['role_status'] ?? 0) !== 1)
        ) {
            $this->recordAttempt($username, 'FAILED');
            return $this->error('Invalid username or password');
        }

        $storedPassword = (string)($user['u_password'] ?? '');
        $passwordStatus = $this->passwordStatus($password, $storedPassword);

        if (!$passwordStatus['valid']) {
            $this->recordAttempt($username, 'FAILED');
            return $this->error('Invalid username or password');
        }

        if ($passwordStatus['needs_hash_upgrade']) {
            $this->upgradePasswordHash((int)$user['u_id'], $password);
        }

        $user = $this->decryptUserProfileFields($user);

        $this->clearOldFailedAttempts($username);
        $this->recordAttempt($username, 'SUCCESS');

        unset($user['u_password']);

        $user['password_must_change'] = $this->passwordMustChange((int)$user['u_id']);

        SessionManager::login($user, $rememberMe);

        return $this->success('Login successful', [
            'user' => SessionManager::user()
        ]);
    }

    /**
     * Handles logout processing for this API workflow.
     */
    public function logout(): array
    {
        SessionManager::logout();

        return $this->success('Logout successful');
    }

    /**
     * Handles me processing for this API workflow.
     */
    public function me(): array
    {
        if (!SessionManager::isLoggedIn()) {
            return $this->error('Unauthorized');
        }

        // Refresh this flag from the database on every session check. A State
        // Admin can reset another user's password while that user is online.
        // Their existing session must immediately become restricted as well.
        $user = SessionManager::user();
        $mustChangePassword = $this->passwordMustChange((int)($user['u_id'] ?? 0));
        $_SESSION['password_must_change'] = $mustChangePassword;
        $user['password_must_change'] = $mustChangePassword;

        return $this->success('User fetched successfully', [
            'user' => $user
        ]);
    }

    /**
     * Handles find user processing for this API workflow.
     */
    private function findUser(string $username): ?array
    {
        $sql = "
            SELECT
                u.u_id,
                u.u_name,
                u.u_password,
                u.fac_id_fk,
                u.role_id_fk,
                u.is_active,
                u.dept_id,
                u.f_name,
                u.m_name,
                u.l_name,
                u.mob_no,
                u.mail_id,
                u.user_type,
                u.assessment_id,
                u.dist_id,
                u.block_id,
                u.division_id,
                r.role_name,
                r.role_status
            FROM s_user u
            LEFT JOIN u_role r
                ON r.role_id = u.role_id_fk
            WHERE
                u.u_name = ?
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new Exception(
                'User query prepare failed: ' . $this->db->error
            );
        }

        $stmt->bind_param(
            's',
            $username
        );

        $stmt->execute();

        $result = $stmt->get_result();

        if (!$result || $result->num_rows === 0) {
            return null;
        }

        return $result->fetch_assoc();
    }

    /**
     * Handles find user by id processing for this API workflow.
     */
    private function findUserById(int $userId): ?array
    {
        if ($userId <= 0) {
            return null;
        }

        $sql = "
            SELECT
                u.u_id,
                u.u_name,
                u.u_password,
                u.fac_id_fk,
                u.role_id_fk,
                u.is_active,
                u.dept_id,
                u.f_name,
                u.m_name,
                u.l_name,
                u.mob_no,
                u.mail_id,
                u.user_type,
                u.assessment_id,
                u.dist_id,
                u.block_id,
                u.division_id,
                r.role_name,
                r.role_status
            FROM s_user u
            LEFT JOIN u_role r
                ON r.role_id = u.role_id_fk
            WHERE u.u_id = ?
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new Exception('User lookup prepare failed: ' . $this->db->error);
        }

        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if (!$result || $result->num_rows === 0) {
            return null;
        }

        return $result->fetch_assoc();
    }

    /**
     * Handles decrypt user profile fields processing for this API workflow.
     */
    private function decryptUserProfileFields(array $user): array
    {
        return Crypto::decryptFields($user, [
            'f_name',
            'm_name',
            'l_name',
            'mail_id',
            'mob_no'
        ]);
    }

    private function passwordMustChange(int $userId): bool
    {
        if (!$this->columnExists('s_user', 'password_must_change')) {
            return false;
        }

        $stmt = $this->db->prepare("SELECT password_must_change FROM s_user WHERE u_id = ? LIMIT 1");

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        return (int)($row['password_must_change'] ?? 0) === 1;
    }

    private function columnExists(string $table, string $column): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?"
        );

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('ss', $table, $column);
        $stmt->execute();

        return (bool)$stmt->get_result()->fetch_assoc();
    }

    /**
     * Handles password status processing for this API workflow.
     */
    private function passwordStatus(string $plainPassword, string $storedPassword): array
    {
        $plainPassword = (string)$plainPassword;
        $storedPassword = (string)$storedPassword;

        if ($storedPassword === '') {
            return [
                'valid' => false,
                'needs_hash_upgrade' => false
            ];
        }

        $passwordInfo = password_get_info($storedPassword);
        $isHashedPassword = (
            !empty($passwordInfo['algo']) ||
            (($passwordInfo['algoName'] ?? 'unknown') !== 'unknown')
        );

        if ($isHashedPassword) {
            return [
                'valid' => password_verify($plainPassword, $storedPassword),
                'needs_hash_upgrade' => false
            ];
        }

        return [
            'valid' => hash_equals($storedPassword, $plainPassword),
            'needs_hash_upgrade' => hash_equals($storedPassword, $plainPassword)
        ];
    }

    /**
     * Handles upgrade password hash processing for this API workflow.
     */
    private function upgradePasswordHash(int $userId, string $plainPassword): void
    {
        if ($userId <= 0) {
            return;
        }

        $hash = self::hashPassword($plainPassword);

        $stmt = $this->db->prepare("
            UPDATE s_user
            SET u_password = ?
            WHERE u_id = ?
            LIMIT 1
        ");

        if (!$stmt) {
            throw new Exception('Password hash update prepare failed: ' . $this->db->error);
        }

        $stmt->bind_param('si', $hash, $userId);
        $stmt->execute();
    }

    /**
     * Handles is locked processing for this API workflow.
     */
    private function isLocked(string $username): bool
    {
        if (!$this->loginAttemptTableExists()) {
            return false;
        }

        $sql = "
            SELECT COUNT(*) AS failed_count
            FROM login_attempts
            WHERE username = ?
              AND status = 'FAILED'
              AND attempt_time >= DATE_SUB(NOW(), INTERVAL ? MINUTE)
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            return false;
        }

        $lockMinutes = self::LOCK_MINUTES;

        $stmt->bind_param(
            'si',
            $username,
            $lockMinutes
        );

        $stmt->execute();

        $row = $stmt->get_result()->fetch_assoc();

        return ((int)($row['failed_count'] ?? 0)) >= self::MAX_FAILED_ATTEMPTS;
    }

    /**
     * Handles record attempt processing for this API workflow.
     */
    private function recordAttempt(string $username, string $status): void
    {
        if (!$this->loginAttemptTableExists()) {
            return;
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '';

        $status = strtoupper($status);

        $sql = "
            INSERT INTO login_attempts
                (
                    username,
                    ip_address,
                    attempt_time,
                    status
                )
            VALUES
                (
                    ?,
                    ?,
                    NOW(),
                    ?
                )
        ";

        try {
            $this->limitAuditLockWait();
            $stmt = $this->db->prepare($sql);

            if (!$stmt) {
                return;
            }

            $stmt->bind_param('sss', $username, $ip, $status);
            $stmt->execute();
        } catch (Throwable $e) {
            // Login auditing must never make an authenticated user wait for
            // a database lock. The next request can continue normally.
            error_log('SaQshi login attempt audit skipped: ' . $e->getMessage());
        }
    }

    /**
     * Handles clear old failed attempts processing for this API workflow.
     */
    private function clearOldFailedAttempts(string $username): void
    {
        if (!$this->loginAttemptTableExists()) {
            return;
        }

        $sql = "
            DELETE FROM login_attempts
            WHERE username = ?
              AND status = 'FAILED'
        ";

        try {
            $this->limitAuditLockWait();
            $stmt = $this->db->prepare($sql);

            if (!$stmt) {
                return;
            }

            $stmt->bind_param('s', $username);
            $stmt->execute();
        } catch (Throwable $e) {
            error_log('SaQshi failed-login cleanup skipped: ' . $e->getMessage());
        }
    }

    /**
     * Handles login attempt table exists processing for this API workflow.
     */
    private function loginAttemptTableExists(): bool
    {
        static $exists = null;

        if ($exists !== null) {
            return $exists;
        }

        $result = $this->db->query(
            "SHOW TABLES LIKE 'login_attempts'"
        );

        $exists = $result && $result->num_rows > 0;

        return $exists;
    }

    /**
     * Keep optional login-attempt audit operations from waiting for MySQL's
     * default 50-second InnoDB lock timeout during an interactive sign-in.
     */
    private function limitAuditLockWait(): void
    {
        $this->db->query('SET SESSION innodb_lock_wait_timeout = 3');
    }

    /**
     * Handles hash password processing for this API workflow.
     */
    public static function hashPassword(string $password): string
    {
        return password_hash(
            $password,
            PASSWORD_BCRYPT,
            [
                'cost' => 12
            ]
        );
    }

    /**
     * Returns human-readable failures for the password policy used by every
     * account-creation, reset and self-service password-change path.
     *
     * A single policy avoids an administrator creating a password that the
     * normal profile flow would reject (or vice versa).
     */
    public static function passwordPolicyErrors(string $password, array $disallowedValues = []): array
    {
        $errors = [];
        $length = strlen($password);

        if ($length < 12) $errors[] = 'Minimum 12 characters';
        if ($length > 128) $errors[] = 'Maximum 128 characters';
        if (preg_match('/[[:cntrl:]]/', $password)) $errors[] = 'No control characters';
        if (!preg_match('/[A-Z]/', $password)) $errors[] = 'At least one capital letter';
        if (!preg_match('/[a-z]/', $password)) $errors[] = 'At least one lower-case letter';
        if (!preg_match('/[0-9]/', $password)) $errors[] = 'At least one digit';
        if (!preg_match('/[^A-Za-z0-9\s]/', $password)) $errors[] = 'At least one special character';
        if (preg_match('/(.)\1\1/', $password)) $errors[] = 'No character repeated three times in sequence';

        $commonPasswords = ['password', 'password1', 'password123', 'welcome', 'welcome1', 'welcome123', 'admin', 'admin123', 'qwerty', 'qwerty123', 'letmein', 'india123'];
        if (in_array(strtolower($password), $commonPasswords, true)) $errors[] = 'Not a commonly used password';

        foreach ($disallowedValues as $value) {
            $value = trim((string)$value);
            if (strlen($value) >= 3 && stripos($password, $value) !== false) {
                $errors[] = 'Must not contain your username or account identifier';
                break;
            }
        }

        return $errors;
    }

    public static function passwordPolicyMessage(array $errors = []): string
    {
        return $errors
            ? 'Password does not meet policy: ' . implode('; ', $errors) . '.'
            : 'Password must be 12-128 characters and include upper-case, lower-case, number and special character.';
    }

    /**
     * Handles success processing for this API workflow.
     */
    private function success(string $message, array $data = []): array
    {
        return [
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ];
    }

    /**
     * Handles error processing for this API workflow.
     */
    private function error(string $message): array
    {
        return [
            'status' => 'error',
            'message' => $message,
            'data' => null
        ];
    }
}
