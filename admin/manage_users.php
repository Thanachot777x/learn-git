<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
requireRole('admin');

$success = '';
$error   = '';

// ตรวจสอบ CSRF token สำหรับการส่งข้อมูล POST ทั้งหมด
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();
}

// เพิ่มผู้ใช้
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $username         = trim($_POST['username'] ?? '');
    $password         = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    $fullname         = trim($_POST['fullname'] ?? '');
    $email            = trim($_POST['email'] ?? '');
    $phone            = trim($_POST['phone'] ?? '');
    $department       = trim($_POST['department'] ?? '');
    $role             = $_POST['role'] ?? '';
    $valid_roles = ['employee', 'technician', 'admin', 'manager'];

    if (empty($username) || empty($password) || empty($fullname) || !in_array($role, $valid_roles)) {
        $error = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    } elseif ($password !== $confirm_password) {
        $error = 'รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน';
    } else {
        $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $check->execute([$username]);
        $email_exists = false;
        if (!empty($email)) {
            $cmail = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            $cmail->execute([$email]);
            $email_exists = $cmail->fetchColumn() > 0;
        }

        if ($check->fetchColumn() > 0) {
            $error = "ชื่อผู้ใช้ '{$username}' มีอยู่แล้ว";
        } elseif ($email_exists) {
            $error = "อีเมล '{$email}' ถูกใช้งานโดยผู้ใช้คนอื่นแล้ว";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare("INSERT INTO users (username, password, fullname, email, phone, department, role) VALUES (?, ?, ?, ?, ?, ?, ?)")
                ->execute([$username, $hashed, $fullname, $email, $phone, $department, $role]);
            $success = "เพิ่มผู้ใช้ '{$username}' เรียบร้อยแล้ว";
        }
    }
}

// เปลี่ยน status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    $user_id    = (int) $_POST['user_id'];
    $new_status = ($_POST['current_status'] === 'active') ? 'inactive' : 'active';
    if ($user_id !== $_SESSION['user_id']) {
        $pdo->prepare("UPDATE users SET status = ? WHERE id = ?")->execute([$new_status, $user_id]);
        $success = 'อัปเดตสถานะผู้ใช้แล้ว';
    } else {
        $error = 'ไม่สามารถเปลี่ยนสถานะตัวเองได้';
    }
}

// รีเซ็ตรหัสผ่าน
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $user_id  = (int) $_POST['user_id'];
    $new_pass = trim($_POST['new_password'] ?? '');
    if (strlen($new_pass) < 4) {
        $error = 'รหัสผ่านต้องมีอย่างน้อย 4 ตัวอักษร';
    } else {
        $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hashed, $user_id]);
        $success = 'รีเซ็ตรหัสผ่านเรียบร้อยแล้ว';
    }
}

// แก้ไขข้อมูลผู้ใช้
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $user_id    = (int) $_POST['user_id'];
    $fullname   = trim($_POST['fullname'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $role       = $_POST['role'] ?? '';
    $valid_roles = ['employee', 'technician', 'admin', 'manager'];

    if (empty($fullname) || !in_array($role, $valid_roles)) {
        $error = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    } else {
        $email_taken = false;
        if (!empty($email)) {
            $c = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
            $c->execute([$email, $user_id]);
            $email_taken = $c->fetchColumn() > 0;
        }
        if ($email_taken) {
            $error = "อีเมล '{$email}' ถูกใช้งานโดยผู้ใช้คนอื่นแล้ว";
        } else {
            $pdo->prepare("UPDATE users SET fullname = ?, email = ?, phone = ?, department = ?, role = ? WHERE id = ?")
                ->execute([$fullname, $email, $phone, $department, $role, $user_id]);
            $success = 'แก้ไขข้อมูลผู้ใช้เรียบร้อยแล้ว';
        }
    }
}

// ดึงรายชื่อผู้ใช้
$filter_role = $_GET['role'] ?? '';
$search      = trim($_GET['search'] ?? '');
$where  = ['1=1'];
$params = [];
if ($filter_role) { $where[] = 'role = ?'; $params[] = $filter_role; }
if ($search) { $where[] = '(username LIKE ? OR fullname LIKE ? OR email LIKE ?)'; $params = array_merge($params, ["%$search%","%$search%","%$search%"]); }

$stmt = $pdo->prepare("SELECT * FROM users WHERE " . implode(' AND ', $where) . " ORDER BY role, fullname");
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงรายชื่อแผนก
$departments = $pdo->query("SELECT id, name FROM departments ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<style>
.page-title { font-size: 18px; font-weight: 500; margin: 0 0 2px; }
.page-sub { font-size: 13px; color: #6b7280; margin: 0 0 1.5rem; }
.section-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; margin-bottom: 1.5rem; overflow: hidden; }
.section-header { padding: 14px 20px; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; gap: 8px; }
.section-header span { font-size: 14px; font-weight: 500; }
.section-body { padding: 20px; }
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.form-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; }
.form-label { font-size: 12px; color: #6b7280; margin-bottom: 5px; display: block; text-transform: uppercase; letter-spacing: 0.4px; }
.form-input { width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 7px; font-size: 13px; color: #111827; outline: none; background: #f9fafb; box-sizing: border-box; }
.form-input:focus { border-color: #1a56db; background: #fff; }
.form-select { width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 7px; font-size: 13px; color: #111827; outline: none; background: #f9fafb; box-sizing: border-box; }
.form-select:focus { border-color: #1a56db; background: #fff; }
.btn-actions { display: flex; gap: 10px; margin-top: 6px; }
.btn-save { background: #1a56db; color: #fff; border: none; border-radius: 7px; padding: 9px 20px; font-size: 13px; cursor: pointer; display: flex; align-items: center; gap: 6px; }
.btn-save:hover { background: #1648c0; }
.btn-clear { background: #fff; color: #374151; border: 1px solid #d1d5db; border-radius: 7px; padding: 9px 20px; font-size: 13px; cursor: pointer; display: flex; align-items: center; gap: 6px; }
.filter-row { padding: 14px 20px; border-bottom: 1px solid #e5e7eb; display: flex; gap: 10px; align-items: center; }
.filter-input { padding: 7px 12px; border: 1px solid #d1d5db; border-radius: 7px; font-size: 13px; outline: none; background: #f9fafb; min-width: 220px; }
.filter-select { padding: 7px 12px; border: 1px solid #d1d5db; border-radius: 7px; font-size: 13px; outline: none; background: #f9fafb; }
.btn-filter { background: #1a56db; color: #fff; border: none; border-radius: 7px; padding: 7px 16px; font-size: 13px; cursor: pointer; }
.btn-reset-f { background: #fff; color: #374151; border: 1px solid #d1d5db; border-radius: 7px; padding: 7px 14px; font-size: 13px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; }
table { width: 100%; border-collapse: collapse; font-size: 13px; }
thead tr { background: #f9fafb; }
th { padding: 9px 16px; text-align: left; font-weight: 500; font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.4px; white-space: nowrap; border-bottom: 1px solid #e5e7eb; }
td { padding: 12px 16px; border-top: 1px solid #f3f4f6; vertical-align: middle; }
tr:hover td { background: #f9fafb; }
.user-cell { display: flex; align-items: center; gap: 10px; }
.avatar { width: 32px; height: 32px; border-radius: 50%; background: #dbeafe; color: #1e40af; font-size: 11px; font-weight: 500; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.avatar.tech { background: #dcfce7; color: #166534; }
.avatar.adm { background: #fef3c7; color: #92400e; }
.avatar.mgr { background: #ede9fe; color: #5b21b6; }
.u-name { font-weight: 500; font-size: 13px; color: #111827; }
.u-code { font-size: 11px; color: #9ca3af; font-family: monospace; }
.badge { font-size: 11px; padding: 3px 9px; border-radius: 20px; font-weight: 500; }
.b-admin { background: #fef3c7; color: #92400e; }
.b-tech { background: #dcfce7; color: #166534; }
.b-emp { background: #f3f4f6; color: #374151; }
.b-mgr { background: #ede9fe; color: #5b21b6; }
.b-active { background: #dcfce7; color: #166534; }
.b-inactive { background: #fee2e2; color: #991b1b; }
.btn-icon { width: 30px; height: 30px; border-radius: 6px; border: 1px solid #e5e7eb; background: #fff; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; color: #6b7280; font-size: 14px; }
.btn-icon:hover { background: #f3f4f6; }
.alert-ok { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; border-radius: 8px; padding: 10px 16px; font-size: 13px; margin-bottom: 1rem; display: flex; align-items: center; gap: 8px; }
.alert-err { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; border-radius: 8px; padding: 10px 16px; font-size: 13px; margin-bottom: 1rem; display: flex; align-items: center; gap: 8px; }
</style>

<p class="page-title"><i class="bi bi-people me-2"></i>จัดการผู้ใช้งาน</p>
<p class="page-sub">ระบบจัดการบัญชีผู้ใช้ทั้งหมด</p>

<?php if ($success): ?>
<div class="alert-ok"><i class="bi bi-check-circle"></i><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert-err"><i class="bi bi-exclamation-circle"></i><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- ฟอร์มเพิ่มผู้ใช้ -->
<div class="section-card">
    <div class="section-header">
        <i class="bi bi-person-plus" style="color:#1a56db;"></i>
        <span>เพิ่มผู้ใช้งานใหม่</span>
    </div>
    <div class="section-body">
        <form method="POST">
            <?= csrfInput() ?>
            <div class="form-grid" style="margin-bottom:14px;">
                <div>
                    <label class="form-label">Username <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="username" class="form-input" placeholder="กรอก username" required>
                </div>
                <div>
                    <label class="form-label">ชื่อ-นามสกุล <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="fullname" class="form-input" placeholder="กรอกชื่อ-นามสกุล" required>
                </div>
            </div>
            <div class="form-grid" style="margin-bottom:14px;">
                <div>
                    <label class="form-label">Password <span style="color:#ef4444;">*</span></label>
                    <div style="position:relative;">
                        <input type="password" name="password" id="addPwd" class="form-input" placeholder="กรอกรหัสผ่าน" required minlength="4" style="padding-right:40px;">
                        <button type="button" onclick="togglePwd('addPwd', this)" style="position:absolute; right:8px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:#64748b; padding:4px;">
                            <i class="bi bi-eye-slash"></i>
                        </button>
                    </div>
                </div>
                <div>
                    <label class="form-label">ยืนยันรหัสผ่าน <span style="color:#ef4444;">*</span></label>
                    <div style="position:relative;">
                        <input type="password" name="confirm_password" id="addConfirmPwd" class="form-input" placeholder="กรอกรหัสผ่านอีกครั้ง" required minlength="4" style="padding-right:40px;">
                        <button type="button" onclick="togglePwd('addConfirmPwd', this)" style="position:absolute; right:8px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:#64748b; padding:4px;">
                            <i class="bi bi-eye-slash"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="form-grid" style="margin-bottom:14px;">
                <div>
                    <label class="form-label">อีเมล</label>
                    <input type="email" name="email" class="form-input" placeholder="example@company.com">
                </div>
                <div>
                    <label class="form-label">เบอร์โทรศัพท์</label>
                    <input type="text" name="phone" class="form-input" placeholder="08xxxxxxxx">
                </div>
            </div>
            <div class="form-grid-3" style="margin-bottom:18px;">
                <div>
                    <label class="form-label">แผนก</label>
                    <select name="department" class="form-select">
                        <option value="">-- เลือกแผนก --</option>
                        <?php foreach ($departments as $d): ?>
                        <option value="<?= htmlspecialchars($d['name']) ?>"><?= htmlspecialchars($d['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">สิทธิ์การใช้งาน <span style="color:#ef4444;">*</span></label>
                    <select name="role" class="form-select" required>
                        <option value="">-- เลือกสิทธิ์ --</option>
                        <option value="employee">Employee</option>
                        <option value="technician">Technician</option>
                        <option value="manager">Manager</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div style="display:flex; align-items:flex-end;">
                    <div class="btn-actions" style="margin-top:0; width:100%;">
                        <button type="submit" name="add_user" class="btn-save">
                            <i class="bi bi-floppy"></i> บันทึกข้อมูล
                        </button>
                        <button type="reset" class="btn-clear">
                            <i class="bi bi-arrow-counterclockwise"></i> ล้าง
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ตารางผู้ใช้ -->
<div class="section-card">
    <div class="filter-row">
        <form method="GET" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
            <input type="text" name="search" class="filter-input"
                   placeholder="ค้นหาชื่อ / username / อีเมล"
                   value="<?= htmlspecialchars($search) ?>">
            <select name="role" class="filter-select">
                <option value="">ทุก Role</option>
                <option value="employee"   <?= $filter_role==='employee'   ? 'selected':'' ?>>พนักงาน</option>
                <option value="technician" <?= $filter_role==='technician' ? 'selected':'' ?>>ช่าง IT</option>
                <option value="manager"    <?= $filter_role==='manager'    ? 'selected':'' ?>>Manager</option>
                <option value="admin"      <?= $filter_role==='admin'      ? 'selected':'' ?>>Admin</option>
            </select>
            <button type="submit" class="btn-filter">กรอง</button>
            <a href="<?= BASE_URL ?>/admin/manage_users.php" class="btn-reset-f">ล้าง</a>
        </form>
        <span style="margin-left:auto; font-size:12px; color:#6b7280;">พบ <?= count($users) ?> คน</span>
    </div>

    <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>ผู้ใช้งาน</th>
                    <th>อีเมล</th>
                    <th>เบอร์โทร</th>
                    <th>แผนก</th>
                    <th>Role</th>
                    <th>สถานะ</th>
                    <th>วันที่สร้าง</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $role_map   = ['admin'=>'adm','technician'=>'tech','manager'=>'mgr','employee'=>''];
                $role_label = ['admin'=>'Admin','technician'=>'ช่าง IT','manager'=>'Manager','employee'=>'พนักงาน'];
                $role_badge = ['admin'=>'b-admin','technician'=>'b-tech','manager'=>'b-mgr','employee'=>'b-emp'];
                foreach ($users as $i => $u):
                    $initials = mb_substr($u['fullname'], 0, 2, 'UTF-8');
                    $role_key = $u['role'] ?? 'employee';
                    $r_map    = $role_map[$role_key]   ?? '';
                    $r_label  = $role_label[$role_key] ?? $role_key;
                    $r_badge  = $role_badge[$role_key] ?? 'b-emp';
                ?>
                <tr>
                    <td style="color:#9ca3af; font-size:12px;"><?= $i + 1 ?></td>
                    <td>
                        <div class="user-cell">
                            <div class="avatar <?= $r_map ?>"><?= $initials ?></div>
                            <div>
                                <div class="u-name"><?= htmlspecialchars($u['fullname']) ?></div>
                                <div class="u-code"><?= htmlspecialchars($u['username']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td style="color:#6b7280; font-size:12px;"><?= htmlspecialchars($u['email'] ?? '-') ?></td>
                    <td style="color:#6b7280; font-size:12px;"><?= htmlspecialchars($u['phone'] ?? '-') ?></td>
                    <td style="color:#6b7280; font-size:12px;"><?= htmlspecialchars($u['department'] ?? '-') ?></td>
                    <td><span class="badge <?= $r_badge ?>"><?= htmlspecialchars($r_label) ?></span></td>
                    <td>
                        <?php if ($u['status'] === 'active'): ?>
                            <span class="badge b-active">ใช้งานอยู่</span>
                        <?php else: ?>
                            <span class="badge b-inactive">ปิดใช้งาน</span>
                        <?php endif; ?>
                    </td>
                    <td style="color:#6b7280; font-size:12px;"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                    <td>
                        <div style="display:flex; gap:6px;">
                            <button class="btn-icon"
                                    data-bs-toggle="modal" data-bs-target="#modalEditUser"
                                    data-uid="<?= $u['id'] ?>"
                                    data-fullname="<?= htmlspecialchars($u['fullname']) ?>"
                                    data-email="<?= htmlspecialchars($u['email'] ?? '') ?>"
                                    data-phone="<?= htmlspecialchars($u['phone'] ?? '') ?>"
                                    data-department="<?= htmlspecialchars($u['department'] ?? '') ?>"
                                    data-role="<?= htmlspecialchars($u['role']) ?>"
                                    title="แก้ไขข้อมูล">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <form method="POST" style="display:inline;">
                                <?= csrfInput() ?>
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <input type="hidden" name="current_status" value="<?= $u['status'] ?>">
                                <button type="submit" name="toggle_status" class="btn-icon"
                                        <?= $u['id'] === $_SESSION['user_id'] ? 'disabled' : '' ?>
                                        onclick="return confirm('ยืนยันเปลี่ยนสถานะ?')"
                                        title="<?= $u['status']==='active' ? 'ระงับการใช้งาน' : 'เปิดใช้งาน' ?>">
                                    <i class="bi <?= $u['status']==='active' ? 'bi-pause-circle' : 'bi-play-circle' ?>"></i>
                                </button>
                            </form>
                            <button class="btn-icon"
                                    data-bs-toggle="modal" data-bs-target="#modalResetPwd"
                                    data-uid="<?= $u['id'] ?>"
                                    data-uname="<?= htmlspecialchars($u['username']) ?>"
                                    title="รีเซ็ตรหัสผ่าน">
                                <i class="bi bi-key"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($users)): ?>
                <tr><td colspan="9" style="text-align:center; color:#9ca3af; padding:2rem;">ไม่พบผู้ใช้</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal แก้ไขข้อมูลผู้ใช้ -->
<div class="modal fade" id="modalEditUser" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" style="font-size:15px; font-weight:500;">
                    <i class="bi bi-pencil me-2"></i>แก้ไขข้อมูลผู้ใช้
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <?= csrfInput() ?>
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="editUid">
                    <div class="form-grid" style="margin-bottom:14px;">
                        <div>
                            <label class="form-label">ชื่อ-นามสกุล</label>
                            <input type="text" name="fullname" id="editFullname" class="form-input" required>
                        </div>
                        <div>
                            <label class="form-label">อีเมล</label>
                            <input type="email" name="email" id="editEmail" class="form-input">
                        </div>
                    </div>
                    <div class="form-grid" style="margin-bottom:14px;">
                        <div>
                            <label class="form-label">เบอร์โทรศัพท์</label>
                            <input type="text" name="phone" id="editPhone" class="form-input" placeholder="08xxxxxxxx">
                        </div>
                        <div>
                            <label class="form-label">แผนก</label>
                            <select name="department" id="editDepartment" class="form-select">
                                <option value="">-- เลือกแผนก --</option>
                                <?php foreach ($departments as $d): ?>
                                <option value="<?= htmlspecialchars($d['name']) ?>"><?= htmlspecialchars($d['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-grid">
                        <div>
                            <label class="form-label">สิทธิ์การใช้งาน</label>
                            <select name="role" id="editRole" class="form-select" required>
                                <option value="employee">พนักงาน</option>
                                <option value="technician">ช่าง IT</option>
                                <option value="manager">Manager</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" name="edit_user" class="btn btn-sm btn-primary">บันทึก</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal รีเซ็ตรหัสผ่าน -->
<div class="modal fade" id="modalResetPwd" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" style="font-size:15px; font-weight:500;">
                    <i class="bi bi-key me-2"></i>รีเซ็ตรหัสผ่าน
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <?= csrfInput() ?>
                <div class="modal-body">
                    <p style="font-size:13px; color:#6b7280; margin-bottom:12px;">
                        ผู้ใช้: <strong id="resetUname"></strong>
                    </p>
                    <input type="hidden" name="user_id" id="resetUid">
                    <label class="form-label">รหัสผ่านใหม่</label>
                    <input type="text" name="new_password" class="form-input" required minlength="4" placeholder="อย่างน้อย 4 ตัวอักษร">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" name="reset_password" class="btn btn-sm btn-warning">รีเซ็ต</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('modalResetPwd').addEventListener('show.bs.modal', e => {
    const btn = e.relatedTarget;
    document.getElementById('resetUid').value = btn.getAttribute('data-uid');
    document.getElementById('resetUname').textContent = btn.getAttribute('data-uname');
});

document.getElementById('modalEditUser').addEventListener('show.bs.modal', e => {
    const btn = e.relatedTarget;
    document.getElementById('editUid').value = btn.getAttribute('data-uid');
    document.getElementById('editFullname').value = btn.getAttribute('data-fullname');
    document.getElementById('editEmail').value = btn.getAttribute('data-email');
    document.getElementById('editPhone').value = btn.getAttribute('data-phone');
    document.getElementById('editDepartment').value = btn.getAttribute('data-department');
    document.getElementById('editRole').value = btn.getAttribute('data-role');
});

function togglePwd(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye-slash';
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>