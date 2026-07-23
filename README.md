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
|
 บทบาท (Role)       
|
 Username   
|
 Password 
|
 สิทธิ์และหน้าที่การทำงาน 
|
