<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * สุ่มสร้างหรือดึง CSRF token
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
    $token = generateCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * ตรวจสอบความถูกต้องของ CSRF Token เมื่อส่งแบบ POST
 */
function verifyCsrfToken() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        $session_token = $_SESSION['csrf_token'] ?? '';
        if (empty($token) || empty($session_token) || !hash_equals($session_token, $token)) {
            http_response_code(403);
            die("คำขอไม่ถูกต้องหรือหมดอายุความปลอดภัย (CSRF Token Invalid). กรุณารีเฟรชหน้าเว็บและลองใหม่อีกครั้ง");
        }
    }
}
?>
