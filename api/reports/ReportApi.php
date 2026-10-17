<?php
// =====================================================================
// api/ReportApi.php - OOP Class สำหรับรายงานสรุปและสถิติ
// ใช้งานในหน้า: reports.php
// =====================================================================

class ReportApi
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * คำนวณสรุปสถิติตามช่วงวันที่ (ออกแบบรองรับข้อมูล 100,000+ แถว)
     */
    public function getSummary(string $startDate, string $endDate): array
    {
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
                COUNT(CASE WHEN status = 'arrived' THEN 1 END) AS count_arrived,
                COUNT(CASE WHEN status = 'cancelled' THEN 1 END) AS count_cancelled
            FROM appointments
            WHERE appointment_date BETWEEN :start_date AND :end_date
        ");
        $stmtAppt->execute([':start_date' => $startDate, ':end_date' => $endDate]);
        $apptStats = $stmtAppt->fetch();

        // 3. สรุปแยกรายแพทย์
        $stmtDoc = $this->pdo->prepare("
            SELECT d.id, d.name AS doctor_name, d.specialty,
                   COUNT(a.id) AS total_appts,
                   COUNT(CASE WHEN a.status = 'arrived' THEN 1 END) AS count_arrived,
                   COUNT(CASE WHEN a.status = 'pending' THEN 1 END) AS count_pending,
                   COUNT(CASE WHEN a.status = 'cancelled' THEN 1 END) AS count_cancelled
            FROM doctors d
            LEFT JOIN appointments a ON d.id = a.doctor_id 
                 AND a.appointment_date BETWEEN :start_date AND :end_date
            WHERE d.is_active = 1
            GROUP BY d.id, d.name, d.specialty
            ORDER BY total_appts DESC
        ");
        $stmtDoc->execute([':start_date' => $startDate, ':end_date' => $endDate]);
        $docStats = $stmtDoc->fetchAll();

        return [
            'new_patients_count' => $newPatients,
            'total_appointments' => (int)($apptStats['total_appointments'] ?? 0),
            'count_pending' => (int)($apptStats['count_pending'] ?? 0),
            'count_arrived' => (int)($apptStats['count_arrived'] ?? 0),
            'count_cancelled' => (int)($apptStats['count_cancelled'] ?? 0),
            'doctor_breakdown' => $docStats,
        ];
    }
}
