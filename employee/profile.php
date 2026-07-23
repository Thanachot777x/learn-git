<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
requireLogin();

$success = '';
$error   = '';

// ดึงข้อมูลปัจจุบัน
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();
}

// แก้ข้อมูลส่วนตัว
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $fullname   = trim($_POST['fullname'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $department = trim($_POST['department'] ?? '');

    if (empty($fullname)) {
        $error = 'กรุณากรอกชื่อ-นามสกุล';
    } else {
        $pdo->prepare("UPDATE users SET fullname = ?, email = ?, department = ? WHERE id = ?")
            ->execute([$fullname, $email, $department, $_SESSION['user_id']]);
        $_SESSION['fullname'] = $fullname;
        $user['fullname']   = $fullname;
        $user['email']      = $email;
        $user['department'] = $department;
        $success = 'อัปเดตข้อมูลเรียบร้อยแล้ว';
    }
}

// เปลี่ยนรหัสผ่าน
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm      = $_POST['confirm_password'] ?? '';

    if (empty($old_password) || empty($new_password) || empty($confirm)) {
        $error = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    } elseif (!password_verify($old_password, $user['password'])) {
        $error = 'รหัสผ่านเดิมไม่ถูกต้อง';
    } elseif ($new_password !== $confirm) {
        $error = 'รหัสผ่านใหม่ไม่ตรงกัน';
    } elseif (strlen($new_password) < 4) {
        $error = 'รหัสผ่านใหม่ต้องมีอย่างน้อย 4 ตัวอักษร';
    } else {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")
            ->execute([$hashed, $_SESSION['user_id']]);
        $success = 'เปลี่ยนรหัสผ่านเรียบร้อยแล้ว';
    }
}

// ดึงรายชื่อแผนกจาก DB สำหรับ dropdown (กันชื่อแผนกพิมพ์เพี้ยนไม่ตรงกับตาราง departments)
$departments = $pdo->query("SELECT name FROM departments ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);

$role_text = ['employee'=>'พนักงาน','technician'=>'ช่าง IT','admin'=>'Admin'];
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="row mb-4">
    <div class="col-12">
        <h4 class="fw-bold"><i class="bi bi-person-circle me-2"></i>โปรไฟล์ของฉัน</h4>
        <p class="text-muted mb-0">จัดการข้อมูลส่วนตัวและรหัสผ่าน</p>
    </div>
</div>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-1"></i><?= htmlspecialchars($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-circle me-1"></i><?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-4">
    <!-- ข้อมูลส่วนตัว -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-person me-2"></i>ข้อมูลส่วนตัว</h6>
            </div>
            <div class="card-body">
                <!-- แสดง role + username -->
                <div class="d-flex align-items-center gap-3 mb-4">
                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center"
                         style="width:56px;height:56px;font-size:1.5rem;">
                        <i class="bi bi-person-fill"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-5"><?= htmlspecialchars($user['fullname']) ?></div>
                        <div class="text-muted small">
                            <code><?= htmlspecialchars($user['username']) ?></code>
                            <span class="badge bg-secondary ms-1"><?= $role_text[$user['role']] ?></span>
                        </div>
                    </div>
                </div>

                <form method="POST">
                    <?= csrfInput() ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">ชื่อ-นามสกุล <span class="text-danger">*</span></label>
                        <input type="text" name="fullname" class="form-control"
                               value="<?= htmlspecialchars($user['fullname']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">อีเมล</label>
                        <input type="email" name="email" class="form-control"
                               value="<?= htmlspecialchars($user['email'] ?? '') ?>">
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">แผนก</label>
                        <select name="department" class="form-control">
                            <option value="">เลือกแผนก</option>
                            <?php foreach ($departments as $d): ?>
                            <option value="<?= htmlspecialchars($d) ?>" <?= (($user['department'] ?? '') === $d) ? 'selected' : '' ?>><?= htmlspecialchars($d) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" name="update_profile" class="btn btn-primary w-100">
                        <i class="bi bi-save me-1"></i>บันทึกข้อมูล
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- เปลี่ยนรหัสผ่าน -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-key me-2"></i>เปลี่ยนรหัสผ่าน</h6>
            </div>
            <div class="card-body">
                <form method="POST">
                    <?= csrfInput() ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">รหัสผ่านเดิม <span class="text-danger">*</span></label>
                        <input type="password" name="old_password" class="form-control"
                               placeholder="กรอกรหัสผ่านเดิม" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">รหัสผ่านใหม่ <span class="text-danger">*</span></label>
                        <input type="password" name="new_password" class="form-control"
                               placeholder="กรอกรหัสผ่านใหม่ (อย่างน้อย 4 ตัวอักษร)" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">ยืนยันรหัสผ่านใหม่ <span class="text-danger">*</span></label>
                        <input type="password" name="confirm_password" class="form-control"
                               placeholder="กรอกรหัสผ่านใหม่อีกครั้ง" required>
                    </div>
                    <button type="submit" name="change_password" class="btn btn-primary w-100">
                        <i class="bi bi-key me-1"></i>เปลี่ยนรหัสผ่าน
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>