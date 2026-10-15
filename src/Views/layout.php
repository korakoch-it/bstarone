<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bestar-one Luxury Clinic | ระบบนัดหมายคลินิก</title>
  <!-- Google Fonts: Prompt for modern Thai typography -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/css/style.css">
</head>
<body>

  <!-- Top Navigation Bar -->
  <header class="navbar">
    <div class="brand">
      <div class="brand-icon">✦</div>
      <div>
        <div class="brand-title">BESTAR-ONE LUXURY CLINIC</div>
        <div class="brand-subtitle">Smart Medical & Aesthetic Scheduling System</div>
      </div>
    </div>

    <!-- Navigation Tabs -->
    <nav class="nav-tabs">
      <button class="nav-tab active" data-tab="today">
        <span>📅</span> รายการนัดของวันนี้
      </button>
      <button class="nav-tab" data-tab="patients">
        <span>👥</span> ข้อมูลผู้ป่วย
      </button>
      <button class="nav-tab" data-tab="reports">
        <span>📊</span> รายงานสรุป
      </button>
    </nav>
  </header>

  <!-- Main Content Area -->
  <main class="main-content">

    <!-- ================================================================== -->
    <!-- TAB 1: หน้ารายการนัดของวันนี้ (Today's Appointments Queue)        -->
    <!-- ================================================================== -->
    <section id="tab-today" class="tab-pane active">
      <div class="card">
        <div class="card-header">
          <div>
            <h2 class="card-title">
              <span>📅</span> รายการนัดหมายประจำวัน
              <span id="today-count-badge" class="badge badge-hn" style="font-size: 0.8rem;">กำลังโหลด...</span>
            </h2>
            <div class="card-subtitle">
              แสดงรายการนัดหมายตามลำดับเวลา สามารถกรองตามแพทย์ และปรับเปลี่ยนสถานะได้ทันที
            </div>
          </div>
          
          <div class="controls-bar">
            <!-- Filter by Doctor -->
            <select id="filter-doctor" class="form-control" style="width: auto; min-width: 220px;" onchange="App.loadTodayAppointments()">
              <option value="">แพทย์ทุกคน (All Doctors)</option>
            </select>

            <!-- Date Picker for testing any date -->
            <input type="date" id="filter-date" class="form-control" style="width: auto;" value="<?= date('Y-m-d') ?>" onchange="App.loadTodayAppointments()">

            <!-- New Appointment Button -->
            <button class="btn btn-gold" onclick="App.openModal('modal-booking')">
              <span>➕</span> สร้างนัดหมายใหม่
            </button>
          </div>
        </div>

        <!-- Appointment Table -->
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th style="width: 130px;">เวลาตรวจ</th>
                <th style="width: 110px;">HN</th>
                <th>ชื่อ-นามสกุล คนไข้</th>
                <th>แพทย์ผู้ตรวจ</th>
                <th>อาการ / หมายเหตุ</th>
                <th style="width: 120px;">สถานะ</th>
                <th style="width: 140px;">เปลี่ยนสถานะ</th>
              </tr>
            </thead>
            <tbody id="today-table-body">
              <!-- Populated dynamically via JS -->
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- ================================================================== -->
    <!-- TAB 2: จัดการข้อมูลผู้ป่วย (Patient Directory)                   -->
    <!-- ================================================================== -->
    <section id="tab-patients" class="tab-pane">
      <div class="card">
        <div class="card-header">
          <div>
            <h2 class="card-title">
              <span>👥</span> ระบบจัดการข้อมูลผู้ป่วย
            </h2>
            <div class="card-subtitle">
              ค้นหาและจัดการเวชระเบียนผู้ป่วย ระบบจะออกรหัส HN อัตโนมัติและ Normalize เบอร์โทรศัพท์เป็นมาตรฐาน
            </div>
          </div>

          <div class="controls-bar">
            <!-- Live Search input -->
            <input type="text" id="patient-search-input" class="form-control" style="min-width: 280px;" placeholder="🔍 ค้นหาด้วย ชื่อ, HN หรือเบอร์โทร...">

            <!-- Register New Patient Button -->
            <button class="btn btn-primary" onclick="App.openModal('modal-patient-create')">
              <span>➕</span> ลงทะเบียนผู้ป่วยใหม่
            </button>
          </div>
        </div>

        <!-- Patient Table -->
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th style="width: 110px;">HN</th>
                <th>ชื่อ-นามสกุล</th>
                <th>เบอร์โทรศัพท์ (Normalized)</th>
                <th>วันเกิด (อายุ)</th>
                <th>วันที่ลงทะเบียน</th>
                <th style="width: 180px;">จัดการ</th>
              </tr>
            </thead>
            <tbody id="patient-table-body">
              <!-- Populated dynamically via JS -->
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- ================================================================== -->
    <!-- TAB 3: รายงานสรุป (Summary Reports & Analytics)                  -->
    <!-- ================================================================== -->
    <section id="tab-reports" class="tab-pane">
      <div class="card">
        <div class="card-header">
          <div>
            <h2 class="card-title">
              <span>📊</span> รายงานสรุปเชิงสถิติ (Summary Analytics)
            </h2>
            <div class="card-subtitle">
              คำนวณและประมวลผลสรุปตามช่วงวันที่ ออกแบบเพื่อรองรับข้อมูลขนาดใหญ่ (100,000+ แถว) ด้วย B-Tree Indexing
            </div>
          </div>

          <div class="controls-bar">
            <label class="form-label" style="margin: 0;">ช่วงวันที่:</label>
            <input type="date" id="report-start-date" class="form-control" style="width: auto;" value="<?= date('Y-m-01') ?>">
            <span>ถึง</span>
            <input type="date" id="report-end-date" class="form-control" style="width: auto;" value="<?= date('Y-m-d') ?>">
            
            <button class="btn btn-primary" onclick="App.loadReports()">
              <span>🔍</span> ค้นหารายงาน
            </button>
          </div>
        </div>

        <!-- Quick Presets -->
        <div style="margin-bottom: 1.5rem; display: flex; gap: 8px;">
          <button id="preset-today" class="btn btn-secondary btn-sm">วันนี้</button>
          <button id="preset-week" class="btn btn-secondary btn-sm">7 วันย้อนหลัง</button>
          <button id="preset-month" class="btn btn-secondary btn-sm">เดือนปัจจุบัน</button>
        </div>

        <!-- Metrics Cards Grid -->
        <div class="metrics-grid">
          <div class="metric-card metric-patients">
            <div class="metric-icon">👤</div>
            <div>
              <div id="metric-new-patients" class="metric-value">0</div>
              <div class="metric-label">จำนวนผู้ป่วยใหม่</div>
            </div>
          </div>

          <div class="metric-card metric-total">
            <div class="metric-icon">📋</div>
            <div>
              <div id="metric-total-appts" class="metric-value">0</div>
              <div class="metric-label">จำนวนนัดหมายทั้งหมด</div>
            </div>
          </div>

          <div class="metric-card metric-pending">
            <div class="metric-icon">⏳</div>
            <div>
              <div id="metric-pending" class="metric-value">0</div>
              <div class="metric-label">สถานะ: รอรับบริการ</div>
            </div>
          </div>

          <div class="metric-card metric-arrived">
            <div class="metric-icon">✅</div>
            <div>
              <div id="metric-arrived" class="metric-value">0</div>
              <div class="metric-label">สถานะ: มาแล้ว</div>
            </div>
          </div>

          <div class="metric-card metric-cancelled">
            <div class="metric-icon">❌</div>
            <div>
              <div id="metric-cancelled" class="metric-value">0</div>
              <div class="metric-label">สถานะ: ยกเลิก</div>
            </div>
          </div>
        </div>

        <!-- Doctor Breakdown Table -->
        <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 1rem; color: var(--text-primary);">
          🩺 สถิติการตรวจจำแนกตามแพทย์
        </h3>
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>แพทย์ผู้ตรวจ</th>
                <th>ความเชี่ยวชาญ</th>
                <th>จำนวนนัดหมายรวม</th>
                <th>มาแล้ว (Arrived)</th>
                <th>รอรับบริการ (Pending)</th>
                <th>ยกเลิก (Cancelled)</th>
              </tr>
            </thead>
            <tbody id="report-doctor-tbody">
              <!-- Populated dynamically via JS -->
            </tbody>
          </table>
        </div>
      </div>
    </section>

  </main>

  <!-- ================================================================== -->
  <!-- MODAL: ลงทะเบียนผู้ป่วยใหม่ (Create Patient)                        -->
  <!-- ================================================================== -->
  <div id="modal-patient-create" class="modal-overlay">
    <div class="modal">
      <div class="modal-header">
        <h3 class="modal-title">ลงทะเบียนผู้ป่วยใหม่</h3>
        <button class="modal-close" onclick="App.closeModal('modal-patient-create')">&times;</button>
      </div>
      <form id="form-patient-create">
        <div class="modal-body">
          <div class="form-group">
            <label class="form-label">ชื่อ-นามสกุล <span style="color: red;">*</span></label>
            <input type="text" id="create-patient-name" class="form-control" required placeholder="เช่น ธนากร เจริญสุข">
          </div>

          <div class="form-group">
            <label class="form-label">เบอร์โทรศัพท์ <span style="color: red;">*</span></label>
            <input type="text" id="create-patient-phone" class="form-control" required placeholder="เช่น 081-234-5678 หรือ +66812345678">
            <div class="form-hint">💡 รองรับหลายรูปแบบ ระบบจะแปลงเป็นมาตรฐาน 0812345678 ให้อัตโนมัติ</div>
          </div>

          <div class="form-group">
            <label class="form-label">วันเกิด <span style="color: red;">*</span></label>
            <input type="date" id="create-patient-birth" class="form-control" required>
          </div>

          <div class="form-hint" style="background: #f1f5f9; padding: 10px; border-radius: 6px; margin-top: 10px;">
            📌 <strong>หมายเหตุ:</strong> ระบบจะออกรหัส <strong>HN</strong> (Hospital Number) ให้อัตโนมัติในรูปแบบ <code>HN000001</code>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="App.closeModal('modal-patient-create')">ยกเลิก</button>
          <button type="submit" class="btn btn-primary">บันทึกข้อมูลผู้ป่วย</button>
        </div>
      </form>
    </div>
  </div>

  <!-- ================================================================== -->
  <!-- MODAL: แก้ไขข้อมูลผู้ป่วย (Edit Patient)                           -->
  <!-- ================================================================== -->
  <div id="modal-patient-edit" class="modal-overlay">
    <div class="modal">
      <div class="modal-header">
        <h3 class="modal-title">แก้ไขข้อมูลผู้ป่วย</h3>
        <button class="modal-close" onclick="App.closeModal('modal-patient-edit')">&times;</button>
      </div>
      <form id="form-patient-edit">
        <input type="hidden" id="edit-patient-id">
        <div class="modal-body">
          <div class="form-group">
            <label class="form-label">รหัสประจำตัวผู้ป่วย (HN)</label>
            <input type="text" id="edit-patient-hn" class="form-control" readonly style="background-color: #f1f5f9; font-weight: 700; color: #0369a1;">
            <div class="form-hint">🔒 รหัส HN ถูกล็อกไว้ไม่สามารถแก้ไขได้ตามมาตรฐานเวชระเบียน</div>
          </div>

          <div class="form-group">
            <label class="form-label">ชื่อ-นามสกุล <span style="color: red;">*</span></label>
            <input type="text" id="edit-patient-name" class="form-control" required>
          </div>

          <div class="form-group">
            <label class="form-label">เบอร์โทรศัพท์ <span style="color: red;">*</span></label>
            <input type="text" id="edit-patient-phone" class="form-control" required>
            <div class="form-hint">💡 รองรับหลายรูปแบบ ระบบจะแปลงเป็นมาตรฐาน 0812345678 ให้อัตโนมัติ</div>
          </div>

          <div class="form-group">
            <label class="form-label">วันเกิด <span style="color: red;">*</span></label>
            <input type="date" id="edit-patient-birth" class="form-control" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="App.closeModal('modal-patient-edit')">ยกเลิก</button>
          <button type="submit" class="btn btn-primary">บันทึกการแก้ไข</button>
        </div>
      </form>
    </div>
  </div>

  <!-- ================================================================== -->
  <!-- MODAL: สร้างนัดหมายใหม่ (Create Appointment)                     -->
  <!-- ================================================================== -->
  <div id="modal-booking" class="modal-overlay">
    <div class="modal">
      <div class="modal-header">
        <h3 class="modal-title">สร้างนัดหมายแพทย์</h3>
        <button class="modal-close" onclick="App.closeModal('modal-booking')">&times;</button>
      </div>
      <form id="form-booking-create">
        <div class="modal-body">

          <!-- Overlap Warning Banner -->
          <div id="book-conflict-alert" class="conflict-alert">
            <!-- Populated dynamically via JS when overlap occurs -->
          </div>

          <div class="form-group">
            <label class="form-label">เลือกผู้ป่วย <span style="color: red;">*</span></label>
            <div style="display: flex; gap: 8px;">
              <select id="book-patient-id" class="form-control" required>
                <option value="">-- กรุณาค้นหาหรือเลือกผู้ป่วย --</option>
              </select>
              <button type="button" class="btn btn-secondary btn-sm" onclick="App.openModal('modal-patient-create')" title="เพิ่มผู้ป่วยใหม่">
                ➕ ใหม่
              </button>
            </div>
            <div class="form-hint">💡 พิมพ์ค้นหาผู้ป่วยในช่องด้านล่างเพื่อแสดงในรายการ</div>
            <input type="text" id="book-patient-search" class="form-control" style="margin-top: 6px;" placeholder="🔍 พิมพ์เพื่อค้นหาคนไข้ (ชื่อ, HN, เบอร์โทร)..." oninput="App.searchPatientForBooking(this.value)">
          </div>

          <div class="form-group">
            <label class="form-label">เลือกแพทย์ผู้ตรวจ <span style="color: red;">*</span></label>
            <select id="book-doctor-id" class="form-control" required>
              <option value="">-- กรุณาเลือกแพทย์ --</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">วันที่นัดหมาย <span style="color: red;">*</span></label>
            <input type="date" id="book-date" class="form-control" required value="<?= date('Y-m-d') ?>">
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <div class="form-group">
              <label class="form-label">เวลาเริ่มต้น <span style="color: red;">*</span></label>
              <input type="time" id="book-start-time" class="form-control" required step="900" value="10:00">
            </div>
            <div class="form-group">
              <label class="form-label">เวลาสิ้นสุด <span style="color: red;">*</span></label>
              <input type="time" id="book-end-time" class="form-control" required step="900" value="11:00">
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">อาการเบื้องต้น / หัตถการที่นัดหมาย</label>
            <textarea id="book-note" class="form-control" rows="2" placeholder="เช่น ปรึกษาผ่าตัด, เลเซอร์ผิวพรรณ, ติดตามอาการ..."></textarea>
          </div>

        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="App.closeModal('modal-booking')">ยกเลิก</button>
          <button type="submit" id="book-submit-btn" class="btn btn-gold">
            <span>📅</span> ยืนยันการนัดหมาย
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Toast Notification Container -->
  <div id="toast-container" class="toast-container"></div>

  <!-- Helper for searching patients inside booking modal -->
  <script>
    App.searchPatientForBooking = async function(keyword) {
      if (!keyword || keyword.trim().length < 1) return;
      try {
        const res = await fetch('/api/patients?search=' + encodeURIComponent(keyword.trim()));
        const json = await res.json();
        const select = document.getElementById('book-patient-id');
        if (json.success && json.data.length > 0) {
          select.innerHTML = json.data.map(p => `
            <option value="${p.id}">${p.full_name} (${p.hn}) - ${p.phone}</option>
          `).join('');
        }
      } catch (err) {
        console.error(err);
      }
    };
  </script>
  <script src="/js/app.js"></script>
</body>
</html>
