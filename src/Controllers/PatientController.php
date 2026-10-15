<?php

namespace App\Controllers;

use App\Config\Database;
use App\Services\PatientService;
use InvalidArgumentException;
use Throwable;

class PatientController
{
    public static function index(): void
    {
        $keyword = $_GET['search'] ?? '';
        $pdo = Database::getConnection();
        $patients = PatientService::searchPatients($pdo, $keyword);

        self::json([
            'success' => true,
            'data' => $patients,
        ]);
    }

    public static function show(int $id): void
    {
        $pdo = Database::getConnection();
        $patient = PatientService::getPatientById($pdo, $id);

        if (!$patient) {
            self::json(['success' => false, 'error' => 'ไม่พบข้อมูลผู้ป่วย'], 404);
            return;
        }

        self::json([
            'success' => true,
            'data' => $patient,
        ]);
    }

    public static function store(): void
    {
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

        try {
            $pdo = Database::getConnection();
            $patient = PatientService::createPatient($pdo, $input);

            self::json([
                'success' => true,
                'message' => "ลงทะเบียนผู้ป่วยเรียบร้อยแล้ว (HN: {$patient['hn']})",
                'data' => $patient,
            ], 201);
        } catch (InvalidArgumentException $e) {
            self::json(['success' => false, 'error' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            self::json(['success' => false, 'error' => 'เกิดข้อผิดพลาดในการบันทึก: ' . $e->getMessage()], 500);
        }
    }

    public static function update(int $id): void
    {
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

        try {
            $pdo = Database::getConnection();
            $patient = PatientService::updatePatient($pdo, $id, $input);

            self::json([
                'success' => true,
                'message' => 'อัปเดตข้อมูลผู้ป่วยสำเร็จ',
                'data' => $patient,
            ]);
        } catch (InvalidArgumentException $e) {
            self::json(['success' => false, 'error' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            self::json(['success' => false, 'error' => 'เกิดข้อผิดพลาดในการแก้ไข: ' . $e->getMessage()], 500);
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
