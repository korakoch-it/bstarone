<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/class.report.php';

$api = new ClassReport();

$rawInput = file_get_contents('php://input');
$input = !empty($rawInput) ? json_decode($rawInput, true) : [];

$action = $_GET['action'] ?? $input['action'] ?? '';
try {
    switch ($action) {
        case 'getOverview':
            $startDate = trim($input['startDate'] ?? $input['start_date'] ?? $_GET['startDate'] ?? $_GET['start_date'] ?? '');
            $endDate = trim($input['endDate'] ?? $input['end_date'] ?? $_GET['endDate'] ?? $_GET['end_date'] ?? '');
            $pageIndex = max(1, (int)($input['pageIndex'] ?? 1));
            $pageSize = max(1, (int)($input['pageSize'] ?? 10));
            $summary = $api->getSummary($startDate, $endDate, $pageIndex, $pageSize);
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
