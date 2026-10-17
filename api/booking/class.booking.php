<?php
require_once __DIR__ . '/../config/database.php';

class ClassBooking extends Database
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getDoctors()
    {
        $stmt = $this->pdo->query("SELECT * FROM doctors WHERE is_active = 1 ORDER BY id ASC");
        return $stmt->fetchAll();
    }

    /**
     * ดึงสล็อตเวลาการนัดหมายจากฐานข้อมูล (Config ในตาราง time_slots)
     */
    public function getTimeSlots(): array
    {
        try {
            $stmt = $this->pdo->query("SELECT id, slot_time, is_active, sort_order FROM time_slots WHERE is_active = 1 ORDER BY sort_order ASC, slot_time ASC");
            $rows = $stmt->fetchAll();
            if (!empty($rows)) {
                return $rows;
            }
        } catch (Throwable $e) {
            // fallback if table does not exist
        }

        // Fallback default 09:00 - 18:00 ทีละครึ่งชั่วโมง
        $slots = [];
        $start = strtotime('09:00');
        $end = strtotime('18:00');
        $order = 1;
        for ($t = $start; $t <= $end; $t += 1800) {
            $slots[] = [
                'id' => $order,
                'slot_time' => date('H:i:s', $t),
                'is_active' => 1,
                'sort_order' => $order++
            ];
        }
        return $slots;
    }

    /**
     * ดึงค่าการตั้งค่าคลินิกจากฐานข้อมูล (ตาราง clinic_settings)
     */
    public function getClinicSettings(): array
    {
        try {
            $stmt = $this->pdo->query("SELECT setting_key, setting_value FROM clinic_settings");
            return $stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
        } catch (Throwable $e) {
            return [
                'slot_interval_minutes' => '30',
                'clinic_open_time' => '09:00:00',
                'clinic_close_time' => '18:00:00',
                'default_appointment_duration' => '30'
            ];
        }
    }

    /**
     * ตรวจสอบนัดเวลาซ้อนกัน (Overlap Conflict)
     * เงื่อนไข: Existing.start < New.end AND Existing.end > New.start
     * - นัดที่ยกเลิก (cancelled) ไม่นับว่าชน
     * - นัดต่อเนื่องติดกัน (Back-to-back) ไม่ชน
     */
    public function checkConflict(
        int $doctorId,
        string $date,
        string $startTime,
        string $endTime,
        ?int $excludeId = null
    ): ?array {
        $sql = "
            SELECT a.id, a.start_time, a.end_time, a.status,
                   d.name AS doctor_name,
                   p.full_name AS patient_name, p.hn AS patient_hn
            FROM appointments a
            JOIN doctors d ON a.doctor_id = d.id
            JOIN patients p ON a.patient_id = p.id
            WHERE a.doctor_id = :doctor_id
              AND a.appointment_date = :appointment_date
              AND a.status != 'cancelled'
              AND a.start_time < :new_end_time
              AND a.end_time > :new_start_time
        ";

        if ($excludeId !== null) {
            $sql .= " AND a.id != :exclude_id";
        }

        $sql .= " LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':doctor_id', $doctorId, PDO::PARAM_INT);
        $stmt->bindValue(':appointment_date', $date, PDO::PARAM_STR);
        $stmt->bindValue(':new_start_time', $startTime, PDO::PARAM_STR);
        $stmt->bindValue(':new_end_time', $endTime, PDO::PARAM_STR);

        if ($excludeId !== null) {
            $stmt->bindValue(':exclude_id', $excludeId, PDO::PARAM_INT);
        }

        $stmt->execute();
        $conflict = $stmt->fetch();

        return $conflict ?: null;
    }

    /**
     * สร้างนัดหมายใหม่ (เช็คนัดชนก่อนบันทึก)
     */
    public function create(array $data): int
    {
        $patientId = (int)($data['patient_id'] ?? 0);
        $doctorId = (int)($data['doctor_id'] ?? 0);
        $date = trim($data['appointment_date'] ?? '');
        $startTime = trim($data['start_time'] ?? '');
        $endTime = trim($data['end_time'] ?? '');
        $note = trim($data['note'] ?? '');

        if ($patientId <= 0 || $doctorId <= 0 || empty($date) || empty($startTime) || empty($endTime)) {
            throw new InvalidArgumentException("กรุณากรอกข้อมูลการนัดหมายให้ครบถ้วน");
        }

        if (strlen($startTime) === 5) $startTime .= ':00';
        if (strlen($endTime) === 5) $endTime .= ':00';

        if ($startTime >= $endTime) {
            throw new InvalidArgumentException("เวลาเริ่มต้นต้องน้อยกว่าเวลาสิ้นสุด");
        }

        $conflict = $this->checkConflict($doctorId, $date, $startTime, $endTime);
        if ($conflict) {
            $conflictRange = substr($conflict['start_time'], 0, 5) . ' - ' . substr($conflict['end_time'], 0, 5);
            throw new RuntimeException(
                "แพทย์ท่านนี้มีนัดหมายแล้วในช่วงเวลา {$conflictRange} น. (คนไข้: {$conflict['patient_name']})"
            );
        }

        $this->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO appointments (patient_id, doctor_id, appointment_date, start_time, end_time, status, note)
                VALUES (:patient_id, :doctor_id, :appointment_date, :start_time, :end_time, 'pending', :note)
            ");
            $stmt->execute([
                ':patient_id' => $patientId,
                ':doctor_id' => $doctorId,
                ':appointment_date' => $date,
                ':start_time' => $startTime,
                ':end_time' => $endTime,
                ':note' => $note ?: null,
            ]);

            $this->commit();
            return (int)$this->pdo->lastInsertId();
        } catch (Exception $e) {
            $this->rollBack();
            throw $e;
        }
    }
}
