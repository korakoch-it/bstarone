<?php
// =====================================================================
// api/index/ClassIndex.php - ฟังก์ชันของหน้า "รายการนัดวันนี้" (page/index.php)
// =====================================================================

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
        $countSql = "SELECT COUNT(*) FROM appointments WHERE 1=1";
        if ($targetDate !== null) {
            $countSql .= " AND appointment_date = :appointment_date";
        }
        if ($doctorId !== null && $doctorId > 0) {
            $countSql .= " AND doctor_id = :doctor_id";
        }
        if ($patientId !== null && $patientId > 0) {
            $countSql .= " AND patient_id = :patient_id";
        }
        if ($status !== null) {
            $countSql .= " AND status = :status";
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
            WHERE 1=1
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
}
