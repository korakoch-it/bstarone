<?php

namespace App\Services;

use PDO;
use InvalidArgumentException;
use RuntimeException;

class AppointmentService
{
    /**
     * Check if a doctor has an overlapping appointment.
     * Condition: Existing.start_time < New.end_time AND Existing.end_time > New.start_time
     * Cancelled appointments (status = 'cancelled') are ignored.
     *
     * @return array|null The conflicting appointment record if conflict exists, null otherwise.
     */
    public static function checkDoctorConflict(
        PDO $pdo,
        int $doctorId,
        string $appointmentDate,
        string $startTime,
        string $endTime,
        ?int $excludeAppointmentId = null
    ): ?array {
        $sql = "
            SELECT a.id, a.doctor_id, a.patient_id, a.appointment_date, 
                   a.start_time, a.end_time, a.status,
                   p.full_name AS patient_name, p.hn AS patient_hn,
                   d.name AS doctor_name
            FROM appointments a
            JOIN patients p ON a.patient_id = p.id
            JOIN doctors d ON a.doctor_id = d.id
            WHERE a.doctor_id = :doctor_id
              AND a.appointment_date = :appointment_date
              AND a.status != 'cancelled'
              AND a.start_time < :new_end_time
              AND a.end_time > :new_start_time
        ";

        if ($excludeAppointmentId !== null) {
            $sql .= " AND a.id != :exclude_id";
        }

        $sql .= " LIMIT 1";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':doctor_id', $doctorId, PDO::PARAM_INT);
        $stmt->bindValue(':appointment_date', $appointmentDate, PDO::PARAM_STR);
        $stmt->bindValue(':new_start_time', $startTime, PDO::PARAM_STR);
        $stmt->bindValue(':new_end_time', $endTime, PDO::PARAM_STR);

        if ($excludeAppointmentId !== null) {
            $stmt->bindValue(':exclude_id', $excludeAppointmentId, PDO::PARAM_INT);
        }

        $stmt->execute();
        $conflict = $stmt->fetch();

        return $conflict ?: null;
    }

    /**
     * Create a new appointment with validation and overlap check
     */
    public static function createAppointment(PDO $pdo, array $data): array
    {
        $patientId = (int)($data['patient_id'] ?? 0);
        $doctorId = (int)($data['doctor_id'] ?? 0);
        $appointmentDate = trim($data['appointment_date'] ?? '');
        $startTime = trim($data['start_time'] ?? '');
        $endTime = trim($data['end_time'] ?? '');
        $note = trim($data['note'] ?? '');

        // Validation
        if ($patientId <= 0) {
            throw new InvalidArgumentException("กรุณาเลือกผู้ป่วย");
        }
        if ($doctorId <= 0) {
            throw new InvalidArgumentException("กรุณาเลือกแพทย์");
        }
        if (empty($appointmentDate)) {
            throw new InvalidArgumentException("กรุณาระบุวันที่นัดหมาย");
        }
        if (empty($startTime) || empty($endTime)) {
            throw new InvalidArgumentException("กรุณาระบุเวลาเริ่มต้นและเวลาสิ้นสุด");
        }

        // Standardize time format to HH:MM:SS
        if (strlen($startTime) === 5) {
            $startTime .= ':00';
        }
        if (strlen($endTime) === 5) {
            $endTime .= ':00';
        }

        if ($startTime >= $endTime) {
            throw new InvalidArgumentException("เวลาเริ่มต้นต้องน้อยกว่าเวลาสิ้นสุด (เช่น 10:00 - 11:00)");
        }

        // Overlap verification
        $conflict = self::checkDoctorConflict($pdo, $doctorId, $appointmentDate, $startTime, $endTime);
        if ($conflict) {
            $conflictRange = substr($conflict['start_time'], 0, 5) . ' - ' . substr($conflict['end_time'], 0, 5);
            throw new RuntimeException(
                "ไม่สามารถสร้างนัดหมายได้: {$conflict['doctor_name']} มีนัดหมายแล้วในช่วงเวลา {$conflictRange} " .
                "(คนไข้: {$conflict['patient_name']} [{$conflict['patient_hn']}])"
            );
        }

        $stmt = $pdo->prepare("
            INSERT INTO appointments (patient_id, doctor_id, appointment_date, start_time, end_time, status, note)
            VALUES (:patient_id, :doctor_id, :appointment_date, :start_time, :end_time, 'pending', :note)
        ");
        $stmt->execute([
            ':patient_id' => $patientId,
            ':doctor_id' => $doctorId,
            ':appointment_date' => $appointmentDate,
            ':start_time' => $startTime,
            ':end_time' => $endTime,
            ':note' => $note ?: null,
        ]);

        $newId = (int)$pdo->lastInsertId();
        return self::getAppointmentById($pdo, $newId);
    }

    /**
     * Get today's appointments ordered by time ASC, with optional doctor filter
     */
    public static function getTodayAppointments(PDO $pdo, ?int $doctorId = null, ?string $targetDate = null): array
    {
        $date = $targetDate ?: date('Y-m-d');

        $sql = "
            SELECT a.id, a.patient_id, a.doctor_id, a.appointment_date, 
                   a.start_time, a.end_time, a.status, a.note, a.created_at,
                   p.hn AS patient_hn, p.full_name AS patient_name, p.phone AS patient_phone,
                   d.name AS doctor_name, d.specialty AS doctor_specialty
            FROM appointments a
            JOIN patients p ON a.patient_id = p.id
            JOIN doctors d ON a.doctor_id = d.id
            WHERE a.appointment_date = :appointment_date
        ";

        if ($doctorId !== null && $doctorId > 0) {
            $sql .= " AND a.doctor_id = :doctor_id";
        }

        $sql .= " ORDER BY a.start_time ASC, a.id ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':appointment_date', $date, PDO::PARAM_STR);
        if ($doctorId !== null && $doctorId > 0) {
            $stmt->bindValue(':doctor_id', $doctorId, PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Update appointment status: 'pending', 'arrived', 'cancelled'
     */
    public static function updateStatus(PDO $pdo, int $id, string $status): array
    {
        $allowed = ['pending', 'arrived', 'cancelled'];
        if (!in_array($status, $allowed, true)) {
            throw new InvalidArgumentException("สถานะไม่ถูกต้อง: ต้องเป็น 'pending' (รอ), 'arrived' (มาแล้ว), หรือ 'cancelled' (ยกเลิก)");
        }

        $stmt = $pdo->prepare("UPDATE appointments SET status = :status WHERE id = :id");
        $stmt->execute([':status' => $status, ':id' => $id]);

        $item = self::getAppointmentById($pdo, $id);
        if (!$item) {
            throw new RuntimeException("ไม่พบนัดหมายรหัส #{$id}");
        }

        return $item;
    }

    public static function getAppointmentById(PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare("
            SELECT a.*, 
                   p.hn AS patient_hn, p.full_name AS patient_name, p.phone AS patient_phone,
                   d.name AS doctor_name, d.specialty AS doctor_specialty
            FROM appointments a
            JOIN patients p ON a.patient_id = p.id
            JOIN doctors d ON a.doctor_id = d.id
            WHERE a.id = :id
        ");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Get active doctors list for dropdowns
     */
    public static function getActiveDoctors(PDO $pdo): array
    {
        $stmt = $pdo->query("SELECT id, name, specialty, phone FROM doctors WHERE is_active = 1 ORDER BY id ASC");
        return $stmt->fetchAll();
    }
}
