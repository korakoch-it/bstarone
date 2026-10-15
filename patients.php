<?php
// =====================================================================
// patients.php - ระบบจัดการข้อมูลผู้ป่วย (Patient Management)
// =====================================================================

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/api/PatientApi.php';

$patientApi = new PatientApi($pdo);

$message = '';
$error = '';

// จัดการการส่งฟอร์ม (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // 1. เพิ่มผู้ป่วยใหม่
    if ($action === 'create') {
        try {
            $newPatient = $patientApi->create($_POST);
            $message = "ลงทะเบียนผู้ป่วยใหม่สำเร็จ รหัส HN: " . $newPatient['hn'];
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
    // 2. แก้ไขผู้ป่วย
    elseif ($action === 'update') {
        $id = (int)($_POST['patient_id'] ?? 0);
        try {
            $patientApi->update($id, $_POST);
            $message = "แก้ไขข้อมูลผู้ป่วยเรียบร้อยแล้ว";
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// ค้นหา
$keyword = trim($_GET['search'] ?? '');
$patients = $patientApi->search($keyword);

// ตรวจสอบว่ากำลังกดแก้ไขใครอยู่หรือไม่
$editPatient = null;
if (isset($_GET['edit_id'])) {
    $editPatient = $patientApi->getById((int)$_GET['edit_id']);
}

$pageTitle = 'จัดการข้อมูลผู้ป่วย';
$activePage = 'patients';
require_once __DIR__ . '/header.php';
?>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px; align-items: start;">

  <!-- Form Column (ซ้าย): ฟอร์มเพิ่ม หรือ แก้ไข -->
  <div class="card">
    <h3 class="card-title" style="font-size: 1.15rem; margin-bottom: 1rem;">
      <?= $editPatient ? '✏️ แก้ไขข้อมูลผู้ป่วย' : '➕ ลงทะเบียนผู้ป่วยใหม่' ?>
    </h3>

    <?php if ($message): ?>
      <div class="alert alert-success">✅ <?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-danger">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="patients.php">
      <input type="hidden" name="action" value="<?= $editPatient ? 'update' : 'create' ?>">
      <?php if ($editPatient): ?>
        <input type="hidden" name="patient_id" value="<?= $editPatient['id'] ?>">
        <div class="form-group">
          <label class="form-label">รหัสประจำตัวผู้ป่วย (HN)</label>
          <input type="text" class="form-control" value="<?= htmlspecialchars($editPatient['hn']) ?>" readonly style="background: #f1f5f9; font-weight: 700; color: #0369a1;">
          <div class="form-hint">🔒 รหัส HN ถูกล็อกไม่สามารถแก้ไขได้</div>
        </div>
      <?php endif; ?>

      <div class="form-group">
        <label class="form-label">ชื่อ-นามสกุล <span style="color: red;">*</span></label>
        <input type="text" name="full_name" class="form-control" required placeholder="เช่น ธนากรณ์ เจริญสุข" value="<?= htmlspecialchars($editPatient['full_name'] ?? '') ?>">
      </div>

      <div class="form-group">
        <label class="form-label">เบอร์โทรศัพท์ <span style="color: red;">*</span></label>
        <input type="text" name="phone" class="form-control" required placeholder="เช่น 081-234-5678 หรือ +66812345678" value="<?= htmlspecialchars($editPatient['phone'] ?? '') ?>">
        <div class="form-hint">💡 รองรับหลายรูปแบบ ระบบจะแปลงเป็นมาตรฐาน 0812345678 ให้อัตโนมัติ</div>
      </div>

      <div class="form-group">
        <label class="form-label">วันเกิด <span style="color: red;">*</span></label>
        <input type="date" name="birth_date" class="form-control" required value="<?= htmlspecialchars($editPatient['birth_date'] ?? '') ?>">
      </div>

      <div style="display: flex; gap: 10px; margin-top: 1rem;">
        <?php if ($editPatient): ?>
          <a href="patients.php" class="btn btn-secondary" style="flex: 1;">ยกเลิก</a>
          <button type="submit" class="btn btn-primary" style="flex: 2;">บันทึกการแก้ไข</button>
        <?php else: ?>
          <button type="submit" class="btn btn-primary" style="width: 100%;">
            <span>➕</span> บันทึกข้อมูลผู้ป่วย
          </button>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <!-- Table Column (ขวา): ตารางผู้ป่วย + ช่องค้นหา -->
  <div class="card">
    <div class="card-header">
      <div>
        <h2 class="card-title">
          <span>👥</span> รายชื่อผู้ป่วยในระบบ
          <span class="badge badge-hn" style="font-size: 0.85rem;"><?= count($patients) ?> คน</span>
        </h2>
        <div class="card-subtitle">ค้นหาจากชื่อ, HN หรือเบอร์โทรศัพท์</div>
      </div>

      <form method="GET" action="patients.php" style="display: flex; gap: 8px;">
        <input type="text" name="search" class="form-control" placeholder="🔍 ค้นหา (ชื่อ, HN, เบอร์)..." value="<?= htmlspecialchars($keyword) ?>">
        <button type="submit" class="btn btn-secondary">ค้นหา</button>
        <?php if ($keyword): ?>
          <a href="patients.php" class="btn btn-secondary btn-sm" title="ล้างค่าค้นหา">ล้าง</a>
        <?php endif; ?>
      </form>
    </div>

    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th style="width: 100px;">HN</th>
            <th>ชื่อ-นามสกุล</th>
            <th>เบอร์โทร</th>
            <th>วันเกิด</th>
            <th>วันที่ลงทะเบียน</th>
            <th style="width: 90px;">จัดการ</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($patients)): ?>
            <tr>
              <td colspan="6" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                ไม่พบข้อมูลผู้ป่วย
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($patients as $p): ?>
              <tr>
                <td><span class="badge badge-hn"><?= htmlspecialchars($p['hn']) ?></span></td>
                <td><strong><?= htmlspecialchars($p['full_name']) ?></strong></td>
                <td><code><?= htmlspecialchars($p['phone']) ?></code></td>
                <td><?= htmlspecialchars($p['birth_date']) ?></td>
                <td><?= htmlspecialchars($p['registration_date']) ?></td>
                <td>
                  <a href="patients.php?edit_id=<?= $p['id'] ?>" class="btn btn-secondary btn-sm">
                    ✏️ แก้ไข
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/footer.php'; ?>
