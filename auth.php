<?php
require_once 'vendor/autoload.php';
require_once 'auth_middleware.php';

use \Firebase\JWT\JWT;

function authenticateUser($email, $password) {
    global $conn;

    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && $user['password_hash'] === $password) {
        $payload = [
            'user_id' => $user['id'],
            'role' => $user['role'],
            'exp' => time() + 3600 * 24 // Token expires in 1 day
        ];
        $jwt = JWT::encode($payload, 'UGM_JAYA', 'HS256');

        return $jwt;
    } else {
        return null; // Authentication failed
    }
}

function registerUser($name, $dateOfBirth, $gender, $citizenshipNumber, $callNumber = null, $bloodType = null) {
    global $conn;

    // 1. Hash the password (use password_hash)
    $medicalRecordId = generateUniqueMedicalRecordId();

    $dateOfBirth = convertDateFormat($dateOfBirth);
    
    if ($dateOfBirth === null) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid date of birth format. Please use DD-MM-YYYY.']);
        return;
    }

    // 2. Insert user data into database
    $sql = "INSERT INTO medic_record (name, medical_record_id, date_of_birth, gender, citizenship_number, call_number, blood_type, role) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'patient')";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssss", $name, $medicalRecordId, $dateOfBirth, $gender, $citizenshipNumber, $callNumber, $bloodType);
    if ($stmt->execute()) {
        $sql = "INSERT INTO users (name, email, password_hash, role) 
            VALUES (?, ?, ?, 'patient')";
        $stmt = $conn->prepare($sql);
        $namestr = explode(" ", $name);
        $email = implode("",$namestr)."@example.com";
        $pass = "123";
        $stmt->bind_param("sss", $name, $email, $pass);
        if($stmt->execute()) {
            return $medicalRecordId;
        }
    } else {
        return null; // Registration failed
    }
}

function generateUniqueMedicalRecordId() {
    global $conn;

    while (true) {
        // Generate a random 5-digit number
        $randomDigits = str_pad(rand(0, 99999), 5, '0', STR_PAD_LEFT);
        $medicalRecordId = 'MR' . $randomDigits;

        // Check if the generated ID already exists in the database
        $sql = "SELECT id FROM medic_record WHERE medical_record_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $medicalRecordId);
        $stmt->execute();
        $stmt->store_result(); 

        if ($stmt->num_rows == 0) {
            // The ID is unique, break out of the loop
            break;
        }

        // If the ID exists, try generating another one
    }

    return $medicalRecordId;
}

function handleAuthRequest($method) {
    global $conn;

    if ($method == 'POST' && isset($_GET['action'])) {
        $action = $_GET['action'];
        $data = json_decode(file_get_contents("php://input"), true);

        // Check if decoding was successful
        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400); // Bad Request
            echo json_encode(['error' => 'Invalid JSON data']);
            return;
        }
        switch ($action) {
            case 'login':
                // Get login credentials from the request body
                $email = $data['email'];
                $password = $data['password'];

                // Authenticate user
                $jwt = authenticateUser($email, $password);
                if ($jwt) {
                    echo json_encode(['token' => $jwt]);
                } else {
                    http_response_code(401); // Unauthorized
                    echo json_encode(['error' => 'Invalid credentials']);
                }
                break;

            case 'register':
                $name = $data['name'];
                $dateOfBirth = $data['date_of_birth'];
                $gender = $data['gender'];
                $citizenshipNumber = $data['citizenship_number'];
                $callNumber = $data['call_number'] ?? null; // Use null if not provided
                $bloodType = $data['blood_type'] ?? null;

                // Register the user
                header('Content-Type: application/json');
                $result = registerUser($name, $dateOfBirth, $gender, $citizenshipNumber, $callNumber, $bloodType);
                if ($result) {
                    echo json_encode([
                        'token' => "Unknown",
                        'medical_record_id' => $result
                    ]);
                } 
                break;       
            }
    }
}

function convertDateFormat($dateString) {
    $timestamp = strtotime($dateString); 
    if ($timestamp === false) {
        return null; // Invalid date format
    }
    return date('Y-m-d', $timestamp);
}


