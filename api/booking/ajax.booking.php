<?php
// =====================================================================
// api/booking/ajax.php - AJAX Endpoint ของหน้า page/booking.php
// actions: search_patients, check_conflict, save_appointment
// =====================================================================

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/class.booking.php';
require_once __DIR__ . '/../patients/class.patient.php';


$booking = new ClassBooking();
$patient = new ClassPatient();

// รับข้อมูล JSON payload เท่านั้น (ไม่รับ formdata)
$rawInput = file_get_contents('php://input');
$input = !empty($rawInput) ? json_decode($rawInput, true) : [];
if (!empty($rawInput) && json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON payload'], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = $_GET['action'] ?? $input['action'] ?? '';

try {
    switch ($action) {
        case 'search_patients':
            $keyword = trim($_GET['q'] ?? $input['q'] ?? '');
            $limit = max(1, min(100, (int)($_GET['limit'] ?? $input['limit'] ?? 20)));
            echo json_encode([
                'success' => true,
                'data' => $patient->search($keyword, $limit)
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'get_time_slots':
            echo json_encode([
                'success' => true,
                'data' => $booking->getTimeSlots(),
                'settings' => $booking->getClinicSettings()
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'check_conflict':
            $doctorId = (int)($_GET['doctor_id'] ?? $input['doctor_id'] ?? 0);
            $date = trim($_GET['date'] ?? $input['date'] ?? '');
            $startTime = trim($_GET['start_time'] ?? $input['start_time'] ?? '');
            $endTime = trim($_GET['end_time'] ?? $input['end_time'] ?? '');

            if (strlen($startTime) === 5) $startTime .= ':00';
            if (strlen($endTime) === 5) $endTime .= ':00';

            $conflict = $booking->checkConflict($doctorId, $date, $startTime, $endTime);
            echo json_encode([
                'success' => true,
                'has_conflict' => $conflict !== null,
                'conflict' => $conflict
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'save_appointment':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new InvalidArgumentException("Method Not Allowed: ต้องส่งแบบ POST");
            }
            if (empty($input)) {
                throw new InvalidArgumentException("ข้อมูลต้องส่งเป็น JSON เท่านั้น (ไม่รองรับ FormData)");
            }
            $newId = $booking->create($input);
            echo json_encode([
                'success' => true,
                'message' => 'สร้างนัดหมายสำเร็จเรียบร้อยแล้ว!',
                'appointment_id' => $newId
            ], JSON_UNESCAPED_UNICODE);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Unknown action'], JSON_UNESCAPED_UNICODE);
    }
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
