# Bestar-one Luxury Clinic - ระบบนัดหมายคลินิก

ระบบนัดหมายคลินิกขนาดเล็ก (Clinic Appointment System) ที่ออกแบบและพัฒนาขึ้นตามโจทย์ทดสอบโปรแกรมเมอร์ของ **Bestar-one** โดยเน้นความถูกต้องของ Business Logic, สถาปัตยกรรมโค้ดที่สะอาด เป็นระเบียบ จัดกลุ่มฟังก์ชันตามหน้าการทำงานในรูปแบบ OOP ภายใต้โฟลเดอร์ `/api`, หน้าเว็บตกแต่งด้วย **Bootstrap 5 (CDN)**, การป้องกันการนัดหมายเวลาซ้อนกันอย่างแม่นยำ และการออกแบบฐานข้อมูลที่รองรับปริมาณข้อมูลขนาดใหญ่ระดับ 100,000+ แถวได้อย่างมีประสิทธิภาพ

---

## สารบัญ
1. [ภาพรวมฟีเจอร์ของระบบ](#ภาพรวมฟีเจอร์ของระบบ)
2. [สถาปัตยกรรมระบบและโครงสร้างไฟล์](#สถาปัตยกรรมระบบและโครงสร้างไฟล์)
3. [วิธีติดตั้งและรันทีละขั้นตอน (Step-by-step Setup)](#วิธีติดตั้งและรันทีละขั้นตอน)
4. [การออกแบบฐานข้อมูลและการใส่ Index (Database & Indexing Rationale)](#การออกแบบฐานข้อมูลและการใส่-index)
5. [การทำงานของระบบเช็คนัดชน (Overlap Prevention Engine)](#การทำงานของระบบเช็คนัดชน)
6. [ข้อจำกัดที่รู้อยู่และส่วนที่สามารถพัฒนาต่อยอด (Known Limitations)](#ข้อจำกัดที่รู้อยู่และส่วนที่สามารถพัฒนาต่อยอด)
7. [การเปิดเผยการใช้งาน AI (AI Disclosure)](#การเปิดเผยการใช้งาน-ai)
8. [โครงร่างสคริปต์สำหรับบันทึกคลิปวิดีโอ 10–15 นาที](#โครงร่างสคริปต์สำหรับบันทึกคลิปวิดีโอ-1015-นาที)

---

## ภาพรวมฟีเจอร์ของระบบ

ระบบครอบคลุมทั้ง 4 ฟีเจอร์หลักตามเกณฑ์ขั้นต่ำของโจทย์อย่างสมบูรณ์:

1. **ระบบจัดการข้อมูลผู้ป่วย (Patient Management)**
   - เพิ่ม แก้ไข และค้นหาผู้ป่วยตามชื่อ-นามสกุล, รหัส HN หรือเบอร์โทรศัพท์
   - ระบบออกรหัส HN อัตโนมัติในรูปแบบ `HN000001` โดยเรียงลำดับต่อเนื่อง ไม่ซ้ำซ้อน
   - **Phone Normalization Engine**: แปลงเบอร์โทรศัพท์ทุกรูปแบบ เช่น `081-234-5678`, `+66812345678`, `66 81 234 5678`, `+66 81-234-5678` ให้ถูกจัดเก็บเป็นมาตรฐาน `0812345678` เสมอ
   - **Live AJAX Search & Pagination**: ค้นหาผู้ป่วยแบบ Real-time (Debounce) และสลับหน้าแบบแบ่งหน้าผ่าน AJAX โดยไม่ต้องรีเฟรชหน้าเว็บ

2. **ระบบนัดหมายแพทย์ (Appointment Scheduling)**
   - นัดหมายโดยเลือกผู้ป่วย, แพทย์, วันที่, เวลาเริ่มต้น และเวลาสิ้นสุด
   - **AJAX Patient Autocomplete**: ค้นหาผู้ป่วยเพื่อลงนัดหมายผ่าน AJAX ช่วยให้รองรับข้อมูลคนไข้ระดับ 100,000+ รายได้อย่างมีประสิทธิภาพ
   - **Real-time AJAX Overlap Check**: ระบบตรวจสอบนัดชนทันทีที่เปลี่ยนแพทย์/วันที่/เวลาผ่าน AJAX แจ้งเตือนสถานะทันทีบนหน้าจอก่อนกดบันทึก
   - นัดที่ถูกยกเลิก (`status = 'cancelled'`) จะไม่ถูกนับว่าชน

3. **หน้ารายการนัดของวันนี้ (Today's Schedule & Queue)**
   - แสดงคิวนัดหมายของวันนี้เรียงลำดับตามเวลา (`start_time ASC`) ดึงข้อมูลผ่าน AJAX
   - กรองแสดงผลเฉพาะแพทย์แต่ละท่าน หรือเปลี่ยนวันที่ได้ทันทีโดยไม่ต้องโหลดหน้าใหม่
   - ปรับเปลี่ยนสถานะได้ทันทีผ่าน AJAX: **รอ** (pending) / **มาแล้ว** (arrived) / **ยกเลิก** (cancelled)

4. **รายงานสรุปและสถิติ (High-Volume Analytics Dashboard)**
   - เลือกช่วงวันที่แล้วดึงรายงานสรุปและสถิติรายแพทย์ผ่าน AJAX
   - แสดงตัวเลขสรุป 5 มิติ: จำนวนผู้ป่วยใหม่, จำนวนนัดหมายทั้งหมด, และจำนวนนัดแยกตามสถานะ
   - ออกแบบ Query และ B-Tree Index รองรับตารางที่มีข้อมูลมากกว่า 100,000 แถว

---

## สถาปัตยกรรมระบบและโครงสร้างไฟล์

ระบบจัดกลุ่ม Business Logic เป็น **OOP Classes ภายใต้โฟลเดอร์ `/api` โดยแยกโฟลเดอร์ตามหน้าจอ** แต่ละโฟลเดอร์มี Class ฟังก์ชัน และ `ajax.php` (AJAX Endpoint ตอบกลับ JSON) ของหน้านั้นเอง ให้ Frontend ที่ตกแต่งด้วย **Bootstrap 5 (CDN)** เรียกใช้:

```
bstarone_test/
├── config/
│   └── database.php            # การเชื่อมต่อฐานข้อมูล MySQL ด้วย PDO
├── api/                        # แยกโฟลเดอร์ตามหน้าจอ (Class + AJAX Endpoint):
│   ├── index/                  # หน้ารายการนัดวันนี้
│   │   ├── IndexApi.php        # getDoctors, getTodayPaginated, updateStatus
│   │   └── ajax.php            # get_appointments, update_appointment_status
│   ├── booking/                # หน้านัดหมายแพทย์
│   │   ├── BookingApi.php      # getDoctors, checkConflict (เช็คนัดชน), create
│   │   └── ajax.php            # search_patients, check_conflict, save_appointment
│   ├── patients/               # หน้าจัดการผู้ป่วย
│   │   ├── PatientApi.php      # สร้าง HN, normalize เบอร์โทร, search, create, update
│   │   └── ajax.php            # get_patients, get_patient, save_patient
│   └── reports/                # หน้ารายงานสรุป
│       ├── ReportApi.php       # getSummary (รองรับข้อมูล 100,000+ แถว)
│       └── ajax.php            # get_report
├── page/                       # รวมไฟล์หน้าเว็บ Frontend (AJAX Fetch API + Bootstrap 5):
│   ├── index.php               # หน้ารายการนัดของวันนี้ (ดึงข้อมูลคิวนัดหมาย & เปลี่ยนสถานะด้วย AJAX)
│   ├── booking.php             # หน้านัดหมายแพทย์ (Live Patient Search & Real-time Overlap Check ด้วย AJAX)
│   ├── patients.php            # หน้าจัดการข้อมูลผู้ป่วย (Live Search, Pagination & บันทึกคนไข้ด้วย AJAX)
│   ├── reports.php             # หน้ารายงานสรุปและสถิติ (ดึงข้อมูล 5 Metrics & สถิติรายแพทย์ด้วย AJAX)
│   ├── header.php              # ส่วนหัวเว็บ เมนูนำทาง และโหลด Bootstrap 5 CDN
│   └── footer.php              # ส่วนท้ายเว็บ
├── database/
│   ├── schema.sql              # DDL ตาราง patients, doctors, appointments พร้อม Indexes
│   └── seed.sql                # ข้อมูลเริ่มต้น (แพทย์ 4 ท่าน, ผู้ป่วยตัวอย่าง, นัดหมายทดสอบ)
├── Dockerfile                  # PHP 8.2-FPM พร้อมโมดูล pdo_mysql
├── nginx.conf                  # Nginx configuration
├── docker-compose.yml          # Container orchestration (Web:8080, App, DB:3306)
├── index.php                   # Entrypoint Redirect ไปยัง /page/index.php
├── .env.example                # ตัวอย่างไฟล์ Environment Variables
└── README.md
```

---

## วิธีติดตั้งและรันทีละขั้นตอน

### วิธีที่ 1: รันผ่าน Docker Compose (แนะนำ สะดวกที่สุด)
ระบบถูกตั้งค่าให้อิมพอร์ต `schema.sql` และ `seed.sql` อัตโนมัติเมื่อสร้าง Container:

1. เปิด Terminal ในโฟลเดอร์โปรเจกต์
2. สั่งรันคำสั่ง:
   ```bash
   docker compose up -d
   ```
3. เปิดเว็บเบราว์เซอร์แล้วเข้าใช้งานได้ทันทีที่:
   ```
   http://localhost:8080
   ```

---

### วิธีที่ 2: รันผ่าน Local PHP + MySQL (เช่น XAMPP / Laragon / PHP CLI)
1. นำเข้าไฟล์ฐานข้อมูลเข้าสู่ MySQL:
   ```bash
   mysql -u root -p < database/schema.sql
   mysql -u root -p < database/seed.sql
   ```
2. สั่งรัน PHP Built-in Server:
   ```bash
   php -S localhost:8000
   ```
3. เปิดเบราว์เซอร์ไปที่ `http://localhost:8000`

---

## การออกแบบฐานข้อมูลและการใส่ Index

เพื่อรองรับประสิทธิภาพตามข้อกำหนด **"ควรคิดเผื่อกรณีที่ข้อมูลมีหลักแสนแถวด้วย"** จึงได้ออกแบบ Composite Indexes บนตาราง `appointments` และ `patients` ดังนี้:

```mermaid
erDiagram
    PATIENTS ||--o{ APPOINTMENTS : "has"
    DOCTORS ||--o{ APPOINTMENTS : "attends"

    PATIENTS {
        bigint id PK
        varchar hn UK "HN000001 (Unique Index)"
        varchar full_name "Index: idx_patients_full_name"
        varchar phone "Index: idx_patients_phone"
        date birth_date
        date registration_date "Index: idx_patients_registration_date"
    }

    DOCTORS {
        bigint id PK
        varchar name
        varchar specialty
        tinyint is_active "Index: idx_doctors_active"
    }

    APPOINTMENTS {
        bigint id PK
        bigint patient_id FK
        bigint doctor_id FK
        date appointment_date
        time start_time
        time end_time
        varchar status "pending | arrived | cancelled"
        text note
    }
```

### เหตุผลในการเลือกใส่ Index แต่ละตำแหน่ง:

| ตาราง | ชื่อ Index | คอลัมน์ที่ Index | เหตุผลและความจำเป็นเชิงประสิทธิภาพ |
|---|---|---|---|
| `appointments` | `idx_appointments_overlap_check` | `(doctor_id, appointment_date, status, start_time, end_time)` | **หัวใจของการเช็คนัดชน**: เมื่อมีนัดหมายหลักแสนแถว การ query ด้วยเงื่อนไขแพทย์ วันที่ และสถานะ จะใช้ B-Tree Composite Index ค้นหาได้ในเวลา $O(\log N)$ แทนที่จะทำ Full Table Scan |
| `appointments` | `idx_appointments_today_queue` | `(appointment_date, doctor_id, start_time)` | **หน้ารายการนัดของวันนี้**: ใช้สำหรับ `WHERE appointment_date = CURDATE()` และเรียงลำดับ `ORDER BY start_time ASC` ดึงข้อมูลขึ้นมาแสดงผลได้ทันทีโดยไม่ต้อง Sorting ในหน่วยความจำ |
| `appointments` | `idx_appointments_report_date_status` | `(appointment_date, status)` | **หน้ารายงานสรุป**: สำหรับ `WHERE appointment_date BETWEEN ? AND ?` และรวมกลุ่มตาม `status` ช่วยให้คำนวณแบบ Covering Index Scan ได้โดยไม่ต้องอ่าน Data Blocks |
| `patients` | `idx_patients_registration_date` | `(registration_date)` | **การนับผู้ป่วยใหม่ในรายงาน**: เร่งความเร็วการนับ `COUNT(*)` ผู้ป่วยใหม่ตามช่วงวันที่ลงทะเบียนได้อย่างรวดเร็ว |
| `patients` | `idx_patients_phone`, `idx_patients_full_name` | `(phone)`, `(full_name)` | **ระบบค้นหาผู้ป่วย**: ค้นหาผู้ป่วยด้วยเบอร์โทรที่ถูก normalize แล้ว หรือชื่อ-นามสกุล ได้อย่างฉับไว |

---

## การทำงานของระบบเช็คนัดชน

### สูตรคณิตศาสตร์ (Mathematical Interval Overlap Condition)
ช่วงเวลาสองช่วงจะทับซ้อนกัน ก็ต่อเมื่อ:
$$\text{Existing.start\_time} < \text{New.end\_time} \quad \text{AND} \quad \text{Existing.end\_time} > \text{New.start\_time}$$

### ตารางแจกแจงกรณีทดสอบ (Edge Cases Matrix)
สมมติแพทย์มีนัดเดิมเวลา **10:00 - 11:00 น.**

| กรณี | ช่วงเวลานัดใหม่ | เงื่อนไขคณิตศาสตร์ | ผลลัพธ์ | คำอธิบาย |
|---|---|---|---|---|
| 1. ชนบางส่วน (เหลื่อมท้าย) | 10:30 - 11:30 | $10:00 < 11:30$ (จริง) $\land$ $11:00 > 10:30$ (จริง) | **ชน (Overlap)** | ปฏิเสธการบันทึก |
| 2. ชนบางส่วน (เหลื่อมหน้า) | 09:30 - 10:30 | $10:00 < 10:30$ (จริง) $\land$ $11:00 > 09:30$ (จริง) | **ชน (Overlap)** | ปฏิเสธการบันทึก |
| 3. อยู่ภายในช่วงนัดเดิม | 10:15 - 10:45 | $10:00 < 10:45$ (จริง) $\land$ $11:00 > 10:15$ (จริง) | **ชน (Overlap)** | ปฏิเสธการบันทึก |
| 4. คร่อมช่วงนัดเดิมทั้งหมด | 09:00 - 12:00 | $10:00 < 12:00$ (จริง) $\land$ $11:00 > 09:00$ (จริง) | **ชน (Overlap)** | ปฏิเสธการบันทึก |
| 5. เวลาตรงกันเป๊ะ | 10:00 - 11:00 | $10:00 < 11:00$ (จริง) $\land$ $11:00 > 10:00$ (จริง) | **ชน (Overlap)** | ปฏิเสธการบันทึก |
| 6. นัดต่อเนื่องติดกัน (หลัง) | 11:00 - 12:00 | $10:00 < 12:00$ (จริง) $\land$ $11:00 > 11:00$ (เท็จ) | **ไม่ชน (Valid)** | นัดต่อกันได้ตามปกติ |
| 7. นัดต่อเนื่องติดกัน (ก่อน) | 09:00 - 10:00 | $10:00 < 10:00$ (เท็จ) $\land$ $11:00 > 09:00$ (จริง) | **ไม่ชน (Valid)** | นัดต่อกันได้ตามปกติ |
| 8. คนละแพทย์เวลาเดียวกัน | 10:00 - 11:00 (แพทย์ B) | เงื่อนไข `doctor_id = :doctor_id` กรองออก | **ไม่ชน (Valid)** | แพทย์คนละท่านตรวจพร้อมกันได้ |
| 9. นัดเดิมถูกยกเลิกแล้ว | 10:30 - 11:30 | เงื่อนไข `status != 'cancelled'` กรองออก | **ไม่ชน (Valid)** | นัดที่ยกเลิกไม่นับว่าชน |

---

## ข้อจำกัดที่รู้อยู่และส่วนที่สามารถพัฒนาต่อยอด

1. **ระบบความปลอดภัยและการพิสูจน์ตัวตน (Authentication & Authorization)**:
   - ปัจจุบันยังไม่ได้สร้างระบบ Login/Role (เนื่องจากอยู่นอกเหนือเกณฑ์ 4 ข้อแรกของโจทย์) หากพัฒนาต่อยอดควรมีระบบ Role-Based Access Control เช่น แยกสิทธิ์ระหว่าง แอดมิน, พยาบาลหน้าเคาน์เตอร์, และแพทย์
2. **ระบบการนัดหมายซ้ำ (Recurring Appointments)**:
   - ยังไม่รองรับการนัดแบบเกิดซ้ำ เช่น ทุกสัปดาห์หรือทุกเดือน
3. **ระบบแจ้งเตือนอัตโนมัติ (Automated Notification)**:
   - ยังไม่มีการเชื่อมต่อ SMS Gateway หรือ LINE Notify เพื่อส่งข้อความเตือนนัดหมายคนไข้ล่วงหน้า 1 วัน
4. **ความจุของห้องตรวจ (Room / Resource Allocation)**:
   - ปัจจุบันระบบเช็คเฉพาะตารางเวลาของแพทย์ หากในอนาคตคลินิกมีจำนวนห้องตรวจจำกัด ควรเพิ่มการตรวจสอบความพร้อมของห้องหัตถการและเครื่องมือแพทย์ร่วมด้วย

---

## การเปิดเผยการใช้งาน AI

ตามข้อกำหนดของแบบทดสอบ ("การใช้ AI และเครื่องมือช่วย: ใช้ได้ แต่ต้องระบุใน README ว่าใช้กับส่วนไหน"):

* **ส่วนที่ใช้ AI ช่วย**:
  1. ช่วยเลือกชุดคลาส Bootstrap 5 และจัดวาง Layout ให้มีความสวยงาม ทันสมัย (Luxury Medical Aesthetic)
  2. ช่วยคิดชุดข้อมูลตัวอย่าง (Seed Data) และ Test Cases เพื่อให้ครอบคลุม Edge Cases ของเบอร์โทรศัพท์และช่วงเวลา
  3. ช่วยจัดรูปแบบเอกสาร README ให้มีความเป็นมืออาชีพ สวยงาม และอ่านง่าย
* **ส่วนที่ทำและเข้าใจด้วยตนเอง**:
  - การออกแบบ Database Schema และ Relational Foreign Keys
  - การกำหนดสูตรคณิตศาสตร์และเงื่อนไข SQL สำหรับการตรวจสอบนัดชน (`start_time < new_end AND end_time > new_start`)
  - การเลือกตำแหน่ง B-Tree Composite Indexing เพื่อรองรับข้อมูลขนาด 100,000+ แถว
  - โค้ด OOP Classes ในโฟลเดอร์ `/api` แยกตามหน้าจอ (`IndexApi`, `BookingApi`, `PatientApi`, `ReportApi`)
  - ผู้สมัครสามารถอธิบายที่มา ตรรกะ และการทำงานของโค้ดทุกส่วนได้อย่างละเอียดในคลิปวิดีโอ

---

## โครงร่างสคริปต์สำหรับบันทึกคลิปวิดีโอ 10–15 นาที

แนวทางการพูดและนำเสนอคลิปวิดีโอแบ่งตามเวลาและหัวข้อที่บริษัทกำหนด:

### ส่วนที่ 1: Demo ระบบใช้งานได้จริงครบทุกฟีเจอร์ (3–4 นาที)
1. **แนะนำตัว**: กล่าวทักทายทีมงาน Bestar-one แนะนำชื่อและตำแหน่งที่สมัคร
2. **Demo ฟีเจอร์ 1 (จัดการผู้ป่วย - `patients.php`)**:
   - กรอกฟอร์มลงทะเบียนคนไข้ใหม่ โดยพิมพ์เบอร์โทรแบบ `+66 81-234-5678` ให้ดูก่อนบันทึก
   - แสดงให้เห็นว่าระบบบันทึกเป็น `0812345678` อัตโนมัติ และออกรหัส HN ต่อเนื่อง
   - พิมพ์ค้นหาในช่องค้นหาด้วยชื่อ, HN และเบอร์โทร
3. **Demo ฟีเจอร์ 2 & 3 (สร้างนัดหมาย `booking.php` & หน้ารายการนัดของวันนี้ `index.php`)**:
   - แสดงการสร้างนัดหมาย ลองเลือกเวลาที่ชนกับนัดเดิม ชี้ให้เห็นแถบเตือนสีแดงทันที
   - เปลี่ยนเวลาให้ไม่ชน แล้วกดยืนยันนัดหมาย
   - ไปที่หน้ารายการนัดของวันนี้ แสดงการกรองตามแพทย์ และกดเปลี่ยนสถานะเป็น "มาแล้ว"
4. **Demo ฟีเจอร์ 4 (รายงานสรุป - `reports.php`)**:
   - เปิดหน้ารายงานสรุป เลือกช่วงวันที่ แสดงตัวเลขสรุปผู้ป่วยใหม่และจำนวนนัดตามสถานะ

### ส่วนที่ 2: อธิบายการออกแบบฐานข้อมูลและ Index (3 นาที)
1. เปิดไฟล์ `database/schema.sql` ให้กรรมการดู
2. อธิบายตาราง `doctors`, `patients`, `appointments` และความสัมพันธ์ Foreign Keys
3. **เจาะลึก Indexing**:
   - ชี้ไปที่ `idx_appointments_overlap_check`: อธิบายว่าเมื่อข้อมูลมีหลักแสนแถว การเช็คนัดชนด้วยแพทย์ วันที่ สถานะ และเวลา จะวิ่งบน B-Tree ทำให้เร็วระดับ $O(\log N)$
   - ชี้ไปที่ `idx_appointments_report_date_status`: อธิบายว่าเป็น Covering Index สำหรับหน้ารายงาน

### ส่วนที่ 3: เปิดโค้ดส่วนเช็คนัดชนกันและอธิบายทีละส่วน (3 นาที)
1. เปิดไฟล์ `api/booking/BookingApi.php` ฟังก์ชัน `checkConflict`
2. อธิบายเงื่อนไข SQL:
   - `doctor_id = :doctor_id AND appointment_date = :date`
   - `status != 'cancelled'` (อธิบายว่าทำไมนัดที่ยกเลิกไม่นับว่าชน)
   - `start_time < :new_end_time AND end_time > :new_start_time` (อธิบายตรรกะว่าทำไมเงื่อนไขนี้ถึงครอบคลุมทุกกรณีทั้งเหลื่อมหน้า เหลื่อมหลัง และคร่อมทั้งช่วง)
   - อธิบายว่ากรณี 10:00-11:00 กับ 11:00-12:00 จะได้ค่าเท็จ ทำให้คนไข้สามารถนัดต่อกันได้พอดี

### ส่วนที่ 4: สิ่งที่อยากปรับปรุงหากมีเวลาเพิ่ม (2 นาที)
1. อธิบายเรื่อง Role-Based Permissions (Admin, Nurse, Doctor)
2. อธิบายเรื่อง SMS / LINE Alert แจ้งเตือนนัดหมายล่วงหน้า
3. อธิบายการจัดการทรัพยากรห้องตรวจ (Room/Equipment Allocation)
4. กล่าวขอบคุณทีมงานและปิดคลิป
