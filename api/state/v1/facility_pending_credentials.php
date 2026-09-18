<?php

/**
 * Deprecated credential-export endpoint. Passwords are never retrievable
 * after hashing and must not be derived from facility identifiers.
 */
require_once __DIR__ . '/_management_bootstrap.php';

Security::requireMethod('GET');

if (SessionManager::roleId() !== 11) {
    Response::forbidden('Facility User credentials are available only to role 11.');
}

try {
    Response::success('Temporary passwords are never stored or exported. Reset a password and deliver it through an approved secure channel.', ['rows' => [], 'count' => 0]);
} catch (Throwable $e) {
    Response::serverError($e->getMessage());
}
