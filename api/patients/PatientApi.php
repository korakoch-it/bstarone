<?php
// =====================================================================
// api/PatientApi.php - OOP Class สำหรับจัดการข้อมูลผู้ป่วย
// ใช้งานในหน้า: patients.php, booking.php
// =====================================================================

class PatientApi
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * แปลงเบอร์โทรศัพท์ให้อยู่ในรูปแบบมาตรฐาน 0812345678
     * รองรับ: 081-234-5678, +66812345678, 66 81 234 5678
     */
    public function normalizePhone(string $rawPhone): string
    {
        $clean = preg_replace('/[^\d+]/', '', trim($rawPhone));

        if (str_starts_with($clean, '+')) {
            $clean = substr($clean, 1);
        }

        if (str_starts_with($clean, '66')) {
            $clean = '0' . substr($clean, 2);
        }

        if (!preg_match('/^0\d{8,9}$/', $clean)) {
            throw new InvalidArgumentException("หมายเลขโทรศัพท์ไม่ถูกต้อง (กรุณากรอกเช่น 081-234-5678 หรือ +66812345678)");
        }

        return $clean;
    }

    /**
     * ออกรหัส HN อัตโนมัติในรูปแบบ HN000001
     */
    public function generateHN(): string
    {
        $stmt = $this->pdo->query("SELECT MAX(CAST(SUBSTRING(hn, 3) AS UNSIGNED)) AS max_seq FROM patients");
        $row = $stmt->fetch();
        $nextSeq = ($row && $row['max_seq'] !== null) ? ((int)$row['max_seq'] + 1) : 1;

        return 'HN' . str_pad((string)$nextSeq, 6, '0', STR_PAD_LEFT);
    }

    /**
     * ลงทะเบียนผู้ป่วยใหม่
     */
    public function create(array $data): array
    {
        $fullName = trim($data['full_name'] ?? '');
        $rawPhone = trim($data['phone'] ?? '');
        $birthDate = trim($data['birth_date'] ?? '');
        $regDate = !empty($data['registration_date']) ? trim($data['registration_date']) : date('Y-m-d');

        if (empty($fullName) || empty($birthDate)) {
            throw new InvalidArgumentException("กรุณากรอกชื่อ-นามสกุล และวันเกิด");
        }

        $phone = $this->normalizePhone($rawPhone);
        $hn = $this->generateHN();

        $stmt = $this->pdo->prepare("
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

        $newId = (int)$this->pdo->lastInsertId();
        return $this->getById($newId);
    }

    /**
     * แก้ไขข้อมูลผู้ป่วย (HN ล็อกห้ามแก้ไข)
     */
    public function update(int $id, array $data): bool
    {
        $fullName = trim($data['full_name'] ?? '');
        $rawPhone = trim($data['phone'] ?? '');
        $birthDate = trim($data['birth_date'] ?? '');

        if (empty($fullName) || empty($birthDate)) {
            throw new InvalidArgumentException("กรุณากรอกชื่อ-นามสกุล และวันเกิด");
        }

        $phone = $this->normalizePhone($rawPhone);

        $stmt = $this->pdo->prepare("
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
     * ค้นหาผู้ป่วยตามชื่อ, HN หรือเบอร์โทรศัพท์
     */
    public function search(string $keyword = '', int $limit = 50): array
    {
        $trimmed = trim($keyword);
        if (empty($trimmed)) {
            $stmt = $this->pdo->prepare("SELECT * FROM patients ORDER BY id DESC LIMIT :limit");
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        }

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

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':hn_kw', '%' . $trimmed . '%', PDO::PARAM_STR);
        $stmt->bindValue(':name_kw', '%' . $trimmed . '%', PDO::PARAM_STR);
        if (!empty($digits)) {
            $stmt->bindValue(':phone_kw', '%' . $digits . '%', PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * ค้นหาผู้ป่วยแบบแบ่งหน้า (Pagination)
     * @return array [
     *   'data' => array,
     *   'total' => int,
     *   'page' => int,
     *   'per_page' => int,
     *   'total_pages' => int,
     * ]
     */
    public function searchPaginated(string $keyword = '', int $page = 1, int $perPage = 10): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;

        $trimmed = trim($keyword);
        $digits = preg_replace('/\D/', '', $trimmed);
        if (str_starts_with($digits, '66')) {
            $digits = '0' . substr($digits, 2);
        }

        if (empty($trimmed)) {
            $countStmt = $this->pdo->query("SELECT COUNT(*) FROM patients");
            $total = (int)$countStmt->fetchColumn();

            $stmt = $this->pdo->prepare("SELECT * FROM patients ORDER BY id DESC LIMIT :limit OFFSET :offset");
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $data = $stmt->fetchAll();
        } else {
            $whereClause = "
                WHERE hn LIKE :hn_kw
                   OR full_name LIKE :name_kw
                   " . (!empty($digits) ? "OR phone LIKE :phone_kw" : "") . "
            ";

            $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM patients {$whereClause}");
            $countStmt->bindValue(':hn_kw', '%' . $trimmed . '%', PDO::PARAM_STR);
            $countStmt->bindValue(':name_kw', '%' . $trimmed . '%', PDO::PARAM_STR);
            if (!empty($digits)) {
                $countStmt->bindValue(':phone_kw', '%' . $digits . '%', PDO::PARAM_STR);
            }
            $countStmt->execute();
            $total = (int)$countStmt->fetchColumn();

            $sql = "
                SELECT * FROM patients
                {$whereClause}
                ORDER BY id DESC
                LIMIT :limit OFFSET :offset
            ";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':hn_kw', '%' . $trimmed . '%', PDO::PARAM_STR);
            $stmt->bindValue(':name_kw', '%' . $trimmed . '%', PDO::PARAM_STR);
            if (!empty($digits)) {
                $stmt->bindValue(':phone_kw', '%' . $digits . '%', PDO::PARAM_STR);
            }
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $data = $stmt->fetchAll();
        }

        $totalPages = (int)ceil($total / $perPage);
        if ($totalPages < 1) {
            $totalPages = 1;
        }

        return [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $totalPages,
        ];
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM patients WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
