<?php

namespace App\Services;

use PDO;
use InvalidArgumentException;

class ReportService
{
    /**
     * Generate summary report for a given date range.
     * Optimized for high volume (100k+ rows) using composite indexing and single-pass aggregation.
     *
     * @return array Summary metrics including new patients, total appointments, and status breakdown.
     */
    public static function getSummary(PDO $pdo, string $startDate, string $endDate): array
    {
        $start = trim($startDate);
        $end = trim($endDate);

        if (empty($start) || empty($end)) {
            throw new InvalidArgumentException("กรุณาระบุช่วงวันที่เริ่มต้นและสิ้นสุด");
        }

        if ($start > $end) {
            throw new InvalidArgumentException("วันที่เริ่มต้นต้องไม่มากกว่าวันที่สิ้นสุด");
        }

        // 1. Metric: Number of new patients registered in this date range
        // Leverages: INDEX idx_patients_registration_date (registration_date)
        $patientSql = "
            SELECT COUNT(*) AS new_patients_count
            FROM patients
            WHERE registration_date BETWEEN :start_date AND :end_date
        ";
        $stmtPatient = $pdo->prepare($patientSql);
        $stmtPatient->execute([':start_date' => $start, ':end_date' => $end]);
        $patientRow = $stmtPatient->fetch();
        $newPatientsCount = (int)($patientRow['new_patients_count'] ?? 0);

        // 2. Metric: Appointments summary and breakdown by status in this date range
        // Leverages: COMPOSITE INDEX idx_appointments_report_date_status (appointment_date, status)
        // Single-pass aggregation via COUNT(CASE ...) prevents multiple table scans
        $apptSql = "
            SELECT 
                COUNT(*) AS total_appointments,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) AS count_pending,
                COUNT(CASE WHEN status = 'arrived' THEN 1 END) AS count_arrived,
                COUNT(CASE WHEN status = 'cancelled' THEN 1 END) AS count_cancelled
            FROM appointments
            WHERE appointment_date BETWEEN :start_date AND :end_date
        ";
        $stmtAppt = $pdo->prepare($apptSql);
        $stmtAppt->execute([':start_date' => $start, ':end_date' => $end]);
        $apptRow = $stmtAppt->fetch();

        // 3. Breakdown by doctor within this range (useful for visual presentation)
        $doctorSql = "
            SELECT d.id AS doctor_id, d.name AS doctor_name, d.specialty,
                   COUNT(a.id) AS doctor_total_appointments,
                   COUNT(CASE WHEN a.status = 'arrived' THEN 1 END) AS doctor_arrived,
                   COUNT(CASE WHEN a.status = 'pending' THEN 1 END) AS doctor_pending,
                   COUNT(CASE WHEN a.status = 'cancelled' THEN 1 END) AS doctor_cancelled
            FROM doctors d
            LEFT JOIN appointments a ON d.id = a.doctor_id 
                 AND a.appointment_date BETWEEN :start_date AND :end_date
            WHERE d.is_active = 1
            GROUP BY d.id, d.name, d.specialty
            ORDER BY doctor_total_appointments DESC, d.id ASC
        ";
        $stmtDoc = $pdo->prepare($doctorSql);
        $stmtDoc->execute([':start_date' => $start, ':end_date' => $end]);
        $doctorStats = $stmtDoc->fetchAll();

        return [
            'date_range' => [
                'start_date' => $start,
                'end_date' => $end,
            ],
            'metrics' => [
                'new_patients_count' => $newPatientsCount,
                'total_appointments' => (int)($apptRow['total_appointments'] ?? 0),
                'count_pending' => (int)($apptRow['count_pending'] ?? 0),
                'count_arrived' => (int)($apptRow['count_arrived'] ?? 0),
                'count_cancelled' => (int)($apptRow['count_cancelled'] ?? 0),
            ],
            'doctor_breakdown' => $doctorStats,
        ];
    }
}
