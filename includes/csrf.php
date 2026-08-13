<?php
if (session_status() === PHP_SESSION_NONE) {
    // ต่ออายุ session เป็น 8 ชั่วโมง (default ของ PHP คือ 24 นาที — ฟอร์มที่เปิดค้างไว้นานจะเด้ง token ไม่ตรงกัน)
    ini_set('session.gc_maxlifetime', 8 * 60 * 60);
    session_set_cookie_params([
        'lifetime' => 8 * 60 * 60,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/**
 * สุ่มสร้างหรือดึง CSRF token (เก็บใน $_SESSION — ไม่ต้องพึ่งคุกกี้)
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * สร้าง HTML input hidden tag สำหรับฟอร์ม
 */
function csrfInput() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generateCsrfToken()) . '">';
}

/**
 * ตรวจสอบความถูกต้องของ CSRF Token เมื่อส่งแบบ POST
 * ถ้า token ไม่ตรง (เช่น session หมดอายุ หรือโดนโจมตี) → redirect กลับหน้าเดิมพร้อมข้อความเตือน
 * แทนที่จะ die() เป็นหน้า error เฉยๆ
 */
function verifyCsrfToken() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }
    $token         = $_POST['csrf_token'] ?? '';
    $session_token = $_SESSION['csrf_token'] ?? '';
    if (empty($token) || empty($session_token) || !hash_equals($session_token, $token)) {
        $_SESSION['flash_error'] = 'เซสชันหมดอายุหรือคำขอไม่ถูกต้อง (CSRF Token Invalid) กรุณารีเฟรชหน้าเว็บและลองใหม่อีกครั้ง';

        // redirect กลับไปหน้าเดิม — อนุญาตเฉพาะลิงก์ภายในระบบเท่านั้น (กัน open redirect)
        $back    = $_SERVER['HTTP_REFERER'] ?? '';
        $scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host    = $_SERVER['HTTP_HOST'] ?? '';
        $is_same_site = ($back !== '' && $host !== '' && stripos($back, $scheme . '://' . $host) === 0);

        if (!$is_same_site) {
            $base = defined('BASE_URL') ? BASE_URL : '';
            if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
                $back = $base . '/' . $_SESSION['role'] . '/dashboard.php';
            } else {
                $back = $base . '/auth/login.php';
            }
        }

        header('Location: ' . $back);
        exit();
    }
}
