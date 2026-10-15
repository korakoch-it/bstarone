<?php
// =====================================================================
// booking.php - หน้านัดหมายแพทย์ (Appointment Booking & Overlap Check)
// =====================================================================

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $newId = createAppointment($pdo, $_POST);
        $success = "สร้างนัดหมายสำเร็จเรียบร้อยแล้ว!";
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$patients = searchPatients($pdo, '', 100);
$doctors = getDoctors($pdo);

$pageTitle = 'สร้างนัดหมายแพทย์';
$activePage = 'booking';
require_once __DIR__ . '/header.php';
?>

<div class="card" style="max-width: 680px; margin: 0 auto;">
  <div class="card-header">
    <div>
      <h2 class="card-title"><span>➕</span> สร้างนัดหมายแพทย์</h2>
      <div class="card-subtitle">
        ระบุข้อมูลคนไข้ แพทย์ และช่วงเวลานัด ระบบจะตรวจสอบเวลานัดชนให้อัตโนมัติ
      </div>
    </div>
    <a href="index.php" class="btn btn-secondary btn-sm">← กลับไปหน้านัดวันนี้</a>
  </div>

  <?php if ($success): ?>
    <div class="alert alert-success">
      ✅ <?= htmlspecialchars($success) ?>
      <div style="margin-top: 8px;">
        <a href="index.php" class="btn btn-primary btn-sm">ดูรายการนัดวันนี้</a>
        <a href="booking.php" class="btn btn-secondary btn-sm">สร้างนัดหมายเพิ่ม</a>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($error): ?>
    <div class="alert alert-danger">
      <strong>⚠️ ไม่สามารถนัดหมายได้:</strong><br>
      <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <form method="POST" action="booking.php">
    <!-- เลือกผู้ป่วย -->
    <div class="form-group">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
        <label class="form-label" style="margin: 0;">เลือกผู้ป่วย <span style="color: red;">*</span></label>
        <a href="patients.php" style="font-size: 0.8rem; color: #064e3b; text-decoration: none;">+ ลงทะเบียนคนไข้ใหม่</a>
      </div>
      <select name="patient_id" class="form-control" required>
        <option value="">-- กรุณาเลือกคนไข้ --</option>
        <?php foreach ($patients as $p): ?>
          <option value="<?= $p['id'] ?>" <?= (isset($_POST['patient_id']) && (int)$_POST['patient_id'] === (int)$p['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($p['full_name']) ?> (<?= htmlspecialchars($p['hn']) ?>) - <?= htmlspecialchars($p['phone']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- เลือกแพทย์ -->
    <div class="form-group">
      <label class="form-label">เลือกแพทย์ผู้ตรวจ <span style="color: red;">*</span></label>
      <select name="doctor_id" class="form-control" required>
        <option value="">-- กรุณาเลือกแพทย์ --</option>
        <?php foreach ($doctors as $d): ?>
          <option value="<?= $d['id'] ?>" <?= (isset($_POST['doctor_id']) && (int)$_POST['doctor_id'] === (int)$d['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($d['name']) ?> - <?= htmlspecialchars($d['specialty']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- วันที่นัด -->
    <div class="form-group">
      <label class="form-label">วันที่นัดหมาย <span style="color: red;">*</span></label>
      <input type="date" name="appointment_date" class="form-control" required value="<?= htmlspecialchars($_POST['appointment_date'] ?? date('Y-m-d')) ?>">
    </div>

    <!-- เวลาเริ่ม - สิ้นสุด -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
      <div class="form-group">
        <label class="form-label">เวลาเริ่มต้น <span style="color: red;">*</span></label>
        <input type="time" name="start_time" class="form-control" required step="900" value="<?= htmlspecialchars($_POST['start_time'] ?? '10:00') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">เวลาสิ้นสุด <span style="color: red;">*</span></label>
        <input type="time" name="end_time" class="form-control" required step="900" value="<?= htmlspecialchars($_POST['end_time'] ?? '11:00') ?>">
      </div>
    </div>

    <!-- อาการ/หมายเหตุ -->
    <div class="form-group">
      <label class="form-label">อาการเบื้องต้น / หัตถการที่นัดหมาย</label>
      <textarea name="note" class="form-control" rows="2" placeholder="เช่น ปรึกษาเสริมจมูก, ฉีดบท็อกซ์, ติดตามผล"><?= htmlspecialchars($_POST['note'] ?? '') ?></textarea>
    </div>

    <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 1.5rem;">
      <a href="index.php" class="btn btn-secondary">ยกเลิก</a>
      <button type="submit" class="btn btn-gold">
        <span>📅</span> บันทึกนัดหมาย
      </button>
    </div>
  </form>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
