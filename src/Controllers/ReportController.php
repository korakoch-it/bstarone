<?php

namespace App\Controllers;

use App\Config\Database;
use App\Services\ReportService;
use InvalidArgumentException;
use Throwable;

class ReportController
{
    public static function summary(): void
    {
        $startDate = $_GET['start_date'] ?? date('Y-m-01'); // Default 1st day of current month
        $endDate = $_GET['end_date'] ?? date('Y-m-d');      // Default today

        try {
            $pdo = Database::getConnection();
            $result = ReportService::getSummary($pdo, $startDate, $endDate);

            self::json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (InvalidArgumentException $e) {
            self::json(['success' => false, 'error' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            self::json(['success' => false, 'error' => 'เกิดข้อผิดพลาดในการสร้างรายงาน: ' . $e->getMessage()], 500);
        }
    }

    private static function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
}
