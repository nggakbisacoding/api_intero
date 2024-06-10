<?php
require_once 'vendor/autoload.php';

use \Firebase\JWT\JWT;
use Firebase\JWT\Key;

function validateJWT() {
    $headers = apache_request_headers();
    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : null;

    if ($authHeader) {
        list($jwt) = sscanf($authHeader, 'Bearer %s');
        
        try {
            // Decode the token and get the payload as an object
            $decoded = JWT::decode($jwt, new Key('UGM_JAYA', 'HS256'));
            // Access payload data as properties of the object
            if (!in_array($decoded->role, ['patient', 'doctor'])) {
                http_response_code(403); // Forbidden
                echo json_encode(['error' => 'Access denied']);
                exit;
            }
            
            return $decoded->user_id;
        } catch (Exception $e) {
            http_response_code(401); // Unauthorized
            echo json_encode(['error' => 'Invalid token']);
            exit;
        }
    } else {
        http_response_code(401); // Unauthorized
        echo json_encode(['error' => 'Missing token']);
        exit;
    }
}
