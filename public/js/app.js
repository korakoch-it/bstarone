/**
 * BESTAR-ONE LUXURY CLINIC - FRONTEND APPLICATION
 * Handles AJAX interactions, real-time validations, modals, and report updates.
 */

const App = {
  activeTab: 'today',
  doctors: [],
  debounceTimer: null,

  init() {
    this.setupTabs();
    this.loadDoctors();
    this.loadTodayAppointments();
    this.setupPatientSearch();
    this.setupBookingValidation();
    this.setupForms();
    this.setupReportPresets();
    this.loadReports();
  },

  // --------------------------------------------------------------------------
  // Tab Navigation
  // --------------------------------------------------------------------------
  setupTabs() {
    const tabs = document.querySelectorAll('.nav-tab');
    tabs.forEach(tab => {
      tab.addEventListener('click', () => {
        const target = tab.dataset.tab;
        this.switchTab(target);
      });
    });
  },

  switchTab(target) {
    this.activeTab = target;
    document.querySelectorAll('.nav-tab').forEach(t => {
      t.classList.toggle('active', t.dataset.tab === target);
    });
    document.querySelectorAll('.tab-pane').forEach(p => {
      p.classList.toggle('active', p.id === `tab-${target}`);
    });

    if (target === 'today') this.loadTodayAppointments();
    if (target === 'patients') this.loadPatients();
    if (target === 'reports') this.loadReports();
  },

  // --------------------------------------------------------------------------
  // Doctor Data
  // --------------------------------------------------------------------------
  async loadDoctors() {
    try {
      const res = await fetch('/api/doctors');
      const json = await res.json();
      if (json.success) {
        this.doctors = json.data;
        this.populateDoctorSelects();
      }
    } catch (err) {
      console.error('Failed to load doctors:', err);
    }
  },

  populateDoctorSelects() {
    const filterSelect = document.getElementById('filter-doctor');
    const bookingSelect = document.getElementById('book-doctor-id');

    if (filterSelect) {
      filterSelect.innerHTML = '<option value="">แพทย์ทุกคน (All Doctors)</option>';
      this.doctors.forEach(d => {
        filterSelect.innerHTML += `<option value="${d.id}">${d.name} (${d.specialty})</option>`;
      });
    }

    if (bookingSelect) {
      bookingSelect.innerHTML = '<option value="">-- กรุณาเลือกแพทย์ --</option>';
      this.doctors.forEach(d => {
        bookingSelect.innerHTML += `<option value="${d.id}">${d.name} - ${d.specialty}</option>`;
      });
    }
  },

  // --------------------------------------------------------------------------
  // Module 3: Today's Appointments
  // --------------------------------------------------------------------------
  async loadTodayAppointments() {
    const doctorId = document.getElementById('filter-doctor')?.value || '';
    const dateInput = document.getElementById('filter-date')?.value || '';
    const tbody = document.getElementById('today-table-body');
    const countBadge = document.getElementById('today-count-badge');

    tbody.innerHTML = `<tr><td colspan="7" class="empty-state">กำลังโหลดข้อมูล...</td></tr>`;

    try {
      let url = `/api/appointments?doctor_id=${doctorId}`;
      if (dateInput) url += `&date=${dateInput}`;

      const res = await fetch(url);
      const json = await res.json();

      if (!json.success || !json.data || json.data.length === 0) {
        tbody.innerHTML = `
          <tr>
            <td colspan="7" class="empty-state">
              <div class="empty-state-icon">📋</div>
              <div>ไม่พบรายการนัดหมายสำหรับเงื่อนไขนี้</div>
            </td>
          </tr>
        `;
        if (countBadge) countBadge.textContent = '0 รายการ';
        return;
      }

      if (countBadge) countBadge.textContent = `${json.data.length} รายการ`;

      tbody.innerHTML = json.data.map(item => {
        const timeRange = `${item.start_time.substring(0, 5)} - ${item.end_time.substring(0, 5)}`;
        
        let statusBadgeClass = 'badge-pending';
        let statusText = 'รอรับบริการ';
        if (item.status === 'arrived') {
          statusBadgeClass = 'badge-arrived';
          statusText = 'มาถึงแล้ว';
        } else if (item.status === 'cancelled') {
          statusBadgeClass = 'badge-cancelled';
          statusText = 'ยกเลิกแล้ว';
        }

        return `
          <tr>
            <td><strong>${timeRange}</strong> น.</td>
            <td><span class="badge badge-hn">${item.patient_hn}</span></td>
            <td>
              <div style="font-weight: 600;">${item.patient_name}</div>
              <div style="font-size: 0.8rem; color: var(--text-muted);">${item.patient_phone}</div>
            </td>
            <td>
              <div>${item.doctor_name}</div>
              <div style="font-size: 0.78rem; color: var(--text-muted);">${item.doctor_specialty}</div>
            </td>
            <td style="max-width: 200px; color: var(--text-secondary); font-size: 0.85rem;">
              ${item.note || '<span style="color: #cbd5e1;">-</span>'}
            </td>
            <td>
              <span class="badge ${statusBadgeClass}">${statusText}</span>
            </td>
            <td>
              <select class="status-select" onchange="App.changeAppointmentStatus(${item.id}, this.value)">
                <option value="pending" ${item.status === 'pending' ? 'selected' : ''}>⏳ รอ</option>
                <option value="arrived" ${item.status === 'arrived' ? 'selected' : ''}>✅ มาแล้ว</option>
                <option value="cancelled" ${item.status === 'cancelled' ? 'selected' : ''}>❌ ยกเลิก</option>
              </select>
            </td>
          </tr>
        `;
      }).join('');
    } catch (err) {
      console.error(err);
      tbody.innerHTML = `<tr><td colspan="7" class="empty-state" style="color: red;">เกิดข้อผิดพลาดในการโหลดข้อมูล</td></tr>`;
    }
  },

  async changeAppointmentStatus(id, newStatus) {
    try {
      const res = await fetch(`/api/appointments/${id}/status`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ status: newStatus }),
      });
      const json = await res.json();
      if (json.success) {
        this.showToast('ปรับปรุงสถานะนัดหมายเรียบร้อยแล้ว', 'success');
        this.loadTodayAppointments();
      } else {
        this.showToast(json.error || 'ไม่สามารถเปลี่ยนสถานะได้', 'error');
        this.loadTodayAppointments();
      }
    } catch (err) {
      this.showToast('เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์', 'error');
      this.loadTodayAppointments();
    }
  },

  // --------------------------------------------------------------------------
  // Module 1: Patients Directory & Search
  // --------------------------------------------------------------------------
  setupPatientSearch() {
    const searchInput = document.getElementById('patient-search-input');
    if (!searchInput) return;

    searchInput.addEventListener('input', (e) => {
      clearTimeout(this.debounceTimer);
      this.debounceTimer = setTimeout(() => {
        this.loadPatients(e.target.value);
      }, 300);
    });
  },

  async loadPatients(keyword = '') {
    const tbody = document.getElementById('patient-table-body');
    if (!tbody) return;

    tbody.innerHTML = `<tr><td colspan="6" class="empty-state">กำลังค้นหาข้อมูล...</td></tr>`;

    try {
      const res = await fetch(`/api/patients?search=${encodeURIComponent(keyword)}`);
      const json = await res.json();

      if (!json.success || !json.data || json.data.length === 0) {
        tbody.innerHTML = `
          <tr>
            <td colspan="6" class="empty-state">
              <div class="empty-state-icon">👤</div>
              <div>ไม่พบข้อมูลผู้ป่วยที่ตรงกับการค้นหา</div>
            </td>
          </tr>
        `;
        return;
      }

      tbody.innerHTML = json.data.map(p => {
        const birthDate = new Date(p.birth_date);
        const age = Math.floor((new Date() - birthDate) / (365.25 * 24 * 60 * 60 * 1000));
        
        return `
          <tr>
            <td><span class="badge badge-hn">${p.hn}</span></td>
            <td><strong>${p.full_name}</strong></td>
            <td><code>${p.phone}</code></td>
            <td>${p.birth_date} (${age} ปี)</td>
            <td>${p.registration_date}</td>
            <td>
              <button class="btn btn-secondary btn-sm" onclick="App.openEditPatientModal(${p.id})">
                ✏️ แก้ไข
              </button>
              <button class="btn btn-primary btn-sm" onclick="App.openBookingModalWithPatient(${p.id}, '${p.hn}', '${p.full_name}')">
                📅 นัดตรวจ
              </button>
            </td>
          </tr>
        `;
      }).join('');
    } catch (err) {
      console.error(err);
      tbody.innerHTML = `<tr><td colspan="6" class="empty-state" style="color: red;">เกิดข้อผิดพลาดในการโหลดข้อมูล</td></tr>`;
    }
  },

  // --------------------------------------------------------------------------
  // Module 2: Appointment Booking & Overlap Check
  // --------------------------------------------------------------------------
  setupBookingValidation() {
    const doctorSelect = document.getElementById('book-doctor-id');
    const dateInput = document.getElementById('book-date');
    const startInput = document.getElementById('book-start-time');
    const endInput = document.getElementById('book-end-time');

    const check = () => this.checkSlotAvailability();

    if (doctorSelect) doctorSelect.addEventListener('change', check);
    if (dateInput) dateInput.addEventListener('change', check);
    if (startInput) startInput.addEventListener('change', check);
    if (endInput) endInput.addEventListener('change', check);
  },

  async checkSlotAvailability() {
    const doctorId = document.getElementById('book-doctor-id')?.value;
    const date = document.getElementById('book-date')?.value;
    const start = document.getElementById('book-start-time')?.value;
    const end = document.getElementById('book-end-time')?.value;
    const alertBox = document.getElementById('book-conflict-alert');
    const submitBtn = document.getElementById('book-submit-btn');

    if (!doctorId || !date || !start || !end) {
      if (alertBox) alertBox.style.display = 'none';
      return;
    }

    if (start >= end) {
      if (alertBox) {
        alertBox.innerHTML = '⚠️ เวลาเริ่มต้นต้องน้อยกว่าเวลาสิ้นสุด';
        alertBox.style.display = 'block';
      }
      if (submitBtn) submitBtn.disabled = true;
      return;
    }

    try {
      const res = await fetch(`/api/appointments/check-availability?doctor_id=${doctorId}&date=${date}&start_time=${start}&end_time=${end}`);
      const json = await res.json();

      if (json.success) {
        if (!json.available && json.conflict) {
          const c = json.conflict;
          const range = `${c.start_time.substring(0, 5)} - ${c.end_time.substring(0, 5)}`;
          if (alertBox) {
            alertBox.innerHTML = `
              <strong>🚫 นัดหมายเวลาซ้อนกัน:</strong><br>
              ${c.doctor_name} มีนัดหมายแล้วในช่วงเวลา <strong>${range} น.</strong><br>
              คนไข้: ${c.patient_name} (${c.patient_hn})
            `;
            alertBox.style.display = 'block';
          }
          if (submitBtn) submitBtn.disabled = true;
        } else {
          if (alertBox) alertBox.style.display = 'none';
          if (submitBtn) submitBtn.disabled = false;
        }
      }
    } catch (err) {
      console.error(err);
    }
  },

  // --------------------------------------------------------------------------
  // Module 4: Summary Reports
  // --------------------------------------------------------------------------
  setupReportPresets() {
    const todayBtn = document.getElementById('preset-today');
    const weekBtn = document.getElementById('preset-week');
    const monthBtn = document.getElementById('preset-month');

    const startInput = document.getElementById('report-start-date');
    const endInput = document.getElementById('report-end-date');

    const formatDate = (d) => d.toISOString().split('T')[0];

    if (todayBtn) {
      todayBtn.addEventListener('click', () => {
        const today = formatDate(new Date());
        startInput.value = today;
        endInput.value = today;
        this.loadReports();
      });
    }

    if (weekBtn) {
      weekBtn.addEventListener('click', () => {
        const now = new Date();
        const start = new Date(now.setDate(now.getDate() - 7));
        startInput.value = formatDate(start);
        endInput.value = formatDate(new Date());
        this.loadReports();
      });
    }

    if (monthBtn) {
      monthBtn.addEventListener('click', () => {
        const now = new Date();
        startInput.value = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-01`;
        endInput.value = formatDate(new Date());
        this.loadReports();
      });
    }
  },

  async loadReports() {
    const startDate = document.getElementById('report-start-date')?.value || '';
    const endDate = document.getElementById('report-end-date')?.value || '';

    if (!startDate || !endDate) return;

    try {
      const res = await fetch(`/api/reports/summary?start_date=${startDate}&end_date=${endDate}`);
      const json = await res.json();

      if (json.success && json.data) {
        const m = json.data.metrics;
        document.getElementById('metric-new-patients').textContent = m.new_patients_count.toLocaleString();
        document.getElementById('metric-total-appts').textContent = m.total_appointments.toLocaleString();
        document.getElementById('metric-pending').textContent = m.count_pending.toLocaleString();
        document.getElementById('metric-arrived').textContent = m.count_arrived.toLocaleString();
        document.getElementById('metric-cancelled').textContent = m.count_cancelled.toLocaleString();

        // Populate doctor breakdown
        const docTbody = document.getElementById('report-doctor-tbody');
        if (docTbody && json.data.doctor_breakdown) {
          docTbody.innerHTML = json.data.doctor_breakdown.map(d => `
            <tr>
              <td><strong>${d.doctor_name}</strong></td>
              <td>${d.specialty}</td>
              <td><strong>${d.doctor_total_appointments}</strong></td>
              <td><span class="badge badge-arrived">${d.doctor_arrived}</span></td>
              <td><span class="badge badge-pending">${d.doctor_pending}</span></td>
              <td><span class="badge badge-cancelled">${d.doctor_cancelled}</span></td>
            </tr>
          `).join('');
        }
      }
    } catch (err) {
      console.error('Failed to load reports:', err);
    }
  },

  // --------------------------------------------------------------------------
  // Modals & Forms
  // --------------------------------------------------------------------------
  openModal(modalId) {
    document.getElementById(modalId)?.classList.add('active');
  },

  closeModal(modalId) {
    document.getElementById(modalId)?.classList.remove('active');
  },

  openBookingModalWithPatient(id, hn, name) {
    this.openModal('modal-booking');
    const select = document.getElementById('book-patient-id');
    if (select) {
      select.innerHTML = `<option value="${id}">${name} (${hn})</option>`;
      select.value = id;
    }
  },

  async openEditPatientModal(id) {
    try {
      const res = await fetch(`/api/patients/${id}`);
      const json = await res.json();
      if (json.success && json.data) {
        const p = json.data;
        document.getElementById('edit-patient-id').value = p.id;
        document.getElementById('edit-patient-hn').value = p.hn;
        document.getElementById('edit-patient-name').value = p.full_name;
        document.getElementById('edit-patient-phone').value = p.phone;
        document.getElementById('edit-patient-birth').value = p.birth_date;
        this.openModal('modal-patient-edit');
      }
    } catch (err) {
      this.showToast('ไม่สามารถดึงข้อมูลผู้ป่วยได้', 'error');
    }
  },

  setupForms() {
    // 1. Patient Create Form
    const patientForm = document.getElementById('form-patient-create');
    if (patientForm) {
      patientForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const data = {
          full_name: document.getElementById('create-patient-name').value,
          phone: document.getElementById('create-patient-phone').value,
          birth_date: document.getElementById('create-patient-birth').value,
        };

        try {
          const res = await fetch('/api/patients', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data),
          });
          const json = await res.json();
          if (json.success) {
            this.showToast(json.message, 'success');
            patientForm.reset();
            this.closeModal('modal-patient-create');
            this.loadPatients();
          } else {
            this.showToast(json.error || 'บันทึกไม่สำเร็จ', 'error');
          }
        } catch (err) {
          this.showToast('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
        }
      });
    }

    // 2. Patient Edit Form
    const editPatientForm = document.getElementById('form-patient-edit');
    if (editPatientForm) {
      editPatientForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const id = document.getElementById('edit-patient-id').value;
        const data = {
          full_name: document.getElementById('edit-patient-name').value,
          phone: document.getElementById('edit-patient-phone').value,
          birth_date: document.getElementById('edit-patient-birth').value,
        };

        try {
          const res = await fetch(`/api/patients/${id}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data),
          });
          const json = await res.json();
          if (json.success) {
            this.showToast('แก้ไขข้อมูลสำเร็จ', 'success');
            this.closeModal('modal-patient-edit');
            this.loadPatients();
          } else {
            this.showToast(json.error || 'แก้ไขไม่สำเร็จ', 'error');
          }
        } catch (err) {
          this.showToast('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
        }
      });
    }

    // 3. Appointment Booking Form
    const bookingForm = document.getElementById('form-booking-create');
    if (bookingForm) {
      bookingForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const data = {
          patient_id: document.getElementById('book-patient-id').value,
          doctor_id: document.getElementById('book-doctor-id').value,
          appointment_date: document.getElementById('book-date').value,
          start_time: document.getElementById('book-start-time').value,
          end_time: document.getElementById('book-end-time').value,
          note: document.getElementById('book-note').value,
        };

        try {
          const res = await fetch('/api/appointments', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data),
          });
          const json = await res.json();

          if (json.success) {
            this.showToast(json.message, 'success');
            bookingForm.reset();
            this.closeModal('modal-booking');
            this.loadTodayAppointments();
            this.loadReports();
          } else {
            this.showToast(json.error || 'สร้างนัดหมายไม่สำเร็จ', 'error');
          }
        } catch (err) {
          this.showToast('เกิดข้อผิดพลาดในการบันทึกนัดหมาย', 'error');
        }
      });
    }
  },

  // --------------------------------------------------------------------------
  // Toast Helper
  // --------------------------------------------------------------------------
  showToast(message, type = 'info') {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    let icon = 'ℹ️';
    if (type === 'success') icon = '✅';
    if (type === 'error') icon = '⚠️';

    toast.innerHTML = `<span>${icon}</span> <span>${message}</span>`;
    container.appendChild(toast);

    setTimeout(() => {
      toast.style.opacity = '0';
      toast.style.transform = 'translateY(10px)';
      toast.style.transition = 'all 0.3s ease';
      setTimeout(() => toast.remove(), 300);
    }, 4000);
  }
};

// Auto-initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => App.init());
