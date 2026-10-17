<?php
// =====================================================================
// api/index/ajax.index.php - AJAX Endpoint ของหน้า page/index.php
// actions: get_appointments, update_appointment_status
// =====================================================================

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/class.index.php';

$index = new ClassIndex();

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
        case 'getOverview':
            $filterDoctorId = !empty($input['filterDoctorId']) ? (int)$input['filterDoctorId'] : null;
            $filterPatientId = !empty($input['filterPatientId']) ? (int)$input['filterPatientId'] : null;
            $filterDate = !empty($input['filterDate']) ? trim($input['filterDate']) : null;
            $filterStatus = !empty($input['filterStatus']) ? trim($input['filterStatus']) : null;
            $pageIndex = max(1, (int)($input['pageIndex'] ?? 1));
            $pageSize = max(1, (int)($input['pageSize'] ?? 10));
            $result = $index->getOverview($filterPatientId, $filterDoctorId, $filterDate, $filterStatus, $pageIndex, $pageSize);
            echo json_encode(array_merge(['success' => true], $result), JSON_UNESCAPED_UNICODE);
            break;

        case 'update_appointment_status':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new InvalidArgumentException("Method Not Allowed: ต้องส่งแบบ POST");
            }
            if (empty($input)) {
                throw new InvalidArgumentException("ข้อมูลต้องส่งเป็น JSON เท่านั้น (ไม่รองรับ FormData)");
            }
            $appointmentId = (int)($input['appointment_id'] ?? 0);
            $status = trim($input['status'] ?? '');
            $index->updateStatus($appointmentId, $status);
            echo json_encode([
                'success' => true,
                'message' => 'อัปเดตสถานะนัดหมายเรียบร้อยแล้ว'
            ], JSON_UNESCAPED_UNICODE);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Unknown action: ' . $action], JSON_UNESCAPED_UNICODE);
    }
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

