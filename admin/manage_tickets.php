<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
requireRole('admin');

$success = '';
$error   = '';

if (isset($_SESSION['flash_success'])) {
    $success = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}

// ลบ Ticket
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delete_id = (int)$_GET['id'];
    try {
        $pdo->prepare("DELETE FROM ticket_updates WHERE ticket_id = ?")->execute([$delete_id]);
        $pdo->prepare("DELETE FROM tickets WHERE id = ?")->execute([$delete_id]);
        $_SESSION['flash_success'] = 'ลบรายการ Ticket เรียบร้อยแล้ว';
        header("Location: manage_tickets.php");
        exit();
    } catch (PDOException $e) {
        $error = 'ไม่สามารถลบรายการได้: ' . $e->getMessage();
    }
}

// อัปเดทการมอบหมายงาน / เปลี่ยนสถานะ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_ticket'])) {
    $ticket_id   = (int)$_POST['ticket_id'];
    $assigned_to = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null;
    $new_status  = $_POST['status'] ?? 'open';
    $note        = trim($_POST['note'] ?? '');

    try {
        $stmt_old = $pdo->prepare("SELECT status, assigned_to FROM tickets WHERE id = ?");
        $stmt_old->execute([$ticket_id]);
        $old_ticket = $stmt_old->fetch(PDO::FETCH_ASSOC);

        if ($old_ticket) {
            $old_status = $old_ticket['status'];

            $stmt_up = $pdo->prepare("UPDATE tickets SET assigned_to = ?, status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt_up->execute([$assigned_to, $new_status, $ticket_id]);

            if ($old_status !== $new_status || !empty($note) || $old_ticket['assigned_to'] !== $assigned_to) {
                $stmt_log = $pdo->prepare("
                    INSERT INTO ticket_updates (ticket_id, updated_by, old_status, new_status, note)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt_log->execute([$ticket_id, $_SESSION['user_id'], $old_status, $new_status, $note ?: 'ผู้ดูแลระบบอัปเดตข้อมูล Ticket']);
            }

            $_SESSION['flash_success'] = 'อัปเดตข้อมูล Ticket เรียบร้อยแล้ว';
            header("Location: manage_tickets.php");
            exit();
        }
    } catch (PDOException $e) {
        $error = 'เกิดข้อผิดพลาดในการอัปเดต: ' . $e->getMessage();
    }
}

// ตัวกรอง
$status_filter   = $_GET['status'] ?? 'all';
$priority_filter = $_GET['priority'] ?? 'all';
$search          = trim($_GET['search'] ?? '');

$query = "
    SELECT t.*, u.fullname as reporter_name, u.department as reporter_dept, u2.fullname as tech_name
    FROM tickets t
    JOIN users u ON t.user_id = u.id
    LEFT JOIN users u2 ON t.assigned_to = u2.id
    WHERE 1=1
";
$params = [];

if ($status_filter !== 'all') {
    $query .= " AND t.status = ?";
    $params[] = $status_filter;
}

if ($priority_filter !== 'all') {
    $query .= " AND t.priority = ?";
    $params[] = $priority_filter;
}

if ($search !== '') {
    $query .= " AND (t.ticket_no LIKE ? OR t.title LIKE ? OR u.fullname LIKE ? OR t.device_name LIKE ?)";
    $search_param = "%{$search}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$query .= " ORDER BY t.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงรายชื่อช่างเทคนิค
$techs = $pdo->query("SELECT id, fullname FROM users WHERE role = 'technician' AND status = 'active' ORDER BY fullname")->fetchAll(PDO::FETCH_ASSOC);
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<style>
.page-title { font-size: 18px; font-weight: 600; color: #111827; margin: 0 0 4px; }
.page-sub { font-size: 13px; color: #6b7280; margin: 0 0 20px; }
.card-box { background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 20px; margin-bottom: 24px; }
.filter-bar { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; justify-content: space-between; margin-bottom: 20px; }
.filter-group { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
.f-select, .f-input { padding: 7px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; outline: none; background: #fff; }
.f-input:focus, .f-select:focus { border-color: #3b82f6; }
.badge-status { padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 500; display: inline-block; }
.bg-open { background: #fef3c7; color: #92400e; }
.bg-in_progress { background: #dbeafe; color: #1e40af; }
.bg-resolved { background: #dcfce7; color: #166534; }
.bg-closed { background: #f3f4f6; color: #374151; }
.badge-priority { padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 500; }
.p-low { background: #f3f4f6; color: #4b5563; }
.p-medium { background: #e0f2fe; color: #0369a1; }
.p-high { background: #ffedd5; color: #c2410c; }
.p-urgent { background: #fee2e2; color: #b91c1c; }
.table-custom { width: 100%; border-collapse: collapse; font-size: 13px; }
.table-custom th { background: #f9fafb; padding: 12px; border-bottom: 1px solid #e5e7eb; text-align: left; font-weight: 600; color: #374151; }
.table-custom td { padding: 12px; border-bottom: 1px solid #f3f4f6; vertical-align: middle; color: #1f2937; }
.table-custom tr:hover { background: #f9fafb; }
.btn-act { border: none; background: #3b82f6; color: #fff; padding: 5px 10px; border-radius: 5px; font-size: 12px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
.btn-act:hover { background: #2563eb; color: #fff; }
.btn-del { border: none; background: #ef4444; color: #fff; padding: 5px 10px; border-radius: 5px; font-size: 12px; cursor: pointer; text-decoration: none; margin-left: 4px; }
.btn-del:hover { background: #dc2626; color: #fff; }
</style>

<div class="main-content">
    <div class="page-title"><i class="bi bi-ticket-detailed me-1"></i> จัดการข้อมูล Ticket ทั้งหมด</div>
    <div class="page-sub">ตรวจสอบ มอบหมายงานช่าง และติดตามสถานะการแจ้งซ่อมในระบบ</div>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card-box">
        <form method="GET" class="filter-bar">
            <div class="filter-group">
                <label style="font-size: 13px; font-weight: 500;">สถานะ:</label>
                <select name="status" class="f-select" onchange="this.form.submit()">
                    <option value="all" <?= $status_filter==='all'?'selected':'' ?>>ทั้งหมด</option>
                    <option value="open" <?= $status_filter==='open'?'selected':'' ?>>รอดำเนินการ (Open)</option>
                    <option value="in_progress" <?= $status_filter==='in_progress'?'selected':'' ?>>กำลังดำเนินการ (In Progress)</option>
                    <option value="resolved" <?= $status_filter==='resolved'?'selected':'' ?>>แก้ไขแล้ว (Resolved)</option>
                    <option value="closed" <?= $status_filter==='closed'?'selected':'' ?>>ปิดงาน (Closed)</option>
                </select>

                <label style="font-size: 13px; font-weight: 500; margin-left: 8px;">ความสำคัญ:</label>
                <select name="priority" class="f-select" onchange="this.form.submit()">
                    <option value="all" <?= $priority_filter==='all'?'selected':'' ?>>ทั้งหมด</option>
                    <option value="low" <?= $priority_filter==='low'?'selected':'' ?>>ต่ำ (Low)</option>
                    <option value="medium" <?= $priority_filter==='medium'?'selected':'' ?>>ปานกลาง (Medium)</option>
                    <option value="high" <?= $priority_filter==='high'?'selected':'' ?>>สูง (High)</option>
                    <option value="urgent" <?= $priority_filter==='urgent'?'selected':'' ?>>เร่งด่วน (Urgent)</option>
                </select>
            </div>

            <div class="filter-group">
                <input type="text" name="search" class="f-input" placeholder="ค้นหา Ticket No, ชื่อผู้แจ้ง..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search"></i> ค้นหา</button>
                <?php if ($status_filter !== 'all' || $priority_filter !== 'all' || $search !== ''): ?>
                    <a href="manage_tickets.php" class="btn btn-outline-secondary btn-sm">ล้างค่า</a>
                <?php endif; ?>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>เลข Ticket</th>
                        <th>ผู้แจ้ง / แผนก</th>
                        <th>หัวข้อ / อุปกรณ์</th>
                        <th>สถานที่</th>
                        <th>ความสำคัญ</th>
                        <th>ช่างที่ดูแล</th>
                        <th>สถานะ</th>
                        <th>วันที่แจ้ง</th>
                        <th style="text-align: right;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tickets)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; color: #9ca3af; padding: 24px;">ไม่พบข้อมูลรายการ Ticket</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tickets as $t): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($t['ticket_no']) ?></strong></td>
                                <td>
                                    <div><?= htmlspecialchars($t['reporter_name']) ?></div>
                                    <small style="color: #6b7280;"><?= htmlspecialchars($t['reporter_dept'] ?: '-') ?></small>
                                </td>
                                <td>
                                    <div><strong><?= htmlspecialchars($t['title']) ?></strong></div>
                                    <small style="color: #6b7280;"><?= htmlspecialchars($t['device_type'] ?: '-') ?> (<?= htmlspecialchars($t['serial_no'] ?: '-') ?>)</small>
                                </td>
                                <td>
                                    <small><?= htmlspecialchars($t['building'] ?: '-') ?> ชั้น <?= htmlspecialchars($t['floor'] ?: '-') ?> ห้อง <?= htmlspecialchars($t['room'] ?: '-') ?></small>
                                </td>
                                <td>
                                    <?php
                                    $p_class = 'p-medium';
                                    $p_text  = 'ปานกลาง';
                                    if ($t['priority'] === 'low') { $p_class = 'p-low'; $p_text = 'ต่ำ'; }
                                    elseif ($t['priority'] === 'high') { $p_class = 'p-high'; $p_text = 'สูง'; }
                                    elseif ($t['priority'] === 'urgent') { $p_class = 'p-urgent'; $p_text = 'เร่งด่วน'; }
                                    ?>
                                    <span class="badge-priority <?= $p_class ?>"><?= $p_text ?></span>
                                </td>
                                <td>
                                    <?php if ($t['tech_name']): ?>
                                        <span class="badge bg-light text-dark border"><i class="bi bi-person-wrench me-1"></i><?= htmlspecialchars($t['tech_name']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted" style="font-size: 12px; font-style: italic;">ยังไม่ได้มอบหมาย</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $s_class = 'bg-open';
                                    $s_text  = 'รอดำเนินการ';
                                    if ($t['status'] === 'in_progress') { $s_class = 'bg-in_progress'; $s_text = 'กำลังซ่อม'; }
                                    elseif ($t['status'] === 'resolved') { $s_class = 'bg-resolved'; $s_text = 'แก้ไขแล้ว'; }
                                    elseif ($t['status'] === 'closed') { $s_class = 'bg-closed'; $s_text = 'ปิดงานแล้ว'; }
                                    ?>
                                    <span class="badge-status <?= $s_class ?>"><?= $s_text ?></span>
                                </td>
                                <td><small style="color: #6b7280;"><?= date('d/m/Y H:i', strtotime($t['created_at'])) ?></small></td>
                                <td style="text-align: right;">
                                    <button type="button" class="btn-act" data-bs-toggle="modal" data-bs-target="#editModal<?= $t['id'] ?>">
                                        <i class="bi bi-pencil-square"></i> จัดการ
                                    </button>
                                    <a href="manage_tickets.php?action=delete&id=<?= $t['id'] ?>" class="btn-del" onclick="return confirm('ยืนยันการลบ Ticket หมายเลข <?= $t['ticket_no'] ?> ?');">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>

                            <!-- Modal แก้ไขข้อมูล & มอบหมายงาน -->
                            <div class="modal fade" id="editModal<?= $t['id'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <form method="POST">
                                            <input type="hidden" name="update_ticket" value="1">
                                            <input type="hidden" name="ticket_id" value="<?= $t['id'] ?>">

                                            <div class="modal-header">
                                                <h5 class="modal-title">จัดการ Ticket: <?= htmlspecialchars($t['ticket_no']) ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row g-3 mb-3">
                                                    <div class="col-md-6">
                                                        <strong>ผู้แจ้ง:</strong> <?= htmlspecialchars($t['reporter_name']) ?> (<?= htmlspecialchars($t['reporter_dept'] ?: '-') ?>)
                                                    </div>
                                                    <div class="col-md-6">
                                                        <strong>อุปกรณ์:</strong> <?= htmlspecialchars($t['device_name'] ?: '-') ?> (S/N: <?= htmlspecialchars($t['serial_no'] ?: '-') ?>)
                                                    </div>
                                                    <div class="col-md-12">
                                                        <strong>รายละเอียดปัญหา:</strong>
                                                        <div class="p-2 bg-light rounded border mt-1" style="font-size: 13px;">
                                                            <?= nl2br(htmlspecialchars($t['description'])) ?>
                                                        </div>
                                                    </div>
                                                    <?php if ($t['image_path']): ?>
                                                        <div class="col-md-12">
                                                            <strong>รูปภาพประกอบ:</strong><br>
                                                            <a href="<?= BASE_URL ?>/<?= htmlspecialchars($t['image_path']) ?>" target="_blank">
                                                                <img src="<?= BASE_URL ?>/<?= htmlspecialchars($t['image_path']) ?>" style="max-height: 150px; border-radius: 6px;" class="mt-1 border">
                                                            </a>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>

                                                <hr>

                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label font-weight-bold">มอบหมายช่างผู้ดูแล:</label>
                                                        <select name="assigned_to" class="form-select">
                                                            <option value="">-- ยังไม่มอบหมาย --</option>
                                                            <?php foreach ($techs as $tech): ?>
                                                                <option value="<?= $tech['id'] ?>" <?= $t['assigned_to'] == $tech['id'] ? 'selected' : '' ?>>
                                                                    <?= htmlspecialchars($tech['fullname']) ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label class="form-label font-weight-bold">เปลี่ยนสถานะงาน:</label>
                                                        <select name="status" class="form-select">
                                                            <option value="open" <?= $t['status']==='open'?'selected':'' ?>>รอดำเนินการ (Open)</option>
                                                            <option value="in_progress" <?= $t['status']==='in_progress'?'selected':'' ?>>กำลังดำเนินการ (In Progress)</option>
                                                            <option value="resolved" <?= $t['status']==='resolved'?'selected':'' ?>>แก้ไขเรียบร้อย (Resolved)</option>
                                                            <option value="closed" <?= $t['status']==='closed'?'selected':'' ?>>ปิดงาน (Closed)</option>
                                                        </select>
                                                    </div>

                                                    <div class="col-md-12">
                                                        <label class="form-label font-weight-bold">บันทึกเพิ่มเติม / หมายเหตุ:</label>
                                                        <textarea name="note" class="form-select" style="height: 80px;" placeholder="ระบุเหตุผลการเปลี่ยนสถานะ หรือคำสั่งการช่าง..."></textarea>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">ยกเลิก</button>
                                                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-save me-1"></i> บันทึกการเปลี่ยนแปลง</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
