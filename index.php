<?php
require_once 'database.php';
require_once 'auth.php';
require_once 'auth_middleware.php'; // Include the middleware

// Handle API requests based on the endpoint and method
$endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : null;

// Additional check for 'action' parameter when endpoint is 'auth'
$action = isset($_GET['action']) ? $_GET['action'] : null;
if (!$endpoint ||
    (!in_array($endpoint, ['allappointments','allschedules','appointment_history', 'doctors', 'appointments', 'patients']) 
    && (!in_array($endpoint, ['auth', 'public', 'medic']))) ||
    ($endpoint === 'auth' && !in_array($action, ['login', 'register'])) // Assuming register is also public
   ) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid endpoint or action']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

$requiresAuth = (!in_array($endpoint, ['allappointments','allschedules', 'appointment_history', 'auth', 'public', 'medic', 'doctors', 'appointments', 'patients'])); // Authentication required except for auth/login

// Authenticate if needed
$userId = null;
if ($requiresAuth) {
    $userId = validateJWT();
    echo($userId->role);
    if (!$userId) {
        exit;
    }
}
 
switch ($endpoint) {
    case 'doctors':
        require_once 'doctors.php';
        handleDoctorsRequest($method, $userId); // No $userId needed for public endpoints
        break;

    case 'appointments':
        require_once 'appointments.php';
        handleAppointmentsRequest($method, $userId);
        break;
    case 'patients':
        require_once 'patients.php';
        handlePatientsRequest($method, $userId);
        break;
    case 'allschedules':
        require_once 'doctors.php';
        getAllSchedules();
        break;
    case 'allappointments':
        require_once 'appointments.php';
        getAllAppointments();
        break;
    case 'auth':
        require_once 'auth.php';
        handleAuthRequest($method);
        break;
    case 'appointment_history':        
        require_once 'appointments.php';
        getAppointmentHistory(); 
        break;
    case 'public':
        require_once 'public.php';
        break;
    case 'medic_record':
        require_once 'medic.php';
        break;
    default:
        exit;
        break;
}

?>
