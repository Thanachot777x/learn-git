<?php
/**
 * การตั้งค่าการเชื่อมต่อฐานข้อมูลและ BASE_URL
 * ออกแบบมาให้ Auto-detect คำนวณเส้นทางอัตโนมัติ ใครดาวน์โหลดไปรันเครื่องไหนหรือตั้งชื่อโฟลเดอร์อะไรก็ใช้งานได้ทันที
 */

// คำนวณหา BASE_URL อัตโนมัติ
if (!defined('BASE_URL')) {
    $script_dir = str_replace('\\', '/', dirname(__DIR__));
    $doc_root   = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');
    $base_path  = str_replace($doc_root, '', $script_dir);
    define('BASE_URL', rtrim($base_path, '/'));
}

// ตรวจสอบสภาพแวดล้อม (Localhost vs Production Cloud)
$is_local = (
    !isset($_SERVER['SERVER_NAME']) || 
    $_SERVER['SERVER_NAME'] === 'localhost' || 
    $_SERVER['SERVER_NAME'] === '127.0.0.1' ||
    $_SERVER['SERVER_NAME'] === '::1'
);

if ($is_local) {
    // ----------------------------------------------------
    // ตั้งค่าสำหรับเครื่อง Local (XAMPP / WAMP / Localhost)
    // ----------------------------------------------------
    $host     = "localhost";
    $dbname   = "it_support";
    $username = "root";
    $password = "";
} else {
    // ----------------------------------------------------
    // ตั้งค่าสำหรับบน Cloud (InfinityFree / Web Server)
    // ----------------------------------------------------
    $host     = "sql211.infinityfree.com";
    $dbname   = "if0_42458402_it_supportdb";
    $username = "if0_42458402";
    $password = "GQ4aotFyL69";
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("เชื่อมต่อฐานข้อมูลไม่สำเร็จ: " . $e->getMessage());
}
?>