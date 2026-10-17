<?php
// =====================================================================
// api/reports/ajax.php - AJAX Endpoint ของหน้า page/reports.php
// actions: get_report
// =====================================================================

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/class.report.php';

$api = new ClassReport();

$action = $_GET['action'] ?? '';
try {
    switch ($action) {
        case 'get_report':
            $startDate = trim($_GET['start_date'] ?? date('Y-m-01'));
            $endDate = trim($_GET['end_date'] ?? date('Y-m-d'));
            $summary = $api->getSummary($startDate, $endDate);
            echo json_encode(array_merge(['success' => true], $summary), JSON_UNESCAPED_UNICODE);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Unknown action'], JSON_UNESCAPED_UNICODE);
    }
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
