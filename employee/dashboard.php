<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
requireRole('employee');

// ค้นหา
$search  = trim($_GET['search'] ?? '');
$perpage = (int)($_GET['perpage'] ?? 20);
$page    = (int)($_GET['page'] ?? 1);
if ($perpage < 1) $perpage = 20;
if ($page < 1)    $page    = 1;
$offset  = ($page - 1) * $perpage;

// สำคัญ: เห็นแค่ ticket ของตัวเองเท่านั้น
$where  = ['t.user_id = ?'];
$params = [$_SESSION['user_id']];

if ($search) {
    $where[]  = '(t.device_name LIKE ? OR t.device_type LIKE ? OR t.serial_no LIKE ? OR t.description LIKE ?)';
    $params   = array_merge($params, ["%$search%", "%$search%", "%$search%", "%$search%"]);
}

$whereSQL = implode(' AND ', $where);

$total = $pdo->prepare("SELECT COUNT(*) FROM tickets t WHERE $whereSQL");
$total->execute($params);
$total_rows = (int)$total->fetchColumn();
$total_pages = max(1, ceil($total_rows / $perpage));

$stmt = $pdo->prepare("
    SELECT t.*, u.fullname as tech_name, u.department as tech_dept,
           owner.fullname as owner_name, owner.department as owner_dept
    FROM tickets t
    LEFT JOIN users u ON t.assigned_to = u.id
    LEFT JOIN users owner ON t.user_id = owner.id
    WHERE $whereSQL
    ORDER BY t.created_at DESC
    LIMIT $perpage OFFSET $offset
");
$stmt->execute($params);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

$status_text = ['open'=>'รอดำเนินการ','in_progress'=>'กำลังดำเนินการ','resolved'=>'แก้ไขแล้ว','closed'=>'Closed'];
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<style>
.tbl-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; overflow: hidden; }
.tbl-toolbar { padding: 16px 20px; display: flex; align-items: center; gap: 10px; justify-content: space-between; }
.tbl-toolbar-left { display: flex; align-items: center; gap: 8px; font-size: 13px; color: #374151; }
.tbl-toolbar-right { display: flex; align-items: center; gap: 8px; font-size: 13px; color: #374151; }
.t-select { padding: 5px 8px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; outline: none; }
.t-input { padding: 6px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; outline: none; width: 200px; }
.t-input:focus { border-color: #3b82f6; }
table { width: 100%; border-collapse: collapse; font-size: 13px; }
thead tr { background: #f3f6fb; }
th { padding: 11px 14px; text-align: left; font-weight: 500; font-size: 13px; color: #374151; border-bottom: 1px solid #e5e7eb; white-space: nowrap; }
td { padding: 12px 14px; border-top: 1px solid #f3f4f6; vertical-align: middle; color: #374151; }
tr:hover td { background: #f9fafb; }
.device-name { font-weight: 500; }
.device-desc { font-size: 12px; color: #6b7280; margin-top: 2px; }
.badge-wait { color: #374151; font-size: 13px; }
.badge-prog { color: #d97706; font-size: 13px; }
.badge-done { color: #16a34a; font-size: 13px; }
.badge-closed { color: #6b7280; font-size: 13px; }
.btn-add { background: #3b82f6; color: #fff; border: none; border-radius: 7px; padding: 9px 18px; font-size: 13px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; }
.btn-add:hover { background: #2563eb; color: #fff; }
.btn-view { background: #f3f4f6; color: #374151; border: none; border-radius: 6px; padding: 5px 10px; font-size: 12px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
.btn-view:hover { background: #e5e7eb; }
.pagination-bar { padding: 14px 20px; display: flex; align-items: center; justify-content: space-between; border-top: 1px solid #e5e7eb; }
.pagination-info { font-size: 13px; color: #6b7280; }
.pagination-btns { display: flex; align-items: center; gap: 4px; }
.pg-btn { padding: 5px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; background: #fff; color: #374151; cursor: pointer; text-decoration: none; }
.pg-btn:hover { background: #f3f4f6; }
.pg-btn.active { background: #3b82f6; color: #fff; border-color: #3b82f6; }
.pg-btn.disabled { color: #9ca3af; pointer-events: none; }
</style>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
    <p style="font-size:18px; font-weight:500; margin:0;">Ticket ของฉัน</p>
    <a href="<?= BASE_URL ?>/employee/submit_ticket.php" class="btn-add">
        <i class="bi bi-plus-circle"></i> แจ้งปัญหา
    </a>
</div>

<div class="tbl-card">
    <div class="tbl-toolbar">
        <div class="tbl-toolbar-left">
            <form method="GET" style="display:flex; align-items:center; gap:8px;">
                แสดง
                <select name="perpage" class="t-select" onchange="this.form.submit()">
                    <option value="10"  <?= $perpage==10  ? 'selected':'' ?>>10</option>
                    <option value="20"  <?= $perpage==20  ? 'selected':'' ?>>20</option>
                    <option value="50"  <?= $perpage==50  ? 'selected':'' ?>>50</option>
                    <option value="100" <?= $perpage==100 ? 'selected':'' ?>>100</option>
                </select>
                แถว
                <?php if ($search): ?>
                    <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                <?php endif; ?>
            </form>
        </div>
        <div class="tbl-toolbar-right">
            <form method="GET" style="display:flex; align-items:center; gap:8px;">
                ค้นหา
                <input type="hidden" name="perpage" value="<?= $perpage ?>">
                <input type="text" name="search" class="t-input" value="<?= htmlspecialchars($search) ?>" placeholder="ค้นหา...">
            </form>
        </div>
    </div>

    <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>ลำดับ</th>
                    <th>เลขที่ Ticket</th>
                    <th>ประเภทอุปกรณ์</th>
                    <th>ชื่ออุปกรณ์</th>
                    <th>หมายเลขเครื่อง</th>
                    <th>วันที่แจ้งซ่อม</th>
                    <th>สถานะ</th>
                    <th>ช่างผู้ดำเนินการซ่อม</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tickets as $i => $t): ?>
                <tr>
                    <td><?= $offset + $i + 1 ?></td>
                    <td><code><?= htmlspecialchars($t['ticket_no']) ?></code></td>
                    <td><?= htmlspecialchars($t['device_type'] ?: '-') ?></td>
                    <td>
                        <div class="device-name"><?= htmlspecialchars($t['device_name'] ?: '-') ?></div>
                        <?php if ($t['description']): ?>
                        <div class="device-desc">อาการ : <?= htmlspecialchars(mb_substr($t['description'], 0, 40, 'UTF-8')) ?><?= mb_strlen($t['description'], 'UTF-8') > 40 ? '...' : '' ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($t['serial_no'] ?: '-') ?></td>
                    <td><?= date('d/m/Y H:i:s', strtotime($t['created_at'])) ?></td>
                    <td>
                        <?php
                        $sc = ['open'=>'badge-wait','in_progress'=>'badge-prog','resolved'=>'badge-done','closed'=>'badge-closed'];
                        ?>
                        <span class="<?= $sc[$t['status']] ?>"><?= $status_text[$t['status']] ?></span>
                    </td>
                    <td><?= $t['tech_name'] ? htmlspecialchars($t['tech_name']) : '<span style="color:#9ca3af;">-</span>' ?></td>
                    <td>
                        <a href="<?= BASE_URL ?>/employee/view_ticket.php?id=<?= $t['id'] ?>" class="btn-view">
                            <i class="bi bi-eye"></i> ดู
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($tickets)): ?>
                <tr><td colspan="9" style="text-align:center; color:#9ca3af; padding:2.5rem;">ไม่พบรายการ</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="pagination-bar">
        <div class="pagination-info">
            <?php
            $from = $total_rows > 0 ? $offset + 1 : 0;
            $to   = min($offset + $perpage, $total_rows);
            echo "แสดง {$from} ถึง {$to} จาก {$total_rows} แถว";
            ?>
        </div>
        <div class="pagination-btns">
            <?php
            $base = "?perpage={$perpage}" . ($search ? "&search=".urlencode($search) : "");
            ?>
            <a href="<?= $base ?>&page=<?= max(1,$page-1) ?>" class="pg-btn <?= $page<=1 ? 'disabled':'' ?>">ก่อนหน้า</a>
            <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                <a href="<?= $base ?>&page=<?= $p ?>" class="pg-btn <?= $p==$page ? 'active':'' ?>"><?= $p ?></a>
            <?php endfor; ?>
            <a href="<?= $base ?>&page=<?= min($total_pages,$page+1) ?>" class="pg-btn <?= $page>=$total_pages ? 'disabled':'' ?>">ถัดไป</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>