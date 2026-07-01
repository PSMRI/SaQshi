<?php

/**
 * login.php
 * -------------------------------------------------------
 * Secure login API for SaQshi.
 *
 * Method:
 * POST
 *
 * URL:
 * /api/auth/v1/login.php
 * -------------------------------------------------------
 */

require_once __DIR__ . '/../../public_api.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/../../assets/conn/db.php';

Security::requireMethod('POST');

try {

    $request = Security::jsonInput();

    Security::requireFields($request, [
        'username',
        'password'
    ]);

    $username = Security::cleanString($request['username']);
    $password = (string)$request['password'];

    $auth = new Auth($con);

    $result = $auth->login(
        $username,
        $password
    );

    if (
        !isset($result['status']) ||
        $result['status'] !== 'success'
    ) {
        Response::error(
            $result['message'] ?? 'Invalid username or password'
        );
    }

    /*
     * IMPORTANT:
     * CSRF token is regenerated only after successful login.
     * Frontend must store this token and should not call csrf.php
     * again immediately after login.
     */
    $csrfToken = Csrf::regenerate();

    Response::success(
        'Login successful',
        [
            'user' => $result['data']['user'] ?? null,
            'csrf_token' => $csrfToken,
            'csrf' => [
                'token' => $csrfToken,
                'header_name' => 'X-CSRF-TOKEN'
            ]
        ]
    );

} catch (Throwable $e) {

    Response::serverError($e->getMessage());
}