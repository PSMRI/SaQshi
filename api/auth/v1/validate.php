<?php

/**
 * SaQshi API
 * auth/v1/validate.php
 * Purpose: validate endpoint/support workflow.
 */

require_once __DIR__ . '/../../auth_api.php';

Security::requireMethod('GET');

Response::success('Session is valid', SessionManager::user());
