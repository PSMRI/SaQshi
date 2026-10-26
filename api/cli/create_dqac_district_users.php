<?php

declare(strict_types=1);

require_once __DIR__ . '/../assets/conn/db.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Crypto.php';

$execute = in_array('--execute', $argv ?? [], true);
$temporaryPassword = '12345';

$role = $con->query("SELECT role_name FROM u_role WHERE role_id = 4 AND role_status = 1 LIMIT 1")->fetch_assoc();
if (!$role) {
    throw new RuntimeException('Active role_id_fk = 4 was not found.');
}

$districts = $con->query("SELECT dist_id, MAX(state_id) AS state_id, MAX(division_id) AS division_id, MAX(Dist_Name) AS district_name FROM facilities WHERE dist_id IS NOT NULL AND dist_id > 0 GROUP BY dist_id ORDER BY district_name");
if (!$districts) {
    throw new RuntimeException($con->error);
}

$insert = $con->prepare('INSERT INTO s_user (u_name, u_password, role_id_fk, is_active, f_name, l_name, user_type, state_id, division_id, dist_id, password_must_change, created_by, updated_by) VALUES (?, ?, 4, 1, ?, ?, ?, ?, ?, ?, 1, NULL, NULL)');
if (!$insert) {
    throw new RuntimeException($con->error);
}

$created = [];
$skipped = [];
while ($district = $districts->fetch_assoc()) {
    $districtId = (int)$district['dist_id'];
    $districtName = trim((string)$district['district_name']);
    $usernameSuffix = strtolower((string)preg_replace('/[^a-z0-9]+/i', '', $districtName));
    $username = 'daq@' . $usernameSuffix;

    $existing = $con->prepare('SELECT u_id, u_name FROM s_user WHERE role_id_fk = 4 AND dist_id = ? LIMIT 1');
    $existing->bind_param('i', $districtId);
    $existing->execute();
    if ($row = $existing->get_result()->fetch_assoc()) {
        $skipped[] = "{$districtId}|{$districtName}|existing district user {$row['u_name']}";
        continue;
    }
    $existingUsername = $con->prepare('SELECT u_id FROM s_user WHERE u_name = ? LIMIT 1');
    $existingUsername->bind_param('s', $username);
    $existingUsername->execute();
    if ($existingUsername->get_result()->fetch_assoc()) {
        $skipped[] = "{$districtId}|{$districtName}|username {$username} already exists";
        continue;
    }

    if (!$execute) {
        $created[] = "{$districtId}|{$districtName}|{$username}";
        continue;
    }

    $hash = Auth::hashPassword($temporaryPassword);
    $firstName = Crypto::encrypt('DQAC');
    $lastName = Crypto::encrypt($districtName);
    $userType = (string)$role['role_name'];
    $stateId = (int)$district['state_id'];
    $divisionId = (int)$district['division_id'];
    $insert->bind_param('sssssiii', $username, $hash, $firstName, $lastName, $userType, $stateId, $divisionId, $districtId);
    if (!$insert->execute()) {
        throw new RuntimeException("Unable to create {$username}: {$insert->error}");
    }
    $created[] = "{$districtId}|{$districtName}|{$username}|user_id={$con->insert_id}";
}

echo 'mode=' . ($execute ? 'execute' : 'dry-run') . PHP_EOL;
echo 'created_or_ready=' . count($created) . PHP_EOL;
echo 'skipped=' . count($skipped) . PHP_EOL;
foreach ($created as $line) echo "CREATED|{$line}" . PHP_EOL;
foreach ($skipped as $line) echo "SKIPPED|{$line}" . PHP_EOL;
