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

<div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6 mb-6">
  <!-- Card Header -->
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 mb-6 border-b border-slate-100">
    <div>
      <h2 class="text-xl sm:text-2xl font-bold text-slate-800 flex items-center gap-2">
        <span>📅</span> <span>รายการนัดหมายประจำวัน</span>
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-mono font-bold bg-sky-50 text-sky-700 border border-sky-200">
          <?= count($appointments) ?> รายการ
        </span>
      </h2>
      <p class="text-xs sm:text-sm text-slate-400 mt-1">
        แสดงรายการนัดหมายเรียงตามเวลา สามารถกรองตามแพทย์และปรับเปลี่ยนสถานะได้
      </p>
    </div>

    <!-- Filter Form -->
    <form method="GET" action="index.php" class="flex flex-wrap items-center gap-2.5">
      <select name="doctor_id" class="px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 bg-white" onchange="this.form.submit()">
        <option value="">-- แพทย์ทุกคน (All Doctors) --</option>
        <?php foreach ($doctors as $doc): ?>
          <option value="<?= $doc['id'] ?>" <?= $selectedDoctor === (int)$doc['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($doc['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <input type="date" name="date" class="px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 bg-white" value="<?= htmlspecialchars($selectedDate) ?>" onchange="this.form.submit()">

      <a href="booking.php" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-semibold text-white bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 shadow-sm transition-all">
        <span>➕</span> <span>สร้างนัดหมายใหม่</span>
      </a>
    </form>
  </div>

  <!-- Notifications -->
  <?php if ($message): ?>
    <div class="mb-5 p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center gap-2">
      <span>✅</span> <span><?= htmlspecialchars($message) ?></span>
    </div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="mb-5 p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-center gap-2">
      <span>⚠️</span> <span><?= htmlspecialchars($error) ?></span>
    </div>
  <?php endif; ?>

  <!-- Appointments Table -->
  <div class="overflow-x-auto rounded-xl border border-slate-100">
    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
      <thead class="bg-slate-50 text-slate-500 uppercase text-xs font-semibold tracking-wider">
        <tr>
          <th class="px-4 py-3.5 w-32">เวลานัด</th>
          <th class="px-4 py-3.5 w-28">HN</th>
          <th class="px-4 py-3.5">ชื่อคนไข้</th>
          <th class="px-4 py-3.5">เบอร์โทร</th>
          <th class="px-4 py-3.5">แพทย์ผู้ตรวจ</th>
          <th class="px-4 py-3.5">หมายเหตุ/อาการ</th>
          <th class="px-4 py-3.5 w-28">สถานะ</th>
          <th class="px-4 py-3.5 w-36">เปลี่ยนสถานะ</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100 bg-white">
        <?php if (empty($appointments)): ?>
          <tr>
            <td colspan="8" class="text-center py-12 text-slate-400">
              <div class="text-3xl mb-1">📋</div>
              <div>ไม่มีรายการนัดหมายสำหรับเงื่อนไขที่เลือก</div>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($appointments as $item): 
            $timeRange = substr($item['start_time'], 0, 5) . ' - ' . substr($item['end_time'], 0, 5);
            $badgeColor = 'bg-amber-50 text-amber-800 border-amber-200';
            $badgeText = 'รอรับบริการ';
            if ($item['status'] === 'arrived') {
                $badgeColor = 'bg-emerald-50 text-emerald-800 border-emerald-200';
                $badgeText = 'มาถึงแล้ว';
            } elseif ($item['status'] === 'cancelled') {
                $badgeColor = 'bg-rose-50 text-rose-800 border-rose-200';
                $badgeText = 'ยกเลิกแล้ว';
            }
          ?>
            <tr class="hover:bg-slate-50/80 transition-colors">
              <td class="px-4 py-3.5 font-bold text-slate-800 whitespace-nowrap">
                <?= $timeRange ?> น.
              </td>
              <td class="px-4 py-3.5 whitespace-nowrap">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-mono font-semibold bg-sky-50 text-sky-700 border border-sky-200">
                  <?= htmlspecialchars($item['patient_hn']) ?>
                </span>
              </td>
              <td class="px-4 py-3.5 font-semibold text-slate-800">
                <?= htmlspecialchars($item['patient_name']) ?>
              </td>
              <td class="px-4 py-3.5 text-slate-600 font-mono text-xs">
                <?= htmlspecialchars($item['patient_phone']) ?>
              </td>
              <td class="px-4 py-3.5">
                <div class="font-medium text-slate-800"><?= htmlspecialchars($item['doctor_name']) ?></div>
                <div class="text-xs text-slate-400"><?= htmlspecialchars($item['doctor_specialty']) ?></div>
              </td>
              <td class="px-4 py-3.5 text-slate-600 max-w-xs truncate">
                <?= htmlspecialchars($item['note'] ?: '-') ?>
              </td>
              <td class="px-4 py-3.5 whitespace-nowrap">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border <?= $badgeColor ?>">
                  <?= $badgeText ?>
                </span>
              </td>
              <td class="px-4 py-3.5 whitespace-nowrap">
                <form method="POST" action="index.php" class="m-0">
                  <input type="hidden" name="action" value="update_status">
                  <input type="hidden" name="appointment_id" value="<?= $item['id'] ?>">
                  <select name="status" class="px-2 py-1 text-xs border border-slate-300 rounded-md bg-white font-medium focus:outline-none focus:ring-1 focus:ring-emerald-500 cursor-pointer" onchange="this.form.submit()">
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
