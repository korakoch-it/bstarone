<?php
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

        case 'saveAppointment':
            if (!empty($input['appointment_id']) || ($input['mode'] ?? '') === 'update') {
                $id = (int)($input['appointment_id'] ?? $input['id'] ?? 0);
                if ($id <= 0) {
                    throw new InvalidArgumentException("ไม่พบรหัสนัดหมายที่ต้องการแก้ไข");
                }
                $index->update($id, $input);
                echo json_encode([
                    'success' => true,
                    'message' => 'แก้ไขข้อมูลการนัดหมายสำเร็จเรียบร้อยแล้ว!',
                    'appointment_id' => $id
                ], JSON_UNESCAPED_UNICODE);
            } else {
                $newId = $index->create($input);
                echo json_encode([
                    'success' => true,
                    'message' => 'สร้างนัดหมายสำเร็จเรียบร้อยแล้ว!',
                    'appointment_id' => $newId
                ], JSON_UNESCAPED_UNICODE);
            }
            break;

        case 'deleteAppointment':
            $appointmentId = (int)($input['appointment_id'] ?? $input['id'] ?? 0);
            if ($appointmentId <= 0) {
                throw new InvalidArgumentException("ไม่พบรหัสนัดหมายที่ต้องการลบ");
            }
            $index->delete($appointmentId);
            echo json_encode([
                'success' => true,
                'message' => 'ลบรายการนัดหมายเรียบร้อยแล้ว'
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

