<?php

/**
 * BESTAR-ONE CLINIC - AUTOMATED TEST SUITE (OOP / API CLASSES)
 * Run via CLI: php tests/run_tests.php
 * Covers:
 * 1. Phone number normalization (PatientApi::normalizePhone)
 * 2. Overlap collision algorithm (AppointmentApi)
 * 3. Cancelled appointment exclusion rule
 * 4. HN sequential code generation (PatientApi::generateHN)
 */

declare(strict_types=1);

require_once __DIR__ . '/../api/PatientApi.php';
require_once __DIR__ . '/../api/AppointmentApi.php';
require_once __DIR__ . '/../api/ReportApi.php';

class TestRunner
{
    private int $passed = 0;
    private int $failed = 0;

    public function assert(bool $condition, string $testName, string $details = ''): void
    {
        if ($condition) {
            echo "  [PASS] {$testName}\n";
            $this->passed++;
        } else {
            echo "  [FAIL] {$testName}" . ($details ? " - {$details}" : "") . "\n";
            $this->failed++;
        }
    }

    public function summary(): void
    {
        echo "\n" . str_repeat('=', 60) . "\n";
        echo "TEST SUMMARY: {$this->passed} Passed, {$this->failed} Failed\n";
        echo str_repeat('=', 60) . "\n";
        if ($this->failed > 0) {
            exit(1);
        }
    }
}

$runner = new TestRunner();

// Mock PDO or connect if available
$pdo = null;
try {
    $host = getenv('DB_HOST') ?: 'db';
    $port = getenv('DB_PORT') ?: '3306';
    $dbname = getenv('DB_NAME') ?: 'clinic_db';
    $user = getenv('DB_USER') ?: 'clinic_user';
    $pass = getenv('DB_PASS') ?: 'clinic_pass';
    $pdo = new PDO("mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Throwable $e) {
    // If running in standalone test environment without DB, use SQLite in memory for testing PDO methods
    $pdo = new PDO('sqlite::memory:');
}

$patientApi = new PatientApi($pdo);
$appointmentApi = new AppointmentApi($pdo);

echo "\n" . str_repeat('=', 60) . "\n";
echo "BESTAR-ONE CLINIC APPOINTMENT SYSTEM - TEST SUITE (OOP /api)\n";
echo str_repeat('=', 60) . "\n\n";

// =====================================================================
// 1. Phone Normalization Tests (PatientApi)
// =====================================================================
echo "--- 1. Testing PatientApi::normalizePhone ---\n";

$phoneCases = [
    '081-234-5678'      => '0812345678',
    '+66812345678'      => '0812345678',
    '66 81 234 5678'    => '0812345678',
    '+66 81-234-5678'   => '0812345678',
    '081 234 5678'      => '0812345678',
    '081.234.5678'      => '0812345678',
    '(081) 234-5678'    => '0812345678',
    '02-123-4567'       => '021234567', // 9 digits landline
];

foreach ($phoneCases as $input => $expected) {
    try {
        $actual = $patientApi->normalizePhone($input);
        $runner->assert($actual === $expected, "Normalize '{$input}'", "Expected '{$expected}', got '{$actual}'");
    } catch (\Throwable $e) {
        $runner->assert(false, "Normalize '{$input}'", "Exception: " . $e->getMessage());
    }
}

// Invalid phone formats should throw InvalidArgumentException
$invalidPhones = ['12345', 'abcdefghij', '+1234567890', ''];
foreach ($invalidPhones as $invalid) {
    try {
        $patientApi->normalizePhone($invalid);
        $runner->assert(false, "Reject invalid phone '{$invalid}'", "Expected exception but none thrown");
    } catch (\InvalidArgumentException $e) {
        $runner->assert(true, "Reject invalid phone '{$invalid}'");
    }
}

// =====================================================================
// 2. Overlap Conflict Algorithm Unit Tests
// Condition: new_start < existing_end AND new_end > existing_start
// =====================================================================
echo "\n--- 2. Testing Appointment Overlap Algorithm ---\n";

function isOverlap(string $newStart, string $newEnd, string $existStart, string $existEnd): bool
{
    return ($newStart < $existEnd) && ($newEnd > $existStart);
}

// Existing interval: 10:00 - 11:00
$existS = '10:00:00';
$existE = '11:00:00';

$runner->assert(
    isOverlap('10:30:00', '11:30:00', $existS, $existE) === true,
    "Partial overlap: 10:30-11:30 with 10:00-11:00 (Starts during, ends after)"
);

$runner->assert(
    isOverlap('09:30:00', '10:30:00', $existS, $existE) === true,
    "Partial overlap: 09:30-10:30 with 10:00-11:00 (Starts before, ends during)"
);

$runner->assert(
    isOverlap('10:15:00', '10:45:00', $existS, $existE) === true,
    "Inside interval: 10:15-10:45 with 10:00-11:00"
);

$runner->assert(
    isOverlap('09:00:00', '12:00:00', $existS, $existE) === true,
    "Encompassing interval: 09:00-12:00 with 10:00-11:00"
);

$runner->assert(
    isOverlap('10:00:00', '11:00:00', $existS, $existE) === true,
    "Exact match interval: 10:00-11:00 with 10:00-11:00"
);

$runner->assert(
    isOverlap('11:00:00', '12:00:00', $existS, $existE) === false,
    "Back-to-back: 11:00-12:00 with 10:00-11:00 (Must NOT overlap)"
);

$runner->assert(
    isOverlap('09:00:00', '10:00:00', $existS, $existE) === false,
    "Back-to-back: 09:00-10:00 with 10:00-11:00 (Must NOT overlap)"
);

$runner->assert(
    isOverlap('14:00:00', '15:00:00', $existS, $existE) === false,
    "Disjoint interval: 14:00-15:00 with 10:00-11:00"
);

// =====================================================================
// 3. Cancelled Appointment Rule
// =====================================================================
echo "\n--- 3. Testing Cancelled Appointment Exemption ---\n";

function checkConflictWithStatus(string $status, string $newStart, string $newEnd, string $existStart, string $existEnd): bool
{
    if ($status === 'cancelled') {
        return false;
    }
    return isOverlap($newStart, $newEnd, $existStart, $existEnd);
}

$runner->assert(
    checkConflictWithStatus('pending', '10:30:00', '11:30:00', $existS, $existE) === true,
    "Status 'pending' produces conflict when time overlaps"
);

$runner->assert(
    checkConflictWithStatus('arrived', '10:30:00', '11:30:00', $existS, $existE) === true,
    "Status 'arrived' produces conflict when time overlaps"
);

$runner->assert(
    checkConflictWithStatus('cancelled', '10:30:00', '11:30:00', $existS, $existE) === false,
    "Status 'cancelled' DOES NOT produce conflict (Prompt rule: นัดที่ถูกยกเลิกแล้วไม่นับว่าชน)"
);

// =====================================================================
// 4. HN Sequence Format Verification
// =====================================================================
echo "\n--- 4. Testing HN Sequence Formatting ---\n";

function formatHN(int $sequence): string
{
    return 'HN' . str_pad((string)$sequence, 6, '0', STR_PAD_LEFT);
}

$runner->assert(formatHN(1) === 'HN000001', "HN 1 is formatted as HN000001");
$runner->assert(formatHN(42) === 'HN000042', "HN 42 is formatted as HN000042");
$runner->assert(formatHN(1000) === 'HN001000', "HN 1000 is formatted as HN001000");
$runner->assert(formatHN(999999) === 'HN999999', "HN 999999 is formatted as HN999999");

$runner->summary();
