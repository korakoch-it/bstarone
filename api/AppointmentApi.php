<?php
// =====================================================================
// api/AppointmentApi.php - OOP Class สำหรับจัดการการนัดหมายและเช็คนัดชน
// ใช้งานในหน้า: index.php (นัดวันนี้), booking.php (สร้างนัดหมาย)
// =====================================================================

class AppointmentApi
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * ตรวจสอบนัดเวลาซ้อนกัน (Overlap Conflict)
     * เงื่อนไขคณิตศาสตร์: Existing.start < New.end AND Existing.end > New.start
     * - นัดที่ยกเลิก (cancelled) ไม่นับว่าชน
     * - อนุญาตให้นัดต่อเนื่องติดกันได้ (Back-to-back ไม่ชน)
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
     * ดึงรายการนัดหมายของวันนี้ เรียงตามเวลา
     */
    public function getToday(?int $doctorId = null, ?string $targetDate = null): array
    {
        $date = $targetDate ?: date('Y-m-d');

        $sql = "
            SELECT a.id, a.appointment_date, a.start_time, a.end_time, a.status, a.note,
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

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':appointment_date', $date, PDO::PARAM_STR);
        if ($doctorId !== null && $doctorId > 0) {
            $stmt->bindValue(':doctor_id', $doctorId, PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * เปลี่ยนสถานะนัดหมาย (รอ / มาแล้ว / ยกเลิก)
     */
    public function updateStatus(int $id, string $status): bool
    {
        $allowed = ['pending', 'arrived', 'cancelled'];
        if (!in_array($status, $allowed, true)) {
            throw new InvalidArgumentException("สถานะไม่ถูกต้อง: ต้องเป็น pending, arrived หรือ cancelled");
        }

        $stmt = $this->pdo->prepare("UPDATE appointments SET status = :status WHERE id = :id");
        return $stmt->execute([':status' => $status, ':id' => $id]);
    }

    /**
     * สร้างนัดหมายใหม่
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

        // ตรวจสอบนัดชน
        $conflict = $this->checkConflict($doctorId, $date, $startTime, $endTime);
        if ($conflict) {
            $conflictRange = substr($conflict['start_time'], 0, 5) . ' - ' . substr($conflict['end_time'], 0, 5);
            throw new RuntimeException(
                "แพทย์ท่านนี้มีนัดหมายแล้วในช่วงเวลา {$conflictRange} น. (คนไข้: {$conflict['patient_name']})"
            );
        }

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

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * ดึงรายชื่อแพทย์ที่พร้อมตรวจ
     */
    public function getDoctors(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM doctors WHERE is_active = 1 ORDER BY id ASC");
        return $stmt->fetchAll();
    }
}
