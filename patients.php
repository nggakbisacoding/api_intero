<?php
function getPatientProfile($userId) {
    global $conn;

    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $patient = $result->fetch_assoc();

    if ($patient) {
        echo json_encode($patient);
    } else {
        echo json_encode(array('error' => 'Patient not found'));
    }
}

function updatePatientProfile($userId) {
    global $conn;
    $data = json_decode(file_get_contents("php://input"), true);

    // Validate and sanitize input data here (e.g., check for required fields)
    
    $sql = "UPDATE users SET name=?, email=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $data['name'], $data['email'], $userId);
    if ($stmt->execute()) {
        echo json_encode(array('message' => 'Profile updated successfully'));
    } else {
        echo json_encode(array('error' => 'Failed to update profile'));
    }
}

function handlePatientsRequest($method, $userId) {
    global $conn;

    if ($method == 'GET') { // Read (Get patient's own profile)
        $sql = "SELECT * FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $patient = $result->fetch_assoc();
        echo json_encode($patient);

    } elseif ($method == 'PUT') { // Update (Update patient's own profile)
        $data = json_decode(file_get_contents("php://input"), true);

        $sql = "UPDATE users SET name=?, email=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $data['name'], $data['email'], $userId);
        if ($stmt->execute()) {
            echo json_encode(array('message' => 'Profile updated successfully'));
        } else {
            echo json_encode(array('error' => 'Failed to update profile'));
        }
    }
}
