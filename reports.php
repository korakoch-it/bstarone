<?php
// =====================================================================
// reports.php - หน้ารายงานสรุปและสถิติ (Summary Reports)
// =====================================================================

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');

$error = '';
$report = null;

try {
    $report = getReportSummary($pdo, $startDate, $endDate);
} catch (Exception $e) {
    $error = $e->getMessage();
}

$pageTitle = 'รายงานสรุปและสถิติ';
$activePage = 'reports';
require_once __DIR__ . '/header.php';
?>

<div class="card">
  <div class="card-header">
    <div>
      <h2 class="card-title"><span>📊</span> รายงานสรุปเชิงสถิติ</h2>
      <div class="card-subtitle">
        สรุปจำนวนผู้ป่วยใหม่และสถิตินัดหมาย ออกแบบเพื่อรองรับข้อมูลขนาดใหญ่ (100,000+ แถว)
      </div>
    </div>

    <!-- Date Range Filter -->
    <form method="GET" action="reports.php" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
      <label class="form-label" style="margin: 0;">ช่วงวันที่:</label>
      <input type="date" name="start_date" class="form-control" style="width: auto;" value="<?= htmlspecialchars($startDate) ?>">
      <span>ถึง</span>
      <input type="date" name="end_date" class="form-control" style="width: auto;" value="<?= htmlspecialchars($endDate) ?>">
      <button type="submit" class="btn btn-primary">ค้นหารายงาน</button>
    </form>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-danger">⚠️ <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <?php if ($report): ?>
    <!-- 5 Metrics Grid -->
    <div class="metrics-grid">
      <div class="metric-card metric-patients">
        <div class="metric-icon">👤</div>
        <div>
          <div class="metric-value"><?= number_format($report['new_patients_count']) ?></div>
          <div class="metric-label">จำนวนผู้ป่วยใหม่</div>
        </div>
      </div>

      <div class="metric-card metric-total">
        <div class="metric-icon">📋</div>
        <div>
          <div class="metric-value"><?= number_format($report['total_appointments']) ?></div>
          <div class="metric-label">จำนวนนัดหมายทั้งหมด</div>
        </div>
      </div>

      <div class="metric-card metric-pending">
        <div class="metric-icon">⏳</div>
        <div>
          <div class="metric-value"><?= number_format($report['count_pending']) ?></div>
          <div class="metric-label">สถานะ: รอรับบริการ</div>
        </div>
      </div>

      <div class="metric-card metric-arrived">
        <div class="metric-icon">✅</div>
        <div>
          <div class="metric-value"><?= number_format($report['count_arrived']) ?></div>
          <div class="metric-label">สถานะ: มาแล้ว</div>
        </div>
      </div>

      <div class="metric-card metric-cancelled">
        <div class="metric-icon">❌</div>
        <div>
          <div class="metric-value"><?= number_format($report['count_cancelled']) ?></div>
          <div class="metric-label">สถานะ: ยกเลิก</div>
        </div>
      </div>
    </div>

    <!-- Doctor Breakdown -->
    <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 1rem;">
      🩺 สถิติจำแนกตามแพทย์ผู้ตรวจ
    </h3>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>แพทย์ผู้ตรวจ</th>
            <th>ความเชี่ยวชาญ</th>
            <th style="width: 140px;">นัดหมายรวม</th>
            <th style="width: 120px;">มาแล้ว</th>
            <th style="width: 120px;">รอ</th>
            <th style="width: 120px;">ยกเลิก</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($report['doctor_breakdown'] as $doc): ?>
            <tr>
              <td><strong><?= htmlspecialchars($doc['doctor_name']) ?></strong></td>
              <td><?= htmlspecialchars($doc['specialty']) ?></td>
              <td><strong><?= number_format($doc['total_appts']) ?></strong></td>
              <td><span class="badge badge-arrived"><?= number_format($doc['count_arrived']) ?></span></td>
              <td><span class="badge badge-pending"><?= number_format($doc['count_pending']) ?></span></td>
              <td><span class="badge badge-cancelled"><?= number_format($doc['count_cancelled']) ?></span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
