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

    if (empty($device_type) || empty($device_name) || empty($serial_no) || empty($description) || empty($building) || empty($floor) || empty($room)) {
        $error = 'กรุณากรอกข้อมูลให้ครบทุกช่อง';
    } else {
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
            $title = $device_type . ($device_name ? ': ' . $device_name : '');

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
.page-title { font-size: 18px; font-weight: 500; margin: 0 0 2px; }
.page-sub { font-size: 13px; color: #6b7280; margin: 0 0 1.5rem; }
.form-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 28px; }
.f-label { font-size: 13px; color: #374151; margin-bottom: 6px; display: block; }
.f-input { width: 100%; padding: 9px 12px; border: 1px solid #d1d5db; border-radius: 7px; font-size: 13px; color: #111827; background: #fff; outline: none; box-sizing: border-box; transition: border-color 0.15s; }
.f-input:focus { border-color: #3b82f6; }
.f-input:disabled { background: #f3f4f6; color: #6b7280; }
.f-select { width: 100%; padding: 9px 12px; border: 1px solid #d1d5db; border-radius: 7px; font-size: 13px; color: #111827; background: #fff; outline: none; box-sizing: border-box; appearance: auto; }
.f-select:focus { border-color: #3b82f6; }
.f-textarea { width: 100%; padding: 9px 12px; border: 1px solid #d1d5db; border-radius: 7px; font-size: 13px; color: #111827; background: #fff; outline: none; box-sizing: border-box; resize: vertical; min-height: 130px; }
.f-textarea:focus { border-color: #3b82f6; }
.mb16 { margin-bottom: 16px; }
.btn-save { background: #3b82f6; color: #fff; border: none; border-radius: 7px; padding: 10px 22px; font-size: 13px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
.btn-save:hover { background: #2563eb; }
.btn-clear { background: #fff; color: #374151; border: 1px solid #d1d5db; border-radius: 7px; padding: 10px 18px; font-size: 13px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
.alert-ok { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; border-radius: 8px; padding: 12px 16px; font-size: 13px; margin-bottom: 1rem; display: flex; align-items: center; gap: 8px; }
.alert-err { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; border-radius: 8px; padding: 12px 16px; font-size: 13px; margin-bottom: 1rem; display: flex; align-items: center; gap: 8px; }
.f-label .req { color: #ef4444; }

/* Upload zone */
.upload-zone { border: 2px dashed #d1d5db; border-radius: 8px; padding: 20px; text-align: center; cursor: pointer; transition: border-color 0.2s, background 0.2s; background: #f9fafb; }
.upload-zone:hover { border-color: #3b82f6; background: #eff6ff; }
.upload-zone.dragover { border-color: #3b82f6; background: #eff6ff; }
.upload-zone input[type=file] { display: none; }
.upload-zone .uz-icon { font-size: 28px; color: #9ca3af; margin-bottom: 6px; }
.upload-zone .uz-text { font-size: 13px; color: #6b7280; }
.upload-zone .uz-hint { font-size: 11px; color: #9ca3af; margin-top: 4px; }
#preview-wrap { margin-top: 10px; display: none; }
#preview-wrap img { max-height: 160px; border-radius: 8px; border: 1px solid #e5e7eb; object-fit: cover; }
#preview-wrap .remove-img { font-size: 12px; color: #ef4444; cursor: pointer; margin-top: 6px; display: inline-flex; align-items: center; gap: 4px; background: none; border: none; padding: 0; }
</style>

<p class="page-title">แจ้งซ่อม</p>
<p class="page-sub">กรอกรายละเอียดอุปกรณ์และปัญหาที่พบ</p>

<?php if ($success): ?>
<div class="alert-ok"><i class="bi bi-check-circle"></i><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert-err"><i class="bi bi-exclamation-circle"></i><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="form-card">
<form method="POST" enctype="multipart/form-data">
    <?= csrfInput() ?>

    <!-- ผู้แจ้งซ่อม -->
    <div class="mb16" style="max-width: 340px;">
        <label class="f-label">ผู้แจ้งซ่อม</label>
        <input type="text" class="f-input" value="<?= htmlspecialchars($_SESSION['fullname']) ?>" disabled>
    </div>

    <!-- ประเภท / ชื่ออุปกรณ์ / หมายเลขเครื่อง -->
    <div class="mb16" style="display: grid; grid-template-columns: 220px 1fr 200px; gap: 16px;">
        <div>
            <label class="f-label">ประเภทอุปกรณ์ <span class="req">*</span></label>
            <select name="device_type" class="f-select" required>
                <option value="">เลือกประเภทอุปกรณ์</option>
                <?php foreach ($device_types as $dt): ?>
                <option value="<?= htmlspecialchars($dt) ?>" <?= (($_POST['device_type'] ?? '') === $dt) ? 'selected' : '' ?>><?= htmlspecialchars($dt) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="f-label">ชื่ออุปกรณ์ <span class="req">*</span></label>
            <input type="text" name="device_name" class="f-input" placeholder="เช่น Macbook Pro 2020" required value="<?= htmlspecialchars($_POST['device_name'] ?? '') ?>">
        </div>
        <div>
            <label class="f-label">หมายเลขเครื่อง <span class="req">*</span></label>
            <input type="text" name="serial_no" class="f-input" placeholder="Serial / Asset No." required value="<?= htmlspecialchars($_POST['serial_no'] ?? '') ?>">
        </div>
    </div>

    <!-- อาการ / หมวดหมู่ / ระดับ / อาคาร -->
    <div class="mb16" style="display: grid; grid-template-columns: 1fr 160px 160px 180px; gap: 16px; align-items: start;">
        <div>
            <label class="f-label">อาการ / รายละเอียดปัญหา <span class="req">*</span></label>
            <textarea name="description" class="f-textarea" placeholder="อธิบายอาการปัญหาโดยละเอียด" required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
        </div>
        <div>
            <label class="f-label">หมวดหมู่ปัญหา</label>
            <select name="category" class="f-select">
                <option value="other" <?= (($_POST['category'] ?? '') === 'other') ? 'selected' : '' ?>>อื่นๆ</option>
                <option value="hardware" <?= (($_POST['category'] ?? '') === 'hardware') ? 'selected' : '' ?>>Hardware</option>
                <option value="software" <?= (($_POST['category'] ?? '') === 'software') ? 'selected' : '' ?>>Software</option>
                <option value="network" <?= (($_POST['category'] ?? '') === 'network') ? 'selected' : '' ?>>Network</option>
            </select>
        </div>
        <div>
            <label class="f-label">ระดับความสำคัญ</label>
            <select name="priority" class="f-select">
                <option value="low" <?= (($_POST['priority'] ?? '') === 'low') ? 'selected' : '' ?>>ปกติ</option>
                <option value="medium" <?= (($_POST['priority'] ?? 'medium') === 'medium') ? 'selected' : '' ?>>ปานกลาง</option>
                <option value="high" <?= (($_POST['priority'] ?? '') === 'high') ? 'selected' : '' ?>>สูง</option>
                <option value="urgent" <?= (($_POST['priority'] ?? '') === 'urgent') ? 'selected' : '' ?>>เร่งด่วน</option>
            </select>
        </div>
        <div>
            <label class="f-label">อาคาร / ตึก <span class="req">*</span></label>
            <select name="building" class="f-select" required>
                <option value="">เลือกอาคาร / ตึก</option>
                <?php foreach ($buildings as $b): ?>
                <option value="<?= htmlspecialchars($b) ?>" <?= (($_POST['building'] ?? '') === $b) ? 'selected' : '' ?>><?= htmlspecialchars($b) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- ชั้น / ห้อง -->
    <div class="mb16" style="display: grid; grid-template-columns: 140px 200px; gap: 16px;">
        <div>
            <label class="f-label">ชั้น <span class="req">*</span></label>
            <input type="text" name="floor" class="f-input" placeholder="เช่น 3" required value="<?= htmlspecialchars($_POST['floor'] ?? '') ?>">
        </div>
        <div>
            <label class="f-label">ห้อง <span class="req">*</span></label>
            <input type="text" name="room" class="f-input" placeholder="เช่น 302" required value="<?= htmlspecialchars($_POST['room'] ?? '') ?>">
        </div>
    </div>

    <!-- อัปโหลดรูปภาพ -->
    <div class="mb16">
        <label class="f-label">รูปภาพประกอบ <span style="color:#9ca3af; font-weight:400;">(  JPG, PNG, GIF, WEBP  )</span></label>
        <div class="upload-zone" id="uploadZone" onclick="document.getElementById('imageInput').click()">
            <input type="file" name="image" id="imageInput" accept="image/jpeg,image/png,image/gif,image/webp">
            <div class="uz-icon"><i class="bi bi-image"></i></div>
            <div class="uz-text">คลิกหรือลากรูปภาพมาวางที่นี่</div>
            <div class="uz-hint">เพื่อให้ช่าง IT เห็นอาการของปัญหาได้ชัดเจนขึ้น</div>
        </div>
        <div id="preview-wrap">
            <img id="preview-img" src="" alt="preview">
            <br>
            <button type="button" class="remove-img" onclick="removeImage()">
                <i class="bi bi-x-circle"></i> ลบรูปภาพ
            </button>
        </div>
    </div>

    <!-- ปุ่ม -->
    <div style="display: flex; gap: 10px; margin-top: 8px;">
        <button type="submit" class="btn-save">
            <i class="bi bi-floppy"></i> บันทึกข้อมูล
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