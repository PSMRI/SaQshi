<?php

/*!
 * ==========================================================
 * SaQshi Open Source
 * Legacy Database Connection
 * db.php
 * Version 1.0.0 | Updated 2026-07-10
 * ==========================================================
 */

require_once dirname(__DIR__, 2) . '/api/core/Env.php';

if (is_file(dirname(__DIR__, 2) . '/api/core/ErrorHandler.php')) {
    require_once dirname(__DIR__, 2) . '/api/core/ErrorHandler.php';
}

Env::load(dirname(__DIR__, 2) . '/.env');

$dbHost = Env::get('DB_HOST');
$dbPort = Env::get('DB_PORT', '3306');
$dbName = Env::get('DB_DATABASE');
$dbUser = Env::get('DB_USERNAME');
$dbPass = Env::get('DB_PASSWORD');
$dbTimeout = max(1, (int) Env::get('DB_CONNECT_TIMEOUT', '5'));

if (!$dbHost || !$dbName || !$dbUser || $dbPass === null) {
    if (class_exists('ErrorHandler')) {
        ErrorHandler::log('Legacy database environment configuration missing', [
            'required' => ['DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD']
        ]);
        ErrorHandler::sendFriendly(503);
    }

    die('Service configuration is missing. Please contact support.');
}

$con = mysqli_init();

if ($con) {
    mysqli_options($con, MYSQLI_OPT_CONNECT_TIMEOUT, $dbTimeout);
}

$connected = $con && @mysqli_real_connect($con, $dbHost, $dbUser, $dbPass, $dbName, (int)$dbPort);

if (!$connected) {
    if (class_exists('ErrorHandler')) {
        ErrorHandler::log('Legacy database connection failed', ['db_error' => mysqli_connect_error()]);
        ErrorHandler::sendFriendly(503);
    }

    die('Service is temporarily unavailable. Please try again after some time.');
}

mysqli_set_charset($con, 'utf8mb4');
