<?php
// =====================================================================
// functions.php - รวมฟังก์ชันการทำงานหลักของระบบนัดหมายคลินิก
// =====================================================================

/**
 * 1. ฟังก์ชันแปลงเบอร์โทรศัพท์ให้อยู่ในรูปแบบมาตรฐาน 0812345678
 * รองรับ: 081-234-5678, +66812345678, 66 81 234 5678
 */
function normalizePhone(string $rawPhone): string
{
    // ลบอักขระที่ไม่ใช่ตัวเลขและเครื่องหมายบวกออกทั้งหมด (เช่น ช่องว่าง, ขีด, วงเล็บ)
    $clean = preg_replace('/[^\d+]/', '', trim($rawPhone));

    // ลบเครื่องหมาย + ข้างหน้า (ถ้ามี)
    if (str_starts_with($clean, '+')) {
        $clean = substr($clean, 1);
    }

    // ถ้าขึ้นต้นด้วยรหัสประเทศ 66 ให้เปลี่ยนเป็น 0
    if (str_starts_with($clean, '66')) {
        $clean = '0' . substr($clean, 2);
    }

    // ตรวจสอบว่าต้องเป็นตัวเลข 9-10 หลัก และขึ้นต้นด้วย 0
    if (!preg_match('/^0\d{8,9}$/', $clean)) {
        throw new InvalidArgumentException("หมายเลขโทรศัพท์ไม่ถูกต้อง (กรุณากรอกเช่น 081-234-5678 หรือ +66812345678)");
    }

    return $clean;
}

/**
 * 2. ฟังก์ชันสร้างรหัส HN อัตโนมัติ (เช่น HN000001) แบบไม่ซ้ำกัน
 */
function generateHN(PDO $pdo): string
{
    // หาค่าตัวเลขสูงสุดหลังคำว่า 'HN' ในฐานข้อมูล
    $stmt = $pdo->query("SELECT MAX(CAST(SUBSTRING(hn, 3) AS UNSIGNED)) AS max_seq FROM patients");
    $row = $stmt->fetch();
    $nextSeq = ($row && $row['max_seq'] !== null) ? ((int)$row['max_seq'] + 1) : 1;

    // เติมศูนย์ข้างหน้าให้ครบ 6 หลัก (HN000001)
    return 'HN' . str_pad((string)$nextSeq, 6, '0', STR_PAD_LEFT);
}

/**
 * 3. ฟังก์ชันตรวจสอบนัดเวลาซ้อนกัน (Overlap Prevention)
 * -------------------------------------------------------------
 * เงื่อนไขคณิตศาสตร์: Existing.start < New.end AND Existing.end > New.start
 * - ครอบคลุมทั้งกรณีซ้อนทับบางส่วนและทั้งหมด
 * - อนุญาตให้นัดต่อเนื่องติดกันได้ เช่น 10:00-11:00 กับ 11:00-12:00 ไม่ถือว่าชน
 * - นัดที่ถูกยกเลิก (cancelled) ไม่นับว่าชน
 */
function checkDoctorConflict(
    PDO $pdo,
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

    $stmt = $pdo->prepare($sql);
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
 * 4. ฟังก์ชันดึงรายการนัดหมายของวันนี้ เรียงตามเวลา
 */
function getTodayAppointments(PDO $pdo, ?int $doctorId = null, ?string $targetDate = null): array
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

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':appointment_date', $date, PDO::PARAM_STR);
    if ($doctorId !== null && $doctorId > 0) {
        $stmt->bindValue(':doctor_id', $doctorId, PDO::PARAM_INT);
    }
    $stmt->execute();

    return $stmt->fetchAll();
}

/**
 * 5. ฟังก์ชันเปลี่ยนสถานะนัดหมาย (รอ / มาแล้ว / ยกเลิก)
 */
function updateAppointmentStatus(PDO $pdo, int $id, string $status): bool
{
    $allowed = ['pending', 'arrived', 'cancelled'];
    if (!in_array($status, $allowed, true)) {
        throw new InvalidArgumentException("สถานะไม่ถูกต้อง");
    }

    $stmt = $pdo->prepare("UPDATE appointments SET status = :status WHERE id = :id");
    return $stmt->execute([':status' => $status, ':id' => $id]);
}

/**
 * 6. ฟังก์ชันสร้างนัดหมายใหม่
 */
function createAppointment(PDO $pdo, array $data): int
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

    // ปรับรูปแบบเวลาให้มีวินาที เช่น 10:00:00
    if (strlen($startTime) === 5) $startTime .= ':00';
    if (strlen($endTime) === 5) $endTime .= ':00';

    if ($startTime >= $endTime) {
        throw new InvalidArgumentException("เวลาเริ่มต้นต้องน้อยกว่าเวลาสิ้นสุด");
    }

    // ตรวจสอบนัดชน
    $conflict = checkDoctorConflict($pdo, $doctorId, $date, $startTime, $endTime);
    if ($conflict) {
        $conflictRange = substr($conflict['start_time'], 0, 5) . ' - ' . substr($conflict['end_time'], 0, 5);
        throw new RuntimeException(
            "แพทย์ท่านนี้มีนัดหมายแล้วในช่วงเวลา {$conflictRange} น. (คนไข้: {$conflict['patient_name']})"
        );
    }

    $stmt = $pdo->prepare("
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

    return (int)$pdo->lastInsertId();
}

/**
 * 7. ฟังก์ชันลงทะเบียนผู้ป่วยใหม่
 */
function createPatient(PDO $pdo, array $data): array
{
    $fullName = trim($data['full_name'] ?? '');
    $rawPhone = trim($data['phone'] ?? '');
    $birthDate = trim($data['birth_date'] ?? '');
    $regDate = !empty($data['registration_date']) ? trim($data['registration_date']) : date('Y-m-d');

    if (empty($fullName) || empty($birthDate)) {
        throw new InvalidArgumentException("กรุณากรอกชื่อ-นามสกุล และวันเกิด");
    }

    $phone = normalizePhone($rawPhone);
    $hn = generateHN($pdo);

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
    return getPatientById($pdo, $newId);
}

/**
 * 8. ฟังก์ชันแก้ไขข้อมูลผู้ป่วย (HN ล็อกห้ามแก้)
 */
function updatePatient(PDO $pdo, int $id, array $data): bool
{
    $fullName = trim($data['full_name'] ?? '');
    $rawPhone = trim($data['phone'] ?? '');
    $birthDate = trim($data['birth_date'] ?? '');

    if (empty($fullName) || empty($birthDate)) {
        throw new InvalidArgumentException("กรุณากรอกชื่อ-นามสกุล และวันเกิด");
    }

    $phone = normalizePhone($rawPhone);

    $stmt = $pdo->prepare("
        UPDATE patients
        SET full_name = :full_name, phone = :phone, birth_date = :birth_date
        WHERE id = :id
    ");
    return $stmt->execute([
        ':full_name' => $fullName,
        ':phone' => $phone,
        ':birth_date' => $birthDate,
        ':id' => $id,
    ]);
}

/**
 * 9. ฟังก์ชันค้นหาผู้ป่วย (รองรับ ชื่อ, HN หรือเบอร์โทรศัพท์)
 */
function searchPatients(PDO $pdo, string $keyword = '', int $limit = 50): array
{
    $trimmed = trim($keyword);
    if (empty($trimmed)) {
        $stmt = $pdo->prepare("SELECT * FROM patients ORDER BY id DESC LIMIT :limit");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // ถอดตัวเลขกรณีค้นหาด้วยเบอร์
    $digits = preg_replace('/\D/', '', $trimmed);
    if (str_starts_with($digits, '66')) {
        $digits = '0' . substr($digits, 2);
    }

    $sql = "
        SELECT * FROM patients
        WHERE hn LIKE :hn_kw
           OR full_name LIKE :name_kw
           " . (!empty($digits) ? "OR phone LIKE :phone_kw" : "") . "
        ORDER BY id DESC
        LIMIT :limit
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':hn_kw', '%' . $trimmed . '%', PDO::PARAM_STR);
    $stmt->bindValue(':name_kw', '%' . $trimmed . '%', PDO::PARAM_STR);
    if (!empty($digits)) {
        $stmt->bindValue(':phone_kw', '%' . $digits . '%', PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function getPatientById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function getDoctors(PDO $pdo): array
{
    $stmt = $pdo->query("SELECT * FROM doctors WHERE is_active = 1 ORDER BY id ASC");
    return $stmt->fetchAll();
}

/**
 * 10. ฟังก์ชันรายงานสรุปสถิติตามช่วงวันที่ (ออกแบบรองรับข้อมูล 100,000+ แถว)
 */
function getReportSummary(PDO $pdo, string $startDate, string $endDate): array
{
    if ($startDate > $endDate) {
        throw new InvalidArgumentException("วันที่เริ่มต้นต้องไม่มากกว่าวันที่สิ้นสุด");
    }

    // 1. นับผู้ป่วยใหม่ (ใช้ Index: idx_patients_registration_date)
    $stmtPatient = $pdo->prepare("
        SELECT COUNT(*) AS new_patients_count
        FROM patients
        WHERE registration_date BETWEEN :start_date AND :end_date
    ");
    $stmtPatient->execute([':start_date' => $startDate, ':end_date' => $endDate]);
    $newPatients = (int)($stmtPatient->fetch()['new_patients_count'] ?? 0);

    // 2. นับนัดหมายรวมและแยกตามสถานะ (ใช้ Index: idx_appointments_report_date_status)
    $stmtAppt = $pdo->prepare("
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
    $stmtDoc = $pdo->prepare("
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
