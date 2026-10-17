<?php
require_once __DIR__ . '/../config/database.php';

class ClassIndex extends Database
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getOverview(int $patientId = null, int $doctorId = null, string $targetDate = null, string $status = null, int $pageIndex = 1, int $pageSize = 10)
    {
        $pageIndex = max(1, $pageIndex);
        $pageSize = max(1, $pageSize);
        $offset = ($pageIndex - 1) * $pageSize;

        $targetDate = !empty($targetDate) ? trim($targetDate) : null;
        $status = !empty($status) ? trim($status) : null;

        // 1. นับจำนวนทั้งหมด
        $countSql = "SELECT COUNT(*) FROM appointments a JOIN patients p ON a.patient_id = p.id WHERE a.is_delete = 0";
        if ($targetDate !== null) {
            $countSql .= " AND a.appointment_date = :appointment_date";
        }
        if ($doctorId !== null && $doctorId > 0) {
            $countSql .= " AND a.doctor_id = :doctor_id";
        }
        if ($patientId !== null && $patientId > 0) {
            $countSql .= " AND a.patient_id = :patient_id";
        }
        if ($status !== null) {
            $countSql .= " AND a.status = :status";
        }
        $countStmt = $this->pdo->prepare($countSql);
        if ($targetDate !== null) {
            $countStmt->bindValue(':appointment_date', $targetDate, PDO::PARAM_STR);
        }
        if ($doctorId !== null && $doctorId > 0) {
            $countStmt->bindValue(':doctor_id', $doctorId, PDO::PARAM_INT);
        }
        if ($patientId !== null && $patientId > 0) {
            $countStmt->bindValue(':patient_id', $patientId, PDO::PARAM_INT);
        }
        if ($status !== null) {
            $countStmt->bindValue(':status', $status, PDO::PARAM_STR);
        }
        $countStmt->execute();
        $totalCount = (int)$countStmt->fetchColumn();

        // 2. ดึงข้อมูลแบ่งหน้า
        $sql = "
            SELECT a.id
                , a.patient_id
                , a.doctor_id
                , a.appointment_date
                , a.start_time
                , a.end_time
                , a.status
                , a.note
                , p.hn AS patient_hn
                , p.full_name AS patient_name
                , p.phone AS patient_phone
                , d.name AS doctor_name
                , d.specialty AS doctor_specialty
            FROM appointments a
            JOIN patients p ON a.patient_id = p.id
            JOIN doctors d ON a.doctor_id = d.id
            WHERE a.is_delete = 0
        ";
        if ($targetDate !== null) {
            $sql .= " AND a.appointment_date = :appointment_date";
        }
        if ($doctorId !== null && $doctorId > 0) {
            $sql .= " AND a.doctor_id = :doctor_id";
        }
        if ($patientId !== null && $patientId > 0) {
            $sql .= " AND a.patient_id = :patient_id";
        }
        if ($status !== null) {
            $sql .= " AND a.status = :status";
        }
        $sql .= " ORDER BY a.appointment_date DESC, a.start_time ASC, a.id ASC LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);
        if ($targetDate !== null) {
            $stmt->bindValue(':appointment_date', $targetDate, PDO::PARAM_STR);
        }
        if ($doctorId !== null && $doctorId > 0) {
            $stmt->bindValue(':doctor_id', $doctorId, PDO::PARAM_INT);
        }
        if ($patientId !== null && $patientId > 0) {
            $stmt->bindValue(':patient_id', $patientId, PDO::PARAM_INT);
        }
        if ($status !== null) {
            $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $pageSize, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'data' => $stmt->fetchAll(),
            'pageIndex' => $pageIndex,
            'pageSize' => $pageSize,
            'totalCount' => $totalCount,
            'totalPages' => max(1, (int)ceil($totalCount / $pageSize)),
        ];
    }

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
            throw new RuntimeException("แพทย์ท่านนี้มีนัดหมายแล้วในช่วงเวลา {$conflictRange} น.)");
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

    public function update(int $id, array $data): bool
    {
        $doctorId = (int)($data['doctor_id'] ?? 0);
        $date = trim($data['appointment_date'] ?? '');
        $startTime = trim($data['start_time'] ?? '');
        $endTime = trim($data['end_time'] ?? '');
        $note = trim($data['note'] ?? '');
        $status = trim($data['status'] ?? 'pending');

        if ($doctorId <= 0 || empty($date) || empty($startTime) || empty($endTime)) {
            throw new InvalidArgumentException("กรุณากรอกข้อมูลการนัดหมายให้ครบถ้วน");
        }

        if (strlen($startTime) === 5) $startTime .= ':00';
        if (strlen($endTime) === 5) $endTime .= ':00';

        if ($startTime >= $endTime) {
            throw new InvalidArgumentException("เวลาเริ่มต้นต้องน้อยกว่าเวลาสิ้นสุด");
        }

        $conflict = $this->checkConflict($doctorId, $date, $startTime, $endTime, $id);
        if ($conflict) {
            $conflictRange = substr($conflict['start_time'], 0, 5) . ' - ' . substr($conflict['end_time'], 0, 5);
            throw new RuntimeException("แพทย์ท่านนี้มีนัดหมายแล้วในช่วงเวลา {$conflictRange} น.)");
        }

        $stmt = $this->pdo->prepare("
            UPDATE appointments
            SET doctor_id = :doctor_id, appointment_date = :appointment_date,
                start_time = :start_time, end_time = :end_time, status = :status, note = :note
            WHERE id = :id
        ");
        return $stmt->execute([
            ':doctor_id' => $doctorId,
            ':appointment_date' => $date,
            ':start_time' => $startTime,
            ':end_time' => $endTime,
            ':status' => $status,
            ':note' => $note ?: null,
            ':id' => $id,
        ]);
    }

    public function delete(int $id)
    {
        $stmt = $this->pdo->prepare("UPDATE appointments SET is_delete = 1 WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function checkConflict(
        int $doctorId,
        string $date,
        string $startTime,
        string $endTime,
        ?int $excludeId = null
    ): ?array {
        $sql = "
            SELECT a.id
            , a.start_time
            , a.end_time
            , a.status
            , d.name AS doctor_name
            , p.full_name AS patient_name
            , p.hn AS patient_hn
            FROM appointments a
            JOIN doctors d ON a.doctor_id = d.id
            JOIN patients p ON a.patient_id = p.id
            WHERE a.doctor_id = :doctor_id
              AND a.appointment_date = :appointment_date
              AND a.status != 'cancelled'
              AND a.is_delete = 0
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

        // ไม่นับของผู้ป่วยที่ทำการแก้ไข
        if ($excludeId !== null) {
            $stmt->bindValue(':exclude_id', $excludeId, PDO::PARAM_INT);
        }

        $stmt->execute();
        $conflict = $stmt->fetch();

        return $conflict ?: null;
    }
}
