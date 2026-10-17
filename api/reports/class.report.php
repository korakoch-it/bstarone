<?php
require_once __DIR__ . '/../config/database.php';

class ClassReport extends Database
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * คำนวณสรุปสถิติตามช่วงวันที่ (ออกแบบรองรับข้อมูล 100,000+ แถว)
     */
    public function getSummary(string $startDate = '', string $endDate = '', int $pageIndex = 1, int $pageSize = 10): array
    {
        $startDate = !empty($startDate) ? trim($startDate) : date('Y-m-01');
        $endDate = !empty($endDate) ? trim($endDate) : date('Y-m-d');
        $pageIndex = max(1, $pageIndex);
        $pageSize = max(1, $pageSize);

        if ($startDate > $endDate) {
            throw new InvalidArgumentException("วันที่เริ่มต้นต้องไม่มากกว่าวันที่สิ้นสุด");
        }

        // 1. นับผู้ป่วยใหม่ (ใช้ Index: idx_patients_registration_date)
        $stmtPatient = $this->pdo->prepare("
            SELECT COUNT(*) AS new_patients_count
            FROM patients
            WHERE registration_date BETWEEN :start_date AND :end_date
        ");
        $stmtPatient->execute([':start_date' => $startDate, ':end_date' => $endDate]);
        $newPatients = (int)($stmtPatient->fetch()['new_patients_count'] ?? 0);

        // 2. นับนัดหมายรวมและแยกตามสถานะ (ใช้ Index: idx_appointments_report_date_status)
        $stmtAppt = $this->pdo->prepare("
            SELECT 
                COUNT(*) AS total_appointments,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) AS count_pending,
                COUNT(CASE WHEN status = 'confirmed' THEN 1 END) AS count_confirmed,
                COUNT(CASE WHEN status = 'arrived' THEN 1 END) AS count_arrived,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) AS count_completed,
                COUNT(CASE WHEN status = 'cancelled' THEN 1 END) AS count_cancelled
            FROM appointments
            WHERE appointment_date BETWEEN :start_date AND :end_date
              AND is_delete = 0
        ");
        $stmtAppt->execute([':start_date' => $startDate, ':end_date' => $endDate]);
        $apptStats = $stmtAppt->fetch();

        // 3. สรุปแยกรายแพทย์ พร้อม Pagination
        $countDocStmt = $this->pdo->query("SELECT COUNT(*) FROM doctors WHERE is_active = 1");
        $totalCount = (int)$countDocStmt->fetchColumn();

        $offset = ($pageIndex - 1) * $pageSize;
        $stmtDoc = $this->pdo->prepare("
            SELECT d.id, d.name AS doctor_name, d.specialty,
                   COUNT(a.id) AS total_appts,
                   COUNT(CASE WHEN a.status = 'pending' THEN 1 END) AS count_pending,
                   COUNT(CASE WHEN a.status = 'confirmed' THEN 1 END) AS count_confirmed,
                   COUNT(CASE WHEN a.status = 'arrived' THEN 1 END) AS count_arrived,
                   COUNT(CASE WHEN a.status = 'completed' THEN 1 END) AS count_completed,
                   COUNT(CASE WHEN a.status = 'cancelled' THEN 1 END) AS count_cancelled
            FROM doctors d
            LEFT JOIN appointments a ON d.id = a.doctor_id 
                 AND a.appointment_date BETWEEN :start_date AND :end_date
                 AND a.is_delete = 0
            WHERE d.is_active = 1
            GROUP BY d.id, d.name, d.specialty
            ORDER BY total_appts DESC, d.id ASC
            LIMIT :limit OFFSET :offset
        ");
        $stmtDoc->bindValue(':start_date', $startDate, PDO::PARAM_STR);
        $stmtDoc->bindValue(':end_date', $endDate, PDO::PARAM_STR);
        $stmtDoc->bindValue(':limit', $pageSize, PDO::PARAM_INT);
        $stmtDoc->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmtDoc->execute();
        $results = $stmtDoc->fetchAll();

        return [
            'new_patients_count' => $newPatients,
            'total_appointments' => (int)($apptStats['total_appointments'] ?? 0),
            'count_pending' => (int)($apptStats['count_pending'] ?? 0),
            'count_confirmed' => (int)($apptStats['count_confirmed'] ?? 0),
            'count_arrived' => (int)($apptStats['count_arrived'] ?? 0),
            'count_completed' => (int)($apptStats['count_completed'] ?? 0),
            'count_cancelled' => (int)($apptStats['count_cancelled'] ?? 0),
            'data' => $results,
            'pageIndex' => $pageIndex,
            'pageSize' => $pageSize,
            'totalCount' => $totalCount,
            'totalPages' => max(1, (int)ceil($totalCount / $pageSize)),
        ];
    }
}
