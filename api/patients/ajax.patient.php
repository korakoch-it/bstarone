<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/class.patient.php';

$patient = new ClassPatient();

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
            $filterPatient = !empty($input['filterPatient']) ? trim($input['filterPatient']) : '';
            $pageIndex = max(1, (int)($input['pageIndex'] ?? 1));
            $pageSize = max(1, (int)($input['pageSize'] ?? 10));
            $result = $patient->getOverview($filterPatient, $pageIndex, $pageSize);
            echo json_encode(array_merge(['success' => true], $result), JSON_UNESCAPED_UNICODE);
            break;

        case 'savePatient':
            if (($input['mode'] ?? 'create') === 'update') {
                $id = (int)($input['patient_id'] ?? 0);
                $patient->update($id, $input);
                echo json_encode([
                    'success' => true,
                    'message' => 'แก้ไขข้อมูลผู้ป่วยเรียบร้อยแล้ว',
                    'data' => $patient->getById($id)
                ], JSON_UNESCAPED_UNICODE);
            } else {
                $created = $patient->create($input);
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

