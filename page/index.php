<?php
// =====================================================================
// page/index.php - หน้ารายการนัดของวันนี้ + Modal สร้างนัดหมายแพทย์
// Flat Design Corporativo | Bootstrap 5.3.3 | Powered by Vue 2 (CDN)
// =====================================================================

require_once __DIR__ . '/../api/booking/class.booking.php';

$booking = new ClassBooking();
$timeSlots = $booking->getTimeSlots();
$clinicSettings = $booking->getClinicSettings();

$pageTitle = 'รายการนัดหมายประจำวัน';
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
              <th class="py-3" style="width: 170px;">ช่วงเวลานัด</th>
              <th class="py-3" style="width: 120px;">HN</th>
              <th class="py-3">ผู้ป่วย</th>
              <th class="py-3">แพทย์</th>
              <th class="py-3">หมายเหตุ</th>
              <th class="pe-4 py-3 text-center" style="width: 150px;">สถานะ</th>
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
              <td class="fw-semibold">
                {{ a.start_time.substring(0, 5) }} - {{ a.end_time.substring(0, 5) }}
              </td>
              <td class="fw-semibold">{{ a.patient_hn }}</td>
              <td>{{ a.patient_name }}</td>
              <td>{{ a.doctor_name }}</td>
              <td class="text-muted small">{{ a.note || '-' }}</td>
              <td class="pe-4 text-center">
                <select 
                  class="form-select form-select-sm" 
                  :class="getStatusClass(a.status)"
                  v-model="a.status" 
                  @change="updateStatus(a)"
                  style="font-size: 0.8125rem;"
                >
                  <option value="pending">รอรับบริการ</option>
                  <option value="arrived">มาถึงแล้ว</option>
                  <option value="cancelled">ยกเลิก</option>
                </select>
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

  <!-- Modal สร้างนัดหมายแพทย์ (Vue 2 + Bootstrap 5) -->
  <div class="modal fade" id="bookingModal" ref="bookingModal" tabindex="-1" aria-labelledby="bookingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 600px;">
      <div class="modal-content" style="border-radius: var(--radius-sm); border: 1px solid var(--corp-border);">
        <div class="modal-header py-3 px-4" style="background-color: var(--corp-white); border-bottom: 1px solid var(--corp-border-light);">
          <div class="d-flex align-items-center gap-2.5">
            <div class="d-flex align-items-center justify-content-center text-white" style="background-color: var(--corp-primary); width: 32px; height: 32px; border-radius: var(--radius-sm);">
              <i class="bi bi-calendar-plus"></i>
            </div>
            <div>
              <h2 class="h6 fw-bold mb-0 text-dark" id="bookingModalLabel">สร้างนัดหมายแพทย์</h2>
              <div class="text-muted small" style="font-size: 0.75rem;">Medical Appointment Scheduler</div>
            </div>
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
                <a href="/page/patients.php" class="text-decoration-none small text-primary fw-medium" target="_blank">
                  <i class="bi bi-person-plus me-1"></i>ลงทะเบียนคนไข้ใหม่
                </a>
              </label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-person"></i></span>
                <select v-model="bookingForm.patient_id" class="form-select" required>
                  <option value="">กรุณาเลือก</option>
                  <option v-for="item in dropdownPatients" :key="item.value" :value="item.value">
                    {{ item.label }}
                  </option>
                </select>
              </div>
            </div>

            <div>
              <label class="form-label small fw-semibold text-dark mb-1">แพทย์ผู้ตรวจ <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-hospital"></i></span>
                <select v-model="bookingForm.doctor_id" class="form-select" required>
                  <option value="">กรุณาเลือก</option>
                  <option v-for="item in dropdownDoctors" :key="item.value" :value="item.value">
                    {{ item.label }}
                  </option>
                </select>
              </div>
            </div>

            <div>
              <label class="form-label small fw-semibold text-dark mb-1">วันที่นัดหมาย <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-calendar3"></i></span>
                <input type="date" v-model="bookingForm.appointment_date" class="form-control" required>
              </div>
            </div>

            <div class="row g-3">
              <div class="col-sm-6">
                <label class="form-label small fw-semibold text-dark mb-1">เวลาเริ่ม <span class="text-danger">*</span></label>
                <div class="input-group">
                  <span class="input-group-text"><i class="bi bi-clock"></i></span>
                  <select v-model="bookingForm.start_time" class="form-select font-monospace" required>
                    <option value="">-- เลือกเวลาเริ่ม --</option>
                    <option v-for="t in availableStartTimes" :key="t" :value="t">
                      {{ t }} น.
                    </option>
                  </select>
                </div>
              </div>

              <div class="col-sm-6">
                <label class="form-label small fw-semibold text-dark mb-1">เวลาสิ้นสุด <span class="text-danger">*</span></label>
                <div class="input-group">
                  <span class="input-group-text"><i class="bi bi-clock-fill"></i></span>
                  <select v-model="bookingForm.end_time" class="form-select font-monospace" required>
                    <option value="">-- เลือกเวลาสิ้นสุด --</option>
                    <option v-for="t in availableEndTimes" :key="t" :value="t">
                      {{ t }} น.
                    </option>
                  </select>
                </div>
              </div>

              <div class="col-12 mt-1">
                <div class="d-flex align-items-center justify-content-between text-muted" style="font-size: 0.75rem;">
                  <span class="d-inline-flex align-items-center gap-1.5">
                    <i class="bi bi-database-check text-primary"></i>
                    <span>สล็อตเวลาจากฐานข้อมูล (ช่วงละ {{ clinicSettings.slot_interval_minutes || 30 }} นาที)</span>
                  </span>
                  <span class="badge bg-light text-secondary border font-monospace fw-normal">
                    {{ durationBadge }}
                  </span>
                </div>
              </div>
            </div>

            <div>
              <label class="form-label small fw-semibold text-dark mb-1">อาการ / หมายเหตุการนัด</label>
              <textarea v-model="bookingForm.note" rows="2" class="form-control" placeholder="ระบุอาการเบื้องต้น หรือหมายเหตุสำหรับแพทย์..."></textarea>
            </div>
          </div>

          <div class="modal-footer py-2.5 px-4" style="background-color: var(--corp-white); border-top: 1px solid var(--corp-border-light);">
            <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal">ยกเลิก</button>
            <button type="submit" class="btn btn-sm btn-corp-primary px-4 py-2 d-inline-flex align-items-center" :disabled="submitting">
              <span v-if="submitting" class="spinner-border spinner-border-sm me-2" role="status"></span>
              <i v-else class="bi bi-check-circle me-2"></i>
              <span>บันทึกนัดหมาย</span>
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
    dropdownDoctors: [],
    dropdownPatients: [],
    dropdownStatus: [],
    dropdownAppointmentTime: [],
    timeSlots: <?= json_encode($timeSlots, JSON_UNESCAPED_UNICODE) ?>,
    clinicSettings: <?= json_encode($clinicSettings, JSON_UNESCAPED_UNICODE) ?>,
    results: [],
    pageIndex: 1,
    pageSize: 10,
    totalPages: 1,
    totalCount: 0,
    submitting: false,
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
    bookingForm: {
      patient_id: '',
      doctor_id: '',
      appointment_date: '<?= date('Y-m-d') ?>',
      start_time: '10:00',
      end_time: '10:30',
      note: ''
    },
    modalInstance: null
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
    },
    slotTimes() {
      return (this.timeSlots || []).map(s => s.slot_time.substring(0, 5));
    },
    availableStartTimes() {
      const closeTime = (this.clinicSettings.clinic_close_time || '18:00:00').substring(0, 5);
      return this.slotTimes.filter(t => t < closeTime);
    },
    availableEndTimes() {
      if (!this.bookingForm.start_time) {
        return this.slotTimes.slice(1);
      }
      return this.slotTimes.filter(t => t > this.bookingForm.start_time);
    },
    durationBadge() {
      const start = this.bookingForm.start_time;
      const end = this.bookingForm.end_time;
      if (!start || !end || end <= start) return 'เวลาไม่ถูกต้อง';
      const [sH, sM] = start.split(':').map(Number);
      const [eH, eM] = end.split(':').map(Number);
      const diff = (eH * 60 + eM) - (sH * 60 + sM);
      if (diff < 60) return `ระยะเวลา: ${diff} นาที`;
      const hrs = Math.floor(diff / 60);
      const mins = diff % 60;
      return `ระยะเวลา: ${hrs} ชม.${mins > 0 ? ' ' + mins + ' นาที' : ''}`;
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
    // this.loadPatients();

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('open') === 'booking' || window.location.hash === '#booking') {
      this.openBookingModal();
    }
  },
  methods: {
    getStatusClass(status) {
      if (status === 'arrived') return 'border-success text-success fw-semibold';
      if (status === 'cancelled') return 'border-danger text-danger';
      return 'border-warning text-dark fw-semibold';
    },
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
    async updateStatus(appt) {
      try {
        const res = await fetch('/api/index/ajax.index.php?action=update_appointment_status', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            appointment_id: appt.id,
            status: appt.status
          })
        });
        const data = await res.json();
        this.alert = {
          type: data.success ? 'success' : 'danger',
          message: data.message || data.error
        };
      } catch (e) {
        this.alert = { type: 'danger', message: 'เกิดข้อผิดพลาดในการเปลี่ยนสถานะ' };
      }
    },
    openBookingModal() {
      this.modalAlert.message = '';
      this.bookingForm.appointment_date = this.filters.date || '<?= date('Y-m-d') ?>';
      this.bookingForm.start_time = '10:00';
      this.bookingForm.end_time = '10:30';
      if (!this.modalInstance && this.$refs.bookingModal) {
        this.modalInstance = new bootstrap.Modal(this.$refs.bookingModal);
      }
      if (this.modalInstance) {
        this.modalInstance.show();
      }
    },
    async submitBooking() {
      this.submitting = true;
      this.modalAlert.message = '';
      try {
        const res = await fetch('/api/booking/ajax.booking.php?action=save_appointment', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(this.bookingForm)
        });
        const data = await res.json();
        if (data.success) {
          this.modalInstance.hide();
          this.alert = {
            type: 'success',
            message: data.message || 'สร้างนัดหมายสำเร็จเรียบร้อยแล้ว'
          };
          this.filters.date = this.bookingForm.appointment_date;
          this.getOverview(1);
          // Reset form
          this.bookingForm.patient_id = '';
          this.bookingForm.note = '';
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
        this.submitting = false;
      }
    }
  }
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
