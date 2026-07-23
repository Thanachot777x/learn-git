<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
requireRole('admin');

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();
}

// เพิ่มอาคาร
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_item'])) {
    $name = trim($_POST['name'] ?? '');
    if (empty($name)) {
        $error = 'กรุณากรอกชื่ออาคาร / ตึก';
    } else {
        $check = $pdo->prepare("SELECT COUNT(*) FROM buildings WHERE name = ?");
        $check->execute([$name]);
        if ($check->fetchColumn() > 0) {
            $error = "อาคาร '{$name}' มีอยู่แล้ว";
        } else {
            $pdo->prepare("INSERT INTO buildings (name) VALUES (?)")->execute([$name]);
            $success = "เพิ่มอาคาร '{$name}' เรียบร้อยแล้ว";
        }
    }
}

// แก้ไข
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_item'])) {
    $id   = (int) $_POST['id'];
    $name = trim($_POST['name'] ?? '');
    if (empty($name)) {
        $error = 'กรุณากรอกชื่ออาคาร / ตึก';
    } else {
        $check = $pdo->prepare("SELECT COUNT(*) FROM buildings WHERE name = ? AND id != ?");
        $check->execute([$name, $id]);
        if ($check->fetchColumn() > 0) {
            $error = "อาคาร '{$name}' มีอยู่แล้ว";
        } else {
            $pdo->prepare("UPDATE buildings SET name = ? WHERE id = ?")->execute([$name, $id]);
            $success = 'แก้ไขข้อมูลเรียบร้อยแล้ว';
        }
    }
}

// ลบ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_item'])) {
    $id = (int) $_POST['id'];
    $pdo->prepare("DELETE FROM buildings WHERE id = ?")->execute([$id]);
    $success = 'ลบข้อมูลเรียบร้อยแล้ว';
}

// ดึงรายชื่อ
$items = $pdo->query("SELECT * FROM buildings ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<style>
.page-title { font-size: 18px; font-weight: 500; margin: 0 0 14px; }
.btn-add { background: #16a34a; color: #fff; border: none; border-radius: 7px; padding: 9px 18px; font-size: 13px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; margin-bottom: 16px; }
.btn-add:hover { background: #15803d; }
.section-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; overflow: hidden; }
table { width: 100%; border-collapse: collapse; font-size: 13px; }
thead tr { background: #eef3fb; }
th { padding: 12px 16px; text-align: center; font-weight: 600; font-size: 13px; color: #374151; border-bottom: 1px solid #e5e7eb; }
td { padding: 12px 16px; border-top: 1px solid #f3f4f6; vertical-align: middle; text-align: center; }
tr:hover td { background: #f9fafb; }
.btn-edit { background: #16a34a; color: #fff; border: none; border-radius: 6px; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; }
.btn-edit:hover { background: #15803d; }
.btn-del { background: #f59e0b; color: #fff; border: none; border-radius: 6px; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; }
.btn-del:hover { background: #d97706; }
.alert-ok { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; border-radius: 8px; padding: 10px 16px; font-size: 13px; margin-bottom: 1rem; display: flex; align-items: center; gap: 8px; }
.alert-err { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; border-radius: 8px; padding: 10px 16px; font-size: 13px; margin-bottom: 1rem; display: flex; align-items: center; gap: 8px; }
</style>

<p class="page-title">จัดการข้อมูลอาคาร / ตึก</p>

<?php if ($success): ?>
<div class="alert-ok"><i class="bi bi-check-circle"></i><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert-err"><i class="bi bi-exclamation-circle"></i><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<button class="btn-add" data-bs-toggle="modal" data-bs-target="#modalAdd">
    <i class="bi bi-plus-circle"></i> เพิ่มอาคาร / ตึก
</button>

<div class="section-card">
    <table>
        <thead>
            <tr>
                <th style="width:100px;">ลำดับ</th>
                <th style="text-align:left;">อาคาร / ตึก</th>
                <th style="width:100px;">แก้ไข</th>
                <th style="width:100px;">ลบ</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $i => $d): ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td style="text-align:left;"><?= htmlspecialchars($d['name']) ?></td>
                <td>
                    <button class="btn-edit"
                            data-bs-toggle="modal" data-bs-target="#modalEdit"
                            data-id="<?= $d['id'] ?>"
                            data-name="<?= htmlspecialchars($d['name']) ?>"
                            title="แก้ไข">
                        <i class="bi bi-pencil-fill"></i>
                    </button>
                </td>
                <td>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('ยืนยันลบ \'<?= htmlspecialchars($d['name'], ENT_QUOTES) ?>\'?')">
                        <?= csrfInput() ?>
                        <input type="hidden" name="id" value="<?= $d['id'] ?>">
                        <button type="submit" name="delete_item" class="btn-del" title="ลบ">
                            <i class="bi bi-trash-fill"></i>
                        </button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($items)): ?>
            <tr><td colspan="4" style="color:#9ca3af; padding:2rem;">ไม่มีข้อมูล</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal เพิ่ม -->
<div class="modal fade" id="modalAdd" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" style="font-size:15px; font-weight:500;">
                    <i class="bi bi-plus-circle me-2"></i>เพิ่มอาคาร / ตึก
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <?= csrfInput() ?>
                <div class="modal-body">
                    <label class="form-label" style="font-size:13px;">ชื่ออาคาร / ตึก</label>
                    <input type="text" name="name" class="form-control" required placeholder="เช่น Building 1, อาคารหลัก">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" name="add_item" class="btn btn-sm btn-success">บันทึก</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal แก้ไข -->
<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" style="font-size:15px; font-weight:500;">
                    <i class="bi bi-pencil-fill me-2"></i>แก้ไขอาคาร / ตึก
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <?= csrfInput() ?>
                <div class="modal-body">
                    <input type="hidden" name="id" id="editId">
                    <label class="form-label" style="font-size:13px;">ชื่ออาคาร / ตึก</label>
                    <input type="text" name="name" id="editName" class="form-control" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" name="edit_item" class="btn btn-sm btn-success">บันทึก</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('modalEdit').addEventListener('show.bs.modal', e => {
    const btn = e.relatedTarget;
    document.getElementById('editId').value   = btn.getAttribute('data-id');
    document.getElementById('editName').value = btn.getAttribute('data-name');
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>