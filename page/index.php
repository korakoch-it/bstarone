<?php
// =====================================================================
// page/index.php - หน้ารายการนัดของวันนี้ + Modal สร้างนัดหมายแพทย์
// Flat Design Corporativo | Bootstrap 5.3.3 | Powered by Vue 2 (CDN)
// =====================================================================

$pageTitle = 'การนัดหมาย';
$activePage = 'today';
require_once __DIR__ . '/header.php';
?>

<div id="app" v-cloak>
  <div class="card mb-4">
    <div class="card-header py-3 px-4">
      <div class="row">
        <div class="col-12 d-flex flex-wrap justify-content-between align-items-center gap-3">
          <div class="d-flex align-items-center gap-3">
            <div class="d-flex align-items-center justify-content-center text-white" style="background-color: var(--corp-primary); width: 38px; height: 38px; border-radius: var(--radius-sm);">
              <i class="bi bi-calendar-check" style="font-size: 1.15rem;"></i>
            </div>
            <div>
              <h1 class="h5 fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                <span>การนัดหมาย</span>
              </h1>
            </div>
          </div>

          <div class="d-flex flex-wrap align-items-center gap-2">
            <button type="button" class="btn btn-sm btn-corp-primary d-inline-flex align-items-center gap-1.5 px-3" @click="openBookingModal">
              <i class="bi bi-plus-lg"></i>
              <span>สร้างนัดหมาย</span>
            </button>
          </div>
        </div>
        <div class="col-12 mt-3">
          <div class="row">
            <div class="col-md-4">
              <label for="filterPatient" class="form-label">ผู้ป่วย</label>
              <select id="filterPatient" v-model="filters.patientId" @change="getOverview(1)" class="form-select form-select-sm" >
                <option value="">ทั้งหมด</option>
                <option v-for="item in dropdownPatients" :key="item.value" :value="item.value">{{ item.label }}</option>
              </select>
            </div>
            <div class="col-md-4">
              <label for="filterDoctor" class="form-label">แพทย์</label>
              <select id="filterDoctor" v-model="filters.doctorId" @change="getOverview(1)" class="form-select form-select-sm" >
                <option value="">ทั้งหมด</option>
                <option v-for="item in dropdownDoctors" :key="item.value" :value="item.value">{{ item.label }}</option>
              </select>
            </div>
            <div class="col-md-4">
              <label for="filterDate" class="form-label">วันที่นัดหมาย</label>
              <input type="date" v-model="filters.date" @change="getOverview(1)" class="form-control form-control-sm">
            </div>
            <div class="col-md-4">
              <label for="filterStatus" class="form-label">สถานะ</label>
              <select id="filterStatus" v-model="filters.status" @change="getOverview(1)" class="form-select form-select-sm" >
                <option value="">ทั้งหมด</option>
                <option v-for="item in dropdownStatus" :key="item.value" :value="item.value">{{ item.label }}</option>
              </select>
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
              <th class="ps-4 py-3" style="width: 170px;">ช่วงเวลานัด</th>
              <th class="py-3" style="width: 120px;">HN</th>
              <th class="py-3">ผู้ป่วย</th>
              <th class="py-3">แพทย์</th>
              <th class="py-3">หมายเหตุ</th>
              <th class="py-3" style="width: 130px;">สถานะ</th>
              <th class="pe-4 py-3 text-end" style="width: 100px;">จัดการ</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="results.length === 0">
              <td colspan="7" class="text-center text-muted py-5">
                <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary opacity-50"></i>
                <div class="fw-medium">ไม่มีรายการนัดหมายในวันที่เลือก</div>
              </td>
            </tr>
            <tr v-for="a in results" :key="a.id">
              <td class="ps-4 fw-semibold">
                {{ a.start_time.substring(0, 5) }} - {{ a.end_time.substring(0, 5) }}
              </td>
              <td class="fw-semibold">{{ a.patient_hn }}</td>
              <td>{{ a.patient_name }}</td>
              <td>{{ a.doctor_name }}</td>
              <td class="text-muted small">{{ a.note || '-' }}</td>
              <td>
                <template v-if="a.status == 'pending'">
                  <span class="badge rounded-pill text-bg-warning">รอเข้ารับบริการ</span>
                </template>
                <template v-if="a.status == 'confirmed'">
                  <span class="badge rounded-pill text-bg-primary">ยืนยันนัดหมาย</span>
                </template>
                <template v-else-if="a.status == 'arrived'">
                  <span class="badge rounded-pill text-bg-success">มาแล้ว</span>
                </template>
                <template v-else-if="a.status == 'completed'">
                  <span class="badge rounded-pill text-bg-success">เสร็จสิ้น</span>
                </template>
                <template v-else-if="a.status == 'cancelled'">
                  <span class="badge rounded-pill text-bg-danger">ยกเลิกนัด</span>
                </template>
              </td>
              <td class="pe-4 text-end">
                <div class="d-inline-flex align-items-center gap-1.5">
                  <button 
                    type="button" 
                    class="btn btn-sm btn-outline-primary p-1.5 d-inline-flex align-items-center justify-content-center"
                    style="width: 30px; height: 30px;"
                    title="แก้ไขการนัดหมาย"
                    @click="editAppointment(a)"
                  >
                    <i class="bi bi-pencil"></i>
                  </button>
                  <button 
                    type="button" 
                    class="btn btn-sm btn-outline-danger p-1.5 d-inline-flex align-items-center justify-content-center ms-1"
                    style="width: 30px; height: 30px;"
                    title="ลบการนัดหมาย"
                    @click="confirmDeleteAppointment(a)"
                  >
                    <i class="bi bi-trash3"></i>
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

  <!-- Modal สร้าง/แก้ไขนัดหมายแพทย์ (Vue 2 + Bootstrap 5) -->
  <div class="modal fade" id="bookingModal" ref="bookingModal" tabindex="-1" aria-labelledby="bookingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 600px;">
      <div class="modal-content" style="border-radius: var(--radius-sm); border: 1px solid var(--corp-border);">
        <div class="modal-header py-3 px-4" style="background-color: var(--corp-white); border-bottom: 1px solid var(--corp-border-light);">
          <div class="d-flex align-items-center gap-2.5">
            <h2 class="h6 fw-bold mb-0 text-dark" id="bookingModalLabel">
              {{ isEditMode ? 'แก้ไขการนัดหมายแพทย์' : 'สร้างนัดหมายแพทย์' }}
            </h2>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <form @submit.prevent="submitBooking">
          <div class="modal-body p-4 vstack gap-3">
            <div v-if="modalAlert.message" :class="['alert', 'alert-' + modalAlert.type, 'py-2 px-3 small d-flex align-items-center gap-2 mb-0']">
              <i :class="['bi', modalAlert.type === 'success' ? 'bi-check-circle' : 'bi-exclamation-circle']"></i>
              <span>{{ modalAlert.message }}</span>
            </div>

            <div>
              <label class="form-label small fw-semibold text-dark mb-1 d-flex justify-content-between align-items-center">
                <span>ผู้ป่วย <span class="text-danger">*</span></span>
              </label>
              <select v-model="bookingForm.patient_id" class="form-select" required :disabled="isEditMode">
                <option value="">กรุณาเลือก</option>
                <option v-for="item in dropdownPatients" :key="item.value" :value="item.value">
                  {{ item.label }}
                </option>
              </select>
            </div>

            <div>
              <label class="form-label small fw-semibold text-dark mb-1">แพทย์ผู้ตรวจ <span class="text-danger">*</span></label>
              <select v-model="bookingForm.doctor_id" class="form-select" required>
                <option value="">กรุณาเลือก</option>
                <option v-for="item in dropdownDoctors" :key="item.value" :value="item.value">
                  {{ item.label }}
                </option>
              </select>
            </div>

            <div>
              <label class="form-label small fw-semibold text-dark mb-1">วันที่นัดหมาย <span class="text-danger">*</span></label>
              <input type="date" v-model="bookingForm.appointment_date" class="form-control" required>
            </div>

            <div class="row g-3">
              <div class="col-sm-6">
                <label class="form-label small fw-semibold text-dark mb-1">เวลาเริ่ม <span class="text-danger">*</span></label>
                <select v-model="bookingForm.start_time" class="form-select font-monospace" required>
                  <option value="">กรุณาเลือก</option>
                  <option v-for="t in availableStartTimes" :key="t" :value="t">
                    {{ t }}
                  </option>
                </select>
              </div>

              <div class="col-sm-6">
                <label class="form-label small fw-semibold text-dark mb-1">เวลาสิ้นสุด <span class="text-danger">*</span></label>
                <select v-model="bookingForm.end_time" class="form-select font-monospace" required>
                  <option value="">กรุณาเลือก</option>
                  <option v-for="t in availableEndTimes" :key="t" :value="t">
                    {{ t }}
                  </option>
                </select>
              </div>
            </div>

            <div v-if="isEditMode">
              <label class="form-label small fw-semibold text-dark mb-1">สถานะ</label>
              <select v-model="bookingForm.status" class="form-select">
                <option v-for="(item, index) in dropdownStatus" :key="index" :value="item.value">
                  {{ item.label }}
                </option>
              </select>
            </div>

            <div>
              <label class="form-label small fw-semibold text-dark mb-1">หมายเหตุ</label>
              <textarea v-model="bookingForm.note" rows="2" class="form-control"></textarea>
            </div>
          </div>

          <div class="modal-footer py-2.5 px-4" style="background-color: var(--corp-white); border-top: 1px solid var(--corp-border-light);">
            <button type="button" class="btn btn-sm btn-outline-secondary px-4 py-2" data-bs-dismiss="modal">ยกเลิก</button>
            <button type="submit" class="btn btn-sm btn-corp-primary px-4 py-2 d-inline-flex align-items-center" :disabled="isSubmitting">
              <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2" role="status"></span>
              <i v-else class="bi bi-check-circle me-2"></i>
              <span>{{ isSubmitting ? 'กำลังบันทึก...' : (isEditMode ? 'บันทึกการแก้ไข' : 'บันทึกนัดหมาย') }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal ยืนยันการลบข้อมูล -->
  <div class="modal fade" id="deleteAppointmentModal" ref="deleteAppointmentModal" tabindex="-1" aria-labelledby="deleteAppointmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
      <div class="modal-content" style="border-radius: var(--radius-sm); border: 1px solid var(--corp-border);">
        <div class="modal-body p-4 text-center">
          <div class="d-inline-flex align-items-center justify-content-center rounded-circle text-danger bg-danger-subtle mb-3" style="width: 56px; height: 56px;">
            <i class="bi bi-exclamation-triangle" style="font-size: 1.75rem;"></i>
          </div>
          <h5 class="fw-bold mb-2 text-dark" id="deleteAppointmentModalLabel">ยืนยันการลบข้อมูล</h5>
          <p class="text-muted small mb-4">
            คุณต้องการลบข้อมูลใช่หรือไม่?
          </p>
          <div class="d-flex justify-content-center gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal">
              ยกเลิก
            </button>
            <button 
              type="button" 
              class="btn btn-sm btn-danger px-3 fw-semibold d-inline-flex align-items-center gap-1.5"
              :disabled="isDeleting"
              @click="deleteAppointment"
            >
              <span v-if="isDeleting" class="spinner-border spinner-border-sm" role="status"></span>
              <i v-else class="bi bi-trash3"></i>
              <span>{{ isDeleting ? 'กำลังลบ...' : 'ยืนยันการลบ' }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>

new Vue({
  el: '#app',
  data: {
    dropdownDoctors: [],
    dropdownPatients: [],
    dropdownStatus: [],
    dropdownAppointmentTime: [],
    results: [],
    pageIndex: 1,
    pageSize: 10,
    totalPages: 1,
    totalCount: 0,
    filters: {
      patientId: '',
      doctorId: '',
      date: '<?= date('Y-m-d') ?>',
      status: ''
    },
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
    bookingForm: {
      mode: 'create',
      appointment_id: null,
      patient_id: '',
      doctor_id: '',
      appointment_date: '<?= date('Y-m-d') ?>',
      start_time: '',
      end_time: '',
      status: 'pending',
      note: ''
    },
    isDeleting: false,
    modalInstanceDelete: null,
    selectedAppointmentToDelete: null,
  },
  computed: {
    isEditMode() {
      return this.bookingForm.mode === 'update';
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
    },
    slotTimes() {
      return (this.dropdownAppointmentTime || []).map(item => item.value);
    },
    availableStartTimes() {
      const list = this.slotTimes.length > 1 ? this.slotTimes.slice(0, -1) : [...this.slotTimes];
      if (this.bookingForm.start_time && !list.includes(this.bookingForm.start_time)) {
        list.push(this.bookingForm.start_time);
        list.sort();  
      }
      return list;
    },
    availableEndTimes() {
      let list = [];
      if (!this.bookingForm.start_time) {
        list = this.slotTimes.slice(1);
      } else {
        list = this.slotTimes.filter(t => t > this.bookingForm.start_time);
      }
      if (this.bookingForm.end_time && !list.includes(this.bookingForm.end_time)) {
        list.push(this.bookingForm.end_time);
        list.sort();
      }
      return list;
    }
  },
  watch: {
    'bookingForm.start_time'(newStart) {
      if (!newStart) return;
      const validEnds = this.availableEndTimes;
      if (validEnds.length > 0 && (!this.bookingForm.end_time || this.bookingForm.end_time <= newStart)) {
        this.bookingForm.end_time = validEnds[0];
      }
    }
  },
  mounted() {
    // Dropdown
    this.loadMasterDoctor();
    this.loadMasterPatient();
    this.loadMasterAppointmentStatus();
    this.loadMasterAppointmentTime();

    this.modalInstance = new bootstrap.Modal(this.$refs.bookingModal);
    this.getOverview(1);

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('open') === 'booking' || window.location.hash === '#booking') {
      this.openBookingModal();
    }
  },
  methods: {
    async getOverview(pageIndex = 1) {
      this.pageIndex = Math.max(1, pageIndex);
      try {
        const res = await fetch('/api/index/ajax.index.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            action: 'getOverview',
            filterDoctorId: this.filters.doctorId,
            filterPatientId: this.filters.patientId,
            filterDate: this.filters.date,
            filterStatus: this.filters.status,
            pageIndex: this.pageIndex,
            pageSize: this.pageSize
          })
        });
        const data = await res.json();
        this.results = data.data || [];
        this.totalCount = data.totalCount || 0;
        this.totalPages = data.totalPages || 1;
      } catch (e) {
        this.alert = { type: 'danger', message: 'โหลดข้อมูลไม่สำเร็จ' };
      }
    },
    async loadMasterDoctor() {
      try {
        const res = await fetch('/api/masterdata/ajax.masterdata.php?action=getMasterDoctor');
        const data = await res.json();
        this.dropdownDoctors = data.data || [];
      } catch (e) {
        console.error(e);
      }
    },
    async loadMasterPatient() {
      try {
        const res = await fetch('/api/masterdata/ajax.masterdata.php?action=getMasterPatient');
        const data = await res.json();
        this.dropdownPatients = data.data || [];
      } catch (e) {
        console.error(e);
      }
    },
    async loadMasterAppointmentStatus() {
      try {
        const res = await fetch('/api/masterdata/ajax.masterdata.php?action=getMasterAppointmentStatus');
        const data = await res.json();
        this.dropdownStatus = data.data || [];
      } catch (e) {
        console.error(e);
      }
    },
    async loadMasterAppointmentTime() {
      try {
        const res = await fetch('/api/masterdata/ajax.masterdata.php?action=getMasterAppointmentTime');
        const data = await res.json();
        this.dropdownAppointmentTime = data.data || [];
      } catch (e) {
        console.error(e);
      }
    },
    openBookingModal() {
      this.modalAlert.message = '';
      this.resetForm();
      if (!this.modalInstance && this.$refs.bookingModal) {
        this.modalInstance = new bootstrap.Modal(this.$refs.bookingModal);
      }
      if (this.modalInstance) {
        this.modalInstance.show();
      }
    },
    editAppointment(a) {
      this.modalAlert.message = '';
      this.bookingForm = {
        mode: 'update',
        appointment_id: a.id,
        patient_id: a.patient_id,
        doctor_id: a.doctor_id,
        appointment_date: a.appointment_date,
        note: a.note || '',
        status: a.status || 'pending',
        end_time: (a.end_time || '').substring(0, 5),
        start_time: (a.start_time || '').substring(0, 5)
      };
      this.$nextTick(() => {
        this.bookingForm.end_time = (a.end_time || '').substring(0, 5);
      });
      if (!this.modalInstance && this.$refs.bookingModal) {
        this.modalInstance = new bootstrap.Modal(this.$refs.bookingModal);
      }
      if (this.modalInstance) {
        this.modalInstance.show();
      }
    },
    resetForm() {
      this.bookingForm = {
        mode: 'create',
        appointment_id: null,
        patient_id: '',
        doctor_id: '',
        appointment_date: this.filters.date || '<?= date('Y-m-d') ?>',
        start_time: '',
        end_time: '',
        status: 'pending',
        note: ''
      };
    },
    confirmDeleteAppointment(a) {
      this.selectedAppointmentToDelete = a;
      if (!this.modalInstanceDelete && this.$refs.deleteAppointmentModal) {
        this.modalInstanceDelete = new bootstrap.Modal(this.$refs.deleteAppointmentModal);
      }
      if (this.modalInstanceDelete) {
        this.modalInstanceDelete.show();
      }
    },
    async deleteAppointment() {
      if (!this.selectedAppointmentToDelete) return;
      this.isDeleting = true;
      try {
        const res = await fetch('/api/index/ajax.index.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            action: 'deleteAppointment',
            appointment_id: this.selectedAppointmentToDelete.id
          })
        });
        const data = await res.json();
        if (data.success) {
          if (this.modalInstanceDelete) {
            this.modalInstanceDelete.hide();
          }
          this.alert = {
            type: 'success',
            message: data.message || 'ลบรายการนัดหมายเรียบร้อยแล้ว'
          };
          this.selectedAppointmentToDelete = null;
          this.getOverview(this.pageIndex);
        } else {
          this.alert = {
            type: 'danger',
            message: data.error || 'เกิดข้อผิดพลาดในการลบนัดหมาย'
          };
        }
      } catch (e) {
        this.alert = {
          type: 'danger',
          message: 'เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์'
        };
      } finally {
        this.isDeleting = false;
      }
    },
    async submitBooking() {
      this.isSubmitting = true;
      this.modalAlert.message = '';
      try {
        const res = await fetch('/api/index/ajax.index.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            action: 'saveAppointment',
            mode: this.bookingForm.mode,
            appointment_id: this.bookingForm.appointment_id,
            patient_id: this.bookingForm.patient_id,
            doctor_id: this.bookingForm.doctor_id,
            appointment_date: this.bookingForm.appointment_date,
            start_time: this.bookingForm.start_time,
            end_time: this.bookingForm.end_time,
            status: this.bookingForm.status,
            note: this.bookingForm.note
          })
        });
        const data = await res.json();
        if (data.success) {
          if (this.modalInstance) {
            this.modalInstance.hide();
          }
          this.alert = {
            type: 'success',
            message: data.message || (this.isEditMode ? 'แก้ไขข้อมูลการนัดหมายสำเร็จ' : 'สร้างนัดหมายสำเร็จเรียบร้อยแล้ว')
          };
          this.getOverview(this.pageIndex);
          this.resetForm();
        } else {
          this.modalAlert = {
            type: 'danger',
            message: data.error || data.message || 'บันทึกไม่สำเร็จ'
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
