<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
requireRole('admin');

// นับสถิติ
$total_tickets    = $pdo->query("SELECT COUNT(*) FROM tickets")->fetchColumn();
$open_tickets     = $pdo->query("SELECT COUNT(*) FROM tickets WHERE status = 'open'")->fetchColumn();
$inprog_tickets   = $pdo->query("SELECT COUNT(*) FROM tickets WHERE status = 'in_progress'")->fetchColumn();
$resolved_tickets = $pdo->query("SELECT COUNT(*) FROM tickets WHERE status = 'resolved'")->fetchColumn();
$total_users      = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'employee'")->fetchColumn();
$total_techs      = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'technician'")->fetchColumn();

// ดึง ticket ล่าสุด 5 รายการ
$stmt = $pdo->query("
    SELECT t.*, u.fullname as reporter, u2.fullname as technician
    FROM tickets t
    JOIN users u ON t.user_id = u.id
    LEFT JOIN users u2 ON t.assigned_to = u2.id
    ORDER BY t.created_at DESC
    LIMIT 5
");
$recent_tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<style>
.stat-card {
    background: #fff;
    border: 0.5px solid #e5e7eb;
    border-radius: 12px;
    padding: 20px 22px;
    position: relative;
    overflow: hidden;
    transition: box-shadow .15s ease;
}
.stat-card:hover {
    box-shadow: 0 2px 12px rgba(0,0,0,.07);
}
.stat-card .accent-bar {
    position: absolute;
    top: 0; left: 0;
    width: 3px;
    height: 100%;
    border-radius: 3px 0 0 3px;
}
.stat-card .stat-label {
    font-size: 11px;
    font-weight: 500;
    letter-spacing: .06em;
    text-transform: uppercase;
    color: #6b7280;
    margin-bottom: 10px;
}
.stat-card .stat-value {
    font-size: 30px;
    font-weight: 600;
    letter-spacing: -.03em;
    color: #111827;
    line-height: 1;
}
.stat-card .stat-icon {
    position: absolute;
    right: 18px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 24px;
    opacity: .1;
    color: #111827;
}

/* Badge สถานะ */
.badge-status, .badge-priority {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 9px;
    border-radius: 5px;
    font-size: 11px;
    font-weight: 500;
    letter-spacing: .02em;
}
.badge-status .dot, .badge-priority .dot {
    width: 6px; height: 6px;
    border-radius: 50%;
    flex-shrink: 0;
}
/* Status */
.s-open       { background: #fef2f2; color: #991b1b; }
.s-open .dot  { background: #ef4444; }
.s-inprog     { background: #fffbeb; color: #92400e; }
.s-inprog .dot{ background: #f59e0b; }
.s-resolved   { background: #f0fdf4; color: #166534; }
.s-resolved .dot { background: #22c55e; }
.s-closed     { background: #f9fafb; color: #6b7280; }
.s-closed .dot{ background: #9ca3af; }
/* Priority */
.p-urgent     { background: #fef2f2; color: #991b1b; }
.p-urgent .dot{ background: #ef4444; }
.p-high       { background: #fff7ed; color: #9a3412; }
.p-high .dot  { background: #f97316; }
.p-medium     { background: #eff6ff; color: #1e40af; }
.p-medium .dot{ background: #3b82f6; }
.p-low        { background: #f9fafb; color: #6b7280; }
.p-low .dot   { background: #9ca3af; }

/* ตาราง */
.table-card {
    background: #fff;
    border: 0.5px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
}
.table-card .card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    border-bottom: 0.5px solid #e5e7eb;
}
.table-card .card-head h5 {
    font-size: 14px;
    font-weight: 600;
    color: #111827;
    margin: 0;
}
.table-card .view-all {
    font-size: 12px;
    color: #6b7280;
    text-decoration: none;
    border: 0.5px solid #d1d5db;
    border-radius: 6px;
    padding: 4px 10px;
    transition: background .12s;
}
.table-card .view-all:hover { background: #f9fafb; }
.tbl thead th {
    font-size: 11px;
    font-weight: 600;
    letter-spacing: .06em;
    text-transform: uppercase;
    color: #6b7280;
    background: #f9fafb;
    border-bottom: 0.5px solid #e5e7eb;
    padding: 10px 16px;
    white-space: nowrap;
}
.tbl tbody td {
    padding: 12px 16px;
    font-size: 13px;
    color: #374151;
    border-bottom: 0.5px solid #f3f4f6;
    vertical-align: middle;
}
.tbl tbody tr:last-child td { border-bottom: none; }
.tbl tbody tr:hover td { background: #f9fafb; }
.ticket-no {
    font-family: monospace;
    font-size: 11px;
    background: #f3f4f6;
    border: 0.5px solid #e5e7eb;
    color: #6b7280;
    padding: 2px 7px;
    border-radius: 4px;
}
.no-assign {
    color: #9ca3af;
    font-style: italic;
    font-size: 12px;
}

/* Page header */
.page-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 24px;
    padding-bottom: 18px;
    border-bottom: 0.5px solid #e5e7eb;
}
.page-header h4 {
    font-size: 18px;
    font-weight: 600;
    color: #111827;
    margin: 0 0 4px;
}
.page-header p {
    font-size: 13px;
    color: #6b7280;
    margin: 0;
}
.role-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: #f3f4f6;
    border: 0.5px solid #e5e7eb;
    border-radius: 6px;
    padding: 5px 11px;
    font-size: 12px;
    font-weight: 500;
    color: #374151;
}
</style>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h4>แดชบอร์ด</h4>
        <p>ยินดีต้อนรับ, <?= htmlspecialchars($_SESSION['fullname']) ?></p>
    </div>
    <span class="role-badge">
        <i class="bi bi-shield-check"></i> Admin
    </span>
</div>

<!-- สถิติแถวแรก -->
<div class="row g-3 mb-3">
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="accent-bar" style="background:#3b82f6"></div>
            <div class="stat-label">Ticket ทั้งหมด</div>
            <div class="stat-value"><?= $total_tickets ?></div>
            <i class="bi bi-ticket stat-icon"></i>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="accent-bar" style="background:#ef4444"></div>
            <div class="stat-label">รอดำเนินการ</div>
            <div class="stat-value"><?= $open_tickets ?></div>
            <i class="bi bi-exclamation-circle stat-icon"></i>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="accent-bar" style="background:#f59e0b"></div>
            <div class="stat-label">กำลังดำเนินการ</div>
            <div class="stat-value"><?= $inprog_tickets ?></div>
            <i class="bi bi-arrow-repeat stat-icon"></i>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="accent-bar" style="background:#22c55e"></div>
            <div class="stat-label">แก้ไขแล้ว</div>
            <div class="stat-value"><?= $resolved_tickets ?></div>
            <i class="bi bi-check-circle stat-icon"></i>
        </div>
    </div>
</div>

<!-- สถิติแถวสอง -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="stat-card">
            <div class="accent-bar" style="background:#0d9488"></div>
            <div class="stat-label">พนักงานทั้งหมด</div>
            <div class="stat-value"><?= $total_users ?></div>
            <i class="bi bi-people stat-icon"></i>
        </div>
    </div>
    <div class="col-md-6">
        <div class="stat-card">
            <div class="accent-bar" style="background:#6b7280"></div>
            <div class="stat-label">ช่าง IT ทั้งหมด</div>
            <div class="stat-value"><?= $total_techs ?></div>
            <i class="bi bi-tools stat-icon"></i>
        </div>
    </div>
</div>

<!-- Ticket ล่าสุด -->
<div class="table-card">
    <div class="card-head">
        <h5><i class="bi bi-list-ul me-2" style="opacity:.5"></i>Ticket ล่าสุด</h5>
        <a href="tickets.php" class="view-all">ดูทั้งหมด <i class="bi bi-arrow-right"></i></a>
    </div>
    <div class="table-responsive">
        <table class="table tbl mb-0">
            <thead>
                <tr>
                    <th>รหัส</th>
                    <th>หัวข้อ</th>
                    <th>ผู้แจ้ง</th>
                    <th>ช่างที่รับ</th>
                    <th>ความเร่งด่วน</th>
                    <th>สถานะ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_tickets as $ticket): ?>
                <tr>
                    <td><span class="ticket-no"><?= htmlspecialchars($ticket['ticket_no']) ?></span></td>
                    <td><?= htmlspecialchars($ticket['title']) ?></td>
                    <td><?= htmlspecialchars($ticket['reporter']) ?></td>
                    <td>
                        <?php if ($ticket['technician']): ?>
                            <?= htmlspecialchars($ticket['technician']) ?>
                        <?php else: ?>
                            <span class="no-assign">ยังไม่มอบหมาย</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php
                        $p_class = ['low' => 'p-low', 'medium' => 'p-medium', 'high' => 'p-high', 'urgent' => 'p-urgent'];
                        $p_text  = ['low' => 'ต่ำ', 'medium' => 'ปานกลาง', 'high' => 'สูง', 'urgent' => 'เร่งด่วน'];
                        $p_dot   = ['low' => true, 'medium' => true, 'high' => true, 'urgent' => true];
                        $pc = $p_class[$ticket['priority']] ?? 'p-low';
                        $pt = $p_text[$ticket['priority']]  ?? $ticket['priority'];
                        ?>
                        <span class="badge-priority <?= $pc ?>">
                            <?php if (in_array($ticket['priority'], ['urgent','high'])): ?>
                                <span class="dot"></span>
                            <?php endif; ?>
                            <?= $pt ?>
                        </span>
                    </td>
                    <td>
                        <?php
                        $s_class = ['open' => 's-open', 'in_progress' => 's-inprog', 'resolved' => 's-resolved', 'closed' => 's-closed'];
                        $s_text  = ['open' => 'รอดำเนินการ', 'in_progress' => 'กำลังแก้ไข', 'resolved' => 'แก้ไขแล้ว', 'closed' => 'ปิดแล้ว'];
                        $sc = $s_class[$ticket['status']] ?? 's-closed';
                        $st = $s_text[$ticket['status']]  ?? $ticket['status'];
                        ?>
                        <span class="badge-status <?= $sc ?>">
                            <span class="dot"></span>
                            <?= $st ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($recent_tickets)): ?>
                <tr>
                    <td colspan="6" class="text-center py-4" style="color:#9ca3af;font-size:13px">
                        <i class="bi bi-inbox me-1"></i> ยังไม่มี Ticket
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>