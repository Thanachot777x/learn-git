<?php
/**
 * การตั้งค่าการเชื่อมต่อฐานข้อมูลและ BASE_URL
 * ออกแบบมาให้ Auto-detect คำนวณเส้นทางอัตโนมัติ ใครดาวน์โหลดไปรันเครื่องไหนหรือตั้งชื่อโฟลเดอร์อะไรก็ใช้งานได้ทันที
 */

require_once __DIR__ . '/../includes/csrf.php';

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
    // วิธีที่ปลอดภัย: ใช้ Environment Variable แทน hardcode
    // ตัวอย่างตั้งใน .env หรือ server config:
    //   DB_HOST=sql211.infinityfree.com
    //   DB_NAME=if0_42458402_it_supportdb
    //   DB_USER=if0_42458402
    //   DB_PASS=your_password_here
    // ----------------------------------------------------
    $host     = getenv('DB_HOST') ?: "sql211.infinityfree.com";
    $dbname   = getenv('DB_NAME') ?: "if0_42458402_it_supportdb";
    $username = getenv('DB_USER') ?: "if0_42458402";
    $password = getenv('DB_PASS') ?: ""; // ⚠️ อย่า hardcode password ที่นี่ ใช้ env แทน
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    if ($is_local) {
        // หากเป็น Localhost แล้วยังไม่มีฐานข้อมูล ให้ระบบสร้าง DB และนำเข้า schema ให้อัตโนมัติทันที
        try {
            $pdo_init = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
            $pdo_init->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo_init->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
            
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // เช็คว่าตารางยังไม่ได้ถูกสร้างหรือไม่
            $table_check = $pdo->query("SHOW TABLES LIKE 'users'")->fetch();
            if (!$table_check) {
                $sql_file = __DIR__ . '/../database/schema.sql';
                if (file_exists($sql_file)) {
                    $sql = file_get_contents($sql_file);
                    $pdo->exec($sql);
                }
            }
        } catch (PDOException $ex) {
            die("เชื่อมต่อและสร้างฐานข้อมูลไม่สำเร็จ: " . $ex->getMessage() . "<br><small>กรุณาเปิดบริการ MySQL ใน XAMPP Control Panel</small>");
        }
    } else {
        die("เชื่อมต่อฐานข้อมูลไม่สำเร็จ: " . $e->getMessage());
    }
}
?>