<?php

/**
 * SaQshi API
 * assets/conn/session.php
 * Purpose: session endpoint/support workflow.
 */

require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once __DIR__ . '/db.php';

// Legacy include compatibility. Do not call PHP's native session_start();
// SessionManager owns the cookie name, Redis store and timeout policy.
SessionManager::requireLogin();

$user_check = SessionManager::username();
$login_session = $user_check;
