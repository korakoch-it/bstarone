<?php
require_once __DIR__ . '/../config/database.php';

class ClassPatient extends Database
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * แปลงเบอร์โทรศัพท์ให้อยู่ในรูปแบบมาตรฐาน 0812345678
     * รองรับ: 081-234-5678, +66812345678, 66 81 234 5678
     */
    public function normalizePhone(string $rawPhone)
    {
        $clean = preg_replace('/[^\d+]/', '', trim($rawPhone));

        if (str_starts_with($clean, '+')) {
            $clean = substr($clean, 1);
        }

        if (str_starts_with($clean, '66')) {
            $clean = '0' . substr($clean, 2);
        }

        if (!preg_match('/^0\d{9}$/', $clean)) {
            throw new InvalidArgumentException("หมายเลขโทรศัพท์ไม่ถูกต้อง");
        }

        return $clean;
    }

    /**
     * ออกรหัส HN อัตโนมัติในรูปแบบ HN000001
     */
    public function generateHN()
    {
        $stmt = $this->pdo->query("SELECT COUNT(id) AS running_no FROM patients");
        $row = $stmt->fetch();
        $runningNo = ($row && $row['running_no'] !== null) ? ((int)$row['running_no'] + 1) : 1;

        return 'HN' . str_pad((string)$runningNo, 6, '0', STR_PAD_LEFT);
    }

    /**
     * ลงทะเบียนผู้ป่วยใหม่
     */
    public function create(array $data)
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
        $isActive = isset($data['is_active']) ? ((int)$data['is_active'] ? 1 : 0) : 1;

        $stmt = $this->pdo->prepare("
            INSERT INTO patients (hn, full_name, phone, birth_date, registration_date, is_active)
            VALUES (:hn, :full_name, :phone, :birth_date, :registration_date, :is_active)
        ");
        $stmt->execute([
            ':hn' => $hn,
            ':full_name' => $fullName,
            ':phone' => $phone,
            ':birth_date' => $birthDate,
            ':registration_date' => $regDate,
            ':is_active' => $isActive,
        ]);

        $newId = (int)$this->pdo->lastInsertId();
        return $this->getById($newId);
    }

    /**
     * แก้ไขข้อมูลผู้ป่วย (HN ล็อกห้ามแก้ไข)
     */
    public function update(int $id, array $data)
    {
        $fullName = trim($data['full_name'] ?? '');
        $rawPhone = trim($data['phone'] ?? '');
        $birthDate = trim($data['birth_date'] ?? '');

        if (empty($fullName) || empty($birthDate)) {
            throw new InvalidArgumentException("กรุณากรอกชื่อ-นามสกุล และวันเกิด");
        }

        $phone = $this->normalizePhone($rawPhone);
        $isActive = isset($data['is_active']) ? ((int)$data['is_active'] ? 1 : 0) : 1;

        $stmt = $this->pdo->prepare("
            UPDATE patients
            SET full_name = :full_name, phone = :phone, birth_date = :birth_date, is_active = :is_active
            WHERE id = :id
        ");
        return $stmt->execute([
            ':full_name' => $fullName,
            ':phone' => $phone,
            ':birth_date' => $birthDate,
            ':is_active' => $isActive,
            ':id' => $id,
        ]);
    }

    public function getOverview(string $filterPatient = '', int $pageIndex = 1, int $pageSize = 10)
    {
        $pageIndex = max(1, $pageIndex);
        $pageSize = max(1, $pageSize);
        $offset = ($pageIndex - 1) * $pageSize;

        $filterPatient = !empty($filterPatient) ? trim($filterPatient) : '';
        if (empty($filterPatient)) {
            $countStmt = $this->pdo->query("SELECT COUNT(*) FROM patients");
            $totalCount = (int)$countStmt->fetchColumn();

            $stmt = $this->pdo->prepare("SELECT id
                , hn
                , full_name
                , phone
                , birth_date 
                , registration_date 
                , is_active
                FROM patients ORDER BY id DESC LIMIT :limit OFFSET :offset");
            $stmt->bindValue(':limit', $pageSize, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $data = $stmt->fetchAll();
        } else {
            $whereClause = "
                WHERE 1=1
                  AND (hn LIKE :hn_kw
                       OR full_name LIKE :name_kw
                       OR phone LIKE :phone_kw)
            ";

            $countStmt = $this->pdo->prepare("SELECT COUNT(id) FROM patients {$whereClause}");
            $countStmt->bindValue(':hn_kw', '%' . $filterPatient . '%', PDO::PARAM_STR);
            $countStmt->bindValue(':name_kw', '%' . $filterPatient . '%', PDO::PARAM_STR);
            $countStmt->bindValue(':phone_kw', '%' . $filterPatient . '%', PDO::PARAM_STR);
            $countStmt->execute();
            $totalCount = (int)$countStmt->fetchColumn();

            $sql = "
                SELECT id
                , hn
                , full_name
                , phone
                , birth_date 
                , registration_date 
                , is_active
                FROM patients
                {$whereClause}
                ORDER BY id DESC
                LIMIT :limit OFFSET :offset
            ";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':hn_kw', '%' . $filterPatient . '%', PDO::PARAM_STR);
            $stmt->bindValue(':name_kw', '%' . $filterPatient . '%', PDO::PARAM_STR);
            $stmt->bindValue(':phone_kw', '%' . $filterPatient . '%', PDO::PARAM_STR);
            $stmt->bindValue(':limit', $pageSize, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $data = $stmt->fetchAll();
        }

        return [
            'data' => $data,
            'pageIndex' => $pageIndex,
            'pageSize' => $pageSize,
            'totalCount' => $totalCount,
            'totalPages' => max(1, (int)ceil($totalCount / $pageSize)),
        ];
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT id
        , hn
        , full_name
        , phone
        , birth_date 
        , registration_date
        , is_active
        FROM patients WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
