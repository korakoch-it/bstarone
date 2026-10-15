<?php
// =====================================================================
// index.php - หน้ารายการนัดของวันนี้ (Today's Appointments Queue)
// =====================================================================

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/api/AppointmentApi.php';

$appointmentApi = new AppointmentApi($pdo);

// รับค่าเปลี่ยนสถานะเมื่อมีการกดเปลี่ยน (POST)
$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $apptId = (int)($_POST['appointment_id'] ?? 0);
    $newStatus = trim($_POST['status'] ?? '');
    try {
        $appointmentApi->updateStatus($apptId, $newStatus);
        $message = "อัปเดตสถานะนัดหมายเรียบร้อยแล้ว";
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// ตัวกรอง
$selectedDoctor = !empty($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : null;
$selectedDate = !empty($_GET['date']) ? trim($_GET['date']) : date('Y-m-d');

// ดึงข้อมูลผ่าน OOP Class
$doctors = $appointmentApi->getDoctors();
$appointments = $appointmentApi->getToday($selectedDoctor, $selectedDate);

$pageTitle = 'รายการนัดหมายประจำวัน';
$activePage = 'today';
require_once __DIR__ . '/header.php';
?>

<div class="card">
  <div class="card-header">
    <div>
      <h2 class="card-title">
        <span>📅</span> รายการนัดหมายประจำวัน
        <span class="badge badge-hn" style="font-size: 0.85rem;"><?= count($appointments) ?> รายการ</span>
      </h2>
      <div class="card-subtitle">
        แสดงรายการนัดหมายเรียงตามเวลา สามารถกรองตามแพทย์และปรับเปลี่ยนสถานะได้
      </div>
    </div>

    <!-- Filter Bar -->
    <form method="GET" action="index.php" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
      <select name="doctor_id" class="form-control" style="width: auto;" onchange="this.form.submit()">
        <option value="">-- แพทย์ทุกคน (All Doctors) --</option>
        <?php foreach ($doctors as $doc): ?>
          <option value="<?= $doc['id'] ?>" <?= $selectedDoctor === (int)$doc['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($doc['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <input type="date" name="date" class="form-control" style="width: auto;" value="<?= htmlspecialchars($selectedDate) ?>" onchange="this.form.submit()">

      <a href="booking.php" class="btn btn-gold">
        <span>➕</span> สร้างนัดหมายใหม่
      </a>
    </form>
  </div>

  <?php if ($message): ?>
    <div class="alert alert-success">✅ <?= htmlspecialchars($message) ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert alert-danger">⚠️ <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <!-- Table -->
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th style="width: 140px;">เวลานัด</th>
          <th style="width: 110px;">HN</th>
          <th>ชื่อคนไข้</th>
          <th>เบอร์โทร</th>
          <th>แพทย์ผู้ตรวจ</th>
          <th>หมายเหตุ/อาการ</th>
          <th style="width: 120px;">สถานะ</th>
          <th style="width: 150px;">เปลี่ยนสถานะ</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($appointments)): ?>
          <tr>
            <td colspan="8" style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
              📋 ไม่มีรายการนัดหมายสำหรับเงื่อนไขที่เลือก
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($appointments as $item): 
            $timeRange = substr($item['start_time'], 0, 5) . ' - ' . substr($item['end_time'], 0, 5);
            $badgeClass = 'badge-pending';
            $badgeText = 'รอรับบริการ';
            if ($item['status'] === 'arrived') {
                $badgeClass = 'badge-arrived';
                $badgeText = 'มาถึงแล้ว';
            } elseif ($item['status'] === 'cancelled') {
                $badgeClass = 'badge-cancelled';
                $badgeText = 'ยกเลิกแล้ว';
            }
          ?>
            <tr>
              <td><strong><?= $timeRange ?></strong> น.</td>
              <td><span class="badge badge-hn"><?= htmlspecialchars($item['patient_hn']) ?></span></td>
              <td><strong><?= htmlspecialchars($item['patient_name']) ?></strong></td>
              <td><code><?= htmlspecialchars($item['patient_phone']) ?></code></td>
              <td>
                <div><?= htmlspecialchars($item['doctor_name']) ?></div>
                <div style="font-size: 0.75rem; color: var(--text-muted);"><?= htmlspecialchars($item['doctor_specialty']) ?></div>
              </td>
              <td style="color: var(--text-secondary);"><?= htmlspecialchars($item['note'] ?: '-') ?></td>
              <td>
                <span class="badge <?= $badgeClass ?>"><?= $badgeText ?></span>
              </td>
              <td>
                <!-- Simple Form for changing status -->
                <form method="POST" action="index.php" style="margin: 0;">
                  <input type="hidden" name="action" value="update_status">
                  <input type="hidden" name="appointment_id" value="<?= $item['id'] ?>">
                  <select name="status" class="form-control" style="padding: 4px 8px; font-size: 0.82rem;" onchange="this.form.submit()">
                    <option value="pending" <?= $item['status'] === 'pending' ? 'selected' : '' ?>>⏳ รอ</option>
                    <option value="arrived" <?= $item['status'] === 'arrived' ? 'selected' : '' ?>>✅ มาแล้ว</option>
                    <option value="cancelled" <?= $item['status'] === 'cancelled' ? 'selected' : '' ?>>❌ ยกเลิก</option>
                  </select>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
