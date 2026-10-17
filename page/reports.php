<?php
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

      <form @submit.prevent="getOverview(1)" class="d-flex flex-wrap align-items-center gap-2">
        <span class="small fw-semibold text-secondary">ช่วงวันที่:</span>
        <div class="input-group input-group-sm" style="width: auto;">
          <span class="input-group-text"><i class="bi bi-calendar3"></i></span>
          <input type="date" v-model="filters.startDate" @change="getOverview(1)" class="form-control form-control-sm">
        </div>
        <span class="small text-muted">ถึง</span>
        <div class="input-group input-group-sm" style="width: auto;">
          <span class="input-group-text"><i class="bi bi-calendar3-fill"></i></span>
          <input type="date" v-model="filters.endDate" @change="getOverview(1)" class="form-control form-control-sm">
        </div>
        <button type="submit" class="btn btn-sm btn-corp-primary d-inline-flex align-items-center gap-1.5 px-3">
          <i class="bi bi-search"></i>
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

    <div>
      <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-xl">
          <div class="card h-100 p-3" style="border-left: 4px solid var(--corp-secondary);">
            <div class="d-flex align-items-center gap-3">
              <div class="d-flex align-items-center justify-content-center text-secondary fs-3 flex-shrink-0" style="width: 44px; height: 44px; background-color: rgba(108, 117, 125, 0.12); border-radius: var(--radius-sm);">
                <i class="bi bi-person-plus"></i>
              </div>
              <div>
                <div class="fs-4 fw-bold text-dark lh-1">{{ formatNumber(metrics.new_patients_count) }}</div>
                <div class="text-muted small mt-1">ผู้ป่วยใหม่</div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-6 col-md-4 col-xl">
          <div class="card h-100 p-3" style="border-left: 4px solid #6f42c1;">
            <div class="d-flex align-items-center gap-3">
              <div class="d-flex align-items-center justify-content-center fs-3 flex-shrink-0" style="width: 44px; height: 44px; color: #6f42c1; background-color: rgba(111, 66, 193, 0.12); border-radius: var(--radius-sm);">
                <i class="bi bi-calendar2-check"></i>
              </div>
              <div>
                <div class="fs-4 fw-bold text-dark lh-1">{{ formatNumber(metrics.total_appointments) }}</div>
                <div class="text-muted small mt-1">นัดหมายรวม</div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-6 col-md-4 col-xl">
          <div class="card h-100 p-3" style="border-left: 4px solid var(--corp-warning);">
            <div class="d-flex align-items-center gap-3">
              <div class="d-flex align-items-center justify-content-center text-warning fs-3 flex-shrink-0" style="width: 44px; height: 44px; background-color: rgba(255, 193, 7, 0.15); border-radius: var(--radius-sm);">
                <i class="bi bi-hourglass-split"></i>
              </div>
              <div>
                <div class="fs-4 fw-bold text-dark lh-1">{{ formatNumber(metrics.count_pending) }}</div>
                <div class="text-muted small mt-1">รอเข้ารับบริการ</div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-6 col-md-4 col-xl">
          <div class="card h-100 p-3" style="border-left: 4px solid var(--corp-success);">
            <div class="d-flex align-items-center gap-3">
              <div class="d-flex align-items-center justify-content-center text-success fs-3 flex-shrink-0" style="width: 44px; height: 44px; background-color: rgba(40, 167, 69, 0.12); border-radius: var(--radius-sm);">
                <i class="bi bi-check2-circle"></i>
              </div>
              <div>
                <div class="fs-4 fw-bold text-dark lh-1">{{ formatNumber(metrics.count_completed) }}</div>
                <div class="text-muted small mt-1">เสร็จสิ้น</div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-6 col-md-4 col-xl">
          <div class="card h-100 p-3" style="border-left: 4px solid var(--corp-danger);">
            <div class="d-flex align-items-center gap-3">
              <div class="d-flex align-items-center justify-content-center text-danger fs-3 flex-shrink-0" style="width: 44px; height: 44px; background-color: rgba(220, 53, 69, 0.12); border-radius: var(--radius-sm);">
                <i class="bi bi-x-circle"></i>
              </div>
              <div>
                <div class="fs-4 fw-bold text-dark lh-1">{{ formatNumber(metrics.count_cancelled) }}</div>
                <div class="text-muted small mt-1">ยกเลิก</div>
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
                <th class="py-3 text-center" style="width: 120px;">รอเข้ารับบริการ</th>
                <th class="py-3 text-center" style="width: 120px;">ยืนยันแล้ว</th>
                <th class="py-3 text-center" style="width: 120px;">มาแล้ว</th>
                <th class="py-3 text-center" style="width: 120px;">เสร็จสิ้น</th>
                <th class="pe-4 py-3 text-center" style="width: 120px;">ยกเลิก</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="results.length === 0">
                <td colspan="8" class="text-center py-5 text-muted">
                  <i class="bi bi-clipboard2-data fs-2 d-block mb-2 text-secondary opacity-50"></i>
                  <div class="fw-medium">ไม่พบข้อมูลสถิติแพทย์สำหรับช่วงเวลาที่เลือก</div>
                </td>
              </tr>
              <tr v-for="doc in results" :key="doc.id">
                <td class="ps-4 fw-semibold text-dark">{{ doc.doctor_name }}</td>
                <td class="text-muted small">{{ doc.specialty }}</td>
                <td class="text-center fw-bold text-dark">{{ formatNumber(doc.total_appts) }}</td>
                <td class="text-center">
                  <span class="badge rounded-pill text-bg-warning">{{ formatNumber(doc.count_pending) }}</span>
                </td>
                <td class="text-center">
                  <span class="badge rounded-pill text-bg-info">{{ formatNumber(doc.count_confirmed) }}</span>
                </td>
                <td class="text-center">
                  <span class="badge rounded-pill text-bg-primary">{{ formatNumber(doc.count_arrived) }}</span>
                </td>
                <td class="text-center">
                  <span class="badge rounded-pill text-bg-success">{{ formatNumber(doc.count_completed) }}</span>
                </td>
                <td class="pe-4 text-center">
                  <span class="badge rounded-pill text-bg-danger">{{ formatNumber(doc.count_cancelled) }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <div class="card-footer d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
          <span class="text-muted small">
            หน้า {{ pageIndex }} / {{ totalPages || 1 }} (ทั้งหมด {{ totalCount }} รายการ)
          </span>
          <nav aria-label="Page navigation">
            <ul class="pagination pagination-sm mb-0">
              <li class="page-item" :class="{ disabled: pageIndex <= 1 }">
                <button type="button" class="page-link" @click="getOverview(pageIndex - 1)" :disabled="pageIndex <= 1">
                  ‹ ก่อนหน้า
                </button>
              </li>

              <li 
                v-for="(p, idx) in pageNumbers" 
                :key="idx" 
                class="page-item" 
                :class="{ active: p === pageIndex, disabled: p === '...' }"
              >
                <span v-if="p === '...'" class="page-link">...</span>
                <button 
                  v-else 
                  type="button" 
                  class="page-link" 
                  @click="getOverview(p)"
                >
                  {{ p }}
                </button>
              </li>

              <li class="page-item" :class="{ disabled: pageIndex >= totalPages || totalPages === 0 }">
                <button type="button" class="page-link" @click="getOverview(pageIndex + 1)" :disabled="pageIndex >= totalPages || totalPages === 0">
                  ถัดไป ›
                </button>
              </li>
            </ul>
          </nav>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
new Vue({
  el: '#app',
  data: {
    results: [],
    pageIndex: 1,
    pageSize: 10,
    totalPages: 1,
    totalCount: 0,
    filters: {
      startDate: '<?= htmlspecialchars($startDate) ?>',
      endDate: '<?= htmlspecialchars($endDate) ?>'
    },
    metrics: {
      new_patients_count: 0,
      total_appointments: 0,
      count_pending: 0,
      count_confirmed: 0,
      count_arrived: 0, 
      count_completed: 0,
      count_cancelled: 0
    },
    error: null
  },
  computed: {
    pageNumbers() {
      const pages = [];
      const total = this.totalPages;
      const current = this.pageIndex;

      if (total <= 7) {
        for (let i = 1; i <= total; i++) pages.push(i);
      } else {
        pages.push(1);
        if (current > 3) pages.push('...');
        const start = Math.max(2, current - 1);
        const end = Math.min(total - 1, current + 1);
        for (let i = start; i <= end; i++) pages.push(i);
        if (current < total - 2) pages.push('...');
        pages.push(total);
      }
      return pages;
    }
  },
  mounted() {
    this.getOverview(1);
  },
  methods: {
    formatNumber(val) {
      if (val === null || val === undefined) return '0';
      return Number(val).toLocaleString();
    },
    async getOverview(pageIndex = 1) {
      this.pageIndex = Math.max(1, pageIndex);
      if (this.filters.startDate > this.filters.endDate) {
        this.error = 'วันที่เริ่มต้นต้องไม่มากกว่าวันที่สิ้นสุด';
        return;
      }
      this.error = null;
      try {
        const res = await fetch('/api/reports/ajax.report.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            action: 'getOverview',
            startDate: this.filters.startDate,
            endDate: this.filters.endDate,
            pageIndex: this.pageIndex,
            pageSize: this.pageSize
          })
        });
        const data = await res.json();
        
        if (data.success) {
          this.metrics = {
            new_patients_count: data.new_patients_count,
            total_appointments: data.total_appointments,
            count_pending: data.count_pending,
            count_confirmed: data.count_confirmed,
            count_arrived: data.count_arrived,
            count_completed: data.count_completed,
            count_cancelled: data.count_cancelled
          };
          this.results = data.data || [];
          this.totalCount = data.totalCount || 0;
          this.totalPages = data.totalPages || 1;
        } else {
          this.error = data.error || 'เกิดข้อผิดพลาดในการดึงข้อมูลรายงาน';
        }
      } catch (e) {
        this.error = 'เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์';
      }
    }
  }
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
