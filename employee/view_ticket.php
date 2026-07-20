<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
requireRole('employee');

$ticket_id = (int) ($_GET['id'] ?? 0);
if (!$ticket_id) {
    header("Location: " . BASE_URL . "/employee/dashboard.php");
    exit();
}

$stmt = $pdo->prepare("
    SELECT t.*, u.fullname as technician, u.email as technician_email
    FROM tickets t
    LEFT JOIN users u ON t.assigned_to = u.id
    WHERE t.id = ? AND t.user_id = ?
");
$stmt->execute([$ticket_id, $_SESSION['user_id']]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    header("Location: " . BASE_URL . "/employee/dashboard.php");
    exit();
}

$history = $pdo->prepare("
    SELECT tu.*, u.fullname as updater
    FROM ticket_updates tu
    JOIN users u ON tu.updated_by = u.id
    WHERE tu.ticket_id = ?
    ORDER BY tu.created_at ASC
");
$history->execute([$ticket_id]);
$updates = $history->fetchAll(PDO::FETCH_ASSOC);

$status_class = ['open'=>'danger','in_progress'=>'warning','resolved'=>'success','closed'=>'secondary'];
$status_text  = ['open'=>'รอดำเนินการ','in_progress'=>'กำลังแก้ไข','resolved'=>'แก้ไขแล้ว','closed'=>'ปิดแล้ว'];
$cat_text     = ['hardware'=>'Hardware','software'=>'Software','network'=>'Network','other'=>'อื่นๆ'];
$s = $ticket['status'] ?? 'open';

// progress step ของสถานะ (สำหรับ progress bar ด้านบน)
$step_order = ['open'=>1,'in_progress'=>2,'resolved'=>3,'closed'=>3];
$current_step = $step_order[$s] ?? 1;
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<style>
.td-wrap { font-size: 13.5px; color: #1f2937; }
.td-top { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom: 22px; flex-wrap:wrap; gap:12px; }
.td-eyebrow { font-size: 11px; letter-spacing: 0.6px; color: #9ca3af; text-transform: uppercase; font-weight: 600; margin-bottom: 4px; }
.td-title { font-size: 19px; font-weight: 600; color: #111827; margin: 0; display:flex; align-items:center; gap: 10px; }
.td-ticketno { font-family: 'SFMono-Regular', Consolas, monospace; font-size: 13px; background:#eef2ff; color:#3730a3; padding: 3px 9px; border-radius: 5px; font-weight:600; letter-spacing:0.3px; }
.btn-back { background:#fff; border:1px solid #e5e7eb; color:#374151; border-radius:8px; padding:8px 16px; font-size:13px; text-decoration:none; display:inline-flex; align-items:center; gap:6px; }
.btn-back:hover { background:#f9fafb; color:#111827; }

/* Status banner */
.status-banner { border-radius: 12px; padding: 18px 22px; margin-bottom: 22px; display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; border: 1px solid; }
.status-banner.s-open       { background:#fef2f2; border-color:#fecaca; }
.status-banner.s-progress   { background:#fffbeb; border-color:#fde68a; }
.status-banner.s-done       { background:#f0fdf4; border-color:#bbf7d0; }
.sb-label { font-size: 11px; text-transform:uppercase; letter-spacing:0.5px; font-weight:600; opacity:0.7; margin-bottom:2px; }
.sb-value { font-size: 17px; font-weight: 700; }
.s-open .sb-value, .s-open .sb-label   { color:#991b1b; }
.s-progress .sb-value, .s-progress .sb-label { color:#92400e; }
.s-done .sb-value, .s-done .sb-label   { color:#166534; }

/* progress steps */
.steps { display:flex; align-items:center; gap:0; }
.step { display:flex; align-items:center; gap:8px; }
.step-dot { width:11px; height:11px; border-radius:50%; background:#d1d5db; flex-shrink:0; }
.step:nth-child(1).done .step-dot,
.step:nth-child(1).active .step-dot { background:#ef4444; }
.step:nth-child(3).done .step-dot,
.step:nth-child(3).active .step-dot { background:#f59e0b; }
.step:nth-child(5).done .step-dot,
.step:nth-child(5).active .step-dot { background:#22c55e; }
.step.active .step-dot { box-shadow:0 0 0 4px rgba(0,0,0,0.08); }
.step-text { font-size:12px; color:#9ca3af; font-weight:500; white-space:nowrap; }
.step.done .step-text, .step.active .step-text { color:#374151; font-weight:600; }
.step-line { width:38px; height:1px; background:#e5e7eb; margin:0 8px; }
.step-line.done { background:#22c55e; }

/* card */
.card-panel { background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden; height:100%; }
.card-panel-head { padding:14px 20px; border-bottom:1px solid #f1f2f4; display:flex; align-items:center; gap:8px; }
.card-panel-head i { color:#1a56db; font-size:15px; }
.card-panel-head span { font-size:13.5px; font-weight:600; color:#111827; }
.card-panel-body { padding: 20px; }

/* info rows */
.info-row { display:flex; padding: 10px 0; border-bottom:1px solid #f3f4f6; }
.info-row:last-child { border-bottom:none; }
.info-icon { width: 30px; flex-shrink:0; color:#9ca3af; font-size:14px; padding-top:1px; }
.info-key { width: 110px; flex-shrink:0; color:#9ca3af; font-size:12px; padding-top:2px; }
.info-val { flex:1; color:#111827; font-weight:500; }
.info-val small a { color:#6b7280; }

.desc-box { background:#f9fafb; border:1px solid #f1f2f4; border-radius:9px; padding:14px 16px; margin-top: 4px; color:#374151; line-height:1.6; }
.desc-label { font-size:12px; color:#9ca3af; font-weight:600; text-transform:uppercase; letter-spacing:0.4px; margin-bottom:8px; }

/* device info grid */
.device-grid { display:grid; grid-template-columns: 1fr 1fr; gap: 10px 16px; margin-top: 4px; }
.device-item { background:#f9fafb; border:1px solid #f1f2f4; border-radius:9px; padding:10px 14px; }
.device-item .dk { font-size:11px; color:#9ca3af; font-weight:600; text-transform:uppercase; letter-spacing:0.3px; margin-bottom:3px; }
.device-item .dv { font-size:13.5px; color:#111827; font-weight:500; }

/* timeline */
.tl { position:relative; padding-left: 6px; }
.tl-item { position:relative; padding-left: 34px; padding-bottom: 26px; }
.tl-item:last-child { padding-bottom: 4px; }
.tl-item::before { /* vertical line */
    content:''; position:absolute; left:11px; top:26px; bottom:0; width:2px; background:#e5e7eb;
}
.tl-item:last-child::before { display:none; }
.tl-dot { position:absolute; left:0; top:0; width:24px; height:24px; border-radius:50%; background:#1a56db; color:#fff; font-size:11px; font-weight:700; display:flex; align-items:center; justify-content:center; }
.tl-head { display:flex; justify-content:space-between; align-items:baseline; gap:10px; flex-wrap:wrap; }
.tl-who { font-weight:600; font-size:13.5px; color:#111827; }
.tl-time { font-size:11.5px; color:#9ca3af; white-space:nowrap; }
.tl-transition { margin: 7px 0; font-size:11.5px; display:flex; align-items:center; gap:6px; }
.tl-note { color:#4b5563; font-size:13px; line-height:1.55; margin-top:4px; }
.badge-mini { font-size:11px; padding:3px 9px; border-radius:20px; font-weight:600; }
.bm-danger{background:#fee2e2;color:#991b1b;} .bm-warning{background:#fef3c7;color:#92400e;}
.bm-success{background:#dcfce7;color:#166534;} .bm-secondary{background:#f3f4f6;color:#374151;}

.empty-state { text-align:center; padding: 38px 10px; color:#9ca3af; }
.empty-state i { font-size: 30px; display:block; margin-bottom: 10px; color:#d1d5db; }
</style>

<div class="td-wrap">

    <div class="td-top">
        <div>
            <div class="td-eyebrow">รายละเอียดการแจ้งซ่อม</div>
            <h4 class="td-title">
                <span class="td-ticketno"><?= htmlspecialchars($ticket['ticket_no']) ?></span>
                <?= htmlspecialchars($ticket['title']) ?>
            </h4>
        </div>
        <a href="<?= BASE_URL ?>/employee/dashboard.php" class="btn-back">
            <i class="bi bi-arrow-left"></i> กลับหน้ารายการ
        </a>
    </div>

    <?php
        $banner_cls = $s === 'open' ? 's-open' : (($s === 'resolved' || $s === 'closed') ? 's-done' : 's-progress');
    ?>
    <div class="status-banner <?= $banner_cls ?>">
        <div>
            <div class="sb-label">สถานะปัจจุบัน</div>
            <div class="sb-value"><?= $status_text[$s] ?></div>
        </div>

        <div class="steps">
            <div class="step <?= $current_step >= 1 ? ($current_step==1 ? 'active' : 'done') : '' ?>">
                <span class="step-dot"></span><span class="step-text">รอดำเนินการ</span>
            </div>
            <div class="step-line <?= $current_step >= 2 ? 'done' : '' ?>"></div>
            <div class="step <?= $current_step >= 2 ? ($current_step==2 ? 'active' : 'done') : '' ?>">
                <span class="step-dot"></span><span class="step-text">กำลังแก้ไข</span>
            </div>
            <div class="step-line <?= $current_step >= 3 ? 'done' : '' ?>"></div>
            <div class="step <?= $current_step >= 3 ? 'done' : '' ?>">
                <span class="step-dot"></span><span class="step-text">เสร็จสิ้น</span>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- รายละเอียด -->
        <div class="col-md-5">
            <div class="card-panel">
                <div class="card-panel-head">
                    <i class="bi bi-clipboard-data"></i><span>ข้อมูล Ticket</span>
                </div>
                <div class="card-panel-body">

                    <div class="info-row">
                        <div class="info-icon"><i class="bi bi-tag"></i></div>
                        <div class="info-key">ประเภท</div>
                        <div class="info-val"><?= $cat_text[$ticket['category']] ?? 'อื่นๆ' ?></div>
                    </div>

                    <div class="info-row">
                        <div class="info-icon"><i class="bi bi-person-gear"></i></div>
                        <div class="info-key">ช่างที่รับ</div>
                        <div class="info-val">
                            <?php if ($ticket['technician']): ?>
                                <?= htmlspecialchars($ticket['technician']) ?>
                                <?php if ($ticket['technician_email']): ?>
                                    <br><small><a href="mailto:<?= htmlspecialchars($ticket['technician_email']) ?>"><?= htmlspecialchars($ticket['technician_email']) ?></a></small>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="color:#9ca3af; font-weight:400;">ยังไม่มอบหมาย</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="info-row">
                        <div class="info-icon"><i class="bi bi-calendar-event"></i></div>
                        <div class="info-key">วันที่แจ้ง</div>
                        <div class="info-val" style="font-weight:400; color:#4b5563;"><?= date('d/m/Y H:i', strtotime($ticket['created_at'])) ?></div>
                    </div>

                    <div class="info-row">
                        <div class="info-icon"><i class="bi bi-arrow-repeat"></i></div>
                        <div class="info-key">อัปเดตล่าสุด</div>
                        <div class="info-val" style="font-weight:400; color:#4b5563;"><?= date('d/m/Y H:i', strtotime($ticket['updated_at'])) ?></div>
                    </div>

                    <div class="desc-label" style="margin-top:18px;">รายละเอียดปัญหา</div>
                    <div class="desc-box"><?= nl2br(htmlspecialchars($ticket['description'])) ?></div>

                    <?php if (!empty($ticket['device_type']) || !empty($ticket['device_name']) || !empty($ticket['serial_no']) || !empty($ticket['building'])): ?>
                    <div class="desc-label" style="margin-top:18px;">ข้อมูลอุปกรณ์ / สถานที่</div>
                    <div class="device-grid">
                        <?php if (!empty($ticket['device_type'])): ?>
                        <div class="device-item">
                            <div class="dk">ประเภทอุปกรณ์</div>
                            <div class="dv"><?= htmlspecialchars($ticket['device_type']) ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($ticket['device_name'])): ?>
                        <div class="device-item">
                            <div class="dk">ชื่ออุปกรณ์</div>
                            <div class="dv"><?= htmlspecialchars($ticket['device_name']) ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($ticket['serial_no'])): ?>
                        <div class="device-item">
                            <div class="dk">Serial / Asset No.</div>
                            <div class="dv"><?= htmlspecialchars($ticket['serial_no']) ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($ticket['building'])): ?>
                        <div class="device-item">
                            <div class="dk">สถานที่</div>
                            <div class="dv">
                                <?= htmlspecialchars($ticket['building']) ?>
                                <?php if (!empty($ticket['floor'])): ?> ชั้น <?= htmlspecialchars($ticket['floor']) ?><?php endif; ?>
                                <?php if (!empty($ticket['room'])): ?> ห้อง <?= htmlspecialchars($ticket['room']) ?><?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($ticket['image_path'])): ?>
                    <div class="desc-label" style="margin-top:18px;">รูปภาพประกอบ</div>
                    <a href="<?= BASE_URL ?>/<?= htmlspecialchars($ticket['image_path']) ?>" target="_blank" style="display:inline-block; margin-top:6px;">
                        <img src="<?= BASE_URL ?>/<?= htmlspecialchars($ticket['image_path']) ?>"
                             style="width:160px; height:160px; object-fit:cover; border-radius:9px; border:1px solid #e5e7eb; display:block;">
                    </a>
                    <?php endif; ?>

                </div>
            </div>
        </div>

        <!-- ประวัติ -->
        <div class="col-md-7">
            <div class="card-panel">
                <div class="card-panel-head">
                    <i class="bi bi-clock-history"></i><span>ประวัติการดำเนินงาน</span>
                </div>
                <div class="card-panel-body">
                    <?php if (empty($updates)): ?>
                        <div class="empty-state">
                            <i class="bi bi-hourglass-split"></i>
                            ยังไม่มีการดำเนินงาน รอช่าง IT รับเรื่อง
                        </div>
                    <?php else: ?>
                        <div class="tl">
                            <?php foreach ($updates as $i => $u): ?>
                            <div class="tl-item">
                                <div class="tl-dot"><?= $i + 1 ?></div>
                                <div class="tl-head">
                                    <span class="tl-who"><?= htmlspecialchars($u['updater']) ?></span>
                                    <span class="tl-time"><?= date('d/m/Y H:i', strtotime($u['created_at'])) ?></span>
                                </div>
                                <?php if ($u['old_status'] && $u['new_status']): ?>
                                <div class="tl-transition">
                                    <span class="badge-mini bm-<?= $status_class[$u['old_status']] ?>"><?= $status_text[$u['old_status']] ?></span>
                                    <i class="bi bi-arrow-right" style="color:#9ca3af; font-size:11px;"></i>
                                    <span class="badge-mini bm-<?= $status_class[$u['new_status']] ?>"><?= $status_text[$u['new_status']] ?></span>
                                </div>
                                <?php endif; ?>
                                <div class="tl-note"><?= nl2br(htmlspecialchars($u['note'])) ?></div>
<?php if (!empty($u['image'])): ?>
    <div style="margin-top:10px;">
        <a href="<?= BASE_URL ?>/uploads/updates/<?= htmlspecialchars($u['image']) ?>" target="_blank">
            <img src="<?= BASE_URL ?>/uploads/updates/<?= htmlspecialchars($u['image']) ?>" 
                 style="max-width:200px; border-radius:8px; border:1px solid #e5e7eb;">
        </a>
    </div>
<?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>