<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
requireRole('technician');

// ดึง ticket ทั้งหมดที่มีช่างรับแล้ว (ทุกช่าง)
$stmt = $pdo->query("
    SELECT t.*, u.fullname as reporter, u.department, tech.fullname as tech_name
    FROM tickets t
    JOIN users u ON t.user_id = u.id
    LEFT JOIN users tech ON t.assigned_to = tech.id
    WHERE t.assigned_to IS NOT NULL
    ORDER BY
        FIELD(t.priority, 'urgent', 'high', 'medium', 'low'),
        t.created_at DESC
");
$all_assigned = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึง ticket ที่ยังไม่มีช่างรับ
$unassigned = $pdo->query("
    SELECT t.*, u.fullname as reporter, u.department
    FROM tickets t
    JOIN users u ON t.user_id = u.id
    WHERE t.assigned_to IS NULL AND t.status = 'open'
    ORDER BY FIELD(t.priority, 'urgent', 'high', 'medium', 'low'), t.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// นับสถิติ (เฉพาะของตัวเอง)
$my_tickets = array_filter($all_assigned, fn($t) => (int)$t['assigned_to'] === (int)$_SESSION['user_id']);
$total    = count($my_tickets);
$open     = count(array_filter($my_tickets, fn($t) => $t['status'] === 'open'));
$inprog   = count(array_filter($my_tickets, fn($t) => $t['status'] === 'in_progress'));
$resolved = count(array_filter($my_tickets, fn($t) => $t['status'] === 'resolved'));
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

.badge-mine {
    display: inline-flex;
    align-items: center;
    background: #eff6ff;
    color: #1e40af;
    border: 0.5px solid #bfdbfe;
    border-radius: 4px;
    font-size: 10px;
    font-weight: 500;
    padding: 1px 6px;
    margin-left: 5px;
}

/* ตาราง */
.table-card {
    background: #fff;
    border: 0.5px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
}
.table-card.warn-card {
    border-color: #fcd34d;
}
.table-card .card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    border-bottom: 0.5px solid #e5e7eb;
}
.warn-card .card-head { border-bottom-color: #fde68a; background: #fffdf5; }
.table-card .card-head h5 {
    font-size: 14px;
    font-weight: 600;
    color: #111827;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}
.warn-card .card-head h5 { color: #92400e; }
.count-badge {
    display: inline-flex;
    align-items: center;
    background: #fef3c7;
    color: #92400e;
    border: 0.5px solid #fcd34d;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 600;
    padding: 1px 8px;
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
    border: 0.5px solid #d1d5db;
    background: #fff;
    color: #374151;
    text-decoration: none;
    transition: background .12s;
    white-space: nowrap;
}
.btn-update:hover { background: #f3f4f6; color: #111827; }
.btn-update.primary {
    background: #eff6ff;
    border-color: #bfdbfe;
    color: #1e40af;
}
.btn-update.primary:hover { background: #dbeafe; }
.btn-accept {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 12px;
    font-weight: 500;
    padding: 5px 12px;
    border-radius: 6px;
    border: 0.5px solid #bbf7d0;
    background: #f0fdf4;
    color: #166534;
    cursor: pointer;
    transition: background .12s;
    white-space: nowrap;
}
.btn-accept:hover { background: #dcfce7; }

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
        <h4>แดชบอร์ด</h4>
        <p>ยินดีต้อนรับ, <?= htmlspecialchars($_SESSION['fullname']) ?></p>
    </div>
    <span class="role-badge">
        <i class="bi bi-tools"></i> ช่าง IT
    </span>
</div>

<!-- สถิติ (เฉพาะของฉัน) -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="accent-bar" style="background:#3b82f6"></div>
            <div class="stat-label">งานของฉันทั้งหมด</div>
            <div class="stat-value"><?= $total ?></div>
            <i class="bi bi-briefcase stat-icon"></i>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="accent-bar" style="background:#ef4444"></div>
            <div class="stat-label">รอดำเนินการ</div>
            <div class="stat-value"><?= $open ?></div>
            <i class="bi bi-exclamation-circle stat-icon"></i>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="accent-bar" style="background:#f59e0b"></div>
            <div class="stat-label">กำลังแก้ไข</div>
            <div class="stat-value"><?= $inprog ?></div>
            <i class="bi bi-arrow-repeat stat-icon"></i>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="accent-bar" style="background:#22c55e"></div>
            <div class="stat-label">แก้ไขแล้ว</div>
            <div class="stat-value"><?= $resolved ?></div>
            <i class="bi bi-check-circle stat-icon"></i>
        </div>
    </div>
</div>

<!-- งานทั้งหมด (ของทุกช่าง) -->
<div class="table-card mb-4">
    <div class="card-head">
        <h5><i class="bi bi-list-ul" style="opacity:.5"></i> งานทั้งหมด (ของทุกช่าง)</h5>
    </div>
    <div class="table-responsive">
        <table class="table tbl mb-0">
            <thead>
                <tr>
                    <th>Ticket</th>
                    <th>หัวข้อ</th>
                    <th>ผู้แจ้ง / แผนก</th>
                    <th>ประเภท</th>
                    <th>ช่างผู้รับงาน</th>
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
                $has_row = false;
                foreach ($all_assigned as $t):
                    if ($t['status'] === 'closed') continue;
                    $has_row = true;
                    $is_mine = (int)$t['assigned_to'] === (int)$_SESSION['user_id'];
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
                        <?= htmlspecialchars($t['tech_name']) ?>
                        <?php if ($is_mine): ?>
                            <span class="badge-mine">ของฉัน</span>
                        <?php endif; ?>
                    </td>
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
                        <?php if ($is_mine): ?>
                        <a href="<?= BASE_URL ?>/technician/update_ticket.php?id=<?= $t['id'] ?>" class="btn-update primary">
                            <i class="bi bi-pencil-square"></i> อัปเดต
                        </a>
                        <?php else: ?>
                        <a href="<?= BASE_URL ?>/technician/update_ticket.php?id=<?= $t['id'] ?>" class="btn-update">
                            <i class="bi bi-eye"></i> ดู
                        </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$has_row): ?>
                <tr>
                    <td colspan="9">
                        <div class="empty-state">
                            <i class="bi bi-check-all"></i>
                            ไม่มีงานที่มอบหมาย
                        </div>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Ticket รอรับงาน -->
<?php if (!empty($unassigned)): ?>
<div class="table-card warn-card">
    <div class="card-head">
        <h5>
            <i class="bi bi-inbox"></i>
            Ticket รอช่างรับงาน
            <span class="count-badge"><?= count($unassigned) ?></span>
        </h5>
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
                    <th>วันที่แจ้ง</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($unassigned as $t):
                    $pc = $p_class[$t['priority']] ?? 'p-low';
                    $pt = $p_text[$t['priority']]  ?? $t['priority'];
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
                    <td style="white-space:nowrap;color:#6b7280;font-size:12px">
                        <?= date('d/m/Y', strtotime($t['created_at'])) ?>
                        <div class="sub-text"><?= date('H:i', strtotime($t['created_at'])) ?></div>
                    </td>
                    <td>
                        <form method="POST" action="<?= BASE_URL ?>/technician/update_ticket.php">
                            <input type="hidden" name="ticket_id" value="<?= $t['id'] ?>">
                            <input type="hidden" name="action" value="accept">
                            <button type="submit" class="btn-accept">
                                <i class="bi bi-hand-thumbs-up"></i> รับงาน
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>