<?php

// ---------------------------------------------------------------------
// Bestar-one Clinic Appointment System - Front Controller & API Router
// ---------------------------------------------------------------------

declare(strict_types=1);

// PSR-4 Autoloader
spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../src/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Controllers\PatientController;
use App\Controllers\AppointmentController;
use App\Controllers\ReportController;

$requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// ---------------------------------------------------------------------
// API Routes
// ---------------------------------------------------------------------

// 1. Patients API
if ($requestUri === '/api/patients') {
    if ($requestMethod === 'GET') {
        PatientController::index();
    } elseif ($requestMethod === 'POST') {
        PatientController::store();
    }
}

if (preg_match('#^/api/patients/(\d+)$#', $requestUri, $matches)) {
    $patientId = (int)$matches[1];
    if ($requestMethod === 'GET') {
        PatientController::show($patientId);
    } elseif ($requestMethod === 'PUT' || $requestMethod === 'POST') {
        PatientController::update($patientId);
    }
}

// 2. Appointments API
if ($requestUri === '/api/appointments') {
    if ($requestMethod === 'GET') {
        AppointmentController::index();
    } elseif ($requestMethod === 'POST') {
        AppointmentController::store();
    }
}

if (preg_match('#^/api/appointments/(\d+)/status$#', $requestUri, $matches)) {
    $appointmentId = (int)$matches[1];
    if ($requestMethod === 'PATCH' || $requestMethod === 'POST') {
        AppointmentController::updateStatus($appointmentId);
    }
}

if ($requestUri === '/api/appointments/check-availability') {
    AppointmentController::checkAvailability();
}

if ($requestUri === '/api/doctors') {
    AppointmentController::doctors();
}

// 3. Reports API
if ($requestUri === '/api/reports/summary') {
    ReportController::summary();
}

// ---------------------------------------------------------------------
// Frontend Main Application View
// ---------------------------------------------------------------------
if ($requestUri === '/' || $requestUri === '/index.php' || !str_starts_with($requestUri, '/api/')) {
    require_once __DIR__ . '/../src/Views/layout.php';
    exit;
}

// 404 Not Found for unhandled API endpoints
http_response_code(404);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['success' => false, 'error' => 'API endpoint not found'], JSON_UNESCAPED_UNICODE);
exit;
