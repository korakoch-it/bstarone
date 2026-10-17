<?php
// =====================================================================
// api/index/ClassIndex.php - ฟังก์ชันของหน้า "รายการนัดวันนี้" (page/index.php)
// =====================================================================

require_once __DIR__ . '/../config/database.php';

class ClassMasterData extends Database
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getMasterDoctor()
    {
        $stmt = $this->pdo->query("SELECT id as value, name as label FROM doctors WHERE is_active = 1 ORDER BY id ASC");
        return $stmt->fetchAll();
    }

    public function getMasterPatient()
    {
        $stmt = $this->pdo->query("SELECT id as value, CONCAT(hn, ': ', full_name) as label FROM patients WHERE is_active = 1 ORDER BY id ASC");
        return $stmt->fetchAll();
    }

    public function getMasterAppointmentStatus()
    {
        return [
            ['value'=> '', 'label'=> 'ทั้งหมด'],
            ['value'=> 'pending', 'label'=> 'รอคิว'],
            ['value'=> 'arrived', 'label'=> 'มาแล้ว'],
            ['value'=> 'cancelled', 'label'=> 'ยกเลิก']
        ];
    }

    public function getMasterAppointmentTime()
    {
        return [
            ['value'=> '08:00', 'label'=> '08:00'],
            ['value'=> '08:30', 'label'=> '08:30'],
            ['value'=> '09:00', 'label'=> '09:00'],
            ['value'=> '09:30', 'label'=> '09:30'],
            ['value'=> '10:00', 'label'=> '10:00'],
            ['value'=> '10:30', 'label'=> '10:30'],
            ['value'=> '11:00', 'label'=> '11:00'],
            ['value'=> '11:30', 'label'=> '11:30'],
            ['value'=> '12:00', 'label'=> '12:00'],
            ['value'=> '12:30', 'label'=> '12:30'],
            ['value'=> '13:00', 'label'=> '13:00'],
            ['value'=> '13:30', 'label'=> '13:30'],
            ['value'=> '14:00', 'label'=> '14:00'],
            ['value'=> '14:30', 'label'=> '14:30'],
            ['value'=> '15:00', 'label'=> '15:00'],
            ['value'=> '15:30', 'label'=> '15:30'],
            ['value'=> '16:00', 'label'=> '16:00'],
            ['value'=> '16:30', 'label'=> '16:30'],
            ['value'=> '17:00', 'label'=> '17:00'],
            ['value'=> '17:30', 'label'=> '17:30'],
            ['value'=> '18:00', 'label'=> '18:00'],
            ['value'=> '18:30', 'label'=> '18:30'],
            ['value'=> '19:00', 'label'=> '19:00'],
            ['value'=> '19:30', 'label'=> '19:30'],
            ['value'=> '20:00', 'label'=> '20:00'],
            ['value'=> '20:30', 'label'=> '20:30']
        ];
    }
}
