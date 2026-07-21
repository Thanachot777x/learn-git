<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
requireRole('technician');

// ตัวกรองสถานะ (all, open, in_progress, resolved, closed)
$filter = $_GET['status'] ?? 'all';
$allowed_filters = ['all', 'open', 'in_progress', 'resolved', 'closed'];
if (!in_array($filter, $allowed_filters)) $filter = 'all';

// ดึง ticket ทั้งหมดที่ "ฉัน" รับผิดชอบ
$sql = "
    SELECT t.*, u.fullname as reporter, u.department
    FROM tickets t
    JOIN users u ON t.user_id = u.id
    WHERE t.assigned_to = :uid
";
if ($filter !== 'all') {
    $sql .= " AND t.status = :status";
}
$sql .= " ORDER BY FIELD(t.priority, 'urgent', 'high', 'medium', 'low'), t.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':uid', $_SESSION['user_id'], PDO::PARAM_INT);
if ($filter !== 'all') {
    $stmt->bindValue(':status', $filter);
}
$stmt->execute();
$my_tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// นับสถิติของฉัน (ทุกสถานะ ไม่ขึ้นกับ filter)
$count_stmt = $pdo->prepare("
    SELECT status, COUNT(*) as cnt
    FROM tickets
    WHERE assigned_to = :uid
    GROUP BY status
");
$count_stmt->execute([':uid' => $_SESSION['user_id']]);
$counts = ['open' => 0, 'in_progress' => 0, 'resolved' => 0, 'closed' => 0];
foreach ($count_stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $counts[$row['status']] = (int)$row['cnt'];
}
$total_all = array_sum($counts);
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
.stat-card:hover { box-shadow: 0 2px 12px rgba(0,0,0,.07); }
.stat-card .accent-bar {
    position: absolute;
    top: 0; left: 0;
    width: 3px; height: 100%;
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
    right: 18px; top: 50%;
    transform: translateY(-50%);
    font-size: 24px;
    opacity: .1;
    color: #111827;
}

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
    border-radius: 50%; flex-shrink: 0;
}
.s-open      { background: #fef2f2; color: #991b1b; }
.s-open .dot { background: #ef4444; }
.s-inprog      { background: #fffbeb; color: #92400e; }
.s-inprog .dot { background: #f59e0b; }
.s-resolved      { background: #f0fdf4; color: #166534; }
.s-resolved .dot { background: #22c55e; }
.s-closed      { background: #f9fafb; color: #6b7280; }
.s-closed .dot { background: #9ca3af; }

.p-urgent      { background: #fef2f2; color: #991b1b; }
.p-urgent .dot { background: #ef4444; }
.p-high      { background: #fff7ed; color: #9a3412; }
.p-high .dot { background: #f97316; }
.p-medium      { background: #eff6ff; color: #1e40af; }
.p-medium .dot { background: #3b82f6; }
.p-low      { background: #f9fafb; color: #6b7280; }
.p-low .dot { background: #9ca3af; }

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
    flex-wrap: wrap;
    gap: 10px;
}
.table-card .card-head h5 {
    font-size: 14px;
    font-weight: 600;
    color: #111827;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}
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
    white-space: nowrap;
}
.sub-text {
    font-size: 11px;
    color: #9ca3af;
    margin-top: 2px;
}
.btn-update {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 12px;
    font-weight: 500;
    padding: 5px 12px;
    border-radius: 6px;
    border: 0.5px solid #bfdbfe;
    background: #eff6ff;
    color: #1e40af;
    text-decoration: none;
    transition: background .12s;
    white-space: nowrap;
}
.btn-update:hover { background: #dbeafe; color: #1e40af; }

/* Filter tabs */
.filter-tabs {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}
.filter-tab {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    border-radius: 7px;
    font-size: 12px;
    font-weight: 500;
    text-decoration: none;
    border: 0.5px solid #e5e7eb;
    color: #6b7280;
    background: #fff;
    transition: all .12s;
}
.filter-tab:hover { background: #f9fafb; color: #374151; }
.filter-tab.active {
    background: #111827;
    border-color: #111827;
    color: #fff;
}
.filter-tab .tab-count {
    background: rgba(0,0,0,.08);
    border-radius: 8px;
    padding: 0 6px;
    font-size: 11px;
}
.filter-tab.active .tab-count { background: rgba(255,255,255,.2); }

/* Page header */
.page-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 24px;
    padding-bottom: 18px;
    border-bottom: 0.5px solid #e5e7eb;
    flex-wrap: wrap;
    gap: 12px;
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
.empty-state {
    text-align: center;
    padding: 32px 16px;
    color: #9ca3af;
    font-size: 13px;
}
.empty-state i { font-size: 28px; display: block; margin-bottom: 8px; opacity: .5; }
</style>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h4>Ticket ของฉัน</h4>
        <p>งานทั้งหมดที่มอบหมายให้ <?= htmlspecialchars($_SESSION['fullname']) ?></p>
    </div>
</div>

<!-- สถิติ -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="accent-bar" style="background:#3b82f6"></div>
            <div class="stat-label">งานทั้งหมด</div>
            <div class="stat-value"><?= $total_all ?></div>
            <i class="bi bi-briefcase stat-icon"></i>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="accent-bar" style="background:#ef4444"></div>
            <div class="stat-label">รอดำเนินการ</div>
            <div class="stat-value"><?= $counts['open'] ?></div>
            <i class="bi bi-exclamation-circle stat-icon"></i>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="accent-bar" style="background:#f59e0b"></div>
            <div class="stat-label">กำลังแก้ไข</div>
            <div class="stat-value"><?= $counts['in_progress'] ?></div>
            <i class="bi bi-arrow-repeat stat-icon"></i>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="accent-bar" style="background:#22c55e"></div>
            <div class="stat-label">แก้ไขแล้ว</div>
            <div class="stat-value"><?= $counts['resolved'] ?></div>
            <i class="bi bi-check-circle stat-icon"></i>
        </div>
    </div>
</div>

<!-- ตารางงานของฉัน -->
<div class="table-card">
    <div class="card-head">
        <h5><i class="bi bi-person-check" style="opacity:.5"></i> รายการ Ticket ของฉัน</h5>
        <div class="filter-tabs">
            <a href="?status=all" class="filter-tab <?= $filter === 'all' ? 'active' : '' ?>">
                ทั้งหมด <span class="tab-count"><?= $total_all ?></span>
            </a>
            <a href="?status=open" class="filter-tab <?= $filter === 'open' ? 'active' : '' ?>">
                รอดำเนินการ <span class="tab-count"><?= $counts['open'] ?></span>
            </a>
            <a href="?status=in_progress" class="filter-tab <?= $filter === 'in_progress' ? 'active' : '' ?>">
                กำลังแก้ไข <span class="tab-count"><?= $counts['in_progress'] ?></span>
            </a>
            <a href="?status=resolved" class="filter-tab <?= $filter === 'resolved' ? 'active' : '' ?>">
                แก้ไขแล้ว <span class="tab-count"><?= $counts['resolved'] ?></span>
            </a>
            <a href="?status=closed" class="filter-tab <?= $filter === 'closed' ? 'active' : '' ?>">
                ปิดแล้ว <span class="tab-count"><?= $counts['closed'] ?></span>
            </a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table tbl mb-0">
            <thead>
                <tr>
                    <th>Ticket</th>
                    <th>หัวข้อ</th>
                    <th>ผู้แจ้ง / แผนก</th>
                    <th>ประเภท</th>
                    <th>ความเร่งด่วน</th>
                    <th>สถานะ</th>
                    <th>วันที่แจ้ง</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $cat_text = ['hardware' => 'Hardware', 'software' => 'Software', 'network' => 'Network', 'other' => 'อื่นๆ'];
                $p_class  = ['low' => 'p-low', 'medium' => 'p-medium', 'high' => 'p-high', 'urgent' => 'p-urgent'];
                $p_text   = ['low' => 'ต่ำ', 'medium' => 'ปานกลาง', 'high' => 'สูง', 'urgent' => 'เร่งด่วน'];
                $s_class  = ['open' => 's-open', 'in_progress' => 's-inprog', 'resolved' => 's-resolved', 'closed' => 's-closed'];
                $s_text   = ['open' => 'รอดำเนินการ', 'in_progress' => 'กำลังแก้ไข', 'resolved' => 'แก้ไขแล้ว', 'closed' => 'ปิดแล้ว'];
                ?>
                <?php if (empty($my_tickets)): ?>
                <tr>
                    <td colspan="8">
                        <div class="empty-state">
                            <i class="bi bi-check-all"></i>
                            ไม่มี Ticket ในหมวดนี้
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($my_tickets as $t):
                    $pc = $p_class[$t['priority']] ?? 'p-low';
                    $pt = $p_text[$t['priority']]  ?? $t['priority'];
                    $sc = $s_class[$t['status']]   ?? 's-closed';
                    $st = $s_text[$t['status']]    ?? $t['status'];
                ?>
                <tr>
                    <td><span class="ticket-no"><?= htmlspecialchars($t['ticket_no']) ?></span></td>
                    <td><?= htmlspecialchars($t['title']) ?></td>
                    <td>
                        <?= htmlspecialchars($t['reporter']) ?>
                        <div class="sub-text"><?= htmlspecialchars($t['department']) ?></div>
                    </td>
                    <td><?= $cat_text[$t['category']] ?? 'อื่นๆ' ?></td>
                    <td>
                        <span class="badge-priority <?= $pc ?>">
                            <span class="dot"></span><?= $pt ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge-status <?= $sc ?>">
                            <span class="dot"></span><?= $st ?>
                        </span>
                    </td>
                    <td style="white-space:nowrap;color:#6b7280;font-size:12px">
                        <?= date('d/m/Y', strtotime($t['created_at'])) ?>
                        <div class="sub-text"><?= date('H:i', strtotime($t['created_at'])) ?></div>
                    </td>
                    <td>
                        <a href="<?= BASE_URL ?>/technician/update_ticket.php?id=<?= $t['id'] ?>" class="btn-update">
                            <i class="bi bi-pencil-square"></i> อัปเดต
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>