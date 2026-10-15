<?php
// =====================================================================
// booking.php - หน้านัดหมายแพทย์ (Appointment Booking & Overlap Check)
// =====================================================================

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/api/AppointmentApi.php';
require_once __DIR__ . '/api/PatientApi.php';

$appointmentApi = new AppointmentApi($pdo);
$patientApi = new PatientApi($pdo);

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $newId = $appointmentApi->create($_POST);
        $success = "สร้างนัดหมายสำเร็จเรียบร้อยแล้ว!";
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$patients = $patientApi->search('', 100);
$doctors = $appointmentApi->getDoctors();

$pageTitle = 'สร้างนัดหมายแพทย์';
$activePage = 'booking';
require_once __DIR__ . '/header.php';
?>

<div class="max-w-2xl mx-auto bg-white border border-slate-200 rounded-2xl shadow-sm p-6 sm:p-8 mb-6">
  <!-- Header -->
  <div class="flex items-center justify-between pb-4 mb-6 border-b border-slate-100">
    <div>
      <h2 class="text-xl sm:text-2xl font-bold text-slate-800 flex items-center gap-2">
        <span>➕</span> <span>สร้างนัดหมายแพทย์</span>
      </h2>
      <p class="text-xs sm:text-sm text-slate-400 mt-1">
        ระบุข้อมูลคนไข้ แพทย์ และช่วงเวลานัด ระบบจะตรวจสอบเวลานัดชนให้อัตโนมัติ
      </p>
    </div>
    <a href="index.php" class="px-3 py-1.5 rounded-lg text-xs font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 border border-slate-200 transition-all">
      ← กลับหน้านัดวันนี้
    </a>
  </div>

  <!-- Success Notification -->
  <?php if ($success): ?>
    <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm">
      <div class="font-semibold flex items-center gap-2">
        <span>✅</span> <span><?= htmlspecialchars($success) ?></span>
      </div>
      <div class="mt-3 flex gap-2">
        <a href="index.php" class="px-3.5 py-1.5 rounded-lg text-xs font-semibold text-white bg-emerald-700 hover:bg-emerald-800 transition-all">
          ดูรายการนัดวันนี้
        </a>
        <a href="booking.php" class="px-3.5 py-1.5 rounded-lg text-xs font-medium text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 transition-all">
          สร้างนัดหมายเพิ่ม
        </a>
      </div>
    </div>
  <?php endif; ?>

  <!-- Overlap Conflict / Error Notification -->
  <?php if ($error): ?>
    <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm">
      <div class="font-bold flex items-center gap-2 text-rose-900">
        <span>⚠️</span> <span>ไม่สามารถนัดหมายได้ (ตรวจพบนัดชน):</span>
      </div>
      <div class="mt-1 text-rose-700">
        <?= htmlspecialchars($error) ?>
      </div>
    </div>
  <?php endif; ?>

  <form method="POST" action="booking.php" class="space-y-4">
    <!-- เลือกผู้ป่วย -->
    <div>
      <div class="flex items-center justify-between mb-1.5">
        <label class="block text-xs sm:text-sm font-semibold text-slate-700">
          เลือกผู้ป่วย <span class="text-rose-500">*</span>
        </label>
        <a href="patients.php" class="text-xs text-emerald-700 hover:underline font-medium">
          + ลงทะเบียนคนไข้ใหม่
        </a>
      </div>
      <select name="patient_id" class="w-full px-3.5 py-2.5 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 bg-white" required>
        <option value="">-- กรุณาเลือกคนไข้ --</option>
        <?php foreach ($patients as $p): ?>
          <option value="<?= $p['id'] ?>" <?= (isset($_POST['patient_id']) && (int)$_POST['patient_id'] === (int)$p['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($p['full_name']) ?> (<?= htmlspecialchars($p['hn']) ?>) - <?= htmlspecialchars($p['phone']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- เลือกแพทย์ -->
    <div>
      <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1.5">
        เลือกแพทย์ผู้ตรวจ <span class="text-rose-500">*</span>
      </label>
      <select name="doctor_id" class="w-full px-3.5 py-2.5 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 bg-white" required>
        <option value="">-- กรุณาเลือกแพทย์ --</option>
        <?php foreach ($doctors as $d): ?>
          <option value="<?= $d['id'] ?>" <?= (isset($_POST['doctor_id']) && (int)$_POST['doctor_id'] === (int)$d['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($d['name']) ?> - <?= htmlspecialchars($d['specialty']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- วันที่นัด -->
    <div>
      <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1.5">
        วันที่นัดหมาย <span class="text-rose-500">*</span>
      </label>
      <input type="date" name="appointment_date" class="w-full px-3.5 py-2.5 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 bg-white" required value="<?= htmlspecialchars($_POST['appointment_date'] ?? date('Y-m-d')) ?>">
    </div>

    <!-- เวลาเริ่ม - สิ้นสุด -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div>
        <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1.5">
          เวลาเริ่มต้น <span class="text-rose-500">*</span>
        </label>
        <input type="time" name="start_time" class="w-full px-3.5 py-2.5 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 bg-white" required step="900" value="<?= htmlspecialchars($_POST['start_time'] ?? '10:00') ?>">
      </div>
      <div>
        <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1.5">
          เวลาสิ้นสุด <span class="text-rose-500">*</span>
        </label>
        <input type="time" name="end_time" class="w-full px-3.5 py-2.5 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 bg-white" required step="900" value="<?= htmlspecialchars($_POST['end_time'] ?? '11:00') ?>">
      </div>
    </div>

    <!-- อาการ / หมายเหตุ -->
    <div>
      <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1.5">
        อาการเบื้องต้น / หัตถการที่นัดหมาย
      </label>
      <textarea name="note" rows="2" class="w-full px-3.5 py-2.5 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600" placeholder="เช่น ปรึกษาเสริมจมูก, ฉีดบท็อกซ์, ติดตามอาการหลังทำ"><?= htmlspecialchars($_POST['note'] ?? '') ?></textarea>
    </div>

    <!-- Action Buttons -->
    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
      <a href="index.php" class="px-4 py-2 rounded-lg text-sm font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 transition-all">
        ยกเลิก
      </a>
      <button type="submit" class="px-5 py-2 rounded-lg text-sm font-semibold text-white bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 shadow-sm transition-all flex items-center gap-1.5">
        <span>📅</span> <span>ยืนยันบันทึกนัดหมาย</span>
      </button>
    </div>
  </form>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
