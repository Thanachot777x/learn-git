# 🛠️ IT Support Helpdesk System

ระบบจัดการ IT Support Helpdesk สำหรับองค์กรยุคใหม่ รองรับการแจ้งซ่อม ติดตามสถานะ ไทม์ไลน์ประวัติการซ่อม ออกรายงานสถิติ และบริหารจัดการ Ticket ครบวงจร

---

## ✨ คุณสมบัติเด่นของระบบ (Key Features)

- ⚡ **Auto-Setup Engine**: ไม่ต้องนำเข้า SQL หรือตั้งค่าฐานข้อมูลเอง ระบบจะสร้างฐานข้อมูล `it_support` และนำเข้าโครงสร้างตารางให้อัตโนมัติทันทีที่เปิดใช้งานบน XAMPP
- 🛡️ **CSRF Protection**: ระบบป้องกันการโจมตีแบบ CSRF ในทุกฟอร์มคำขอ
- 🔔 **In-App Notification**: ระบบแจ้งเตือนในระบบแบบเรียลไทม์ผ่านไอคอนกระดิ่งพร้อม Badge แสดงจำนวนรายการที่ยังไม่อ่าน
- 📊 **Interactive Dashboard**: กราฟสถิติมุมมองเชิงลึกด้วย **Chart.js** (วิเคราะห์สัดส่วนประเภทปัญหา Hardware, Software, Network)
- 📄 **Ticket Timeline & Detail View**: หน้าติดตามไทม์ไลน์การทำงานของช่าง IT พร้อมรูปภาพแนบก่อน-หลังซ่อม
- 📱 **Modern SaaS UI & Responsive**: ดีไซน์พรีเมียมด้วย Google Fonts (Prompt & Inter) รองรับหน้าจอทุกขนาด

---

## 📦 การติดตั้ง (Quick Setup)

### 1. ย้ายโฟลเดอร์โปรเจกต์
ดาวน์โหลดและแตกไฟล์ ZIP นำโฟลเดอร์โปรเจกต์ไปวางที่:
```text
C:\xampp\htdocs\it_support  (หรือตั้งชื่อโฟลเดอร์อะไรก็ได้)
```

### 2. สตาร์ท XAMPP
เปิด **XAMPP Control Panel** แล้วกด **Start** ทั้ง **Apache** และ **MySQL**

### 3. เปิดใช้งานได้ทันที (Auto-Setup)
เปิดเบราว์เซอร์แล้วไปที่ URL ของคุณ:
```text
http://localhost/ชื่อโฟลเดอร์ของคุณ/
```
ตัวอย่างเช่น:
- `http://localhost/it_support/`
- `http://localhost/learn-git-main/`

> ⚡ **ระบบจะ Auto-Detect สร้างฐานข้อมูล `it_support` และ Import ตารางให้อัตโนมัติ** โดยที่คุณไม่ต้องเปิด phpMyAdmin หรือตั้งค่าไฟล์ใดๆ เพิ่มเติม!

*(หมายเหตุ: หากต้องการนำเข้าไฟล์ SQL ด้วยตัวเอง สามารถนำเข้าไฟล์ `database/schema.sql` ผ่าน phpMyAdmin ได้เช่นกัน)*

---

## 👤 บัญชีทดสอบ (Test Accounts)

| บทบาท (Role)       | Username   | Password | สิทธิ์และหน้าที่การทำงาน |
|--------------------|------------|----------|-----------------------------------------|
| 👑 Admin           | `admin`    | `1234`   | บริหารจัดการผู้ใช้, ตารางข้อมูลหลัก, และ Ticket ทั้งหมด |
| 📊 Manager         | `Manager1` | `1234`   | ดูแดชบอร์ดภาพรวม, กราฟสถิติ, และมอบหมายงานซ่อมให้ช่าง |
| 🔧 Technician      | `tech01`   | `1234`   | กดรับงานซ่อม, อัปเดตสถานะงาน, และแนบรูปภาพผลการซ่อม |
| 👤 Employee        | `emp1`     | `1234`   | แจ้งซ่อมปัญหา (ฟอร์มง่าย), ติดตามสถานะงานซ่อม และดูไทม์ไลน์ |

---

## 🔧 การตั้งค่าฐานข้อมูล (config/db.php)

> **สำหรับผู้ใช้ Localhost (XAMPP):** ใช้งานได้ทันที ระบบ Auto-Detect คำนวณ URL และตั้งค่าให้อัตโนมัติ

> **สำหรับผู้ที่ต้องการนำขึ้น Cloud / Production Hosting:**
> แก้ไขข้อมูลการเชื่อมต่อฐานข้อมูลในส่วน `else { ... }` ภายในไฟล์ `config/db.php`:

```php
} else {
    // แก้ไขค่าเหล่านี้ให้ตรงกับข้อมูล Hosting ของคุณ
    $host     = "sql211.infinityfree.com"; // MySQL Hostname
    $dbname   = "if0_XXXXXX_it_support";   // ชื่อฐานข้อมูล
    $username = "if0_XXXXXX";              // MySQL Username
    $password = "YOUR_PASSWORD";           // MySQL Password
}
```

---

## 📁 โครงสร้างโปรเจกต์ (Project Structure)

```text
it_support/
├── admin/              ← ระบบจัดการสำหรับ Admin (จัดการผู้ใช้, Ticket, ข้อมูลหลัก)
├── auth/               ← ระบบยืนยันตัวตน (Login / Logout)
├── config/
│   └── db.php          ← ⚙️ ตั้งค่า DB (Auto-detect URL & Auto DB Creator)
├── database/
│   └── schema.sql      ← 📦 ไฟล์ SQL สำหรับ Import โครงสร้างและข้อมูลเริ่มต้น
├── employee/           ← ระบบสำหรับพนักงาน (แจ้งซ่อม, ติดตามสถานะ, ดูรายละเอียด Ticket)
├── includes/           ← 🧩 ส่วนประกอบกลาง (Header, Footer, CSRF, Notifications)
│   ├── csrf.php
│   ├── notifications.php
│   ├── mark_read.php
│   ├── header.php
│   └── footer.php
├── manager/            ← ระบบสำหรับผู้จัดการ (มอบหมายงาน, กราฟสถิติ Chart.js)
├── technician/         ← ระบบสำหรับช่าง IT (รับงานซ่อม, บันทึกอัปเดตสถานะ)
├── assets/             ← ไฟล์ CSS, JS และสไตล์กลางของระบบ
└── uploads/            ← โฟลเดอร์เก็บรูปภาพแนบการแจ้งซ่อมและอัปเดตงาน
```

---

## ⚙️ ความต้องการของระบบ (System Requirements)

- **PHP:** 8.0 ขึ้นไป
- **MySQL / MariaDB:** 10.4 ขึ้นไป
- **Web Server:** Apache (XAMPP / WAMP / Laragon)
