<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
requireRole('admin');

// นับสถิติ
$total_tickets    = $pdo->query("SELECT COUNT(*) FROM tickets")->fetchColumn();
$open_tickets     = $pdo->query("SELECT COUNT(*) FROM tickets WHERE status = 'open'")->fetchColumn();
$inprog_tickets   = $pdo->query("SELECT COUNT(*) FROM tickets WHERE status = 'in_progress'")->fetchColumn();
$resolved_tickets = $pdo->query("SELECT COUNT(*) FROM tickets WHERE status = 'resolved'")->fetchColumn();
$closed_tickets   = $pdo->query("SELECT COUNT(*) FROM tickets WHERE status = 'closed'")->fetchColumn();

$total_users      = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'employee'")->fetchColumn();
$total_techs      = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'technician'")->fetchColumn();

// สรุปสถิติหมวดหมู่ปัญหาสำหรับ Chart.js
$category_summary = $pdo->query("
    SELECT category, COUNT(*) as total
    FROM tickets
    GROUP BY category
")->fetchAll(PDO::FETCH_KEY_PAIR);

$cat_hw = $category_summary['hardware'] ?? 0;
$cat_sw = $category_summary['software'] ?? 0;
$cat_nw = $category_summary['network']  ?? 0;
$cat_ot = $category_summary['other']    ?? 0;

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

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
.stat-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 20px 22px;
    position: relative;
    overflow: hidden;
    transition: box-shadow .15s ease;
}
.stat-card:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,.06);
}
.stat-card .accent-bar {
    position: absolute;
    top: 0; left: 0;
    width: 4px;
    height: 100%;
    border-radius: 3px 0 0 3px;
}
.stat-card .stat-label {
    font-size: 11.5px;
    font-weight: 600;
    letter-spacing: .04em;
    text-transform: uppercase;
    color: #64748b;
    margin-bottom: 8px;
}
.stat-card .stat-value {
    font-size: 30px;
    font-weight: 700;
    color: #0f172a;
    line-height: 1;
}
.stat-card .stat-icon {
    position: absolute;
    right: 18px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 26px;
    opacity: .12;
    color: #0f172a;
}

.chart-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(15,23,42,0.03);
}

/* Badge สถานะ */
.badge-status, .badge-priority {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 11.5px;
    font-weight: 600;
}
.badge-status .dot, .badge-priority .dot {
    width: 6px; height: 6px;
    border-radius: 50%;
    flex-shrink: 0;
}
/* Status */
.s-open       { background: #fef2f2; color: #991b1b; }
.s-open .dot  { background: #ef4444; }
.s-inprog     { background: #eff6ff; color: #1e40af; }
.s-inprog .dot{ background: #3b82f6; }
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
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
}
.table-card .card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    border-bottom: 1px solid #e2e8f0;
}
.table-card .card-head h5 {
    font-size: 14px;
    font-weight: 600;
    color: #0f172a;
    margin: 0;
}
.table-card .view-all {
    font-size: 12px;
    color: #64748b;
    text-decoration: none;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    padding: 4px 10px;
    transition: background .12s;
}
.table-card .view-all:hover { background: #f8fafc; color: #0f172a; }
.tbl thead th {
    font-size: 11.5px;
    font-weight: 600;
    letter-spacing: .04em;
    text-transform: uppercase;
    color: #64748b;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    padding: 11px 16px;
    white-space: nowrap;
}
.tbl tbody td {
    padding: 12px 16px;
    font-size: 13px;
    color: #334155;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}
.tbl tbody tr:last-child td { border-bottom: none; }
.tbl tbody tr:hover td { background: #f8fafc; }
.ticket-no {
    font-family: monospace;
    font-size: 12px;
    background: #eff6ff;
    border: 1px solid #dbeafe;
    color: #1e40af;
    padding: 3px 8px;
    border-radius: 6px;
    font-weight: 600;
}
.no-assign {
    color: #94a3b8;
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
    border-bottom: 1px solid #e2e8f0;
}
.page-header h4 {
    font-size: 20px;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 4px;
}
.page-header p {
    font-size: 13px;
    color: #64748b;
    margin: 0;
}
.role-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #eff6ff;
    border: 1px solid #dbeafe;
    border-radius: 8px;
    padding: 6px 12px;
    font-size: 12.5px;
    font-weight: 600;
    color: #1d4ed8;
}
</style>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h4>แดชบอร์ดภาพรวมระบบ</h4>
        <p>สวัสดี, <?= htmlspecialchars($_SESSION['fullname']) ?> — รายงานสถิติและสถานะภาพรวมผู้ดูแลระบบ</p>
    </div>
    <span class="role-badge">
        <i class="bi bi-shield-check"></i> ผู้ดูแลระบบ (Admin)
    </span>
</div>

<!-- สถิติแถวแรก -->
<div class="row g-3 mb-3">
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="accent-bar" style="background:#3b82f6"></div>
            <div class="stat-label">Ticket ทั้งหมด</div>
            <div class="stat-value"><?= $total_tickets ?></div>
            <i class="bi bi-ticket-detailed stat-icon"></i>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="accent-bar" style="background:#ef4444"></div>
            <div class="stat-label">รอดำเนินการ</div>
            <div class="stat-value"><?= $open_tickets ?></div>
            <i class="bi bi-hourglass-split stat-icon"></i>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="accent-bar" style="background:#f59e0b"></div>
            <div class="stat-label">กำลังดำเนินการ</div>
            <div class="stat-value"><?= $inprog_tickets ?></div>
            <i class="bi bi-tools stat-icon"></i>
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

<!-- สถิติแถวสอง (ผู้ใช้งาน) -->
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
            <div class="accent-bar" style="background:#6366f1"></div>
            <div class="stat-label">ช่าง IT ทั้งหมด</div>
            <div class="stat-value"><?= $total_techs ?></div>
            <i class="bi bi-person-workspace stat-icon"></i>
        </div>
    </div>
</div>

<!-- Charts Row (Chart.js สถิติเชิงลึก) -->
<div class="row g-4 mb-4">
    <!-- Chart 1: สัดส่วนหมวดหมู่ปัญหา -->
    <div class="col-md-6">
        <div class="chart-card">
            <h6 class="fw-bold mb-3 text-dark"><i class="bi bi-pie-chart-fill text-primary me-2"></i>สัดส่วนประเภทปัญหาที่พบบ่อย</h6>
            <div style="height: 240px; position: relative;">
                <canvas id="adminCategoryChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Chart 2: สถานะงานซ่อม -->
    <div class="col-md-6">
        <div class="chart-card">
            <h6 class="fw-bold mb-3 text-dark"><i class="bi bi-bar-chart-line-fill text-success me-2"></i>สรุปสถานะ Ticket ในระบบ</h6>
            <div style="height: 240px; position: relative;">
                <canvas id="adminStatusChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Ticket ล่าสุด -->
<div class="table-card mb-4">
    <div class="card-head">
        <h5><i class="bi bi-list-ul me-2" style="opacity:.5"></i>Ticket ล่าสุด</h5>
        <a href="<?= BASE_URL ?>/admin/manage_tickets.php" class="view-all">ดูทั้งหมด <i class="bi bi-arrow-right"></i></a>
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
                        $pc = $p_class[$ticket['priority']] ?? 'p-low';
                        $pt = $p_text[$ticket['priority']]  ?? $ticket['priority'];
                        ?>
                        <span class="badge-priority <?= $pc ?>">
                            <span class="dot"></span>
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

<script>
// Chart 1: Category Doughnut Chart
const ctxAdminCat = document.getElementById('adminCategoryChart').getContext('2d');
new Chart(ctxAdminCat, {
    type: 'doughnut',
    data: {
        labels: ['ฮาร์ดแวร์', 'ซอฟต์แวร์', 'เครือข่าย', 'อื่นๆ'],
        datasets: [{
            data: [<?= $cat_hw ?>, <?= $cat_sw ?>, <?= $cat_nw ?>, <?= $cat_ot ?>],
            backgroundColor: ['#ef4444', '#3b82f6', '#10b981', '#f59e0b'],
            borderWidth: 2,
            borderColor: '#ffffff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'right' }
        }
    }
});

// Chart 2: Status Bar Chart
const ctxAdminStat = document.getElementById('adminStatusChart').getContext('2d');
new Chart(ctxAdminStat, {
    type: 'bar',
    data: {
        labels: ['รอดำเนินการ', 'กำลังแก้ไข', 'แก้ไขแล้ว', 'ปิดแล้ว'],
        datasets: [{
            label: 'จำนวน Ticket',
            data: [<?= $open_tickets ?>, <?= $inprog_tickets ?>, <?= $resolved_tickets ?>, <?= $closed_tickets ?>],
            backgroundColor: ['#ef4444', '#3b82f6', '#10b981', '#64748b'],
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1 } }
        }
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>