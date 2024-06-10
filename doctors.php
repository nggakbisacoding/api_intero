<?php

function getDoctorProfile($userId) {
    global $conn;

    // 1. Fetch doctor data using the userId
    $sql = "SELECT u.id, u.name, u.email, d.specialization, d.qualifications, d.experience, d.rating 
            FROM doctors d
            JOIN users u ON d.user_id = u.id
            WHERE u.id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $doctor = $result->fetch_assoc();
        echo json_encode($doctor);
    } else {
        http_response_code(404); // Not Found
        echo json_encode(array('error' => 'Doctor not found'));
    }
}

function updateDoctorProfile($userId) {
    global $conn;
    $data = json_decode(file_get_contents("php://input"), true);

    // 1. Validate and sanitize input data here (e.g., check for required fields)
    // Make sure to handle potential issues like empty fields or invalid data

    // 2. Update the doctor's profile in the 'doctors' table
    $sql = "UPDATE doctors SET specialization=?, qualifications=?, experience=?, rating=? WHERE user_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssidi", 
        $data['specialization'], 
        $data['qualifications'], 
        $data['experience'], 
        $data['rating'], 
        $userId
    );

    if ($stmt->execute()) {
        echo json_encode(array('message' => 'Doctor profile updated successfully'));
    } else {
        http_response_code(500); // Internal Server Error
        echo json_encode(array('error' => 'Failed to update doctor profile'));
    }
}

function getDoctorSchedules($userId) {
    global $conn;

    // 1. Fetch schedules for the specific doctor
    $sql = "SELECT * FROM schedules WHERE doctor_id = (SELECT id FROM doctors WHERE user_id = ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $schedules = array();
        while ($row = $result->fetch_assoc()) {
            $schedules[] = $row;
        }
        echo json_encode($schedules);
    } else {
        http_response_code(404); // Not Found
        echo json_encode(array('message' => 'No schedules found for this doctor'));
    }
}

function handleDoctorsRequest($method, $userId) {
    switch ($method) {
        case 'POST':
            if (isset($_GET['id'])) {
                $doctorId = $_GET['id'];
                getDoctorProfileAndSchedule($doctorId);
            } else {
                getDoctorProfile($userId);
            }
            break;
        case 'PUT':
            updateDoctorProfile($userId);
            break;
        // ... add more methods if needed (e.g., POST, PUT, DELETE for schedules)
        default:
            getAllDoctors();
    }
}

function getAllDoctors() {
    global $conn;

    $sql = "SELECT u.id, u.name, u.email, d.specialization, d.qualifications, d.experience, d.rating
            FROM doctors d
            JOIN users u ON d.user_id = u.id";

    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $doctors = array();
        while ($row = $result->fetch_assoc()) {
            $doctors[] = $row;
        }
        // Return doctors as JSON
        header('Content-Type: application/json');
        echo json_encode($doctors, JSON_PRETTY_PRINT);
    } else {
        // No doctors found
        http_response_code(404); // Not Found
        echo json_encode(array('message' => 'No doctors found'));
    }
}

function getDoctorProfileAndSchedule($doctorId) {
    global $conn;

    // 1. Fetch doctor profile data
    $profileSql = "SELECT u.id, u.name, u.email, d.specialization, d.qualifications, d.experience, d.rating
                   FROM doctors d
                   JOIN users u ON d.user_id = u.id
                   WHERE d.id = ?";

    $profileStmt = $conn->prepare($profileSql);
    $profileStmt->bind_param("i", $doctorId);
    $profileStmt->execute();
    $profileResult = $profileStmt->get_result();

    if ($profileResult->num_rows == 0) {
        http_response_code(404); // Not Found
        echo json_encode(array('error' => 'Doctor not found'));
        return;
    }
    $doctor = $profileResult->fetch_assoc();

    // 2. Fetch doctor schedule data (only upcoming schedules)
    $scheduleSql = "SELECT * FROM schedules WHERE doctor_id = ? AND day_of_week >= DAYNAME(NOW())";
    $scheduleStmt = $conn->prepare($scheduleSql);
    $scheduleStmt->bind_param("i", $doctorId);
    $scheduleStmt->execute();
    $scheduleResult = $scheduleStmt->get_result();

    $schedules = array();
    while ($row = $scheduleResult->fetch_assoc()) {
        $schedules[] = $row;
    }

    // 3. Combine profile and schedule data
    $doctor['schedules'] = $schedules;
    echo json_encode($doctor);
}

function getAllSchedules() {
    global $conn;

    $sql = "SELECT s.id, u.name AS doctor_name, d.specialization, s.day_of_week , DATE_ADD(CURDATE(), INTERVAL (
           CASE s.day_of_week 
               WHEN 'Sunday' THEN 7
               WHEN 'Monday' THEN 1
               WHEN 'Tuesday' THEN 2
               WHEN 'Wednesday' THEN 3
               WHEN 'Thursday' THEN 4
               WHEN 'Friday' THEN 5
               WHEN 'Saturday' THEN 6
           END - DAYOFWEEK(CURDATE()) + 7) % 7
       DAY) AS upcoming_available, s.start_time, s.end_time
    FROM schedules s
    INNER JOIN doctors d ON s.doctor_id = d.user_id
    INNER JOIN users u ON d.user_id = u.id
            WHERE s.day_of_week >= DAYNAME(CURDATE())"; // Filter for upcoming schedules

    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $schedules = array();
        while ($row = $result->fetch_assoc()) {
            $schedules[] = $row;
        }

        header('Content-Type: application/json');
        echo json_encode($schedules, JSON_PRETTY_PRINT);
    } else {
        http_response_code(404); // Not Found
        echo json_encode(array('message' => 'No schedules found'));
    }
}