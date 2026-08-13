<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
requireRole('admin');

// กัน timeout ถ้าฐานข้อมูลโตขึ้นเรื่อยๆ
set_time_limit(120);

/**
 * แปลงค่า PHP ให้เป็นค่า SQL ที่ปลอดภัย (NULL / string / number)
 */
function backupQuoteValue(PDO $pdo, $value) {
    if ($value === null) {
        return 'NULL';
    }
    if (is_int($value) || is_float($value)) {
        return (string) $value;
    }
    return $pdo->quote($value);
}

// ดึงรายชื่อตารางทั้งหมดในฐานข้อมูลนี้
$tables = $pdo->query(
    "SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() ORDER BY table_name"
)->fetchAll(PDO::FETCH_COLUMN);

if (empty($tables)) {
    die('ไม่พบตารางในฐานข้อมูล');
}

// สร้างหัวไฟล์
$db_name = $pdo->query("SELECT DATABASE()")->fetchColumn();
$dump  = "-- ============================================================\n";
$dump .= "-- IT Support Helpdesk — Database Backup\n";
$dump .= "-- Database : {$db_name}\n";
$dump .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
$dump .= "-- Tables   : " . count($tables) . "\n";
$dump .= "-- ============================================================\n\n";
$dump .= "SET NAMES utf8mb4;\n";
$dump .= "SET FOREIGN_KEY_CHECKS = 0;\n";

$total_rows = 0;

foreach ($tables as $table) {
    // โครงสร้างตาราง
    $create_sql = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_NUM)[1];

    $dump .= "\n-- ------------------------------------------------------------\n";
    $dump .= "-- Table structure for table `{$table}`\n";
    $dump .= "-- ------------------------------------------------------------\n";
    $dump .= "DROP TABLE IF EXISTS `{$table}`;\n";
    $dump .= $create_sql . ";\n";

    // รายชื่อคอลัมน์ (สำหรับคำสั่ง INSERT)
    $columns = $pdo->query(
        "SELECT column_name FROM information_schema.columns
         WHERE table_schema = DATABASE() AND table_name = " . $pdo->quote($table) . "
         ORDER BY ordinal_position"
    )->fetchAll(PDO::FETCH_COLUMN);
    $col_list = '`' . implode('`, `', $columns) . '`';

    // ข้อมูลทั้งหมด
    $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_NUM);

    $dump .= "\n-- ------------------------------------------------------------\n";
    $dump .= "-- Dumping data for table `{$table}` ({$table}: " . count($rows) . " rows)\n";
    $dump .= "-- ------------------------------------------------------------\n";

    foreach ($rows as $row) {
        $values = array_map(function ($v) use ($pdo) {
            return backupQuoteValue($pdo, $v);
        }, $row);
        $dump .= "INSERT INTO `{$table}` ({$col_list}) VALUES (" . implode(', ', $values) . ");\n";
    }
    $dump .= "\n";

    $total_rows += count($rows);
}

$dump .= "SET FOREIGN_KEY_CHECKS = 1;\n";
$dump .= "\n-- Backup complete: " . $total_rows . " rows in " . count($tables) . " tables\n";

// ส่งออกเป็นไฟล์ดาวน์โหลด
$filename = 'it_support_backup_' . date('Ymd_His') . '.sql';

header('Content-Type: application/sql; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($dump));
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

echo $dump;
exit;
