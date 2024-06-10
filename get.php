<?php
require_once 'vendor/autoload.php';
require_once 'auth_middleware.php';
require_once 'database.php';

use \Firebase\JWT\JWT;

global $conn;
$sql = "SELECT * FROM users WHERE email = 'emilydavis@example.com'";
$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$payload = [
    'user_id' => $user['id'],
    'role' => $user['role'],
    'exp' => time() + 3600 // Token expires in 1 hour
];
$jwt = JWT::encode($payload, 'UGM_JAYA', 'HS256');
print($jwt);
