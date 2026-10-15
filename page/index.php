<?php
// =====================================================================
// page/index.php - หน้ารายการนัดของวันนี้ (Today's Appointments Queue)
// =====================================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../api/AppointmentApi.php';

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
$selectedDoctor = !empty($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : (!empty($_POST['doctor_id']) ? (int)$_POST['doctor_id'] : null);
$selectedDate = !empty($_GET['date']) ? trim($_GET['date']) : (!empty($_POST['date']) ? trim($_POST['date']) : date('Y-m-d'));
$page = max(1, (int)($_GET['p'] ?? ($_POST['p'] ?? 1)));
$perPage = 5;

// ดึงข้อมูลผ่าน OOP Class แบบแบ่งหน้า
$doctors = $appointmentApi->getDoctors();
$paginated = $appointmentApi->getTodayPaginated($selectedDoctor, $selectedDate, $page, $perPage);
$appointments = $paginated['data'];
$total = $paginated['total'];
$totalPages = $paginated['total_pages'];

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
          <?= number_format($total) ?> รายการ
        </span>
      </h2>
      <p class="text-xs sm:text-sm text-slate-400 mt-1">
        แสดงรายการนัดหมายเรียงตามเวลา สามารถกรองตามแพทย์และปรับเปลี่ยนสถานะได้ (หน้า <?= $page ?>/<?= $totalPages ?>)
      </p>
    </div>

    <!-- Filter Form -->
    <form method="GET" action="/page/index.php" class="flex flex-wrap items-center gap-2.5">
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
                <form method="POST" action="/page/index.php" class="m-0">
                  <input type="hidden" name="action" value="update_status">
                  <input type="hidden" name="appointment_id" value="<?= $item['id'] ?>">
                  <input type="hidden" name="p" value="<?= $page ?>">
                  <input type="hidden" name="doctor_id" value="<?= htmlspecialchars((string)($selectedDoctor ?? '')) ?>">
                  <input type="hidden" name="date" value="<?= htmlspecialchars($selectedDate) ?>">
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

  <!-- Pagination Controls -->
  <?php if ($totalPages > 1): ?>
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-4 mt-4 border-t border-slate-100">
      <div class="text-xs text-slate-500">
        แสดงรายการที่ <span class="font-semibold text-slate-700"><?= $total > 0 ? (($page - 1) * $perPage + 1) : 0 ?></span> ถึง 
        <span class="font-semibold text-slate-700"><?= min($page * $perPage, $total) ?></span> 
        จากทั้งหมด <span class="font-semibold text-slate-700"><?= number_format($total) ?></span> รายการ
      </div>
      
      <nav class="inline-flex items-center -space-x-px rounded-lg shadow-sm" aria-label="Pagination">
        <!-- ปุ่มก่อนหน้า -->
        <?php if ($page > 1): ?>
          <a href="?<?= http_build_query(array_merge($_GET, ['p' => $page - 1])) ?>" class="relative inline-flex items-center px-2.5 py-1.5 rounded-l-lg border border-slate-200 bg-white text-xs font-medium text-slate-600 hover:bg-slate-50 transition-colors">
            ‹ ก่อนหน้า
          </a>
        <?php else: ?>
          <span class="relative inline-flex items-center px-2.5 py-1.5 rounded-l-lg border border-slate-200 bg-slate-50 text-xs font-medium text-slate-300 cursor-not-allowed">
            ‹ ก่อนหน้า
          </span>
        <?php endif; ?>

        <!-- เลขหน้า -->
        <?php
        $startPage = max(1, $page - 2);
        $endPage = min($totalPages, $page + 2);
        if ($startPage > 1) {
            echo '<a href="?' . http_build_query(array_merge($_GET, ['p' => 1])) . '" class="relative inline-flex items-center px-3 py-1.5 border border-slate-200 bg-white text-xs font-medium text-slate-600 hover:bg-slate-50">1</a>';
            if ($startPage > 2) {
                echo '<span class="relative inline-flex items-center px-2 py-1.5 border border-slate-200 bg-slate-50 text-xs text-slate-400">...</span>';
            }
        }
        for ($i = $startPage; $i <= $endPage; $i++):
            if ($i === $page): ?>
                <span class="relative z-10 inline-flex items-center px-3 py-1.5 border border-emerald-800 bg-emerald-800 text-xs font-bold text-white shadow-sm">
                  <?= $i ?>
                </span>
            <?php else: ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['p' => $i])) ?>" class="relative inline-flex items-center px-3 py-1.5 border border-slate-200 bg-white text-xs font-medium text-slate-600 hover:bg-slate-50 transition-colors">
                  <?= $i ?>
                </a>
            <?php endif;
        endfor;
        if ($endPage < $totalPages) {
            if ($endPage < $totalPages - 1) {
                echo '<span class="relative inline-flex items-center px-2 py-1.5 border border-slate-200 bg-slate-50 text-xs text-slate-400">...</span>';
            }
            echo '<a href="?' . http_build_query(array_merge($_GET, ['p' => $totalPages])) . '" class="relative inline-flex items-center px-3 py-1.5 border border-slate-200 bg-white text-xs font-medium text-slate-600 hover:bg-slate-50">' . $totalPages . '</a>';
        }
        ?>

        <!-- ปุ่มถัดไป -->
        <?php if ($page < $totalPages): ?>
          <a href="?<?= http_build_query(array_merge($_GET, ['p' => $page + 1])) ?>" class="relative inline-flex items-center px-2.5 py-1.5 rounded-r-lg border border-slate-200 bg-white text-xs font-medium text-slate-600 hover:bg-slate-50 transition-colors">
            ถัดไป ›
          </a>
        <?php else: ?>
          <span class="relative inline-flex items-center px-2.5 py-1.5 rounded-r-lg border border-slate-200 bg-slate-50 text-xs font-medium text-slate-300 cursor-not-allowed">
            ถัดไป ›
          </span>
        <?php endif; ?>
      </nav>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
