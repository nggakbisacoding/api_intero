<?php

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

// Check if the request method is GET
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    getAllDoctors();
} else {
    // Handle other request methods if needed (e.g., POST, PUT, DELETE)
    http_response_code(405); // Method Not Allowed
    echo json_encode(array('error' => 'Method not allowed'));
}
