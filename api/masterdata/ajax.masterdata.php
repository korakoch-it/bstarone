<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/class.masterdata.php';

$masterdata = new ClassMasterData();

$action = $_GET['action'];
try {
    switch ($action) {
        case 'getMasterDoctor':
            $results = $masterdata->getMasterDoctor();
            echo json_encode(['success' => true, 'data' => $results], JSON_UNESCAPED_UNICODE);
            break;

        case 'getMasterPatient':
            $results = $masterdata->getMasterPatient();
            echo json_encode(['success' => true, 'data' => $results], JSON_UNESCAPED_UNICODE);
            break;

        case 'getMasterAppointmentStatus':
            $results = $masterdata->getMasterAppointmentStatus();
            echo json_encode(['success' => true, 'data' => $results], JSON_UNESCAPED_UNICODE);
            break;

        case 'getMasterAppointmentTime':
            $results = $masterdata->getMasterAppointmentTime();
            echo json_encode(['success' => true, 'data' => $results], JSON_UNESCAPED_UNICODE);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Unknown action: ' . $action], JSON_UNESCAPED_UNICODE);
    }
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}


