<?php
function registerUser($name, $medicalRecordId, $dateOfBirth, $gender, $citizenshipNumber, $callNumber, $bloodType) {
    // ... Error Handling and Input validation ...

    $sql = "INSERT INTO users 
            (name, medical_record_id, date_of_birth, gender, citizenship_number, call_number, blood_type, role) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'patient')";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssiss", $name, $medicalRecordId, $dateOfBirth, $gender, $citizenshipNumber, $callNumber, $bloodType, $role);
    
    if ($stmt->execute()) {
        $userId = $stmt->insert_id;

        $payload = [
            'name' => $name,
            'user_id' => $medicalRecordId,
            'role' => $user['user'],
            'exp' => time() + 3600 * 24 // Token expires in 1 day
        ];
        $jwt = JWT::encode($payload, 'UGM_JAYA', 'HS256');

        return $jwt;
    } else {
        return null;
    }
}

function authenticateUser($medicalRecordId, $dateOfBirth) {
    if ($method == 'POST') { // Create (Book appointment)
        $data = json_decode(file_get_contents("php://input"), true);

        // Get patient ID based on medical record and birth date
        $patientId = getPatientIdByMedicalRecord($data['medical_record_id'], $data['date_of_birth']); 

        if (!$patientId) {
            echo json_encode(['error' => 'Invalid medical record or date of birth']);
            return;
        }

        // ... rest of appointment booking logic using $patientId
    }
}

function getPatientIdByMedicalRecord($medicalRecordId, $dateOfBirth) {
    global $conn;

    $sql = "SELECT id FROM users WHERE medical_record_id = ? AND date_of_birth = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $medicalRecordId, $dateOfBirth);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    return $row ? $row['id'] : null; // Return patient ID or null if not found
}