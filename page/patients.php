<?php
$pageTitle = 'จัดการข้อมูลผู้ป่วย';
$activePage = 'patients';
require_once __DIR__ . '/header.php';
?>

<div id="app" v-cloak>
  <div class="card mb-4">
    <div class="card-header py-3 px-4">
      <div class="row">
        <div class="col-12 d-flex flex-wrap justify-content-between align-items-center gap-3">
          <div class="d-flex align-items-center gap-3">
            <div class="d-flex align-items-center justify-content-center text-white" style="background-color: var(--corp-primary); width: 38px; height: 38px; border-radius: var(--radius-sm);">
              <i class="bi bi-people" style="font-size: 1.15rem;"></i>
            </div>
            <div>
              <h1 class="h5 fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                <span>จัดการข้อมูลผู้ป่วย</span>
              </h1>
            </div>
          </div>

          <div class="d-flex flex-wrap align-items-center gap-2">
            <button type="button" class="btn btn-sm btn-corp-primary d-inline-flex align-items-center gap-1.5 px-3" @click="openPatientModal">
              <i class="bi bi-plus-lg"></i>
              <span>ลงทะเบียนผู้ป่วยใหม่</span>
            </button>
          </div>
        </div>
        <div class="col-12 mt-3">
          <div class="row">
            <div class="col-md-5 col-lg-4">
              <label for="filterPatient" class="form-label">ค้นหาผู้ป่วย</label>
              <input 
                  id="filterPatient"
                  type="text" 
                  v-model="filterPatient" 
                  class="form-control" 
                  placeholder="พิมพ์ชื่อ, HN หรือเบอร์โทร..."
                  autocomplete="off"
                >
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card-body p-0">
      <!-- Main Page Alert -->
      <div v-if="alert.message" class="p-3 pb-0">
        <div :class="['alert', 'alert-' + alert.type, 'py-2 px-3 small d-flex align-items-center gap-2 mb-0']">
          <i :class="['bi', alert.type === 'success' ? 'bi-check-circle' : 'bi-exclamation-circle']"></i>
          <span>{{ alert.message }}</span>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead>
            <tr>
              <th class="ps-4 py-3" style="width: 140px;">HN</th>
              <th class="py-3">ชื่อ-นามสกุล</th>
              <th class="py-3" style="width: 160px;">เบอร์โทรศัพท์</th>
              <th class="py-3" style="width: 100px;">อายุ</th>
              <th class="py-3" style="width: 160px;">วันที่ลงทะเบียน</th>
              <th class="pe-4 py-3 text-end" style="width: 100px;">จัดการ</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="results.length === 0">
              <td colspan="7" class="text-center text-muted py-5">
                <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary opacity-50"></i>
                <div class="fw-medium">{{ filterPatient ? 'ไม่พบข้อมูลผู้ป่วยที่ตรงกับคำค้นหา' : 'ยังไม่มีข้อมูลผู้ป่วยในระบบ' }}</div>
              </td>
            </tr>
            <tr v-for="p in results" :key="p.id">
              <td class="ps-4 fw-semibold">{{ p.hn }}</td>
              <td class="fw-semibold text-dark">{{ p.full_name }}</td>
              <td class="text-muted small font-monospace">{{ formatPhone(p.phone) }}</td>
              <td class="text-muted small">{{ calculateAge(p.birth_date) }}</td>
              <td class="text-muted small">{{ p.registration_date }}</td>
              <td class="pe-4 text-end">
                <div class="d-inline-flex align-items-center gap-1.5">
                  <button 
                    type="button" 
                    class="btn btn-sm btn-outline-primary p-1.5 d-inline-flex align-items-center justify-content-center"
                    style="width: 32px; height: 32px;"
                    title="แก้ไขข้อมูลผู้ป่วย"
                    @click="editPatient(p)"
                  >
                    <i class="bi bi-pencil"></i>
                  </button>
                </div>
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

  <!-- Modal ลงทะเบียน / แก้ไขข้อมูลผู้ป่วย (Vue 2 + Bootstrap 5) -->
  <div class="modal fade" id="patientModal" ref="patientModal" tabindex="-1" aria-labelledby="patientModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 550px;">
      <div class="modal-content" style="border-radius: var(--radius-sm); border: 1px solid var(--corp-border);">
        <div class="modal-header py-3 px-4" style="background-color: var(--corp-white); border-bottom: 1px solid var(--corp-border-light);">
          <div class="d-flex align-items-center gap-2.5">
            <h2 class="h6 fw-bold mb-0 text-dark" id="patientModalLabel">
              {{ isEditMode ? 'แก้ไขข้อมูลผู้ป่วย (' + form.hn + ')' : 'ลงทะเบียนผู้ป่วยใหม่' }}
            </h2>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <form @submit.prevent="savePatient">
          <div class="modal-body p-4 vstack gap-3">
            <!-- Modal Alert -->
            <div v-if="modalAlert.message" :class="['alert', 'alert-' + modalAlert.type, 'py-2 px-3 small d-flex align-items-center gap-2 mb-0']">
              <i :class="['bi', modalAlert.type === 'success' ? 'bi-check-circle' : 'bi-exclamation-circle']"></i>
              <span>{{ modalAlert.message }}</span>
            </div>

            <!-- HN Display (Show only in Edit Mode) -->
            <div v-if="isEditMode">
              <label class="form-label small fw-semibold text-dark mb-1">รหัสประจำตัวผู้ป่วย (HN)</label>
              <input disabled type="text" :value="form.hn" class="form-control bg-light" readonly>
            </div>

            <div>
              <label class="form-label small fw-semibold text-dark mb-1">
                ชื่อ-นามสกุล <span class="text-danger">*</span>
              </label>
              <input type="text" v-model.trim="form.full_name" class="form-control" required placeholder="เช่น สมชาย ใจดี">
            </div>

            <div>
              <label class="form-label small fw-semibold text-dark mb-1">
                เบอร์โทรศัพท์ <span class="text-danger">*</span>
              </label>
              <input type="text" v-model.trim="form.phone" class="form-control" required placeholder="เช่น 081-234-5678">
            </div>

            <div>
              <label class="form-label small fw-semibold text-dark mb-1">
                วันเกิด <span class="text-danger">*</span>
              </label>
              <input type="date" v-model="form.birth_date" class="form-control" required>
            </div>

            <div v-if="isEditMode" class="d-flex align-items-center justify-content-between">
              <label class="form-label small fw-semibold text-dark mb-0 d-block" for="patientStatusSwitch">
                สถานะผู้ป่วย
              </label>
              <div class="form-check form-switch mb-0">
                <input 
                  class="form-check-input" 
                  type="checkbox" 
                  role="switch" 
                  id="patientStatusSwitch" 
                  v-model="form.is_active"
                  :true-value="1"
                  :false-value="0"
                  style="cursor: pointer; width: 2.5em; height: 1.25em;"
                >
              </div>
            </div>
          </div>

          <div class="modal-footer px-4 py-3 bg-light">
            <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal">
              ยกเลิก
            </button>
            <button 
              type="submit" 
              class="btn btn-sm btn-corp-primary px-3 fw-semibold d-inline-flex align-items-center gap-1.5"
              :disabled="isSubmitting"
            >
              <span v-if="isSubmitting" class="spinner-border spinner-border-sm" role="status"></span>
              <span>{{ isSubmitting ? 'กำลังบันทึก...' : 'บันทึกข้อมูล' }}</span>
            </button>
          </div>
        </form>
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
    totalCount: 0,
    totalPages: 1,
    filterPatient: '',
    searchDebounceTimer: null,
    alert: {
      type: 'success',
      message: ''
    },
    modalAlert: {
      type: 'danger',
      message: ''
    },
    isSubmitting: false,
    modalInstance: null,
    form: {
      mode: 'create',
      patient_id: '',
      hn: '',
      full_name: '',
      phone: '',
      birth_date: '',
      is_active: 1
    }
  },
  computed: {
    isEditMode() {
      return this.form.mode === 'update';
    },
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
  watch: {
    filterPatient() {
      clearTimeout(this.searchDebounceTimer);
      this.searchDebounceTimer = setTimeout(() => {
        this.getOverview(1);
      }, 250);
    }
  },
  mounted() {
    this.getOverview(1);
    this.modalInstance = new bootstrap.Modal(this.$refs.patientModal);
  },
  methods: {
    async getOverview(page = 1) {
      this.pageIndex = Math.max(1, page);
      try {
        const res = await fetch('/api/patients/ajax.patient.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            action: 'getOverview',
            filterPatient: this.filterPatient,
            pageIndex: this.pageIndex,
            pageSize: this.pageSize
          })
        });
        const data = await res.json();
        this.results = data.data || [];
        this.totalCount = data.totalCount || 0;
        this.totalPages = data.totalPages || 1;
      } catch (e) {
        console.error(e);
      }
    },
    formatPhone(phone) {
      if (!phone) return '-';
      const clean = phone.replace(/\D/g, '');
      if (clean.length === 10) {
        return clean.substring(0, 3) + '-' + clean.substring(3, 6) + '-' + clean.substring(6);
      }
      return phone;
    },
    calculateAge(birthDate) {
      if (!birthDate) return '-';
      const diff = Date.now() - new Date(birthDate).getTime();
      const ageDate = new Date(diff);
      const age = Math.abs(ageDate.getUTCFullYear() - 1970);
      return age + ' ปี';
    },
    openPatientModal() {
      this.modalAlert.message = '';
      this.resetForm();
      if (!this.modalInstance && this.$refs.patientModal) {
        this.modalInstance = new bootstrap.Modal(this.$refs.patientModal);
      }
      if (this.modalInstance) {
        this.modalInstance.show();
      }
    },
    editPatient(p) {
      this.modalAlert.message = '';
      this.form.mode = 'update';
      this.form.patient_id = p.id;
      this.form.hn = p.hn;
      this.form.full_name = p.full_name;
      this.form.phone = p.phone;
      this.form.birth_date = p.birth_date;
      this.form.is_active = p.is_active !== undefined ? Number(p.is_active) : 1;

      if (!this.modalInstance && this.$refs.patientModal) {
        this.modalInstance = new bootstrap.Modal(this.$refs.patientModal);
      }
      if (this.modalInstance) {
        this.modalInstance.show();
      }
    },
    resetForm() {
      this.form = {
        mode: 'create',
        patient_id: '',
        hn: '',
        full_name: '',
        phone: '',
        birth_date: '',
        is_active: 1
      };
    },
    async savePatient() {
      this.isSubmitting = true;
      this.modalAlert.message = '';
      try {
        const res = await fetch('/api/patients/ajax.patient.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            action: 'savePatient',
            mode: this.form.mode,
            patient_id: this.form.patient_id || undefined,
            full_name: this.form.full_name,
            phone: this.form.phone,
            birth_date: this.form.birth_date,
            is_active: this.form.is_active !== undefined ? this.form.is_active : 1
          })
        });
        const result = await res.json();
        if (result.success) {
          if (this.modalInstance) {
            this.modalInstance.hide();
          }
          this.alert = {
            type: 'success',
            message: result.message || 'บันทึกข้อมูลเรียบร้อยแล้ว'
          };
          this.resetForm();
          this.getOverview(this.pageIndex);
        } else {
          this.modalAlert = {
            type: 'danger',
            message: result.error || 'เกิดข้อผิดพลาดในการบันทึกข้อมูล'
          };
        }
      } catch (e) {
        this.modalAlert = {
          type: 'danger',
          message: 'เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์'
        };
      } finally {
        this.isSubmitting = false;
      }
    }
  }
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
