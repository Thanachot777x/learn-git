# IT Support Helpdesk

ระบบจัดการ IT Support Helpdesk สำหรับองค์กร รองรับการแจ้งซ่อม ติดตามสถานะ และบริหารจัดการ Ticket โดยช่างเทคนิค

---

## 📦 การติดตั้ง (Setup)

### 1. ดาวน์โหลดโปรเจกต์
```
โหลดและแตกไฟล์ ZIP แล้ววางโฟลเดอร์ไว้ที่:
C:\xampp\htdocs\it_support  (หรือชื่อโฟลเดอร์อะไรก็ได้)
```

### 2. สตาร์ท XAMPP
เปิด **XAMPP Control Panel** แล้วกด **Start** ทั้ง **Apache** และ **MySQL**

### 3. สร้างฐานข้อมูล
1. เปิดเบราว์เซอร์ไปที่ http://localhost/phpmyadmin
2. กด **"New"** (ซ้ายมือ) เพื่อสร้างฐานข้อมูลใหม่
3. ตั้งชื่อว่า `it_support` แล้วกด **Create**
4. คลิกที่ฐานข้อมูล `it_support` ทางซ้ายมือ
5. กดแท็บ **Import** ด้านบน
6. กด **Choose File** แล้วเลือกไฟล์ `database/schema.sql`
7. กด **Import** (หรือ **Go**) เพื่อนำเข้าข้อมูลทั้งหมด

### 4. เปิดใช้งาน
เปิดเบราว์เซอร์แล้วไปที่:
```
http://localhost/ชื่อโฟลเดอร์ของคุณ/
```
ตัวอย่างเช่น:
- `http://localhost/it_support/`
- `http://localhost/learn-git-main/`

> ✅ **ไม่ต้องแก้โค้ดใด ๆ เพิ่มเติม** ระบบจะตรวจจับชื่อโฟลเดอร์และ URL ให้อัตโนมัติ

---

## 👤 บัญชีทดสอบ (Test Accounts)

| บทบาท (Role)       | Username   | Password |
|--------------------|------------|----------|
| 👑 Admin           | `admin`    | `1234`   |
| 📊 Manager         | `Manager1` | `1234`   |
| 🔧 Technician      | `tech01`   | `1234`   |
| 👤 Employee        | `emp1`     | `1234`   |

---

## 🔧 การตั้งค่าฐานข้อมูล (config/db.php)

> **สำหรับผู้ใช้ Local (XAMPP):** ไม่ต้องแก้อะไรเลย ใช้งานได้ทันที

> **สำหรับผู้ที่ต้องการขึ้น Cloud Hosting:**
> แก้ไขข้อมูลในส่วน `else { ... }` ในไฟล์ `config/db.php`:

```php
} else {
    // แก้ค่าเหล่านี้ให้ตรงกับข้อมูล Hosting ของคุณ
    $host     = "sql211.infinityfree.com"; // MySQL Hostname
    $dbname   = "if0_XXXXXX_it_support";   // ชื่อฐานข้อมูล
    $username = "if0_XXXXXX";              // MySQL Username
    $password = "YOUR_PASSWORD";           // MySQL Password
```

---

## 📁 โครงสร้างโปรเจกต์

```
it_support/
├── admin/              ← หน้าสำหรับ Admin
├── auth/               ← หน้า Login / Logout
├── config/
│   └── db.php          ← ⚙️ ตั้งค่าฐานข้อมูล (Auto-detect URL)
├── database/
│   └── schema.sql      ← 📦 ไฟล์ SQL สำหรับ Import ฐานข้อมูล
├── employee/           ← หน้าสำหรับ Employee
├── includes/           ← Header / Footer / Auth Check
├── manager/            ← หน้าสำหรับ Manager
├── technician/         ← หน้าสำหรับ Technician
├── assets/             ← CSS, JS, ไฟล์ Static
└── uploads/            ← รูปภาพที่อัปโหลด
```

---

## ⚙️ ความต้องการของระบบ

- **PHP:** 8.0 ขึ้นไป
- **MySQL / MariaDB:** 10.4 ขึ้นไป
- **Web Server:** Apache (XAMPP / WAMP / Laragon)
