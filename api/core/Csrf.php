<?php

/**
 * Csrf.php
 * -------------------------------------------------------
 * CSRF Protection for SaQshi APIs
 *
 * Features:
 * - Token generation
 * - Token validation
 * - Token regeneration
 * - Header based validation
 *
 * Client must send:
 *
 * X-CSRF-TOKEN: token
 *
 * OR
 *
 * csrf_token in POST body
 * -------------------------------------------------------
 */

class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    /**
     * Generate token if not exists.
     */
    public static function generate(): string
    {
        SessionManager::start();

        if (
            !isset($_SESSION[self::SESSION_KEY]) ||
            empty($_SESSION[self::SESSION_KEY])
        ) {
            $_SESSION[self::SESSION_KEY] = bin2hex(
                random_bytes(32)
            );
        }

        return $_SESSION[self::SESSION_KEY];
    }

    /**
     * Get current token.
     */
    public static function token(): string
    {
        return self::generate();
    }

    /**
     * Regenerate token.
     */
    public static function regenerate(): string
    {
        SessionManager::start();

        $_SESSION[self::SESSION_KEY] = bin2hex(
            random_bytes(32)
        );

        return $_SESSION[self::SESSION_KEY];
    }

    /**
     * Validate token.
     */
    public static function validate(): void
    {
        SessionManager::start();

        $sessionToken = $_SESSION[self::SESSION_KEY] ?? '';

        if ($sessionToken === '') {
            self::fail('CSRF token missing from session');
        }

        $requestToken = self::extractRequestToken();

        if ($requestToken === '') {
            self::fail('CSRF token missing');
        }

        if (!hash_equals($sessionToken, $requestToken)) {
            self::fail('Invalid CSRF token');
        }
    }

    /**
     * Return token information.
     */
    public static function getTokenInfo(): array
    {
        return [
            'csrf_token' => self::token(),
            'header_name' => 'X-CSRF-TOKEN'
        ];
    }

    /**
     * Extract token from request.
     */
    private static function extractRequestToken(): string
    {
        /*
         * Header
         */
        $headers = function_exists('getallheaders')
            ? getallheaders()
            : [];

        foreach ($headers as $key => $value) {

            if (
                strtolower($key) === 'x-csrf-token'
            ) {
                return trim((string)$value);
            }
        }

        /*
         * POST
         */
        if (isset($_POST['csrf_token'])) {
            return trim((string)$_POST['csrf_token']);
        }

        /*
         * JSON body
         */
        $raw = file_get_contents('php://input');

        if (!empty($raw)) {

            $json = json_decode($raw, true);

            if (
                is_array($json)
                && isset($json['csrf_token'])
            ) {
                return trim((string)$json['csrf_token']);
            }
        }

        return '';
    }

    /**
     * Destroy token.
     */
    public static function destroy(): void
    {
        SessionManager::start();

        unset($_SESSION[self::SESSION_KEY]);
    }

    /**
     * JSON error.
     */
    private static function fail(string $message): never
    {
        http_response_code(403);

        echo json_encode([
            'status' => 'error',
            'message' => $message,
            'data' => null,
            'errors' => null,
            'timestamp' => date('Y-m-d H:i:s')
        ]);

        exit;
    }
}