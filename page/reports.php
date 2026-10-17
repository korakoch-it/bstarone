<?php
// =====================================================================
// page/reports.php - หน้ารายงานสรุปและสถิติ (Flat Design Corporativo)
// Bootstrap 5 + Vue 2 (CDN) | Endpoint: /api/reports/ajax.report.php
// =====================================================================

$startDate = date('Y-m-01');
$endDate = date('Y-m-d');

$pageTitle = 'รายงานสรุปและสถิติ';
$activePage = 'reports';
require_once __DIR__ . '/header.php';
?>

<div id="app" v-cloak class="card mb-4">
  <div class="card-header py-3 px-4">
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
      <div class="d-flex align-items-center gap-3">
        <div class="d-flex align-items-center justify-content-center text-white" style="background-color: var(--corp-primary); width: 38px; height: 38px; border-radius: var(--radius-sm);">
          <i class="bi bi-graph-up" style="font-size: 1.15rem;"></i>
        </div>
        <div>
          <h1 class="h6 fw-bold mb-0 text-dark">รายงานสรุปเชิงสถิติ</h1>
          <div class="text-muted small" style="font-size: 0.8125rem;">Analytics & Executive Summary</div>
        </div>
      </div>

      <form @submit.prevent="loadReport" class="d-flex flex-wrap align-items-center gap-2">
        <span class="small fw-semibold text-secondary">ช่วงวันที่:</span>
        <div class="input-group input-group-sm" style="width: auto;">
          <span class="input-group-text"><i class="bi bi-calendar3"></i></span>
          <input type="date" v-model="filters.startDate" @change="loadReport" class="form-control form-control-sm">
        </div>
        <span class="small text-muted">ถึง</span>
        <div class="input-group input-group-sm" style="width: auto;">
          <span class="input-group-text"><i class="bi bi-calendar3-fill"></i></span>
          <input type="date" v-model="filters.endDate" @change="loadReport" class="form-control form-control-sm">
        </div>
        <button type="submit" class="btn btn-sm btn-corp-primary d-inline-flex align-items-center gap-1.5 px-3" :disabled="loading">
          <span v-if="loading" class="spinner-border spinner-border-sm" role="status"></span>
          <i v-else class="bi bi-search"></i>
          <span>ค้นหารายงาน</span>
        </button>
      </form>
    </div>
  </div>

  <div class="card-body p-4">
    <!-- Error Alert -->
    <div v-if="error" class="alert alert-danger py-2.5 px-3 small mb-4 d-flex align-items-center gap-2" role="alert">
      <i class="bi bi-exclamation-triangle-fill"></i>
      <span>{{ error }}</span>
    </div>

    <!-- Loading Indicator -->
    <div v-if="loading" class="text-center py-4 text-muted small">
      <div class="d-inline-flex align-items-center gap-2 px-3 py-2 bg-light border text-primary fw-semibold" style="border-radius: var(--radius-sm);">
        <span class="spinner-border spinner-border-sm" role="status"></span>
        <span>กำลังคำนวณและประมวลผลข้อมูลสถิติผ่าน AJAX...</span>
      </div>
    </div>

    <div>
      <!-- 5 Metrics Grid (Flat Corporate Cards) -->
      <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-5 g-3 mb-4">
        <div class="col">
          <div class="card h-100 p-3" style="border-left: 4px solid var(--corp-primary);">
            <div class="d-flex align-items-center gap-3">
              <div class="d-flex align-items-center justify-content-center text-primary fs-3 flex-shrink-0" style="width: 44px; height: 44px; background-color: rgba(0, 123, 255, 0.1); border-radius: var(--radius-sm);">
                <i class="bi bi-person-plus"></i>
              </div>
              <div>
                <div class="fs-4 fw-bold text-dark lh-1">{{ formatNumber(metrics.new_patients_count) }}</div>
                <div class="text-muted small mt-1">ผู้ป่วยใหม่</div>
              </div>
            </div>
          </div>
        </div>

        <div class="col">
          <div class="card h-100 p-3" style="border-left: 4px solid var(--corp-info);">
            <div class="d-flex align-items-center gap-3">
              <div class="d-flex align-items-center justify-content-center text-info fs-3 flex-shrink-0" style="width: 44px; height: 44px; background-color: rgba(23, 162, 184, 0.1); border-radius: var(--radius-sm);">
                <i class="bi bi-calendar2-check"></i>
              </div>
              <div>
                <div class="fs-4 fw-bold text-dark lh-1">{{ formatNumber(metrics.total_appointments) }}</div>
                <div class="text-muted small mt-1">นัดหมายรวม</div>
              </div>
            </div>
          </div>
        </div>

        <div class="col">
          <div class="card h-100 p-3" style="border-left: 4px solid var(--corp-warning);">
            <div class="d-flex align-items-center gap-3">
              <div class="d-flex align-items-center justify-content-center text-warning fs-3 flex-shrink-0" style="width: 44px; height: 44px; background-color: rgba(255, 193, 7, 0.15); border-radius: var(--radius-sm);">
                <i class="bi bi-hourglass-split"></i>
              </div>
              <div>
                <div class="fs-4 fw-bold text-dark lh-1">{{ formatNumber(metrics.count_pending) }}</div>
                <div class="text-muted small mt-1">รอรับบริการ</div>
              </div>
            </div>
          </div>
        </div>

        <div class="col">
          <div class="card h-100 p-3" style="border-left: 4px solid var(--corp-success);">
            <div class="d-flex align-items-center gap-3">
              <div class="d-flex align-items-center justify-content-center text-success fs-3 flex-shrink-0" style="width: 44px; height: 44px; background-color: rgba(40, 167, 69, 0.1); border-radius: var(--radius-sm);">
                <i class="bi bi-check2-circle"></i>
              </div>
              <div>
                <div class="fs-4 fw-bold text-dark lh-1">{{ formatNumber(metrics.count_arrived) }}</div>
                <div class="text-muted small mt-1">มาถึงแล้ว</div>
              </div>
            </div>
          </div>
        </div>

        <div class="col">
          <div class="card h-100 p-3" style="border-left: 4px solid var(--corp-danger);">
            <div class="d-flex align-items-center gap-3">
              <div class="d-flex align-items-center justify-content-center text-danger fs-3 flex-shrink-0" style="width: 44px; height: 44px; background-color: rgba(220, 53, 69, 0.1); border-radius: var(--radius-sm);">
                <i class="bi bi-x-circle"></i>
              </div>
              <div>
                <div class="fs-4 fw-bold text-dark lh-1">{{ formatNumber(metrics.count_cancelled) }}</div>
                <div class="text-muted small mt-1">ยกเลิกนัด</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Doctor Breakdown Section -->
      <div class="card mt-4">
        <div class="card-header d-flex align-items-center gap-2">
          <i class="bi bi-person-lines-fill text-primary"></i>
          <h2 class="h6 fw-bold text-dark mb-0">สถิติจำแนกตามแพทย์ผู้ตรวจ</h2>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th class="ps-4 py-3">แพทย์ผู้ตรวจ</th>
                <th class="py-3">ความเชี่ยวชาญ</th>
                <th class="py-3 text-center" style="width: 140px;">นัดหมายรวม</th>
                <th class="py-3 text-center" style="width: 120px;">มาแล้ว</th>
                <th class="py-3 text-center" style="width: 120px;">รอ</th>
                <th class="pe-4 py-3 text-center" style="width: 120px;">ยกเลิก</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="doctorBreakdown.length === 0">
                <td colspan="6" class="text-center py-5 text-muted">
                  <i class="bi bi-clipboard2-data fs-2 d-block mb-2 text-secondary opacity-50"></i>
                  <div class="fw-medium">ไม่พบข้อมูลสถิติแพทย์สำหรับช่วงเวลาที่เลือก</div>
                </td>
              </tr>
              <tr v-for="doc in doctorBreakdown" :key="doc.doctor_id">
                <td class="ps-4 fw-semibold text-dark">{{ doc.doctor_name }}</td>
                <td class="text-muted small">{{ doc.specialty }}</td>
                <td class="text-center fw-bold text-dark">{{ formatNumber(doc.total_appts) }}</td>
                <td class="text-center">
                  <span class="badge bg-success border border-success px-2.5 py-1">
                    {{ formatNumber(doc.count_arrived) }}
                  </span>
                </td>
                <td class="text-center">
                  <span class="badge bg-warning text-dark border border-warning px-2.5 py-1">
                    {{ formatNumber(doc.count_pending) }}
                  </span>
                </td>
                <td class="pe-4 text-center">
                  <span class="badge bg-danger border border-danger px-2.5 py-1">
                    {{ formatNumber(doc.count_cancelled) }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
new Vue({
  el: '#app',
  data: {
    filters: {
      startDate: '<?= htmlspecialchars($startDate) ?>',
      endDate: '<?= htmlspecialchars($endDate) ?>'
    },
    metrics: {
      new_patients_count: 0,
      total_appointments: 0,
      count_pending: 0,
      count_arrived: 0,
      count_cancelled: 0
    },
    doctorBreakdown: [],
    loading: false,
    error: null
  },
  mounted() {
    this.loadReport();
  },
  methods: {
    formatNumber(val) {
      if (val === null || val === undefined) return '0';
      return Number(val).toLocaleString();
    },
    async loadReport() {
      if (this.filters.startDate > this.filters.endDate) {
        this.error = 'วันที่เริ่มต้นต้องไม่มากกว่าวันที่สิ้นสุด';
        return;
      }
      this.error = null;
      this.loading = true;

      try {
        const url = `/api/reports/ajax.report.php?action=get_report&start_date=${encodeURIComponent(this.filters.startDate)}&end_date=${encodeURIComponent(this.filters.endDate)}`;
        const res = await fetch(url);
        const data = await res.json();

        if (data.success) {
          this.metrics = {
            new_patients_count: data.new_patients_count,
            total_appointments: data.total_appointments,
            count_pending: data.count_pending,
            count_arrived: data.count_arrived,
            count_cancelled: data.count_cancelled
          };
          this.doctorBreakdown = data.doctor_breakdown || [];
        } else {
          this.error = data.error || 'เกิดข้อผิดพลาดในการดึงข้อมูลรายงาน';
        }
      } catch (e) {
        this.error = 'เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์';
      } finally {
        this.loading = false;
      }
    }
  }
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
