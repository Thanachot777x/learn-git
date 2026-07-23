<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
requireRole('employee');

$success = '';
$error   = '';

if (isset($_SESSION['flash_success'])) {
    $success = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();
    $description = trim($_POST['description'] ?? '');
    $category    = $_POST['category'] ?? 'other';
    $device_type = trim($_POST['device_type'] ?? '');
    $device_name = trim($_POST['device_name'] ?? '');
    $serial_no   = trim($_POST['serial_no'] ?? '');
    $priority    = $_POST['priority'] ?? 'medium';
    $building    = trim($_POST['building'] ?? '');
    $floor       = trim($_POST['floor'] ?? '');
    $room        = trim($_POST['room'] ?? '');

    $valid_categories = ['hardware', 'software', 'network', 'other'];
    if (!in_array($category, $valid_categories)) {
        $category = 'other';
    }

    if (empty($description) || empty($building)) {
        $error = 'กรุณากรอกรายละเอียดปัญหา และเลือกอาคาร/สถานที่';
    } else {
        // กำหนดค่าเริ่มต้นสำหรับช่องที่ไม่จำเป็น/ผู้ใช้ทั่วไปไม่ทราบ
        if (empty($device_type)) { $device_type = 'อุปกรณ์ทั่วไป'; }
        if (empty($device_name)) { $device_name = 'ไม่ระบุชื่ออุปกรณ์'; }
        if (empty($serial_no))   { $serial_no   = '-'; }
        if (empty($floor))       { $floor       = '-'; }
        if (empty($room))        { $room        = '-'; }

        // จัดการอัปโหลดรูปภาพ
        $image_path = null;
        if (!empty($_FILES['image']['name'])) {
            $file      = $_FILES['image'];
            $allowed   = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $max_size  = 5 * 1024 * 1024; // 5MB

            if (!in_array($file['type'], $allowed)) {
                $error = 'ไฟล์รูปภาพต้องเป็น JPG, PNG, GIF หรือ WEBP เท่านั้น';
            } elseif ($file['size'] > $max_size) {
                $error = 'ขนาดไฟล์รูปภาพต้องไม่เกิน 5MB';
            } elseif ($file['error'] !== UPLOAD_ERR_OK) {
                $error = 'เกิดข้อผิดพลาดในการอัปโหลดรูปภาพ';
            }

            if (empty($error)) {
                $ext        = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename   = 'ticket_' . time() . '_' . uniqid() . '.' . $ext;
                $upload_dir = __DIR__ . '/../uploads/tickets/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                if (move_uploaded_file($file['tmp_name'], $upload_dir . $filename)) {
                    $image_path = 'uploads/tickets/' . $filename;
                } else {
                    $error = 'ไม่สามารถบันทึกรูปภาพได้ กรุณาตรวจสอบโฟลเดอร์ uploads/tickets/';
                }
            }
        }

        if (empty($error)) {
            $title = ($device_type !== 'อุปกรณ์ทั่วไป' ? $device_type : 'แจ้งปัญหาซ่อม') . ($device_name !== 'ไม่ระบุชื่ออุปกรณ์' ? ': ' . $device_name : '');

            $pdo->prepare("
                INSERT INTO tickets
                (ticket_no, user_id, title, description, image_path, category, device_type, device_name, serial_no, priority, building, floor, room)
                VALUES ('', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ")->execute([
                $_SESSION['user_id'], $title, $description, $image_path,
                $category, $device_type, $device_name, $serial_no,
                $priority, $building, $floor, $room
            ]);

            $new_id    = $pdo->lastInsertId();
            $ticket_no = 'TK-' . str_pad($new_id, 6, '0', STR_PAD_LEFT);
            $pdo->prepare("UPDATE tickets SET ticket_no = ? WHERE id = ?")->execute([$ticket_no, $new_id]);

            // ส่งการแจ้งเตือนไปยัง Manager และ Admin
            addNotificationToRole($pdo, 'manager', "มี Ticket ใหม่เข้ามา ({$ticket_no})", "หัวข้อ: {$title}", BASE_URL . "/manager/assign_tickets.php", $new_id);
            addNotificationToRole($pdo, 'admin', "มี Ticket ใหม่เข้ามา ({$ticket_no})", "หัวข้อ: {$title}", BASE_URL . "/admin/manage_tickets.php", $new_id);

            $_SESSION['flash_success'] = "แจ้งปัญหาเรียบร้อยแล้ว! หมายเลข Ticket: {$ticket_no}";
            header("Location: submit_ticket.php");
            exit();
        }
    }
}

$device_types = $pdo->query("SELECT name FROM device_types ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
$buildings    = $pdo->query("SELECT name FROM buildings ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<style>
.page-title { font-size: 20px; font-weight: 600; margin: 0 0 2px; color: #0f172a; }
.page-sub { font-size: 13px; color: #64748b; margin: 0 0 1.5rem; }
.form-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 0; overflow: hidden; }
.form-section { padding: 24px 28px; border-bottom: 1px solid #f1f5f9; }
.form-section:last-of-type { border-bottom: none; }
.section-title { font-size: 14px; font-weight: 600; color: #0f172a; display: flex; align-items: center; gap: 8px; margin-bottom: 16px; }
.section-title i { font-size: 16px; color: #3b82f6; }
.section-hint { font-size: 11.5px; color: #94a3b8; margin-left: auto; font-weight: 400; }
.f-label { font-size: 13px; color: #374151; margin-bottom: 6px; display: block; font-weight: 500; }
.f-label .opt { font-size: 11px; color: #94a3b8; font-weight: 400; margin-left: 4px; }
.f-label .req { color: #ef4444; }
.f-input, .f-select, .f-textarea {
    width: 100%; padding: 10px 14px; border: 1px solid #d1d5db; border-radius: 8px;
    font-size: 13.5px; color: #111827; background: #fff; outline: none;
    box-sizing: border-box; font-family: inherit;
    transition: border-color 0.15s, box-shadow 0.15s;
}
.f-input:focus, .f-select:focus, .f-textarea:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
.f-input:disabled { background: #f8fafc; color: #64748b; }
.f-select { appearance: auto; }
.f-textarea { resize: vertical; min-height: 110px; }
.mb16 { margin-bottom: 16px; }
.form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.form-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; }
@media (max-width: 768px) { .form-grid-2, .form-grid-3 { grid-template-columns: 1fr; } }

.btn-save {
    background: linear-gradient(135deg, #2563eb, #1d4ed8); color: #fff; border: none;
    border-radius: 8px; padding: 11px 26px; font-size: 14px; cursor: pointer;
    display: inline-flex; align-items: center; gap: 8px; font-weight: 600; font-family: inherit;
    box-shadow: 0 2px 8px rgba(37,99,235,0.3); transition: all 0.15s ease;
}
.btn-save:hover { background: linear-gradient(135deg, #1d4ed8, #1e40af); transform: translateY(-1px); box-shadow: 0 4px 14px rgba(37,99,235,0.4); }
.btn-clear {
    background: #fff; color: #374151; border: 1px solid #d1d5db; border-radius: 8px;
    padding: 11px 20px; font-size: 14px; cursor: pointer; display: inline-flex;
    align-items: center; gap: 6px; font-family: inherit; transition: all 0.15s ease;
}
.btn-clear:hover { background: #f8fafc; border-color: #94a3b8; }
.alert-ok { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; border-radius: 10px; padding: 14px 18px; font-size: 13.5px; margin-bottom: 1rem; display: flex; align-items: center; gap: 10px; font-weight: 500; }
.alert-err { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; border-radius: 10px; padding: 14px 18px; font-size: 13.5px; margin-bottom: 1rem; display: flex; align-items: center; gap: 10px; font-weight: 500; }

/* Upload zone */
.upload-zone { border: 2px dashed #cbd5e1; border-radius: 10px; padding: 24px; text-align: center; cursor: pointer; transition: all 0.2s; background: #f8fafc; }
.upload-zone:hover { border-color: #3b82f6; background: #eff6ff; }
.upload-zone.dragover { border-color: #3b82f6; background: #eff6ff; }
.upload-zone input[type=file] { display: none; }
.upload-zone .uz-icon { font-size: 32px; color: #94a3b8; margin-bottom: 8px; }
.upload-zone .uz-text { font-size: 13px; color: #64748b; }
.upload-zone .uz-hint { font-size: 11px; color: #94a3b8; margin-top: 6px; }
#preview-wrap { margin-top: 12px; display: none; }
#preview-wrap img { max-height: 180px; border-radius: 10px; border: 1px solid #e2e8f0; object-fit: cover; }
#preview-wrap .remove-img { font-size: 12px; color: #ef4444; cursor: pointer; margin-top: 8px; display: inline-flex; align-items: center; gap: 4px; background: none; border: none; padding: 0; }
</style>

<p class="page-title"><i class="bi bi-plus-circle me-2" style="color:#3b82f6;"></i>แจ้งปัญหา / แจ้งซ่อม</p>
<p class="page-sub">กรอกรายละเอียดปัญหาที่พบ ช่อง <span style="color:#ef4444;">*</span> จำเป็นต้องกรอก นอกนั้นใส่ได้เท่าที่ทราบ</p>

<?php if ($success): ?>
<div class="alert-ok"><i class="bi bi-check-circle-fill"></i><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert-err"><i class="bi bi-exclamation-circle-fill"></i><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="form-card">
<form method="POST" enctype="multipart/form-data">
    <?= csrfInput() ?>

    <!-- ==================== ส่วนที่ 1: ปัญหาหลัก ==================== -->
    <div class="form-section">
        <div class="section-title">
            <i class="bi bi-chat-left-text"></i> บอกเราว่าเกิดปัญหาอะไร
        </div>

        <div class="mb16" style="max-width: 340px;">
            <label class="f-label">ผู้แจ้งปัญหา</label>
            <input type="text" class="f-input" value="<?= htmlspecialchars($_SESSION['fullname']) ?>" disabled>
        </div>

        <div class="mb16">
            <label class="f-label">อาการ / รายละเอียดปัญหา <span class="req">*</span></label>
            <textarea name="description" class="f-textarea" placeholder="เช่น คอมเปิดไม่ติด, เน็ตหลุดบ่อย, เครื่องปริ้นพิมพ์ไม่ออก ..." required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
        </div>

        <div class="form-grid-3">
            <div>
                <label class="f-label">หมวดหมู่ปัญหา</label>
                <select name="category" class="f-select">
                    <option value="other" <?= (($_POST['category'] ?? '') === 'other') ? 'selected' : '' ?>>ไม่แน่ใจ / อื่นๆ</option>
                    <option value="hardware" <?= (($_POST['category'] ?? '') === 'hardware') ? 'selected' : '' ?>>ฮาร์ดแวร์ (เครื่องพัง ชำรุด)</option>
                    <option value="software" <?= (($_POST['category'] ?? '') === 'software') ? 'selected' : '' ?>>ซอฟต์แวร์ (โปรแกรม ระบบ)</option>
                    <option value="network" <?= (($_POST['category'] ?? '') === 'network') ? 'selected' : '' ?>>เครือข่าย (เน็ต WiFi)</option>
                </select>
            </div>
            <div>
                <label class="f-label">ระดับความเร่งด่วน</label>
                <select name="priority" class="f-select">
                    <option value="low" <?= (($_POST['priority'] ?? '') === 'low') ? 'selected' : '' ?>>🟢 ไม่เร่ง — ใช้งานได้อยู่</option>
                    <option value="medium" <?= (($_POST['priority'] ?? 'medium') === 'medium') ? 'selected' : '' ?>>🟡 ปานกลาง — ใช้งานลำบาก</option>
                    <option value="high" <?= (($_POST['priority'] ?? '') === 'high') ? 'selected' : '' ?>>🟠 ด่วน — ใช้งานไม่ได้</option>
                    <option value="urgent" <?= (($_POST['priority'] ?? '') === 'urgent') ? 'selected' : '' ?>>🔴 ด่วนมาก — กระทบงานหลัก</option>
                </select>
            </div>
            <div>
                <label class="f-label">รูปภาพประกอบ <span class="opt">(ถ้ามี)</span></label>
                <div class="upload-zone" id="uploadZone" onclick="document.getElementById('imageInput').click()">
                    <input type="file" name="image" id="imageInput" accept="image/jpeg,image/png,image/gif,image/webp">
                    <div class="uz-icon"><i class="bi bi-camera"></i></div>
                    <div class="uz-text">คลิกเพื่อเลือกรูป หรือลากมาวาง</div>
                    <div class="uz-hint">JPG, PNG, GIF, WEBP ไม่เกิน 5MB</div>
                </div>
                <div id="preview-wrap">
                    <img id="preview-img" src="" alt="preview">
                    <br>
                    <button type="button" class="remove-img" onclick="removeImage()">
                        <i class="bi bi-x-circle"></i> ลบรูปภาพ
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== ส่วนที่ 2: ข้อมูลอุปกรณ์ (ไม่บังคับ) ==================== -->
    <div class="form-section">
        <div class="section-title">
            <i class="bi bi-pc-display"></i> ข้อมูลอุปกรณ์
            <span class="section-hint">ใส่ได้เท่าที่ทราบ ไม่บังคับ</span>
        </div>

        <div class="form-grid-3">
            <div>
                <label class="f-label">ประเภทอุปกรณ์ <span class="opt">(ไม่บังคับ)</span></label>
                <select name="device_type" class="f-select">
                    <option value="">ไม่ทราบ / ไม่ระบุ</option>
                    <?php foreach ($device_types as $dt): ?>
                    <option value="<?= htmlspecialchars($dt) ?>" <?= (($_POST['device_type'] ?? '') === $dt) ? 'selected' : '' ?>><?= htmlspecialchars($dt) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="f-label">ชื่อ/รุ่นอุปกรณ์ <span class="opt">(ไม่บังคับ)</span></label>
                <input type="text" name="device_name" class="f-input" placeholder="เช่น Acer Aspire, HP LaserJet" value="<?= htmlspecialchars($_POST['device_name'] ?? '') ?>">
            </div>
            <div>
                <label class="f-label">หมายเลขเครื่อง <span class="opt">(ไม่บังคับ)</span></label>
                <input type="text" name="serial_no" class="f-input" placeholder="ดูที่สติ๊กเกอร์ข้างเครื่อง (ถ้ามี)" value="<?= htmlspecialchars($_POST['serial_no'] ?? '') ?>">
            </div>
        </div>
    </div>

    <!-- ==================== ส่วนที่ 3: สถานที่ ==================== -->
    <div class="form-section">
        <div class="section-title">
            <i class="bi bi-geo-alt"></i> สถานที่
        </div>

        <div class="form-grid-3">
            <div>
                <label class="f-label">อาคาร / ตึก <span class="req">*</span></label>
                <select name="building" class="f-select" required>
                    <option value="">เลือกอาคาร / ตึก</option>
                    <?php foreach ($buildings as $b): ?>
                    <option value="<?= htmlspecialchars($b) ?>" <?= (($_POST['building'] ?? '') === $b) ? 'selected' : '' ?>><?= htmlspecialchars($b) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="f-label">ชั้น <span class="opt">(ไม่บังคับ)</span></label>
                <input type="text" name="floor" class="f-input" placeholder="เช่น 3" value="<?= htmlspecialchars($_POST['floor'] ?? '') ?>">
            </div>
            <div>
                <label class="f-label">ห้อง <span class="opt">(ไม่บังคับ)</span></label>
                <input type="text" name="room" class="f-input" placeholder="เช่น 302" value="<?= htmlspecialchars($_POST['room'] ?? '') ?>">
            </div>
        </div>
    </div>

    <!-- ปุ่ม -->
    <div class="form-section" style="background: #f8fafc; display: flex; gap: 12px; padding: 18px 28px;">
        <button type="submit" class="btn-save">
            <i class="bi bi-send"></i> ส่งแจ้งปัญหา
        </button>
        <button type="reset" class="btn-clear" onclick="removeImage()">
            <i class="bi bi-arrow-counterclockwise"></i> ล้างข้อมูล
        </button>
    </div>

</form>
</div>

<script>
const input   = document.getElementById('imageInput');
const zone    = document.getElementById('uploadZone');
const preview = document.getElementById('preview-img');
const wrap    = document.getElementById('preview-wrap');

input.addEventListener('change', () => showPreview(input.files[0]));

zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('dragover'); });
zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
zone.addEventListener('drop', e => {
    e.preventDefault();
    zone.classList.remove('dragover');
    const file = e.dataTransfer.files[0];
    if (file && file.type.startsWith('image/')) {
        const dt = new DataTransfer();
        dt.items.add(file);
        input.files = dt.files;
        showPreview(file);
    }
});

function showPreview(file) {
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        preview.src = e.target.result;
        wrap.style.display = 'block';
        zone.style.display = 'none';
    };
    reader.readAsDataURL(file);
}

function removeImage() {
    input.value = '';
    preview.src = '';
    wrap.style.display = 'none';
    zone.style.display = 'block';
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>