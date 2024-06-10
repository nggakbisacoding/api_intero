<?php
function getAppointments($userId, $role) {
    global $conn;

    $sql = ($role === 'patient')
        ? "SELECT * FROM appointments WHERE patient_id = ?"
        : "SELECT * FROM appointments";

    $stmt = $conn->prepare($sql);
    if ($role === 'patient') {
        $stmt->bind_param("i", $userId);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $appointment = $result->fetch_assoc();
        header('Content-Type: application/json');
        echo json_encode($appointment);
    }
}


function handleAppointmentsRequest($method, $userId) {
    global $conn;

    if ($method == 'GET') { // Read (Get patient's appointments)
        $sql = "SELECT * FROM appointments WHERE patient_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $patient = $result->fetch_assoc();

        if ($patient) {
            header('Content-Type: application/json');
            echo json_encode($patient);
        } else {
            echo json_encode(array('error' => 'Appointments not found'));
        }

    } elseif ($method == 'POST') { // Create (Book appointment)
        $data = json_decode(file_get_contents("php://input"), true);
        $dateOfBirth = $data['date_of_birth'];
        $dateOfBirth = convertDateFormats($dateOfBirth);
    
        if ($dateOfBirth === null) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid date of birth format. Please use DD-MM-YYYY.']);
            return;
        }
        $medicalRecordId = $data['medical_record_id'];
        $doctorId = $data['doctor_id'];
        $scheduleId = $data['schedule_id'];
        header('Content-Type: application/json');

        // 1. Get patient ID based on medical record and birth date
        $patientId = getPatientIdByMedicalRecord($medicalRecordId, $dateOfBirth);

        if (!$patientId) {
            echo json_encode(['error' => 'Invalid medical record or date of birth']);
            return;
        }

        // 2. Check if the schedule is available for the doctor and time
        $checkSql = "SELECT id FROM schedules WHERE id = ? AND doctor_id = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("ii", $scheduleId, $doctorId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows == 0) {
            echo json_encode(['error' => 'Invalid schedule or doctor']);
            return;
        }

        // 3. Book the appointment
        $sql = "INSERT INTO appointments (patient_id, doctor_id, schedule_id, status) VALUES (?, ?, ?, 'pending')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $patientId, $doctorId, $scheduleId);
        
        if ($stmt->execute()) {
            $appointmentId = $stmt->insert_id;

            // 4. Create deposit (if applicable)
            // ... (Your logic to create a deposit based on appointmentId)

            echo json_encode(['success' => true, 'appointment_id' => $appointmentId]);
        } else {
            echo json_encode(['error' => 'Failed to book appointment']);
        }
    } elseif ($method == 'PUT') {  // Update (Reschedule)
        $data = json_decode(file_get_contents("php://input"), true);
        $appointmentId = $data['appointment_id'];
        $newScheduleId = $data['schedule_id'];
        $newAppointmentTime = $data['appointment_time'];
        header('Content-Type: application/json');

        // 1. Verify user owns the appointment
        $sql = "SELECT * FROM appointments WHERE id = ? AND patient_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $appointmentId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            echo json_encode(['error' => 'Appointment not found or not owned by user']);
            return;
        }

        // 2. Check if the new schedule is available
        $sql = "SELECT id FROM schedules WHERE id = ? AND doctor_id = (SELECT doctor_id FROM appointments WHERE id = ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $newScheduleId, $appointmentId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            echo json_encode(['error' => 'Invalid new schedule or doctor']);
            return;
        }

        // 3. Update the appointment
        $sql = "UPDATE appointments SET schedule_id = ?, appointment_time = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isi", $newScheduleId, $newAppointmentTime, $appointmentId);
        if ($stmt->execute()) {
            echo json_encode(['message' => 'Appointment rescheduled successfully']);
        } else {
            echo json_encode(['error' => 'Failed to reschedule appointment']);
        }
    } elseif ($method == 'DELETE') {  // Delete (Cancel)
        $data = json_decode(file_get_contents("php://input"), true);
        $appointmentId = $data['appointment_id'];
        
        // 1. Check if user owns the appointment & if deposit is "paid"
        $sql = "SELECT d.status
                FROM appointments a
                JOIN deposits d ON a.id = d.appointment_id
                WHERE a.id = ? AND a.patient_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $appointmentId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            echo json_encode(['error' => 'Appointment not found or not owned by user']);
            return;
        }

        $depositStatus = $result->fetch_assoc()['status'];

        // 2. If deposit is paid, ask for confirmation (Implement on the frontend)
        // ... (You'll need to handle this confirmation logic in your frontend)

        // 3. Delete the appointment and forfeit the deposit (if confirmed)
        $sql = "DELETE FROM appointments WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $appointmentId);

        if ($stmt->execute()) {
            if ($depositStatus == 'paid') {
                // Forfeit deposit
                $forfeitSql = "UPDATE deposits SET status = 'forfeited' WHERE appointment_id = ?";
                $forfeitStmt = $conn->prepare($forfeitSql);
                $forfeitStmt->bind_param("i", $appointmentId);
                $forfeitStmt->execute();
            }
            echo json_encode(['message' => 'Appointment cancelled successfully']);
        } else {
            echo json_encode(['error' => 'Failed to cancel appointment']);
        }
    }
}

function getAppointmentHistory() {
    global $conn;

    $sql = "SELECT mr.medical_record_id AS medical_record_number,
       u.name AS patient_name,
       d.name AS doctor_name,
       DATE(ah.appointment_time) AS reservation_date, -- Extract the date portion
       TIME(ah.appointment_time) AS reservation_time, -- Extract the time portion
       ah.status AS reservation_status
FROM appointment_history ah
JOIN users u ON ah.patient_id = u.id
JOIN doctors d ON ah.doctor_id = d.user_id
JOIN medic_record mr ON ah.patient_id = mr.id
ORDER BY ah.appointment_time DESC";
    header('Content-Type: application/json');
    $stmt = $conn->query($sql);
    if ($stmt->num_rows > 0) {
        $schedules = array();
        while ($row = $stmt->fetch_assoc()) {
            $schedules[] = $row;
        }
        echo json_encode($schedules, JSON_PRETTY_PRINT);

    } else {
        echo json_encode(array('error' => 'Appointments not found'));
    }
}

function getPatientIdByMedicalRecord($medicalRecordId, $dateOfBirth) {
    global $conn;

    $sql = "SELECT id FROM medic_record WHERE medical_record_id = ? AND date_of_birth = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $medicalRecordId, $dateOfBirth);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    return $row ? $row['id'] : null;
}

function getAllAppointments() {
    global $conn;

    $sql = "SELECT m.medical_record_id AS medical_record_number,
    u.name AS patient_name,
    d.name AS doctor_name,
    a.status AS queue_status,
    a.appointment_time AS reservation_time
from appointments a
join users u on a.patient_id = u.id
join doctors d on a.doctor_id = d.user_id
join medic_record m on a.patient_id = m.id";
    
    header('Content-Type: application/json');
    $stmt = $conn->query($sql);
    if ($stmt->num_rows > 0) {
        $schedules = array();
        while ($row = $stmt->fetch_assoc()) {
            $schedules[] = $row;
        }
        echo json_encode($schedules, JSON_PRETTY_PRINT);

    } else {
        echo json_encode(array('error' => 'Appointments not found'));
    }
}

function convertDateFormats($dateString) {
    $timestamp = strtotime($dateString); 
    if ($timestamp === false) {
        return null; // Invalid date format
    }
    return date('Y-m-d', $timestamp);
}


