<?php
require_once "../../../core/db.php";
require_once "../../../service/AuthService.php";

session_start();

$data = json_decode(file_get_contents("php://input"), true);

$username = $data['username'] ?? '';
$password = $data['password'] ?? '';

$response = AuthService::login($conn, $username, $password);

echo json_encode($response);