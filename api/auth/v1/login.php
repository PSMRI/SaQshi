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
 *
 * Body:
 * {
 *   "username": "admin",
 *   "password": "password"
 * }
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

    if ($result['status'] !== 'success') {
        Response::error($result['message']);
    }

    /*
     * Generate CSRF token after login
     */
    $csrfToken = Csrf::regenerate();

    Response::success(
        'Login successful',
        [
            'user' => $result['data']['user'],
            'csrf_token' => $csrfToken
        ]
    );

} catch (Throwable $e) {

    Response::serverError($e->getMessage());
}