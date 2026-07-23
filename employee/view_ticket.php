<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
requireLogin();

$ticket_id = (int)($_GET['id'] ?? 0);

if (!$ticket_id) {
    header("Location: " . BASE_URL . "/" . $_SESSION['role'] . "/dashboard.php");
    exit();
}

// ดึงรายละเอียด Ticket
$stmt = $pdo->prepare("
    SELECT t.*, 
           reporter.fullname as reporter_name, reporter.department as reporter_dept, reporter.email as reporter_email,
           tech.fullname as tech_name, tech.department as tech_dept, tech.email as tech_email
    FROM tickets t
    JOIN users reporter ON t.user_id = reporter.id
    LEFT JOIN users tech ON t.assigned_to = tech.id
    WHERE t.id = ?
");
$stmt->execute([$ticket_id]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    die("ไม่พบข้อมูล Ticket หมายเลขดังกล่าว");
}

// เช็คสิทธิ์: พนักงานทั่วไปดูได้เฉพาะ Ticket ของตัวเอง
if ($_SESSION['role'] === 'employee' && (int)$ticket['user_id'] !== (int)$_SESSION['user_id']) {
    die("คุณไม่มีสิทธิ์เข้าถึงข้อมูล Ticket นี้");
}

// ดึงประวัติการอัปเดตงาน (Timeline)
$updates_stmt = $pdo->prepare("
    SELECT tu.*, u.fullname as updater_name, u.role as updater_role
    FROM ticket_updates tu
    JOIN users u ON tu.updated_by = u.id
    WHERE tu.ticket_id = ?
    ORDER BY tu.created_at ASC
");
$updates_stmt->execute([$ticket_id]);
$updates = $updates_stmt->fetchAll(PDO::FETCH_ASSOC);

// Map ข้อความภาษาไทย
$status_badge = [
    'open'        => ['bg' => '#fef3c7', 'color' => '#92400e', 'label' => 'รอดำเนินการ', 'icon' => 'bi-clock-history'],
    'in_progress' => ['bg' => '#dbeafe', 'color' => '#1e40af', 'label' => 'กำลังดำเนินการ', 'icon' => 'bi-gear-wide-connected'],
    'resolved'    => ['bg' => '#d1fae5', 'color' => '#065f46', 'label' => 'แก้ไขแล้ว', 'icon' => 'bi-check-circle'],
    'closed'      => ['bg' => '#f1f5f9', 'color' => '#475569', 'label' => 'ปิดงานแล้ว', 'icon' => 'bi-x-circle']
];

$priority_badge = [
    'low'    => ['bg' => '#f1f5f9', 'color' => '#475569', 'label' => '🟢 ปกติ'],
    'medium' => ['bg' => '#e0f2fe', 'color' => '#0369a1', 'label' => '🟡 ปานกลาง'],
    'high'   => ['bg' => '#fef3c7', 'color' => '#92400e', 'label' => '🟠 ด่วน'],
    'urgent' => ['bg' => '#fee2e2', 'color' => '#991b1b', 'label' => '🔴 ด่วนมาก']
];

$category_label = [
    'hardware' => 'ฮาร์ดแวร์ (Hardware)',
    'software' => 'ซอฟต์แวร์ (Software)',
    'network'  => 'เครือข่าย (Network)',
    'other'    => 'อื่นๆ / ไม่ระบุ'
];

$st = $status_badge[$ticket['status']] ?? $status_badge['open'];
$pr = $priority_badge[$ticket['priority']] ?? $priority_badge['medium'];
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<style>
.view-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 24px;
}
.btn-back {
    display: inline-flex; align-items: center; gap: 6px;
    color: #475569; background: #ffffff; border: 1px solid #cbd5e1;
    padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 500;
    text-decoration: none; transition: all 0.15s ease;
}
.btn-back:hover { background: #f1f5f9; color: #0f172a; }

.badge-custom {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 6px 14px; border-radius: 20px; font-size: 12.5px; font-weight: 600;
}

.ticket-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.04);
    margin-bottom: 24px;
}

.info-label { font-size: 11.5px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; margin-bottom: 4px; }
.info-value { font-size: 14px; color: #0f172a; font-weight: 500; }

/* Timeline */
.timeline-wrap {
    position: relative;
    padding-left: 28px;
}
.timeline-wrap::before {
    content: '';
    position: absolute;
    top: 10px; bottom: 10px; left: 10px;
    width: 2px;
    background: #e2e8f0;
}
.timeline-item {
    position: relative;
    margin-bottom: 24px;
}
.timeline-item:last-child { margin-bottom: 0; }
.timeline-dot {
    position: absolute;
    left: -28px; top: 4px;
    width: 22px; height: 22px;
    border-radius: 50%;
    background: #ffffff;
    border: 2px solid #2563eb;
    display: flex; align-items: center; justify-content: center;
    font-size: 10px; color: #2563eb;
}
.timeline-content {
    background: #f8fafc;
    border: 1px solid #f1f5f9;
    border-radius: 10px;
    padding: 16px;
}
.timeline-user { font-size: 13px; font-weight: 600; color: #0f172a; }
.timeline-time { font-size: 11.5px; color: #94a3b8; margin-left: auto; }
.timeline-note { font-size: 13.5px; color: #334155; margin-top: 8px; line-height: 1.5; white-space: pre-line; }

.img-preview-thumb {
    max-width: 100%; max-height: 240px; border-radius: 10px;
    border: 1px solid #e2e8f0; margin-top: 12px; cursor: pointer;
    transition: transform 0.2s;
}
.img-preview-thumb:hover { transform: scale(1.01); }
</style>

<!-- Header & Back Button -->
<div class="view-header">
    <div class="d-flex align-items-center gap-3">
        <a href="<?= BASE_URL ?>/<?= $_SESSION['role'] ?>/dashboard.php" class="btn-back">
            <i class="bi bi-arrow-left"></i> ย้อนกลับ
        </a>
        <div>
            <h4 class="fw-bold mb-0">
                Ticket #<?= htmlspecialchars($ticket['ticket_no']) ?>
            </h4>
            <span class="text-muted small">แจ้งเมื่อ <?= date('d/m/Y H:i', strtotime($ticket['created_at'])) ?> น.</span>
        </div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="badge-custom" style="background: <?= $pr['bg'] ?>; color: <?= $pr['color'] ?>;">
            <?= $pr['label'] ?>
        </span>
        <span class="badge-custom" style="background: <?= $st['bg'] ?>; color: <?= $st['color'] ?>;">
            <i class="bi <?= $st['icon'] ?>"></i> <?= $st['label'] ?>
        </span>
    </div>
</div>

<div class="row g-4">
    <!-- ฝั่งซ้าย: รายละเอียดปัญหา & ประวัติ Timeline -->
    <div class="col-lg-8">
        
        <!-- การ์ดรายละเอียดปัญหา -->
        <div class="ticket-card">
            <h5 class="fw-bold text-dark mb-3">
                <i class="bi bi-card-heading text-primary me-2"></i><?= htmlspecialchars($ticket['title']) ?>
            </h5>
            
            <div class="info-label">อาการ / รายละเอียดปัญหาที่แจ้ง</div>
            <div class="p-3 bg-light rounded-3 text-dark mb-3" style="font-size:14px; line-height:1.6; white-space: pre-line;">
                <?= htmlspecialchars($ticket['description']) ?>
            </div>

            <?php if (!empty($ticket['image_path'])): ?>
                <div class="info-label mt-3">รูปภาพประกอบที่แนบมา</div>
                <a href="<?= BASE_URL ?>/<?= htmlspecialchars($ticket['image_path']) ?>" target="_blank">
                    <img src="<?= BASE_URL ?>/<?= htmlspecialchars($ticket['image_path']) ?>" alt="รูปภาพแจ้งซ่อม" class="img-preview-thumb">
                </a>
            <?php endif; ?>
        </div>

        <!-- การ์ด Timeline ประวัติการดำเนินงาน -->
        <div class="ticket-card">
            <h5 class="fw-bold text-dark mb-4 d-flex align-items-center justify-content-between">
                <span><i class="bi bi-clock-history text-primary me-2"></i>ประวัติการดำเนินงาน (Timeline)</span>
                <span class="badge bg-secondary font-monospace fw-normal fs-6"><?= count($updates) ?> รายการ</span>
            </h5>

            <?php if (empty($updates)): ?>
                <div class="text-center text-muted py-4">
                    <i class="bi bi-hourglass-split fs-2 opacity-50 d-block mb-2"></i>
                    ยังไม่มีการอัปเดตประวัติการซ่อมจากช่าง IT
                </div>
            <?php else: ?>
                <div class="timeline-wrap">
                    <?php foreach ($updates as $up): ?>
                        <div class="timeline-item">
                            <div class="timeline-dot"><i class="bi bi-check"></i></div>
                            <div class="timeline-content">
                                <div class="d-flex align-items-center mb-1">
                                    <span class="timeline-user me-2"><i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($up['updater_name']) ?></span>
                                    <span class="badge bg-light text-dark border me-2" style="font-size:10.5px;"><?= htmlspecialchars($up['updater_role']) ?></span>
                                    <span class="timeline-time"><i class="bi bi-clock me-1"></i><?= date('d/m/Y H:i', strtotime($up['created_at'])) ?> น.</span>
                                </div>

                                <?php if ($up['old_status'] !== $up['new_status'] && !empty($up['new_status'])): ?>
                                    <div class="my-2">
                                        <span class="small text-muted">เปลี่ยนสถานะ:</span>
                                        <span class="badge bg-primary ms-1"><?= $status_badge[$up['new_status']]['label'] ?? $up['new_status'] ?></span>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($up['note'])): ?>
                                    <div class="timeline-note"><?= htmlspecialchars($up['note']) ?></div>
                                <?php endif; ?>

                                <?php if (!empty($up['image'])): ?>
                                    <div class="mt-2">
                                        <a href="<?= BASE_URL ?>/uploads/updates/<?= htmlspecialchars($up['image']) ?>" target="_blank">
                                            <img src="<?= BASE_URL ?>/uploads/updates/<?= htmlspecialchars($up['image']) ?>" alt="รูปภาพอัปเดต" class="img-preview-thumb" style="max-height:140px;">
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- ฝั่งขวา: ข้อมูลสถานที่, อุปกรณ์ & ช่างผู้รับผิดชอบ -->
    <div class="col-lg-4">

        <!-- สถานที่ & ข้อมูลอุปกรณ์ -->
        <div class="ticket-card mb-4">
            <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="bi bi-geo-alt text-primary me-2"></i>สถานที่แจ้งซ่อม</h6>
            
            <div class="mb-3">
                <div class="info-label">อาคาร / ตึก</div>
                <div class="info-value"><i class="bi bi-building me-1 text-muted"></i><?= htmlspecialchars($ticket['building'] ?: '-') ?></div>
            </div>
            
            <div class="row g-2 mb-3">
                <div class="col-6">
                    <div class="info-label">ชั้น</div>
                    <div class="info-value"><?= htmlspecialchars($ticket['floor'] ?: '-') ?></div>
                </div>
                <div class="col-6">
                    <div class="info-label">ห้อง</div>
                    <div class="info-value"><?= htmlspecialchars($ticket['room'] ?: '-') ?></div>
                </div>
            </div>

            <h6 class="fw-bold text-dark border-bottom pb-2 mb-3 mt-4"><i class="bi bi-pc-display text-primary me-2"></i>ข้อมูลอุปกรณ์</h6>

            <div class="mb-3">
                <div class="info-label">หมวดหมู่</div>
                <div class="info-value"><?= htmlspecialchars($category_label[$ticket['category']] ?? $ticket['category']) ?></div>
            </div>

            <div class="mb-3">
                <div class="info-label">ประเภทอุปกรณ์</div>
                <div class="info-value"><?= htmlspecialchars($ticket['device_type'] ?: '-') ?></div>
            </div>

            <div class="mb-3">
                <div class="info-label">ชื่อ / รุ่นอุปกรณ์</div>
                <div class="info-value"><?= htmlspecialchars($ticket['device_name'] ?: '-') ?></div>
            </div>

            <div>
                <div class="info-label">หมายเลขเครื่อง / Serial No.</div>
                <div class="info-value font-monospace small"><?= htmlspecialchars($ticket['serial_no'] ?: '-') ?></div>
            </div>
        </div>

        <!-- ช่างผู้รับผิดชอบ -->
        <div class="ticket-card">
            <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="bi bi-tools text-primary me-2"></i>ช่างผู้รับผิดชอบ</h6>

            <?php if (!empty($ticket['tech_name'])): ?>
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold" style="width:44px; height:44px; font-size:18px;">
                        <?= mb_substr($ticket['tech_name'], 0, 1, 'UTF-8') ?>
                    </div>
                    <div>
                        <div class="fw-bold text-dark"><?= htmlspecialchars($ticket['tech_name']) ?></div>
                        <div class="text-muted small"><?= htmlspecialchars($ticket['tech_dept'] ?: 'แผนก IT Support') ?></div>
                        <?php if (!empty($ticket['tech_email'])): ?>
                            <div class="text-primary small mt-1"><i class="bi bi-envelope me-1"></i><?= htmlspecialchars($ticket['tech_email']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="text-muted text-center py-3 small">
                    <i class="bi bi-hourglass me-1"></i> อยู่ในระหว่างรอผู้จัดการมอบหมายช่าง
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>