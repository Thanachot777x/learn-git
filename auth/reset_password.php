<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
session_start();
require_once __DIR__ . '/../config/db.php';

// ถ้าเข้าสู่ระบบอยู่แล้ว กลับไป dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/" . $_SESSION['role'] . "/dashboard.php");
    exit();
}

$token   = trim($_GET['token'] ?? '');
$error   = '';
$success = false;

// ตรวจสอบ token
$reset = null;
if ($token === '') {
    $error = 'ลิงก์รีเซ็ตรหัสผ่านไม่ถูกต้อง';
} else {
    $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ?");
    $stmt->execute([$token]);
    $reset = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reset) {
        $error = 'ลิงก์รีเซ็ตรหัสผ่านไม่ถูกต้อง หรือถูกใช้งานไปแล้ว';
    } elseif (strtotime($reset['expires_at']) < time()) {
        $error = 'ลิงก์รีเซ็ตรหัสผ่านหมดอายุแล้ว กรุณาขอลิงก์ใหม่';
        $pdo->prepare("DELETE FROM password_resets WHERE id = ?")->execute([$reset['id']]);
        $reset = null;
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND status = 'active'");
        $stmt->execute([$reset['user_id']]);
        if (!$stmt->fetch()) {
            $error = 'บัญชีผู้ใช้นี้ถูกปิดใช้งานแล้ว';
            $pdo->prepare("DELETE FROM password_resets WHERE id = ?")->execute([$reset['id']]);
            $reset = null;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();
    $new_pass = $_POST['new_password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (strlen($new_pass) < 4) {
        $error = 'รหัสผ่านต้องมีอย่างน้อย 4 ตัวอักษร';
    } elseif ($new_pass !== $confirm) {
        $error = 'รหัสผ่านใหม่และยืนยันรหัสผ่านไม่ตรงกัน';
    } elseif (!$reset) {
        $error = 'ลิงก์รีเซ็ตรหัสผ่านไม่ถูกต้องหรือหมดอายุแล้ว';
    } else {
        $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hashed, $reset['user_id']]);
        $pdo->prepare("DELETE FROM password_resets WHERE id = ?")->execute([$reset['id']]);
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตั้งรหัสผ่านใหม่ - IT Support Helpdesk</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Prompt', 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            margin: 0;
            position: relative;
            overflow-x: hidden;
        }
        body::before {
            content: '';
            position: absolute;
            width: 350px;
            height: 350px;
            background: radial-gradient(circle, rgba(59,130,246,0.25) 0%, rgba(0,0,0,0) 70%);
            top: -50px;
            left: -50px;
            border-radius: 50%;
            pointer-events: none;
        }
        body::after {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(99,102,241,0.2) 0%, rgba(0,0,0,0) 70%);
            bottom: -80px;
            right: -80px;
            border-radius: 50%;
            pointer-events: none;
        }
        .login-card {
            width: 100%;
            max-width: 430px;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.4);
            overflow: hidden;
            position: relative;
            z-index: 10;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .login-header {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
        }
        .login-header .logo-badge {
            width: 56px;
            height: 56px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(8px);
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            color: #ffffff;
            margin-bottom: 12px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }
        .login-header h4 {
            font-weight: 700;
            margin: 0;
            font-size: 18px;
            letter-spacing: 0.5px;
        }
        .login-header p {
            font-size: 13px;
            opacity: 0.85;
            margin: 4px 0 0;
            font-weight: 300;
        }
        .card-body {
            padding: 28px 30px 32px;
        }
        .form-label {
            font-size: 13px;
            font-weight: 500;
            color: #475569;
            margin-bottom: 6px;
        }
        .input-group {
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid #cbd5e1;
            transition: all 0.2s ease;
        }
        .input-group:focus-within {
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
        }
        .input-group-text {
            background-color: #f8fafc;
            border: none;
            color: #64748b;
            padding-left: 14px;
            padding-right: 14px;
        }
        .form-control {
            border: none !important;
            box-shadow: none !important;
            padding: 11px 14px;
            font-size: 14px;
            color: #0f172a;
        }
        .btn-login {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-size: 14.5px;
            font-weight: 600;
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35);
            transition: all 0.2s ease;
        }
        .btn-login:hover {
            background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.45);
            transform: translateY(-1px);
        }
        .alert {
            border-radius: 10px;
            font-size: 13px;
            padding: 12px 16px;
            border: none;
        }
        .back-link {
            display: inline-block;
            margin-top: 14px;
            font-size: 13px;
            color: #2563eb;
            text-decoration: none;
        }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="login-card">
    <div class="login-header">
        <div class="logo-badge">
            <i class="bi bi-shield-lock"></i>
        </div>
        <h4>ตั้งรหัสผ่านใหม่</h4>
        <p>กรอกรหัสผ่านใหม่สำหรับบัญชีของคุณ</p>
    </div>
    <div class="card-body">

        <?php if ($success): ?>
            <div class="alert alert-success mb-3">
                <i class="bi bi-check-circle-fill me-2"></i>ตั้งรหัสผ่านใหม่เรียบร้อยแล้ว — เข้าสู่ระบบด้วยรหัสผ่านใหม่ได้เลย
            </div>
            <a href="<?= BASE_URL ?>/auth/login.php" class="btn btn-login w-100">
                <i class="bi bi-box-arrow-in-right me-2"></i>ไปหน้าเข้าสู่ระบบ
            </a>
        <?php elseif ($reset): ?>
            <?php if ($error): ?>
                <div class="alert alert-danger mb-4">
                    <i class="bi bi-exclamation-circle-fill me-2"></i><?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            <form method="POST" action="<?= BASE_URL ?>/auth/reset_password.php?token=<?= urlencode($token) ?>" onsubmit="this.querySelector('button[type=submit]').disabled = true;">
                <?= csrfInput() ?>
                <div class="mb-3">
                    <label class="form-label">รหัสผ่านใหม่</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="new_password" class="form-control" placeholder="อย่างน้อย 4 ตัวอักษร" required minlength="4" autofocus>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label">ยืนยันรหัสผ่านใหม่</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                        <input type="password" name="confirm_password" class="form-control" placeholder="พิมพ์รหัสผ่านใหม่อีกครั้ง" required minlength="4">
                    </div>
                </div>
                <button type="submit" class="btn btn-login w-100">
                    <i class="bi bi-check2-circle me-2"></i>ตั้งรหัสผ่านใหม่
                </button>
            </form>
        <?php else: ?>
            <div class="alert alert-danger mb-3">
                <i class="bi bi-exclamation-circle-fill me-2"></i><?= htmlspecialchars($error) ?>
            </div>
            <a href="<?= BASE_URL ?>/auth/forgot_password.php" class="btn btn-login w-100">
                <i class="bi bi-key me-2"></i>ขอลิงก์รีเซ็ตใหม่
            </a>
        <?php endif; ?>

        <div class="text-center">
            <a href="<?= BASE_URL ?>/auth/login.php" class="back-link">
                <i class="bi bi-arrow-left me-1"></i>กลับไปหน้าเข้าสู่ระบบ
            </a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
