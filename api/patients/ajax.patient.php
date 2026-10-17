<?php
// =====================================================================
// api/patients/ajax.patient.php - AJAX Endpoint ของหน้า page/patients.php
// actions: get_patients, get_patient, save_patient
// =====================================================================

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/class.patient.php';

$api = new ClassPatient();

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
        case 'get_patients':
        case 'list':
            $keyword = trim($_GET['search'] ?? $_GET['q'] ?? $input['search'] ?? $input['q'] ?? '');
            $page = max(1, (int)($_GET['p'] ?? $_GET['page'] ?? $input['p'] ?? $input['page'] ?? 1));
            $perPage = max(1, (int)($_GET['per_page'] ?? $input['per_page'] ?? 10));
            $result = $api->searchPaginated($keyword, $page, $perPage);
            echo json_encode(array_merge(['success' => true], $result), JSON_UNESCAPED_UNICODE);
            break;

        case 'get_patient':
            $id = (int)($_GET['id'] ?? $input['id'] ?? 0);
            $patient = $api->getById($id);
            if (!$patient) {
                throw new RuntimeException("ไม่พบข้อมูลผู้ป่วยรหัส ID: {$id}");
            }
            echo json_encode(['success' => true, 'data' => $patient], JSON_UNESCAPED_UNICODE);
            break;

        case 'save_patient':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new InvalidArgumentException("Method Not Allowed: ต้องส่งแบบ POST");
            }
            if (empty($input)) {
                throw new InvalidArgumentException("ข้อมูลต้องส่งเป็น JSON เท่านั้น (ไม่รองรับ FormData)");
            }
            if (($input['mode'] ?? 'create') === 'update') {
                $id = (int)($input['patient_id'] ?? 0);
                $api->update($id, $input);
                echo json_encode([
                    'success' => true,
                    'message' => 'แก้ไขข้อมูลผู้ป่วยเรียบร้อยแล้ว',
                    'data' => $api->getById($id)
                ], JSON_UNESCAPED_UNICODE);
            } else {
                $created = $api->create($input);
                echo json_encode([
                    'success' => true,
                    'message' => 'ลงทะเบียนผู้ป่วยใหม่สำเร็จ รหัส HN: ' . $created['hn'],
                    'data' => $created
                ], JSON_UNESCAPED_UNICODE);
            }
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Unknown action: ' . $action], JSON_UNESCAPED_UNICODE);
    }
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

