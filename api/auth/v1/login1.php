<?php

/*!
 * ==========================================================
 * SaQshi Open Source
 * Disabled Legacy Login Endpoint
 * login1.php
 * Version 1.0.0 | Updated 2026-07-13
 * ==========================================================
 */

require_once __DIR__ . '/../../public_api.php';

Security::requireMethod('POST');

Response::error(
    'This legacy login endpoint is disabled. Use /api/auth/v1/login.php with encrypted password transport.',
    null,
    410
);
