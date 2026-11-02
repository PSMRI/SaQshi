<?php

declare(strict_types=1);

/**
 * Creates one Division User for every division represented in facilities.
 *
 * Usage:
 *   php api/cli/create_division_users.php             # preview only
 *   php api/cli/create_division_users.php --execute   # create users
 *
 * Usernames use the format div@<normalized-division-name>.  For example,
 * "North Division" becomes "div@northdivision".  The initial password is
 * Saqshi@20266 and is intentionally not marked for a mandatory first-login
 * password change.
 */

require_once __DIR__ . '/../assets/conn/db.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Crypto.php';

$execute = in_array('--execute', $argv ?? [], true);
$defaultPassword = 'Saqshi@20266';

$role = $con->query("SELECT role_name FROM u_role WHERE role_id = 5 AND role_status = 1 LIMIT 1")->fetch_assoc();
if (!$role) {
    throw new RuntimeException('Active role_id_fk = 5 (Division User) was not found.');
}

$divisions = $con->query(
    "SELECT division_id, MAX(state_id) AS state_id, MAX(division) AS division_name
     FROM facilities
     WHERE division_id IS NOT NULL AND division_id > 0
       AND TRIM(COALESCE(division, '')) <> ''
     GROUP BY division_id
     ORDER BY division_name"
);
if (!$divisions) {
    throw new RuntimeException($con->error);
}

$existingDivision = $con->prepare('SELECT u_id, u_name FROM s_user WHERE role_id_fk = 5 AND division_id = ? LIMIT 1');
$existingUsername = $con->prepare('SELECT u_id FROM s_user WHERE u_name = ? LIMIT 1');
$insert = $con->prepare(
    'INSERT INTO s_user (u_name, u_password, role_id_fk, is_active, f_name, l_name, user_type, state_id, division_id, password_must_change, password_changed_on, created_by, updated_by)
     VALUES (?, ?, 5, 1, ?, ?, ?, ?, ?, 0, CURRENT_TIMESTAMP, NULL, NULL)'
);
if (!$existingDivision || !$existingUsername || !$insert) {
    throw new RuntimeException($con->error);
}

$created = [];
$skipped = [];
while ($division = $divisions->fetch_assoc()) {
    $divisionId = (int)$division['division_id'];
    $divisionName = trim((string)$division['division_name']);
    $usernameSuffix = strtolower((string)preg_replace('/[^a-z0-9]+/i', '', $divisionName));
    if ($usernameSuffix === '') {
        $skipped[] = "{$divisionId}|{$divisionName}|division name cannot form a username";
        continue;
    }
    $username = 'div@' . $usernameSuffix;

    $existingDivision->bind_param('i', $divisionId);
    $existingDivision->execute();
    if ($row = $existingDivision->get_result()->fetch_assoc()) {
        $skipped[] = "{$divisionId}|{$divisionName}|existing division user {$row['u_name']}";
        continue;
    }

    $existingUsername->bind_param('s', $username);
    $existingUsername->execute();
    if ($existingUsername->get_result()->fetch_assoc()) {
        $skipped[] = "{$divisionId}|{$divisionName}|username {$username} already exists";
        continue;
    }

    if (!$execute) {
        $created[] = "{$divisionId}|{$divisionName}|{$username}";
        continue;
    }

    $hash = Auth::hashPassword($defaultPassword);
    $firstName = Crypto::encrypt('Division');
    $lastName = Crypto::encrypt($divisionName);
    $userType = (string)$role['role_name'];
    $stateId = (int)$division['state_id'];
    $insert->bind_param('sssssii', $username, $hash, $firstName, $lastName, $userType, $stateId, $divisionId);
    if (!$insert->execute()) {
        throw new RuntimeException("Unable to create {$username}: {$insert->error}");
    }
    $created[] = "{$divisionId}|{$divisionName}|{$username}|user_id={$con->insert_id}";
}

echo 'mode=' . ($execute ? 'execute' : 'dry-run') . PHP_EOL;
echo 'created_or_ready=' . count($created) . PHP_EOL;
echo 'skipped=' . count($skipped) . PHP_EOL;
foreach ($created as $line) echo "CREATED|{$line}" . PHP_EOL;
foreach ($skipped as $line) echo "SKIPPED|{$line}" . PHP_EOL;
