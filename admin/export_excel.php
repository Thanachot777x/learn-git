<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
requireRole(['admin', 'manager']);

require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

// ---- รับค่า filter จาก GET ----
$dateFrom = $_GET['date_from'] ?? null;
$dateTo   = $_GET['date_to'] ?? null;
$status   = $_GET['status'] ?? 'all';

// ---- Query รายการ Ticket (join pattern เดียวกับ manage_tickets.php) ----
$sql = "SELECT t.*, u.fullname AS reporter_name, u.department AS reporter_dept,
               u2.fullname AS tech_name
        FROM tickets t
        JOIN users u ON t.user_id = u.id
        LEFT JOIN users u2 ON t.assigned_to = u2.id
        WHERE 1=1";
$params = [];

if ($dateFrom) {
    $sql .= " AND t.created_at >= ?";
    $params[] = $dateFrom . " 00:00:00";
}
if ($dateTo) {
    $sql .= " AND t.created_at <= ?";
    $params[] = $dateTo . " 23:59:59";
}
if ($status !== 'all') {
    $sql .= " AND t.status = ?";
    $params[] = $status;
}
$sql .= " ORDER BY t.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ---- สรุปสถิติ: ต่อเดือน ----
$byMonth = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, COUNT(*) AS total
    FROM tickets
    GROUP BY month
    ORDER BY month DESC
")->fetchAll(PDO::FETCH_ASSOC);

// ---- สรุปสถิติ: ต่อช่าง ----
$byTech = $pdo->query("
    SELECT u2.fullname AS technician,
           COUNT(t.id) AS total,
           SUM(CASE WHEN t.status IN ('resolved','closed') THEN 1 ELSE 0 END) AS done
    FROM tickets t
    LEFT JOIN users u2 ON t.assigned_to = u2.id
    WHERE u2.fullname IS NOT NULL
    GROUP BY u2.fullname
    ORDER BY total DESC
")->fetchAll(PDO::FETCH_ASSOC);

$statusLabel = [
    'open' => 'รอดำเนินการ',
    'in_progress' => 'กำลังดำเนินการ',
    'resolved' => 'แก้ไขแล้ว',
    'closed' => 'ปิดงาน',
];
$priorityLabel = [
    'low' => 'ต่ำ',
    'medium' => 'ปานกลาง',
    'high' => 'สูง',
    'urgent' => 'เร่งด่วน',
];

// ชื่อเดือนภาษาไทย สำหรับหัวตารางรายเดือน
$thaiMonths = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];

// ============ สร้าง Spreadsheet ============
$spreadsheet = new Spreadsheet();

// สไตล์กลาง
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F4E78']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
];
$zebraFill = ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EAF1F8']];

// ---- ชีท 1: รายการ Ticket ----
$sheet1 = $spreadsheet->getActiveSheet();
$sheet1->setTitle('รายการ Ticket');

$headers = ['เลข Ticket', 'ผู้แจ้ง', 'แผนก', 'หัวข้อ', 'ประเภท', 'ความสำคัญ', 'สถานะ', 'ช่างที่ดูแล', 'สถานที่', 'วันที่แจ้ง', 'อัปเดตล่าสุด'];
$sheet1->fromArray($headers, null, 'A1');
$sheet1->getStyle('A1:K1')->applyFromArray($headerStyle);
$sheet1->getRowDimension(1)->setRowHeight(24);

$rowNum = 2;
foreach ($tickets as $t) {
    $location = trim(($t['building'] ?? '') . ' ชั้น ' . ($t['floor'] ?? '') . ' ห้อง ' . ($t['room'] ?? ''));
    $sheet1->fromArray([
        $t['ticket_no'],
        $t['reporter_name'],
        $t['reporter_dept'],
        $t['title'],
        $t['category'],
        $priorityLabel[$t['priority']] ?? $t['priority'],
        $statusLabel[$t['status']] ?? $t['status'],
        $t['tech_name'] ?? '-',
        $location,
        $t['created_at'],
        $t['updated_at'],
    ], null, "A{$rowNum}");
    // สลับสีแถว (zebra)
    if ($rowNum % 2 === 0) {
        $sheet1->getStyle("A{$rowNum}:K{$rowNum}")->getFill()->applyFromArray($zebraFill);
    }
    $rowNum++;
}

$lastRow = $rowNum - 1;
foreach (range('A', 'K') as $col) {
    $sheet1->getColumnDimension($col)->setAutoSize(true);
}
$sheet1->getStyle("A1:K{$lastRow}")->getBorders()->getAllBorders()
    ->setBorderStyle(Border::BORDER_THIN);
// จัดกลางคอลัมน์: เลข Ticket, ประเภท, ความสำคัญ, สถานะ
if ($lastRow >= 2) {
    $sheet1->getStyle("A2:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet1->getStyle("E2:G{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
}
// ล็อกหัวตารางไว้เวลาเลื่อนดู
$sheet1->freezePane('A2');

// ---- ชีท 2: สรุปสถิติ (เรียบง่าย แบบคนทำ) ----
$sheet2 = $spreadsheet->createSheet();
$sheet2->setTitle('สรุปสถิติ');

$sheet2->setCellValue('A1', 'สรุปสถิติ');
$sheet2->getStyle('A1')->getFont()->setBold(true)->setSize(14);

// --- จำนวน Ticket ต่อเดือน ---
$sheet2->setCellValue('A3', 'จำนวน Ticket ต่อเดือน');
$sheet2->getStyle('A3')->getFont()->setBold(true);

$sheet2->fromArray(['เดือน', 'จำนวน Ticket'], null, 'A4');
$sheet2->getStyle('A4:B4')->applyFromArray($headerStyle);

$r = 5;
$monthTotal = 0;
foreach ($byMonth as $row) {
    [$y, $m] = array_map('intval', explode('-', $row['month']));
    $monthLabel = ($thaiMonths[$m - 1] ?? $m) . ' ' . ($y + 543);
    $monthTotal += (int) $row['total'];
    $sheet2->fromArray([$monthLabel, (int) $row['total']], null, "A{$r}");
    $r++;
}
if ($byMonth) {
    $sheet2->fromArray(['รวม', $monthTotal], null, "A{$r}");
    $sheet2->getStyle("A{$r}:B{$r}")->getFont()->setBold(true);
    $r++;
}
$lastMonthRow = $r - 1;

// --- จำนวน Ticket ต่อช่าง ---
$startTech = $r + 1;
$sheet2->setCellValue("A{$startTech}", 'จำนวน Ticket ต่อช่าง');
$sheet2->getStyle("A{$startTech}")->getFont()->setBold(true);

$sheet2->fromArray(['ช่าง', 'รับงานทั้งหมด', 'เสร็จแล้ว'], null, "A" . ($startTech + 1));
$sheet2->getStyle("A" . ($startTech + 1) . ":C" . ($startTech + 1))->applyFromArray($headerStyle);

$r = $startTech + 2;
$techTotal = 0;
$techDone  = 0;
foreach ($byTech as $row) {
    $techTotal += (int) $row['total'];
    $techDone  += (int) $row['done'];
    $sheet2->fromArray([$row['technician'], (int) $row['total'], (int) $row['done']], null, "A{$r}");
    $r++;
}
if ($byTech) {
    $sheet2->fromArray(['รวม', $techTotal, $techDone], null, "A{$r}");
    $sheet2->getStyle("A{$r}:C{$r}")->getFont()->setBold(true);
}

// ขอบเส้นรอบตารางเท่านั้น (ไม่ใส่สีฉูดฉาด)
$sheet2->getStyle("A4:B{$lastMonthRow}")->getBorders()->getAllBorders()
    ->setBorderStyle(Border::BORDER_THIN);
$sheet2->getStyle("A" . ($startTech + 1) . ":C{$r}")->getBorders()->getAllBorders()
    ->setBorderStyle(Border::BORDER_THIN);

$sheet2->getColumnDimension('A')->setWidth(20);
$sheet2->getColumnDimension('B')->setWidth(16);
$sheet2->getColumnDimension('C')->setWidth(14);

// ---- ส่งไฟล์ให้ดาวน์โหลด ----
$filename = 'it_support_report_' . date('Y-m-d_His') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;