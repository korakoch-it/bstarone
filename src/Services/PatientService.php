<?php

namespace App\Services;

use PDO;
use InvalidArgumentException;
use RuntimeException;

class PatientService
{
    /**
     * Normalize Thai phone numbers into the standard 10-digit format (e.g. 0812345678).
     * Handles:
     * - +66812345678 -> 0812345678
     * - 66 81 234 5678 -> 0812345678
     * - 081-234-5678 -> 0812345678
     * - 081 234 5678 -> 0812345678
     * - +66 81-234-5678 -> 0812345678
     */
    public static function normalizePhone(string $rawPhone): string
    {
        // 1. Strip all non-digit and non-plus characters (spaces, dashes, parens, dots)
        $clean = preg_replace('/[^\d+]/', '', trim($rawPhone));

        // 2. Remove leading '+' if present
        if (str_starts_with($clean, '+')) {
            $clean = substr($clean, 1);
        }

        // 3. If starts with country code '66', replace with '0'
        if (str_starts_with($clean, '66')) {
            $clean = '0' . substr($clean, 2);
        }

        // 4. Validate resulting phone: must be numbers only, 9-10 digits, starting with 0
        if (!preg_match('/^0\d{8,9}$/', $clean)) {
            throw new InvalidArgumentException("หมายเลขโทรศัพท์ไม่ถูกต้อง: กรุณาระบุเบอร์โทรศัพท์ที่ถูกต้อง (เช่น 081-234-5678 หรือ +66812345678)");
        }

        return $clean;
    }

    /**
     * Generate continuous, unique HN in format HN000001 with transaction lock.
     */
    public static function generateNextHN(PDO $pdo): string
    {
        // Extract numeric part after 'HN' prefix and find max
        $stmt = $pdo->query("SELECT MAX(CAST(SUBSTRING(hn, 3) AS UNSIGNED)) AS max_seq FROM patients FOR UPDATE");
        $row = $stmt->fetch();
        $maxSeq = ($row && $row['max_seq'] !== null) ? (int)$row['max_seq'] : 0;
        $nextSeq = $maxSeq + 1;

        return 'HN' . str_pad((string)$nextSeq, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Register a new patient
     */
    public static function createPatient(PDO $pdo, array $data): array
    {
        $fullName = trim($data['full_name'] ?? '');
        $rawPhone = trim($data['phone'] ?? '');
        $birthDate = trim($data['birth_date'] ?? '');
        $regDate = !empty($data['registration_date']) ? trim($data['registration_date']) : date('Y-m-d');

        if (empty($fullName)) {
            throw new InvalidArgumentException("กรุณากรอกชื่อ-นามสกุล");
        }
        if (empty($birthDate)) {
            throw new InvalidArgumentException("กรุณาระบุวันเกิด");
        }

        $phone = self::normalizePhone($rawPhone);

        // Execute in transaction to safely generate sequential HN
        $startedTransaction = false;
        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
            $startedTransaction = true;
        }

        try {
            $hn = self::generateNextHN($pdo);

            $stmt = $pdo->prepare("
                INSERT INTO patients (hn, full_name, phone, birth_date, registration_date)
                VALUES (:hn, :full_name, :phone, :birth_date, :registration_date)
            ");
            $stmt->execute([
                ':hn' => $hn,
                ':full_name' => $fullName,
                ':phone' => $phone,
                ':birth_date' => $birthDate,
                ':registration_date' => $regDate,
            ]);

            $newId = (int)$pdo->lastInsertId();

            if ($startedTransaction) {
                $pdo->commit();
            }

            return self::getPatientById($pdo, $newId);
        } catch (\Throwable $e) {
            if ($startedTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Update existing patient (HN is locked and cannot be changed)
     */
    public static function updatePatient(PDO $pdo, int $id, array $data): array
    {
        $fullName = trim($data['full_name'] ?? '');
        $rawPhone = trim($data['phone'] ?? '');
        $birthDate = trim($data['birth_date'] ?? '');

        if (empty($fullName)) {
            throw new InvalidArgumentException("กรุณากรอกชื่อ-นามสกุล");
        }
        if (empty($birthDate)) {
            throw new InvalidArgumentException("กรุณาระบุวันเกิด");
        }

        $phone = self::normalizePhone($rawPhone);

        $stmt = $pdo->prepare("
            UPDATE patients
            SET full_name = :full_name,
                phone = :phone,
                birth_date = :birth_date
            WHERE id = :id
        ");
        $stmt->execute([
            ':full_name' => $fullName,
            ':phone' => $phone,
            ':birth_date' => $birthDate,
            ':id' => $id,
        ]);

        return self::getPatientById($pdo, $id);
    }

    /**
     * Search patients by name, HN, or phone
     */
    public static function searchPatients(PDO $pdo, string $keyword, int $limit = 30): array
    {
        $trimmed = trim($keyword);
        if (empty($trimmed)) {
            return self::getAllPatients($pdo, $limit);
        }

        // Clean digits if user searches by phone
        $digitsOnly = preg_replace('/\D/', '', $trimmed);
        if (str_starts_with($digitsOnly, '66')) {
            $digitsOnly = '0' . substr($digitsOnly, 2);
        }

        $sql = "
            SELECT id, hn, full_name, phone, birth_date, registration_date, created_at
            FROM patients
            WHERE hn LIKE :hn_kw
               OR full_name LIKE :name_kw
               " . (!empty($digitsOnly) ? "OR phone LIKE :phone_kw" : "") . "
            ORDER BY id DESC
            LIMIT :limit
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':hn_kw', '%' . $trimmed . '%', PDO::PARAM_STR);
        $stmt->bindValue(':name_kw', '%' . $trimmed . '%', PDO::PARAM_STR);
        if (!empty($digitsOnly)) {
            $stmt->bindValue(':phone_kw', '%' . $digitsOnly . '%', PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function getAllPatients(PDO $pdo, int $limit = 50): array
    {
        $stmt = $pdo->prepare("
            SELECT id, hn, full_name, phone, birth_date, registration_date, created_at
            FROM patients
            ORDER BY id DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function getPatientById(PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare("SELECT * FROM patients WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
