<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
requireRole('manager');

// สรุปจำนวน ticket แยกตามสถานะ
$summary = $pdo->query("
    SELECT status, COUNT(*) as total
    FROM tickets
    GROUP BY status
")->fetchAll(PDO::FETCH_KEY_PAIR);

$open_count        = $summary['open']        ?? 0;
$in_progress_count = $summary['in_progress'] ?? 0;
$resolved_count    = $summary['resolved']    ?? 0;
$closed_count      = $summary['closed']      ?? 0;
$total_count       = $open_count + $in_progress_count + $resolved_count + $closed_count;

// จำนวนที่ยังไม่มอบหมาย
$unassigned_count = $pdo->query("SELECT COUNT(*) FROM tickets WHERE assigned_to IS NULL")->fetchColumn();

// ticket ที่ยังไม่มอบหมาย (แสดง 10 รายการล่าสุด เรียงตาม priority)
$unassigned = $pdo->query("
    SELECT t.*, u.fullname as reporter, u.department
    FROM tickets t
    JOIN users u ON t.user_id = u.id
    WHERE t.assigned_to IS NULL
    ORDER BY FIELD(t.priority,'urgent','high','medium','low'), t.created_at DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

$priority_class = ['low'=>'secondary','medium'=>'info','high'=>'warning','urgent'=>'danger'];
$priority_text  = ['low'=>'ต่ำ','medium'=>'ปานกลาง','high'=>'สูง','urgent'=>'เร่งด่วน'];
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<style>
.stat-card { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:20px 22px; position:relative; overflow:hidden; }
.stat-card .accent { position:absolute; top:0; left:0; width:4px; height:100%; border-radius:12px 0 0 12px; }
.stat-card .s-label { font-size:12px; color:#6b7280; font-weight:500; margin-bottom:6px; }
.stat-card .s-value { font-size:28px; font-weight:700; line-height:1; margin-bottom:4px; }
.stat-card .s-icon { position:absolute; right:18px; top:50%; transform:translateY(-50%); font-size:28px; opacity:0.10; }
.stat-card .s-sub { font-size:11px; color:#9ca3af; }
.c-danger   { color:#dc2626; } .a-danger   { background:#dc2626; }
.c-warning  { color:#d97706; } .a-warning  { background:#d97706; }
.c-success  { color:#16a34a; } .a-success  { background:#16a34a; }
.c-secondary{ color:#6b7280; } .a-secondary{ background:#6b7280; }
.c-primary  { color:#1a56db; } .a-primary  { background:#1a56db; }
.unassigned-badge { background:#fef2f2; color:#dc2626; border:1px solid #fecaca; font-size:12px; padding:3px 10px; border-radius:20px; font-weight:600; }
</style>

<div class="d-flex justify-content-between align-items-center mb-4" style="flex-wrap:wrap; gap:10px;">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-speedometer2 me-2"></i>Dashboard ผู้จัดการ</h4>
        <p class="text-muted mb-0" style="font-size:13px;">สวัสดี, <?= htmlspecialchars($_SESSION['fullname']) ?> — ภาพรวมสถานะ Ticket ทั้งหมด</p>
    </div>
    <a href="<?= BASE_URL ?>/manager/assign_tickets.php" class="btn btn-primary btn-sm">
        <i class="bi bi-person-check me-1"></i>ไปหน้ามอบหมายงาน
    </a>
</div>

<!-- stat cards -->
<div class="row g-3 mb-4">
    <div class="col-md col-6">
        <div class="stat-card">
            <div class="accent a-primary"></div>
            <div class="s-label">Ticket ทั้งหมด</div>
            <div class="s-value c-primary"><?= $total_count ?></div>
            <div class="s-sub">รายการในระบบ</div>
            <i class="bi bi-ticket-detailed s-icon"></i>
        </div>
    </div>
    <div class="col-md col-6">
        <div class="stat-card">
            <div class="accent a-danger"></div>
            <div class="s-label">รอดำเนินการ</div>
            <div class="s-value c-danger"><?= $open_count ?></div>
            <div class="s-sub">ยังไม่มีช่างรับงาน</div>
            <i class="bi bi-hourglass-split s-icon"></i>
        </div>
    </div>
    <div class="col-md col-6">
        <div class="stat-card">
            <div class="accent a-warning"></div>
            <div class="s-label">กำลังแก้ไข</div>
            <div class="s-value c-warning"><?= $in_progress_count ?></div>
            <div class="s-sub">ช่างกำลังดำเนินการ</div>
            <i class="bi bi-tools s-icon"></i>
        </div>
    </div>
    <div class="col-md col-6">
        <div class="stat-card">
            <div class="accent a-success"></div>
            <div class="s-label">แก้ไขแล้ว</div>
            <div class="s-value c-success"><?= $resolved_count ?></div>
            <div class="s-sub">รอปิดงาน</div>
            <i class="bi bi-check-circle s-icon"></i>
        </div>
    </div>
    <div class="col-md col-6">
        <div class="stat-card">
            <div class="accent a-secondary"></div>
            <div class="s-label">ปิดแล้ว</div>
            <div class="s-value c-secondary"><?= $closed_count ?></div>
            <div class="s-sub">เสร็จสมบูรณ์</div>
            <i class="bi bi-archive s-icon"></i>
        </div>
    </div>
</div>

<!-- Ticket ที่ยังไม่มอบหมาย -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 pt-3 pb-2 d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-3">
            <h6 class="fw-bold mb-0"><i class="bi bi-inbox me-2 text-danger"></i>Ticket ที่ยังไม่มอบหมาย</h6>
            <?php if ($unassigned_count > 0): ?>
            <span class="unassigned-badge"><?= $unassigned_count ?> รายการ</span>
            <?php endif; ?>
        </div>
        <a href="<?= BASE_URL ?>/manager/assign_tickets.php?status=open" class="btn btn-sm btn-outline-primary">
            ดูทั้งหมด <i class="bi bi-arrow-right ms-1"></i>
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle" style="font-size:13px;">
                <thead class="table-light">
                    <tr>
                        <th>Ticket No.</th>
                        <th>หัวข้อ</th>
                        <th>ผู้แจ้ง / แผนก</th>
                        <th>ความเร่งด่วน</th>
                        <th>วันที่แจ้ง</th>
                        <th>มอบหมาย</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($unassigned as $t):
                        $prio    = $t['priority'] ?? 'low';
                        $p_class = $priority_class[$prio] ?? 'secondary';
                        $p_text  = $priority_text[$prio]  ?? '-';
                    ?>
                    <tr>
                        <td><code><?= htmlspecialchars($t['ticket_no']) ?></code></td>
                        <td><?= htmlspecialchars($t['title']) ?></td>
                        <td>
                            <?= htmlspecialchars($t['reporter']) ?>
                            <br><small class="text-muted"><?= htmlspecialchars($t['department'] ?? '-') ?></small>
                        </td>
                        <td><span class="badge bg-<?= $p_class ?>"><?= $p_text ?></span></td>
                        <td><small><?= date('d/m/Y H:i', strtotime($t['created_at'])) ?></small></td>
                        <td>
                            <a href="<?= BASE_URL ?>/manager/assign_tickets.php" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-person-check"></i> มอบหมาย
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($unassigned)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            <i class="bi bi-check-circle fs-3 d-block mb-2 text-success"></i>
                            ไม่มี Ticket ที่ยังไม่มอบหมาย 🎉
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>