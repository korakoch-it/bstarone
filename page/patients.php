<?php
// =====================================================================
// page/patients.php - ระบบจัดการข้อมูลผู้ป่วย (Flat Design Corporativo)
// Bootstrap 5 + Vue 2 (CDN) | Live Search & Pagination
// Endpoint: /api/patients/ajax.patient.php
// =====================================================================

$pageTitle = 'จัดการข้อมูลผู้ป่วย';
$activePage = 'patients';
require_once __DIR__ . '/header.php';
?>

<div id="app" v-cloak class="row g-4 align-items-start">

  <!-- Form Column: ฟอร์มเพิ่ม หรือ แก้ไข -->
  <div class="col-lg-4">
    <div class="card sticky-top" style="top: 85px;">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h2 class="h6 fw-bold text-dark d-flex align-items-center gap-2 mb-0">
          <div class="d-flex align-items-center justify-content-center text-white" style="background-color: var(--corp-primary); width: 32px; height: 32px; border-radius: var(--radius-sm);">
            <i :class="isEditMode ? 'bi bi-pencil-square' : 'bi bi-person-plus-fill'"></i>
          </div>
          <span>{{ isEditMode ? 'แก้ไขข้อมูล (' + form.hn + ')' : 'ลงทะเบียนผู้ป่วยใหม่' }}</span>
        </h2>
        <button 
          v-if="isEditMode"
          type="button" 
          @click="resetForm" 
          class="btn btn-sm btn-outline-secondary py-1 px-2.5 small"
        >
          <i class="bi bi-x"></i> ยกเลิก
        </button>
      </div>

      <div class="card-body p-3 p-sm-4">
        <!-- Form Alert Area -->
        <div v-if="formAlert.message" :class="['alert', 'alert-' + formAlert.type, 'py-2 px-3 small d-flex align-items-center gap-2 mb-3']">
          <i :class="['bi', formAlert.type === 'success' ? 'bi-check-circle' : 'bi-exclamation-circle']"></i>
          <span>{{ formAlert.message }}</span>
        </div>

        <form @submit.prevent="savePatient" class="vstack gap-3">
          <!-- HN Display (Show only in Edit Mode) -->
          <div v-if="isEditMode">
            <label class="form-label small fw-semibold text-dark mb-1">รหัสประจำตัวผู้ป่วย (HN)</label>
            <input type="text" :value="form.hn" class="form-control form-control-sm bg-light font-monospace fw-bold text-primary" readonly>
            <div class="form-text small text-muted"><i class="bi bi-lock me-1"></i>รหัส HN ถูกล็อกไม่สามารถแก้ไขได้</div>
          </div>

          <div>
            <label class="form-label small fw-semibold text-dark mb-1">
              ชื่อ-นามสกุล <span class="text-danger">*</span>
            </label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-person"></i></span>
              <input type="text" v-model.trim="form.full_name" class="form-control" required placeholder="เช่น ธนากรณ์ เจริญสุข">
            </div>
          </div>

          <div>
            <label class="form-label small fw-semibold text-dark mb-1">
              เบอร์โทรศัพท์ <span class="text-danger">*</span>
            </label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-telephone"></i></span>
              <input type="text" v-model.trim="form.phone" class="form-control" required placeholder="เช่น 081-234-5678">
            </div>
            <div class="form-text small text-muted"><i class="bi bi-info-circle me-1"></i>แปลงเป็นมาตรฐาน 0812345678 อัตโนมัติ</div>
          </div>

          <div>
            <label class="form-label small fw-semibold text-dark mb-1">
              วันเกิด <span class="text-danger">*</span>
            </label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-calendar3"></i></span>
              <input type="date" v-model="form.birth_date" class="form-control" required>
            </div>
          </div>

          <div v-if="isEditMode">
            <label class="form-label small fw-semibold text-dark mb-1">สถานะผู้ป่วย</label>
            <select v-model="form.is_active" class="form-select">
              <option :value="1">ใช้งานปกติ (Active)</option>
              <option :value="0">ระงับการใช้งาน (Inactive)</option>
            </select>
          </div>

          <div class="pt-2">
            <button 
              type="submit" 
              class="btn btn-corp-primary w-100 py-2.5 fw-semibold d-inline-flex align-items-center justify-content-center gap-2"
              :disabled="submitting"
            >
              <span v-if="submitting" class="spinner-border spinner-border-sm" role="status"></span>
              <i v-else :class="isEditMode ? 'bi bi-save' : 'bi bi-check2-circle'"></i>
              <span>{{ submitting ? 'กำลังบันทึกข้อมูล...' : (isEditMode ? 'บันทึกการแก้ไข' : 'บันทึกข้อมูลผู้ป่วย') }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Table Column: ตารางผู้ป่วย + ช่องค้นหา -->
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header">
        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
          <div>
            <h1 class="h6 fw-bold text-dark d-flex align-items-center gap-2 mb-1">
              <i class="bi bi-people text-primary"></i>
              <span>รายชื่อผู้ป่วยในระบบ</span>
              <span class="badge bg-light text-secondary border fw-normal">
                {{ loading ? 'กำลังโหลด...' : total + ' คน' }}
              </span>
            </h1>
            <p class="text-muted small mb-0">
              ค้นหาจากชื่อ, HN หรือเบอร์โทรศัพท์ ดึงข้อมูลแบบ AJAX
            </p>
          </div>

          <!-- Live Search Box with Debounce -->
          <div class="input-group input-group-sm" style="max-width: 280px;">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input 
              type="text" 
              v-model="searchQuery" 
              class="form-control" 
              placeholder="พิมพ์ชื่อ, HN, เบอร์โทร..."
              autocomplete="off"
            >
            <button 
              v-if="searchQuery"
              type="button" 
              @click="clearSearch" 
              class="btn btn-outline-secondary"
              title="ล้างค่าค้นหา"
            >
              <i class="bi bi-x"></i>
            </button>
          </div>
        </div>
      </div>

      <div class="position-relative table-responsive" style="min-height: 250px;">
        <div v-if="loading" class="position-absolute top-0 start-0 w-100 h-100 bg-white bg-opacity-75 d-flex align-items-center justify-content-center" style="z-index: 10;">
          <div class="d-flex align-items-center gap-2 px-3 py-2 bg-white border text-primary fw-semibold small" style="border-radius: var(--radius-sm);">
            <span class="spinner-border spinner-border-sm" role="status"></span>
            <span>กำลังดึงข้อมูลผู้ป่วยผ่าน AJAX...</span>
          </div>
        </div>

        <table class="table table-hover align-middle mb-0">
          <thead>
            <tr>
              <th class="ps-4 py-3" style="width: 120px;">HN</th>
              <th class="py-3">ชื่อ-นามสกุล</th>
              <th class="py-3">เบอร์โทร</th>
              <th class="py-3">อายุ</th>
              <th class="py-3" style="width: 110px;">สถานะ</th>
              <th class="py-3">วันที่ลงทะเบียน</th>
              <th class="pe-4 py-3 text-end" style="width: 100px;">จัดการ</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!loading && patients.length === 0">
              <td colspan="7" class="text-center text-muted py-5">
                <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary opacity-50"></i>
                <div class="fw-medium">
                  {{ searchQuery ? 'ไม่พบข้อมูลผู้ป่วยที่ตรงกับคำค้นหา' : 'ยังไม่มีข้อมูลผู้ป่วยในระบบ' }}
                </div>
              </td>
            </tr>
            <tr v-for="p in patients" :key="p.id">
              <td class="ps-4">
                <span class="badge bg-light text-primary font-monospace border">{{ p.hn }}</span>
              </td>
              <td class="fw-semibold text-dark">{{ p.full_name }}</td>
              <td class="text-muted small font-monospace">{{ formatPhone(p.phone) }}</td>
              <td class="text-muted small">{{ calculateAge(p.birth_date) }}</td>
              <td>
                <span :class="p.is_active == 1 ? 'badge bg-success-subtle text-success border border-success-subtle' : 'badge bg-secondary-subtle text-secondary border border-secondary-subtle'">
                  <i :class="p.is_active == 1 ? 'bi bi-check-circle-fill me-1' : 'bi bi-dash-circle me-1'"></i>
                  {{ p.is_active == 1 ? 'ใช้งาน' : 'ระงับ' }}
                </span>
              </td>
              <td class="text-muted small">{{ p.registration_date }}</td>
              <td class="pe-4 text-end">
                <button 
                  type="button" 
                  class="btn btn-sm btn-outline-primary py-1 px-2.5 d-inline-flex align-items-center gap-1"
                  @click="editPatient(p)"
                >
                  <i class="bi bi-pencil"></i> แก้ไข
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination Footer -->
      <div class="card-footer d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
        <span class="text-muted small">
          หน้า {{ currentPage }} / {{ totalPages || 1 }} (ทั้งหมด {{ total }} รายการ)
        </span>
        <nav aria-label="Pagination">
          <ul class="pagination pagination-sm mb-0">
            <li class="page-item" :class="{ disabled: currentPage <= 1 }">
              <button class="page-link text-dark" @click="loadPatients(currentPage - 1)">‹ ก่อนหน้า</button>
            </li>

            <li 
              v-for="p in pageNumbers" 
              :key="p" 
              class="page-item" 
              :class="{ active: p === currentPage, disabled: p === '...' }"
            >
              <span v-if="p === '...'" class="page-link">...</span>
              <button v-else class="page-link" :class="p === currentPage ? 'bg-primary border-primary fw-bold text-white' : 'text-dark'" @click="loadPatients(p)">
                {{ p }}
              </button>
            </li>

            <li class="page-item" :class="{ disabled: currentPage >= totalPages || totalPages === 0 }">
              <button class="page-link text-dark" @click="loadPatients(currentPage + 1)">ถัดไป ›</button>
            </li>
          </ul>
        </nav>
      </div>
    </div>
  </div>

</div>

<script>
new Vue({
  el: '#app',
  data: {
    patients: [],
    total: 0,
    currentPage: 1,
    perPage: 10,
    totalPages: 1,
    searchQuery: '',
    searchDebounceTimer: null,
    loading: false,
    submitting: false,
    formAlert: {
      type: 'success',
      message: ''
    },
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
      const current = this.currentPage;

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
    searchQuery() {
      clearTimeout(this.searchDebounceTimer);
      this.searchDebounceTimer = setTimeout(() => {
        this.loadPatients(1);
      }, 250);
    }
  },
  mounted() {
    this.loadPatients(1);
  },
  methods: {
    async loadPatients(page = 1) {
      this.loading = true;
      this.currentPage = Math.max(1, page);
      try {
        const url = `/api/patients/ajax.patient.php?action=get_patients&search=${encodeURIComponent(this.searchQuery)}&page=${this.currentPage}&per_page=${this.perPage}`;
        const res = await fetch(url);
        const data = await res.json();
        if (data.success) {
          this.patients = data.data || [];
          this.total = data.total || 0;
          this.totalPages = data.total_pages || 1;
        }
      } catch (e) {
        console.error(e);
      } finally {
        this.loading = false;
      }
    },
    clearSearch() {
      this.searchQuery = '';
      this.loadPatients(1);
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
    editPatient(p) {
      this.formAlert.message = '';
      this.form.mode = 'update';
      this.form.patient_id = p.id;
      this.form.hn = p.hn;
      this.form.full_name = p.full_name;
      this.form.phone = p.phone;
      this.form.birth_date = p.birth_date;
      this.form.is_active = p.is_active !== undefined ? Number(p.is_active) : 1;
      window.scrollTo({ top: 0, behavior: 'smooth' });
    },
    resetForm() {
      this.formAlert.message = '';
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
      this.submitting = true;
      this.formAlert.message = '';
      try {
        const payload = {
          mode: this.form.mode,
          patient_id: this.form.patient_id || undefined,
          full_name: this.form.full_name,
          phone: this.form.phone,
          birth_date: this.form.birth_date,
          is_active: this.form.is_active !== undefined ? this.form.is_active : 1
        };

        const res = await fetch('/api/patients/ajax.patient.php?action=save_patient', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(payload)
        });
        const result = await res.json();
        if (result.success) {
          this.formAlert = {
            type: 'success',
            message: result.message || 'บันทึกข้อมูลเรียบร้อยแล้ว'
          };
          this.resetForm();
          this.loadPatients(this.currentPage);
        } else {
          this.formAlert = {
            type: 'danger',
            message: result.error || 'เกิดข้อผิดพลาดในการบันทึกข้อมูล'
          };
        }
      } catch (e) {
        this.formAlert = {
          type: 'danger',
          message: 'เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์'
        };
      } finally {
        this.submitting = false;
      }
    }
  }
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
