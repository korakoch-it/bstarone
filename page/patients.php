<?php
// =====================================================================
// page/patients.php - ระบบจัดการข้อมูลผู้ป่วย (Patient Management)
// =====================================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../api/PatientApi.php';

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

// ค้นหาแบบ Pagination
$keyword = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['p'] ?? 1));
$perPage = 5;
$paginated = $patientApi->searchPaginated($keyword, $page, $perPage);
$patients = $paginated['data'];
$total = $paginated['total'];
$totalPages = $paginated['total_pages'];

// ตรวจสอบว่ากำลังกดแก้ไขใครอยู่หรือไม่
$editPatient = null;
if (isset($_GET['edit_id'])) {
    $editPatient = $patientApi->getById((int)$_GET['edit_id']);
}

$pageTitle = 'จัดการข้อมูลผู้ป่วย';
$activePage = 'patients';
require_once __DIR__ . '/header.php';
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

  <!-- Form Column (ซ้าย 1 ส่วน): ฟอร์มเพิ่ม หรือ แก้ไข -->
  <div class="lg:col-span-1 bg-white border border-slate-200 rounded-2xl shadow-sm p-6">
    <div class="flex items-center justify-between pb-3 mb-5 border-b border-slate-100">
      <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
        <span><?= $editPatient ? '✏️' : '➕' ?></span>
        <span><?= $editPatient ? 'แก้ไขข้อมูลผู้ป่วย' : 'ลงทะเบียนผู้ป่วยใหม่' ?></span>
      </h3>
      <?php if ($editPatient): ?>
        <a href="/page/patients.php" class="text-xs text-slate-500 hover:text-slate-700">ยกเลิก</a>
      <?php endif; ?>
    </div>

    <!-- Alert notifications -->
    <?php if ($message): ?>
      <div class="mb-4 p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs flex items-center gap-1.5">
        <span>✅</span> <span><?= htmlspecialchars($message) ?></span>
      </div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="mb-4 p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-center gap-1.5">
        <span>⚠️</span> <span><?= htmlspecialchars($error) ?></span>
      </div>
    <?php endif; ?>

    <form method="POST" action="/page/patients.php" class="space-y-4">
      <input type="hidden" name="action" value="<?= $editPatient ? 'update' : 'create' ?>">
      
      <?php if ($editPatient): ?>
        <input type="hidden" name="patient_id" value="<?= $editPatient['id'] ?>">
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">รหัสประจำตัวผู้ป่วย (HN)</label>
          <input type="text" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-slate-50 font-mono font-bold text-sky-700 cursor-not-allowed" value="<?= htmlspecialchars($editPatient['hn']) ?>" readonly>
          <p class="text-xs text-slate-400 mt-1">🔒 รหัส HN ถูกล็อกไม่สามารถแก้ไขได้</p>
        </div>
      <?php endif; ?>

      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">
          ชื่อ-นามสกุล <span class="text-rose-500">*</span>
        </label>
        <input type="text" name="full_name" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600" required placeholder="เช่น ธนากรณ์ เจริญสุข" value="<?= htmlspecialchars($editPatient['full_name'] ?? '') ?>">
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">
          เบอร์โทรศัพท์ <span class="text-rose-500">*</span>
        </label>
        <input type="text" name="phone" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600" required placeholder="เช่น 081-234-5678 หรือ +66812345678" value="<?= htmlspecialchars($editPatient['phone'] ?? '') ?>">
        <p class="text-xs text-slate-400 mt-1">💡 รองรับหลายรูปแบบ ระบบจะแปลงเป็นมาตรฐาน 0812345678 อัตโนมัติ</p>
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">
          วันเกิด <span class="text-rose-500">*</span>
        </label>
        <input type="date" name="birth_date" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 bg-white" required value="<?= htmlspecialchars($editPatient['birth_date'] ?? '') ?>">
      </div>

      <div class="pt-2">
        <?php if ($editPatient): ?>
          <div class="flex gap-2">
            <a href="/page/patients.php" class="w-1/3 text-center px-3 py-2 rounded-lg text-sm font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 transition-all">
              ยกเลิก
            </a>
            <button type="submit" class="w-2/3 px-4 py-2 rounded-lg text-sm font-semibold text-white bg-gradient-to-r from-emerald-800 to-emerald-900 hover:opacity-95 shadow-sm transition-all">
              บันทึกการแก้ไข
            </button>
          </div>
        <?php else: ?>
          <button type="submit" class="w-full px-4 py-2 rounded-lg text-sm font-semibold text-white bg-gradient-to-r from-emerald-800 to-emerald-900 hover:opacity-95 shadow-sm transition-all flex items-center justify-center gap-1.5">
            <span>➕</span> <span>บันทึกข้อมูลผู้ป่วย</span>
          </button>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <!-- Table Column (ขวา 2 ส่วน): ตารางผู้ป่วย + ช่องค้นหา -->
  <div class="lg:col-span-2 bg-white border border-slate-200 rounded-2xl shadow-sm p-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 mb-5 border-b border-slate-100">
      <div>
        <h2 class="text-lg sm:text-xl font-bold text-slate-800 flex items-center gap-2">
          <span>👥</span> <span>รายชื่อผู้ป่วยในระบบ</span>
          <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-mono font-bold bg-sky-50 text-sky-700 border border-sky-200">
            <?= number_format($total) ?> คน
          </span>
        </h2>
        <p class="text-xs text-slate-400 mt-0.5">ค้นหาจากชื่อ, HN หรือเบอร์โทรศัพท์ (หน้า <?= $page ?>/<?= $totalPages ?>)</p>
      </div>

      <!-- Search Form -->
      <form method="GET" action="/page/patients.php" class="flex items-center gap-2">
        <input type="text" name="search" class="px-3 py-1.5 text-xs sm:text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 w-44 sm:w-56" placeholder="🔍 ค้นหา (ชื่อ, HN, เบอร์)..." value="<?= htmlspecialchars($keyword) ?>">
        <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 border border-slate-200 transition-all">
          ค้นหา
        </button>
        <?php if ($keyword): ?>
          <a href="/page/patients.php" class="px-2 py-1.5 text-xs text-slate-400 hover:text-slate-600" title="ล้างค่าค้นหา">ล้าง</a>
        <?php endif; ?>
      </form>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto rounded-xl border border-slate-100">
      <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
        <thead class="bg-slate-50 text-slate-500 uppercase text-xs font-semibold tracking-wider">
          <tr>
            <th class="px-4 py-3 w-24">HN</th>
            <th class="px-4 py-3">ชื่อ-นามสกุล</th>
            <th class="px-4 py-3">เบอร์โทร</th>
            <th class="px-4 py-3">วันเกิด</th>
            <th class="px-4 py-3">วันที่ลงทะเบียน</th>
            <th class="px-4 py-3 w-20 text-center">จัดการ</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 bg-white">
          <?php if (empty($patients)): ?>
            <tr>
              <td colspan="6" class="text-center py-10 text-slate-400">
                <div class="text-2xl mb-1">👤</div>
                <div>ไม่พบข้อมูลผู้ป่วยที่ตรงกับการค้นหา</div>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($patients as $p): ?>
              <tr class="hover:bg-slate-50/80 transition-colors">
                <td class="px-4 py-3 whitespace-nowrap">
                  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-mono font-semibold bg-sky-50 text-sky-700 border border-sky-200">
                    <?= htmlspecialchars($p['hn']) ?>
                  </span>
                </td>
                <td class="px-4 py-3 font-semibold text-slate-800">
                  <?= htmlspecialchars($p['full_name']) ?>
                </td>
                <td class="px-4 py-3 text-slate-600 font-mono text-xs">
                  <?= htmlspecialchars($p['phone']) ?>
                </td>
                <td class="px-4 py-3 text-slate-600 whitespace-nowrap text-xs">
                  <?= htmlspecialchars($p['birth_date']) ?>
                </td>
                <td class="px-4 py-3 text-slate-500 whitespace-nowrap text-xs">
                  <?= htmlspecialchars($p['registration_date']) ?>
                </td>
                <td class="px-4 py-3 text-center whitespace-nowrap">
                  <a href="/page/patients.php?edit_id=<?= $p['id'] ?>&p=<?= $page ?><?= $keyword ? '&search=' . urlencode($keyword) : '' ?>" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 border border-slate-200 transition-all">
                    ✏️ <span>แก้ไข</span>
                  </a>
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

</div>

<?php require_once __DIR__ . '/footer.php'; ?>
