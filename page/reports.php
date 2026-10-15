<?php
// =====================================================================
// page/reports.php - หน้ารายงานสรุปและสถิติ (Summary Reports)
// =====================================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../api/ReportApi.php';

$reportApi = new ReportApi($pdo);

$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');

$error = '';
$report = null;

try {
    $report = $reportApi->getSummary($startDate, $endDate);
} catch (Exception $e) {
    $error = $e->getMessage();
}

$pageTitle = 'รายงานสรุปและสถิติ';
$activePage = 'reports';
require_once __DIR__ . '/header.php';
?>

<div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6 mb-6">
  <!-- Card Header -->
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 mb-6 border-b border-slate-100">
    <div>
      <h2 class="text-xl sm:text-2xl font-bold text-slate-800 flex items-center gap-2">
        <span>📊</span> <span>รายงานสรุปเชิงสถิติ</span>
      </h2>
      <p class="text-xs sm:text-sm text-slate-400 mt-1">
        สรุปจำนวนผู้ป่วยใหม่และสถิตินัดหมาย ออกแบบเพื่อรองรับข้อมูลขนาดใหญ่ (100,000+ แถว)
      </p>
    </div>

    <!-- Date Range Filter -->
    <form method="GET" action="/page/reports.php" class="flex flex-wrap items-center gap-2">
      <span class="text-xs font-semibold text-slate-600">ช่วงวันที่:</span>
      <input type="date" name="start_date" class="px-3 py-1.5 text-xs sm:text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 bg-white" value="<?= htmlspecialchars($startDate) ?>">
      <span class="text-xs text-slate-400">ถึง</span>
      <input type="date" name="end_date" class="px-3 py-1.5 text-xs sm:text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 bg-white" value="<?= htmlspecialchars($endDate) ?>">
      <button type="submit" class="px-4 py-1.5 rounded-lg text-xs sm:text-sm font-semibold text-white bg-gradient-to-r from-emerald-800 to-emerald-900 hover:opacity-95 shadow-sm transition-all">
        ค้นหารายงาน
      </button>
    </form>
  </div>

  <?php if ($error): ?>
    <div class="mb-5 p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-center gap-2">
      <span>⚠️</span> <span><?= htmlspecialchars($error) ?></span>
    </div>
  <?php endif; ?>

  <?php if ($report): ?>
    <!-- 5 Metrics Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
      <!-- 1. ผู้ป่วยใหม่ -->
      <div class="bg-white border-l-4 border-l-blue-500 border border-slate-200 rounded-xl p-4 shadow-sm flex items-center gap-3.5">
        <div class="w-12 h-12 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-2xl shrink-0">
          👤
        </div>
        <div>
          <div class="text-2xl font-extrabold text-slate-800 leading-tight">
            <?= number_format($report['new_patients_count']) ?>
          </div>
          <div class="text-xs font-medium text-slate-500 mt-0.5">จำนวนผู้ป่วยใหม่</div>
        </div>
      </div>

      <!-- 2. นัดหมายทั้งหมด -->
      <div class="bg-white border-l-4 border-l-purple-500 border border-slate-200 rounded-xl p-4 shadow-sm flex items-center gap-3.5">
        <div class="w-12 h-12 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center text-2xl shrink-0">
          📋
        </div>
        <div>
          <div class="text-2xl font-extrabold text-slate-800 leading-tight">
            <?= number_format($report['total_appointments']) ?>
          </div>
          <div class="text-xs font-medium text-slate-500 mt-0.5">จำนวนนัดหมายรวม</div>
        </div>
      </div>

      <!-- 3. รอรับบริการ -->
      <div class="bg-white border-l-4 border-l-amber-500 border border-slate-200 rounded-xl p-4 shadow-sm flex items-center gap-3.5">
        <div class="w-12 h-12 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center text-2xl shrink-0">
          ⏳
        </div>
        <div>
          <div class="text-2xl font-extrabold text-slate-800 leading-tight">
            <?= number_format($report['count_pending']) ?>
          </div>
          <div class="text-xs font-medium text-slate-500 mt-0.5">สถานะ: รอรับบริการ</div>
        </div>
      </div>

      <!-- 4. มาแล้ว -->
      <div class="bg-white border-l-4 border-l-emerald-500 border border-slate-200 rounded-xl p-4 shadow-sm flex items-center gap-3.5">
        <div class="w-12 h-12 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl shrink-0">
          ✅
        </div>
        <div>
          <div class="text-2xl font-extrabold text-slate-800 leading-tight">
            <?= number_format($report['count_arrived']) ?>
          </div>
          <div class="text-xs font-medium text-slate-500 mt-0.5">สถานะ: มาแล้ว</div>
        </div>
      </div>

      <!-- 5. ยกเลิก -->
      <div class="bg-white border-l-4 border-l-rose-500 border border-slate-200 rounded-xl p-4 shadow-sm flex items-center gap-3.5">
        <div class="w-12 h-12 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center text-2xl shrink-0">
          ❌
        </div>
        <div>
          <div class="text-2xl font-extrabold text-slate-800 leading-tight">
            <?= number_format($report['count_cancelled']) ?>
          </div>
          <div class="text-xs font-medium text-slate-500 mt-0.5">สถานะ: ยกเลิก</div>
        </div>
      </div>
    </div>

    <!-- Doctor Breakdown -->
    <div>
      <h3 class="text-base sm:text-lg font-bold text-slate-800 flex items-center gap-2 mb-3">
        <span>🩺</span> <span>สถิติจำแนกตามแพทย์ผู้ตรวจ</span>
      </h3>
      <div class="overflow-x-auto rounded-xl border border-slate-100">
        <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
          <thead class="bg-slate-50 text-slate-500 uppercase text-xs font-semibold tracking-wider">
            <tr>
              <th class="px-4 py-3">แพทย์ผู้ตรวจ</th>
              <th class="px-4 py-3">ความเชี่ยวชาญ</th>
              <th class="px-4 py-3 w-32 text-center">นัดหมายรวม</th>
              <th class="px-4 py-3 w-28 text-center">มาแล้ว</th>
              <th class="px-4 py-3 w-28 text-center">รอ</th>
              <th class="px-4 py-3 w-28 text-center">ยกเลิก</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 bg-white">
            <?php foreach ($report['doctor_breakdown'] as $doc): ?>
              <tr class="hover:bg-slate-50/80 transition-colors">
                <td class="px-4 py-3 font-semibold text-slate-800">
                  <?= htmlspecialchars($doc['doctor_name']) ?>
                </td>
                <td class="px-4 py-3 text-slate-600 text-xs">
                  <?= htmlspecialchars($doc['specialty']) ?>
                </td>
                <td class="px-4 py-3 text-center font-bold text-slate-800">
                  <?= number_format($doc['total_appts']) ?>
                </td>
                <td class="px-4 py-3 text-center">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                    <?= number_format($doc['count_arrived']) ?>
                  </span>
                </td>
                <td class="px-4 py-3 text-center">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                    <?= number_format($doc['count_pending']) ?>
                  </span>
                </td>
                <td class="px-4 py-3 text-center">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-800 border border-rose-200">
                    <?= number_format($doc['count_cancelled']) ?>
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
