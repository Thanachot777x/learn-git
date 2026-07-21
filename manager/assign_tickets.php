<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
requireRole('manager');

// ดึงรายชื่อช่าง
$techs = $pdo->query("SELECT id, fullname FROM users WHERE role = 'technician' AND status = 'active'")->fetchAll(PDO::FETCH_ASSOC);
$tech_ids = array_column($techs, 'id'); // สำหรับเช็ค tech_id ที่ส่งมา

$success = '';
$error   = '';

// มอบหมายงาน
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign'])) {
    $ticket_id = (int) $_POST['ticket_id'];
    $tech_id   = (int) $_POST['tech_id'];

    // เช็คว่า tech_id 
    if (!in_array($tech_id, $tech_ids)) {
        $error = 'ช่างที่เลือกไม่ถูกต้อง กรุณาเลือกใหม่';
    } else {
        $stmt = $pdo->prepare("SELECT status FROM tickets WHERE id = ?");
        $stmt->execute([$ticket_id]);
        $old = $stmt->fetchColumn();

        if ($old === false) {
            $error = 'ไม่พบ Ticket ดังกล่าว';
        } else {
            $pdo->prepare("UPDATE tickets SET assigned_to = ?, status = 'in_progress', updated_at = NOW() WHERE id = ?")
                ->execute([$tech_id, $ticket_id]);

            $pdo->prepare("INSERT INTO ticket_updates (ticket_id, updated_by, old_status, new_status, note) VALUES (?, ?, ?, 'in_progress', ?)")
                ->execute([$ticket_id, $_SESSION['user_id'], $old, 'มอบหมายงานโดย Manager']);

            $success = 'มอบหมายงานเรียบร้อยแล้ว';
        }
    }
}

// ฟิลเตอร์
$filter_status   = $_GET['status']   ?? '';
$filter_priority = $_GET['priority'] ?? '';
$filter_category = $_GET['category'] ?? '';
$search          = trim($_GET['search'] ?? '');

$where  = ['1=1'];
$params = [];

if ($filter_status)   { $where[] = 't.status = ?';   $params[] = $filter_status; }
if ($filter_priority) { $where[] = 't.priority = ?'; $params[] = $filter_priority; }
if ($filter_category) { $where[] = 't.category = ?'; $params[] = $filter_category; }
if ($search) {
    $where[] = '(t.title LIKE ? OR t.ticket_no LIKE ? OR u.fullname LIKE ?)';
    $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
}

$sql = "
    SELECT t.*, u.fullname as reporter, u.department, u2.fullname as technician
    FROM tickets t
    JOIN users u ON t.user_id = u.id
    LEFT JOIN users u2 ON t.assigned_to = u2.id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY FIELD(t.priority,'urgent','high','medium','low'), t.created_at DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

$priority_class = ['low'=>'secondary','medium'=>'info','high'=>'warning','urgent'=>'danger'];
$priority_text  = ['low'=>'ต่ำ','medium'=>'ปานกลาง','high'=>'สูง','urgent'=>'เร่งด่วน'];
$status_class   = ['open'=>'danger','in_progress'=>'warning','resolved'=>'success','closed'=>'secondary'];
$status_text    = ['open'=>'รอดำเนินการ','in_progress'=>'กำลังแก้ไข','resolved'=>'แก้ไขแล้ว','closed'=>'ปิดแล้ว'];
$cat_text       = ['hardware'=>'Hardware','software'=>'Software','network'=>'Network','other'=>'อื่นๆ'];
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="row mb-3">
    <div class="col-12">
        <h4 class="fw-bold"><i class="bi bi-person-check me-2"></i>มอบหมายงาน Ticket</h4>
        <p class="text-muted mb-0">พบ <?= count($tickets) ?> รายการ</p>
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

<?php if (empty($techs)): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle me-1"></i>
        ขณะนี้ไม่มีช่าง IT ที่พร้อมรับงาน (status = active) กรุณาตรวจสอบในหน้าจัดการผู้ใช้งาน
    </div>
<?php endif; ?>

<!-- ฟิลเตอร์ -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="🔍 ค้นหา Ticket / ผู้แจ้ง..."
                       value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">ทุกสถานะ</option>
                    <option value="open"        <?= $filter_status==='open'        ? 'selected':'' ?>>รอดำเนินการ</option>
                    <option value="in_progress" <?= $filter_status==='in_progress' ? 'selected':'' ?>>กำลังแก้ไข</option>
                    <option value="resolved"    <?= $filter_status==='resolved'    ? 'selected':'' ?>>แก้ไขแล้ว</option>
                    <option value="closed"      <?= $filter_status==='closed'      ? 'selected':'' ?>>ปิดแล้ว</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="priority" class="form-select form-select-sm">
                    <option value="">ทุกความเร่งด่วน</option>
                    <option value="urgent" <?= $filter_priority==='urgent' ? 'selected':'' ?>>เร่งด่วน</option>
                    <option value="high"   <?= $filter_priority==='high'   ? 'selected':'' ?>>สูง</option>
                    <option value="medium" <?= $filter_priority==='medium' ? 'selected':'' ?>>ปานกลาง</option>
                    <option value="low"    <?= $filter_priority==='low'    ? 'selected':'' ?>>ต่ำ</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="category" class="form-select form-select-sm">
                    <option value="">ทุกประเภท</option>
                    <option value="hardware" <?= $filter_category==='hardware' ? 'selected':'' ?>>Hardware</option>
                    <option value="software" <?= $filter_category==='software' ? 'selected':'' ?>>Software</option>
                    <option value="network"  <?= $filter_category==='network'  ? 'selected':'' ?>>Network</option>
                    <option value="other"    <?= $filter_category==='other'    ? 'selected':'' ?>>อื่นๆ</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm">กรอง</button>
                <a href="<?= BASE_URL ?>/manager/assign_tickets.php" class="btn btn-outline-secondary btn-sm">ล้าง</a>
            </div>
        </form>
    </div>
</div>

<!-- ตาราง Ticket -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Ticket No.</th>
                        <th>หัวข้อ</th>
                        <th>ผู้แจ้ง / แผนก</th>
                        <th>ประเภท</th>
                        <th>ความเร่งด่วน</th>
                        <th>ช่างที่รับ</th>
                        <th>สถานะ</th>
                        <th>วันที่แจ้ง</th>
                        <th>มอบหมาย</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tickets as $t):
                        // ป้องกัน priority เป็น null
                        $prio     = $t['priority'] ?? 'low';
                        $p_class  = $priority_class[$prio] ?? 'secondary';
                        $p_text   = $priority_text[$prio]  ?? '-';
                        $s_key    = $t['status'] ?? 'open';
                        $s_class  = $status_class[$s_key]  ?? 'secondary';
                        $s_text   = $status_text[$s_key]   ?? $s_key;
                        $c_text   = $cat_text[$t['category']] ?? 'อื่นๆ';
                    ?>
                    <tr>
                        <td><code><?= htmlspecialchars($t['ticket_no']) ?></code></td>
                        <td><?= htmlspecialchars($t['title']) ?></td>
                        <td>
                            <?= htmlspecialchars($t['reporter']) ?>
                            <br><small class="text-muted"><?= htmlspecialchars($t['department'] ?? '-') ?></small>
                        </td>
                        <td><?= $c_text ?></td>
                        <td><span class="badge bg-<?= $p_class ?>"><?= $p_text ?></span></td>
                        <td>
                            <?= $t['technician']
                                ? htmlspecialchars($t['technician'])
                                : '<span class="text-muted small">ยังไม่มอบหมาย</span>' ?>
                        </td>
                        <td><span class="badge bg-<?= $s_class ?>"><?= $s_text ?></span></td>
                        <td><small><?= date('d/m/Y H:i', strtotime($t['created_at'])) ?></small></td>
                        <td>
                            <?php if (!empty($techs)): ?>
                            <button class="btn btn-sm btn-outline-primary" type="button"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalAssign"
                                    data-id="<?= $t['id'] ?>"
                                    data-assigned="<?= (int)($t['assigned_to'] ?? 0) ?>"
                                    data-title="<?= htmlspecialchars($t['ticket_no'] . ' - ' . $t['title']) ?>">
                                <i class="bi bi-person-check"></i>
                                <?= $t['assigned_to'] ? 'เปลี่ยนช่าง' : 'มอบหมาย' ?>
                            </button>
                            <?php else: ?>
                            <span class="text-muted small">ไม่มีช่าง</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($tickets)): ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            <i class="bi bi-search fs-3 d-block mb-2"></i>ไม่พบ Ticket ตามเงื่อนไข
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal มอบหมายงาน -->
<div class="modal fade" id="modalAssign" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-person-check me-2"></i>มอบหมายงาน</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3" id="modal_ticket_title" style="font-size:13px;"></p>
                <form method="POST">
                    <input type="hidden" name="ticket_id" id="modal_ticket_id">
                    <label class="form-label fw-semibold">เลือกช่างผู้รับผิดชอบ</label>
                    <div class="input-group">
                        <select name="tech_id" id="modal_tech_select" class="form-select" required>
                            <option value="">-- เลือกช่าง --</option>
                            <?php foreach ($techs as $tech): ?>
                            <option value="<?= $tech['id'] ?>"><?= htmlspecialchars($tech['fullname']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" name="assign" class="btn btn-primary">มอบหมาย</button>
                    </div>
                    <div class="form-text mt-1">ถ้า Ticket นี้มีช่างรับงานอยู่แล้ว การมอบหมายใหม่จะเปลี่ยนช่างคนรับผิดชอบ</div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('modalAssign').addEventListener('show.bs.modal', e => {
    const btn      = e.relatedTarget;
    const id       = btn.getAttribute('data-id');
    const assigned = btn.getAttribute('data-assigned');
    const title    = btn.getAttribute('data-title');

    document.getElementById('modal_ticket_id').value    = id;
    document.getElementById('modal_ticket_title').textContent = title;

    // เซ็ต dropdown ให้ตรงกับช่างคนที่มอบหมายไว้เดิม (ถ้ามี)
    const sel = document.getElementById('modal_tech_select');
    sel.value = assigned || '';
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>