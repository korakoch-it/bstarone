-- =====================================================================
-- Bestar-one Clinic Appointment System Database Schema
-- Database: clinic_db
-- Designed for high performance, data integrity, and 100,000+ scale
-- =====================================================================

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

CREATE DATABASE IF NOT EXISTS `clinic_db` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `clinic_db`;

-- ---------------------------------------------------------------------
-- Table: doctors (ข้อมูลแพทย์)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `doctors` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL COMMENT 'ชื่อ-นามสกุลแพทย์',
    `specialty` VARCHAR(255) NOT NULL COMMENT 'ความเชี่ยวชาญ/สาขา',
    `phone` VARCHAR(20) DEFAULT NULL COMMENT 'เบอร์โทรติดต่อ',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'สถานะพร้อมตรวจ (1=Active, 0=Inactive)',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table: patients (ข้อมูลผู้ป่วย)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `patients` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `hn` VARCHAR(20) NOT NULL COMMENT 'Hospital Number รหัสประจำตัวผู้ป่วย เช่น HN000001',
    `full_name` VARCHAR(150) NOT NULL COMMENT 'ชื่อ-นามสกุล ผู้ป่วย',
    `phone` VARCHAR(20) NOT NULL COMMENT 'เบอร์โทรศัพท์ที่ผ่านการ normalize แล้ว เช่น 0812345678',
    `birth_date` DATE NOT NULL COMMENT 'วันเกิด',
    `registration_date` DATE NOT NULL COMMENT 'วันที่ลงทะเบียนเข้าระบบ',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'สถานะผู้ป่วย (1=Active, 0=Inactive)',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Constraints
    UNIQUE KEY `uk_patients_hn` (`hn`),

    -- Indexes for fast search and report generation
    INDEX `idx_patients_hn` (`hn`),
    INDEX `idx_patients_phone` (`phone`),
    INDEX `idx_patients_full_name` (`full_name`),
    INDEX `idx_patients_registration_date` (`registration_date`) COMMENT 'Index สำหรับรายงานสรุปจำนวนผู้ป่วยใหม่ตามช่วงวันที่'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table: appointments (ข้อมูลการนัดหมาย)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `appointments` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `patient_id` BIGINT UNSIGNED NOT NULL COMMENT 'รหัสผู้ป่วย',
    `doctor_id` BIGINT UNSIGNED NOT NULL COMMENT 'รหัสแพทย์',
    `appointment_date` DATE NOT NULL COMMENT 'วันที่นัดหมาย',
    `start_time` TIME NOT NULL COMMENT 'เวลาเริ่มต้นนัด',
    `end_time` TIME NOT NULL COMMENT 'เวลาสิ้นสุดนัด',
    `status` ENUM('pending', 'confirmed', 'arrived', 'completed', 'cancelled') NOT NULL DEFAULT 'pending' COMMENT 'สถานะ: pending=รอเข้ารับบริการ  , confirmed=ยืนยันแล้ว, arrived=มาแล้ว, completed=เสร็จสิ้น, cancelled=ยกเลิก',
    `note` TEXT DEFAULT NULL COMMENT 'บันทึกอาการเบื้องต้น/หมายเหตุ',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'สถานะการนัดหมาย (1=Active, 0=Inactive)',
    `is_delete` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'สถานะการลบ (0=ปกติ, 1=ลบข้อมูล)',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Foreign Keys
    CONSTRAINT `fk_appointments_patient` FOREIGN KEY (`patient_id`) 
        REFERENCES `patients` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_appointments_doctor` FOREIGN KEY (`doctor_id`) 
        REFERENCES `doctors` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,

    -- COMPOSITE INDEXES FOR HIGH-PERFORMANCE QUERYING (100k+ Rows):
    -- 1. ตรวจสอบนัดชน (Overlap Check): กรองแพทย์, วันที่, สถานะ และช่วงเวลา start_time, end_time
    INDEX `idx_appointments_overlap_check` (`doctor_id`, `appointment_date`, `status`, `start_time`, `end_time`),

    -- 2. หน้ารายงานสรุป: WHERE appointment_date BETWEEN ? AND ? GROUP BY status
    INDEX `idx_appointments_report_date_status` (`appointment_date`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
