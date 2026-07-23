<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
requireRole('admin');

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();
}

// เพิ่มประเภทอุปกรณ์
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_item'])) {
    $name = trim($_POST['name'] ?? '');
    if (empty($name)) {
        $error = 'กรุณากรอกชื่อประเภทอุปกรณ์';
    } else {
        $check = $pdo->prepare("SELECT COUNT(*) FROM device_types WHERE name = ?");
        $check->execute([$name]);
        if ($check->fetchColumn() > 0) {
            $error = "ประเภทอุปกรณ์ '{$name}' มีอยู่แล้ว";
        } else {
            $maxOrder = (int) $pdo->query("SELECT COALESCE(MAX(sort_order), 0) FROM device_types")->fetchColumn();
            $pdo->prepare("INSERT INTO device_types (name, sort_order) VALUES (?, ?)")->execute([$name, $maxOrder + 1]);
            $success = "เพิ่มประเภทอุปกรณ์ '{$name}' เรียบร้อยแล้ว";
        }
    }
}

// แก้ไข
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_item'])) {
    $id   = (int) $_POST['id'];
    $name = trim($_POST['name'] ?? '');
    if (empty($name)) {
        $error = 'กรุณากรอกชื่อประเภทอุปกรณ์';
    } else {
        $check = $pdo->prepare("SELECT COUNT(*) FROM device_types WHERE name = ? AND id != ?");
        $check->execute([$name, $id]);
        if ($check->fetchColumn() > 0) {
            $error = "ประเภทอุปกรณ์ '{$name}' มีอยู่แล้ว";
        } else {
            $pdo->prepare("UPDATE device_types SET name = ? WHERE id = ?")->execute([$name, $id]);
            $success = 'แก้ไขข้อมูลเรียบร้อยแล้ว';
        }
    }
}

// ลบ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_item'])) {
    $id = (int) $_POST['id'];
    $pdo->prepare("DELETE FROM device_types WHERE id = ?")->execute([$id]);
    $success = 'ลบข้อมูลเรียบร้อยแล้ว';
}

// ขยับลำดับ ขึ้น/ลง
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['move_item'])) {
    $id  = (int) $_POST['id'];
    $dir = $_POST['dir'] ?? '';

    $stmt = $pdo->prepare("SELECT sort_order FROM device_types WHERE id = ?");
    $stmt->execute([$id]);
    $current = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($current) {
        $currentOrder = $current['sort_order'];

        if ($dir === 'up') {
            $stmt = $pdo->prepare("SELECT id, sort_order FROM device_types WHERE sort_order < ? ORDER BY sort_order DESC LIMIT 1");
        } else {
            $stmt = $pdo->prepare("SELECT id, sort_order FROM device_types WHERE sort_order > ? ORDER BY sort_order ASC LIMIT 1");
        }
        $stmt->execute([$currentOrder]);
        $target = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($target) {
            $pdo->prepare("UPDATE device_types SET sort_order = ? WHERE id = ?")->execute([$target['sort_order'], $id]);
            $pdo->prepare("UPDATE device_types SET sort_order = ? WHERE id = ?")->execute([$currentOrder, $target['id']]);
        }
    }
}

// ดึงรายชื่อ
$items = $pdo->query("SELECT * FROM device_types ORDER BY sort_order")->fetchAll(PDO::FETCH_ASSOC);
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
.move-group { display: inline-flex; flex-direction: column; gap: 4px; }
.btn-move { background: #f3f4f6; color: #4b5563; border: 1px solid #e5e7eb; border-radius: 6px; width: 34px; height: 26px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; font-size: 13px; }
.btn-move:hover:not(:disabled) { background: #e5e7eb; color: #111827; }
.btn-move:disabled { opacity: .35; cursor: default; }
.alert-ok { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; border-radius: 8px; padding: 10px 16px; font-size: 13px; margin-bottom: 1rem; display: flex; align-items: center; gap: 8px; }
.alert-err { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; border-radius: 8px; padding: 10px 16px; font-size: 13px; margin-bottom: 1rem; display: flex; align-items: center; gap: 8px; }
</style>

<p class="page-title">จัดการข้อมูลอุปกรณ์</p>

<?php if ($success): ?>
<div class="alert-ok"><i class="bi bi-check-circle"></i><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert-err"><i class="bi bi-exclamation-circle"></i><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<button class="btn-add" data-bs-toggle="modal" data-bs-target="#modalAdd">
    <i class="bi bi-plus-circle"></i> เพิ่มประเภทอุปกรณ์
</button>

<div class="section-card">
    <table>
        <thead>
            <tr>
                <th style="width:90px;">ลำดับ</th>
                <th style="text-align:left;">ประเภทอุปกรณ์</th>
                <th style="width:90px;">ย้าย</th>
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
                    <div class="move-group">
                        <form method="POST">
                            <?= csrfInput() ?>
                            <input type="hidden" name="id" value="<?= $d['id'] ?>">
                            <input type="hidden" name="dir" value="up">
                            <button type="submit" name="move_item" class="btn-move" title="ขยับขึ้น" <?= $i === 0 ? 'disabled' : '' ?>>
                                <i class="bi bi-caret-up-fill"></i>
                            </button>
                        </form>
                        <form method="POST">
                            <?= csrfInput() ?>
                            <input type="hidden" name="id" value="<?= $d['id'] ?>">
                            <input type="hidden" name="dir" value="down">
                            <button type="submit" name="move_item" class="btn-move" title="ขยับลง" <?= $i === count($items) - 1 ? 'disabled' : '' ?>>
                                <i class="bi bi-caret-down-fill"></i>
                            </button>
                        </form>
                    </div>
                </td>
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
            <tr><td colspan="5" style="color:#9ca3af; padding:2rem;">ไม่มีข้อมูล</td></tr>
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
                    <i class="bi bi-plus-circle me-2"></i>เพิ่มประเภทอุปกรณ์
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <?= csrfInput() ?>
                <div class="modal-body">
                    <label class="form-label" style="font-size:13px;">ประเภทอุปกรณ์</label>
                    <input type="text" name="name" class="form-control" required placeholder="เช่น Computer, Printer">
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
                    <i class="bi bi-pencil-fill me-2"></i>แก้ไขประเภทอุปกรณ์
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <?= csrfInput() ?>
                <div class="modal-body">
                    <input type="hidden" name="id" id="editId">
                    <label class="form-label" style="font-size:13px;">ประเภทอุปกรณ์</label>
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