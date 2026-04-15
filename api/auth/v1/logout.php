<?php
require_once "../../../service/AuthService.php";

session_start();

AuthService::logout();

echo json_encode([
    "status" => true,
    "message" => "Logged out successfully"
]);