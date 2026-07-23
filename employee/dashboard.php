<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
requireRole('employee');

// ดึงสรุปจำนวน Ticket ของผู้ใช้นี้
$stats_stmt = $pdo->prepare("
    SELECT status, COUNT(*) as total
    FROM tickets
    WHERE user_id = ?
    GROUP BY status
");
$stats_stmt->execute([$_SESSION['user_id']]);
$stats = $stats_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$count_open        = $stats['open']        ?? 0;
$count_in_progress = $stats['in_progress'] ?? 0;
$count_resolved    = $stats['resolved']    ?? 0;
$count_closed      = $stats['closed']      ?? 0;
$count_total       = $count_open + $count_in_progress + $count_resolved + $count_closed;

// ค้นหา & ฟิลเตอร์สถานะ
$search        = trim($_GET['search'] ?? '');
$status_filter = trim($_GET['status'] ?? '');
$perpage       = (int)($_GET['perpage'] ?? 15);
$page          = (int)($_GET['page'] ?? 1);
if ($perpage < 1) $perpage = 15;
if ($page < 1)    $page    = 1;
$offset        = ($page - 1) * $perpage;

$where  = ['t.user_id = ?'];
$params = [$_SESSION['user_id']];

if ($status_filter) {
    $where[]  = 't.status = ?';
    $params[] = $status_filter;
}

if ($search) {
    $where[]  = '(t.ticket_no LIKE ? OR t.device_name LIKE ? OR t.device_type LIKE ? OR t.description LIKE ?)';
    $params   = array_merge($params, ["%$search%", "%$search%", "%$search%", "%$search%"]);
}

$whereSQL = implode(' AND ', $where);

$total = $pdo->prepare("SELECT COUNT(*) FROM tickets t WHERE $whereSQL");
$total->execute($params);
$total_rows  = (int)$total->fetchColumn();
$total_pages = max(1, ceil($total_rows / $perpage));

$stmt = $pdo->prepare("
    SELECT t.*, u.fullname as tech_name, u.department as tech_dept
    FROM tickets t
    LEFT JOIN users u ON t.assigned_to = u.id
    WHERE $whereSQL
    ORDER BY t.created_at DESC
    LIMIT $perpage OFFSET $offset
");
$stmt->execute($params);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

$status_info = [
    'open'        => ['label' => 'รอดำเนินการ', 'bg' => '#fef3c7', 'color' => '#92400e', 'icon' => 'bi-clock-history'],
    'in_progress' => ['label' => 'กำลังซ่อมแซม', 'bg' => '#dbeafe', 'color' => '#1e40af', 'icon' => 'bi-gear-wide-connected'],
    'resolved'    => ['label' => 'ซ่อมเสร็จแล้ว', 'bg' => '#d1fae5', 'color' => '#065f46', 'icon' => 'bi-check-circle'],
    'closed'      => ['label' => 'ปิดงานแล้ว', 'bg' => '#f1f5f9', 'color' => '#475569', 'icon' => 'bi-archive']
];

$priority_text = [
    'low'    => '🟢 ปกติ',
    'medium' => '🟡 ปานกลาง',
    'high'   => '🟠 ด่วน',
    'urgent' => '🔴 ด่วนมาก'
];
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<style>
.dash-header { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; margin-bottom: 24px; }
.dash-title { font-size: 20px; font-weight: 700; color: #0f172a; margin: 0; }
.dash-sub { font-size: 13px; color: #64748b; margin: 2px 0 0; }

.btn-new-ticket {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: #fff !important; border: none; border-radius: 10px;
    padding: 10px 22px; font-size: 13.5px; font-weight: 600;
    text-decoration: none; display: inline-flex; align-items: center; gap: 8px;
    box-shadow: 0 4px 12px rgba(37,99,235,0.3); transition: all 0.2s ease;
}
.btn-new-ticket:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(37,99,235,0.4); }

/* Stat Summary Cards */
.stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 28px; }
.stat-card-box {
    background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px;
    padding: 18px 20px; position: relative; overflow: hidden;
    box-shadow: 0 2px 8px rgba(15,23,42,0.03); transition: all 0.2s ease;
    text-decoration: none; color: inherit; display: block;
}
.stat-card-box:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(15,23,42,0.06); }
.stat-card-box.active { border-color: #2563eb; ring: 2px solid #2563eb; }
.stat-card-icon {
    width: 42px; height: 42px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px; margin-bottom: 12px;
}
.stat-num { font-size: 26px; font-weight: 700; color: #0f172a; line-height: 1; }
.stat-name { font-size: 12.5px; color: #64748b; font-weight: 500; margin-top: 4px; }

/* Filter Tabs & Search */
.filter-bar {
    background: #fff; border: 1px solid #e2e8f0; border-radius: 12px;
    padding: 14px 18px; margin-bottom: 24px;
    display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;
}
.filter-tabs { display: flex; gap: 6px; flex-wrap: wrap; }
.tab-btn {
    padding: 7px 14px; border-radius: 8px; font-size: 12.5px; font-weight: 500;
    text-decoration: none; color: #64748b; background: #f8fafc; border: 1px solid #e2e8f0;
    transition: all 0.15s ease;
}
.tab-btn:hover { background: #f1f5f9; color: #0f172a; }
.tab-btn.active { background: #2563eb; color: #ffffff; border-color: #2563eb; font-weight: 600; }

/* Ticket Cards Grid */
.ticket-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; margin-bottom: 24px; }
.t-card {
    background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px;
    padding: 20px; position: relative; display: flex; flex-direction: column;
    box-shadow: 0 2px 10px rgba(15,23,42,0.03); transition: all 0.2s ease;
}
.t-card:hover { transform: translateY(-3px); box-shadow: 0 10px 24px -4px rgba(15,23,42,0.08); border-color: #cbd5e1; }
.t-card-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }
.t-no { font-family: monospace; font-weight: 700; color: #2563eb; font-size: 13px; background: #eff6ff; padding: 3px 8px; border-radius: 6px; }
.t-status { font-size: 11.5px; font-weight: 600; padding: 4px 10px; border-radius: 20px; display: inline-flex; align-items: center; gap: 5px; }

.t-title { font-size: 15px; font-weight: 600; color: #0f172a; margin-bottom: 6px; line-height: 1.3; }
.t-desc { font-size: 13px; color: #64748b; margin-bottom: 16px; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; height: 36px; }

.t-meta { display: flex; align-items: center; justify-content: space-between; font-size: 12px; color: #94a3b8; border-top: 1px solid #f1f5f9; pt-12px; padding-top: 12px; margin-top: auto; }
.t-tech { display: flex; align-items: center; gap: 6px; color: #334155; font-weight: 500; }
.t-tech-avatar { width: 22px; height: 22px; border-radius: 50%; background: #dbeafe; color: #1e40af; display: inline-flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 700; }

.btn-detail {
    background: #f1f5f9; color: #334155; border: none; border-radius: 8px;
    padding: 6px 14px; font-size: 12.5px; font-weight: 600; text-decoration: none;
    transition: all 0.15s ease; display: inline-flex; align-items: center; gap: 4px;
}
.btn-detail:hover { background: #2563eb; color: #ffffff; }

.pagination-bar { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; padding-top: 12px; }
.pg-btn { padding: 6px 14px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 13px; background: #fff; color: #334155; text-decoration: none; }
.pg-btn:hover { background: #f8fafc; }
.pg-btn.active { background: #2563eb; color: #fff; border-color: #2563eb; font-weight: 600; }
.pg-btn.disabled { color: #cbd5e1; pointer-events: none; }
</style>

<div class="dash-header">
    <div>
        <h4 class="dash-title">ติดตามการแจ้งซ่อมของฉัน</h4>
        <p class="dash-sub">สวัสดี, <?= htmlspecialchars($_SESSION['fullname']) ?> — ตรวจสอบสถานะงานซ่อมอุปกรณ์ของคุณได้ที่นี่</p>
    </div>
    <a href="<?= BASE_URL ?>/employee/submit_ticket.php" class="btn-new-ticket">
        <i class="bi bi-plus-circle"></i> แจ้งปัญหาใหม่
    </a>
</div>

<!-- Stat Cards -->
<div class="stat-grid">
    <a href="?status=" class="stat-card-box <?= $status_filter==='' ? 'active':'' ?>">
        <div class="stat-card-icon" style="background:#eff6ff; color:#2563eb;"><i class="bi bi-ticket-detailed"></i></div>
        <div class="stat-num"><?= $count_total ?></div>
        <div class="stat-name">รายการทั้งหมด</div>
    </a>
    <a href="?status=open" class="stat-card-box <?= $status_filter==='open' ? 'active':'' ?>">
        <div class="stat-card-icon" style="background:#fef3c7; color:#d97706;"><i class="bi bi-clock-history"></i></div>
        <div class="stat-num"><?= $count_open ?></div>
        <div class="stat-name">รอดำเนินการ</div>
    </a>
    <a href="?status=in_progress" class="stat-card-box <?= $status_filter==='in_progress' ? 'active':'' ?>">
        <div class="stat-card-icon" style="background:#dbeafe; color:#1d4ed8;"><i class="bi bi-gear-wide-connected"></i></div>
        <div class="stat-num"><?= $count_in_progress ?></div>
        <div class="stat-name">กำลังซ่อมแซม</div>
    </a>
    <a href="?status=resolved" class="stat-card-box <?= $status_filter==='resolved' ? 'active':'' ?>">
        <div class="stat-card-icon" style="background:#d1fae5; color:#059669;"><i class="bi bi-check-circle"></i></div>
        <div class="stat-num"><?= $count_resolved ?></div>
        <div class="stat-name">ซ่อมเสร็จแล้ว</div>
    </a>
</div>

<!-- Filter Bar -->
<div class="filter-bar">
    <div class="filter-tabs">
        <a href="?status=<?= $search ? '&search='.urlencode($search):'' ?>" class="tab-btn <?= $status_filter===''?'active':'' ?>">ทั้งหมด</a>
        <a href="?status=open<?= $search ? '&search='.urlencode($search):'' ?>" class="tab-btn <?= $status_filter==='open'?'active':'' ?>">รอดำเนินการ (<?= $count_open ?>)</a>
        <a href="?status=in_progress<?= $search ? '&search='.urlencode($search):'' ?>" class="tab-btn <?= $status_filter==='in_progress'?'active':'' ?>">กำลังซ่อม (<?= $count_in_progress ?>)</a>
        <a href="?status=resolved<?= $search ? '&search='.urlencode($search):'' ?>" class="tab-btn <?= $status_filter==='resolved'?'active':'' ?>">ซ่อมเสร็จแล้ว (<?= $count_resolved ?>)</a>
    </div>
    
    <form method="GET" style="display:flex; align-items:center; gap:8px;">
        <?php if ($status_filter): ?><input type="hidden" name="status" value="<?= htmlspecialchars($status_filter) ?>"><?php endif; ?>
        <input type="text" name="search" class="form-control form-control-sm" style="width:200px; border-radius:8px;" value="<?= htmlspecialchars($search) ?>" placeholder="ค้นหา Ticket / อุปกรณ์...">
        <button type="submit" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;"><i class="bi bi-search"></i></button>
    </form>
</div>

<!-- Ticket List (Grid Cards) -->
<?php if (empty($tickets)): ?>
    <div class="text-center py-5 bg-white rounded-3 border">
        <i class="bi bi-inbox fs-1 text-muted opacity-50 d-block mb-2"></i>
        <p class="text-muted mb-0">ไม่พบรายการแจ้งซ่อม</p>
    </div>
<?php else: ?>
    <div class="ticket-grid">
        <?php foreach ($tickets as $t): ?>
            <?php $st = $status_info[$t['status']] ?? $status_info['open']; ?>
            <div class="t-card">
                <div class="t-card-header">
                    <span class="t-no"><?= htmlspecialchars($t['ticket_no']) ?></span>
                    <span class="t-status" style="background: <?= $st['bg'] ?>; color: <?= $st['color'] ?>;">
                        <i class="bi <?= $st['icon'] ?>"></i> <?= $st['label'] ?>
                    </span>
                </div>

                <div class="t-title"><?= htmlspecialchars($t['title']) ?></div>
                <div class="t-desc"><?= htmlspecialchars($t['description'] ?: 'ไม่มีรายละเอียดเพิ่มเติม') ?></div>

                <div class="t-meta">
                    <div class="t-tech">
                        <?php if (!empty($t['tech_name'])): ?>
                            <span class="t-tech-avatar"><?= mb_substr($t['tech_name'], 0, 1, 'UTF-8') ?></span>
                            <span><?= htmlspecialchars($t['tech_name']) ?></span>
                        <?php else: ?>
                            <span class="text-muted"><i class="bi bi-clock me-1"></i>รอช่างรับงาน</span>
                        <?php endif; ?>
                    </div>
                    <a href="<?= BASE_URL ?>/employee/view_ticket.php?id=<?= $t['id'] ?>" class="btn-detail">
                        ดูรายละเอียด <i class="bi bi-chevron-right"></i>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <div class="pagination-bar">
        <div class="text-muted small">
            แสดง <?= $total_rows > 0 ? $offset + 1 : 0 ?> ถึง <?= min($offset + $perpage, $total_rows) ?> จาก <?= $total_rows ?> รายการ
        </div>
        <div class="d-flex gap-1">
            <?php $base = "?perpage={$perpage}" . ($status_filter ? "&status=".urlencode($status_filter):"") . ($search ? "&search=".urlencode($search):""); ?>
            <a href="<?= $base ?>&page=<?= max(1,$page-1) ?>" class="pg-btn <?= $page<=1?'disabled':'' ?>">ก่อนหน้า</a>
            <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                <a href="<?= $base ?>&page=<?= $p ?>" class="pg-btn <?= $p==$page?'active':'' ?>"><?= $p ?></a>
            <?php endfor; ?>
            <a href="<?= $base ?>&page=<?= min($total_pages,$page+1) ?>" class="pg-btn <?= $page>=$total_pages?'disabled':'' ?>">ถัดไป</a>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>