<?php

namespace App\Controllers;

use App\Config\Database;
use App\Services\AppointmentService;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class AppointmentController
{
    /**
     * Get appointments for today or specified date, with doctor filter
     */
    public static function index(): void
    {
        $doctorId = !empty($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : null;
        $date = !empty($_GET['date']) ? trim($_GET['date']) : date('Y-m-d');

        try {
            $pdo = Database::getConnection();
            $appointments = AppointmentService::getTodayAppointments($pdo, $doctorId, $date);

            self::json([
                'success' => true,
                'date' => $date,
                'data' => $appointments,
            ]);
        } catch (Throwable $e) {
            self::json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get list of active doctors
     */
    public static function doctors(): void
    {
        try {
            $pdo = Database::getConnection();
            $doctors = AppointmentService::getActiveDoctors($pdo);

            self::json([
                'success' => true,
                'data' => $doctors,
            ]);
        } catch (Throwable $e) {
            self::json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Book a new appointment
     */
    public static function store(): void
    {
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

        try {
            $pdo = Database::getConnection();
            $appointment = AppointmentService::createAppointment($pdo, $input);

            self::json([
                'success' => true,
                'message' => 'สร้างนัดหมายสำเร็จเรียบร้อย',
                'data' => $appointment,
            ], 201);
        } catch (InvalidArgumentException $e) {
            self::json(['success' => false, 'error' => $e->getMessage()], 422);
        } catch (RuntimeException $e) {
            // Overlap conflict: HTTP 409 Conflict
            self::json(['success' => false, 'error' => $e->getMessage()], 409);
        } catch (Throwable $e) {
            self::json(['success' => false, 'error' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update appointment status (pending, arrived, cancelled)
     */
    public static function updateStatus(int $id): void
    {
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $status = trim($input['status'] ?? '');

        try {
            $pdo = Database::getConnection();
            $appointment = AppointmentService::updateStatus($pdo, $id, $status);

            self::json([
                'success' => true,
                'message' => 'ปรับปรุงสถานะนัดหมายสำเร็จ',
                'data' => $appointment,
            ]);
        } catch (InvalidArgumentException $e) {
            self::json(['success' => false, 'error' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            self::json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Live slot availability checker (called by frontend when user selects time)
     */
    public static function checkAvailability(): void
    {
        $doctorId = (int)($_GET['doctor_id'] ?? 0);
        $date = trim($_GET['date'] ?? '');
        $startTime = trim($_GET['start_time'] ?? '');
        $endTime = trim($_GET['end_time'] ?? '');

        if ($doctorId <= 0 || empty($date) || empty($startTime) || empty($endTime)) {
            self::json(['success' => false, 'error' => 'พารามิเตอร์ไม่ครบถ้วน'], 422);
            return;
        }

        if (strlen($startTime) === 5) $startTime .= ':00';
        if (strlen($endTime) === 5) $endTime .= ':00';

        try {
            $pdo = Database::getConnection();
            $conflict = AppointmentService::checkDoctorConflict($pdo, $doctorId, $date, $startTime, $endTime);

            self::json([
                'success' => true,
                'available' => ($conflict === null),
                'conflict' => $conflict,
            ]);
        } catch (Throwable $e) {
            self::json(['success' => false, 'error' => $e->getMessage()], 500);
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
