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

$error      = '';
$reset_link = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();
    $username = trim($_POST['username'] ?? '');

    if ($username === '') {
        $error = 'กรุณากรอกชื่อผู้ใช้';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND status = 'active'");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // ลบ token เก่าที่ยังไม่ถูกใช้ของผู้ใช้นี้
            $pdo->prepare("DELETE FROM password_resets WHERE user_id = ?")->execute([$user['id']]);

            $token = bin2hex(random_bytes(32));
            $stmt  = $pdo->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE))");
            $stmt->execute([$user['id'], $token]);

            $reset_link = BASE_URL . '/auth/reset_password.php?token=' . $token;
        } else {
            $error = 'ไม่พบผู้ใช้ "' . htmlspecialchars($username) . '" ในระบบ หรือบัญชีถูกปิดใช้งาน';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลืมรหัสผ่าน - IT Support Helpdesk</title>
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
        .btn-outline-back {
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 12px;
            font-size: 14.5px;
            font-weight: 500;
            color: #475569;
            background: #fff;
            transition: all 0.2s ease;
        }
        .btn-outline-back:hover {
            background: #f8fafc;
            color: #0f172a;
        }
        .alert {
            border-radius: 10px;
            font-size: 13px;
            padding: 12px 16px;
            border: none;
        }
        .reset-link-box {
            background: #f0f6ff;
            border: 1px dashed #93c5fd;
            border-radius: 10px;
            padding: 14px;
            word-break: break-all;
        }
        .reset-link-box a {
            color: #1d4ed8;
            font-size: 12.5px;
            font-weight: 500;
        }
        .reset-note {
            font-size: 12px;
            color: #64748b;
            margin-top: 10px;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="login-header">
        <div class="logo-badge">
            <i class="bi bi-key"></i>
        </div>
        <h4>ลืมรหัสผ่าน</h4>
        <p>กรอกชื่อผู้ใช้เพื่อขอรีเซ็ตรหัสผ่าน</p>
    </div>
    <div class="card-body">

        <?php if ($error): ?>
            <div class="alert alert-danger mb-4">
                <i class="bi bi-exclamation-circle-fill me-2"></i><?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($reset_link): ?>
            <div class="alert alert-success mb-3">
                <i class="bi bi-check-circle-fill me-2"></i>พบผู้ใช้ในระบบแล้ว — คัดลอกลิงก์ด้านล่างเพื่อรีเซ็ตรหัสผ่าน
            </div>
            <div class="reset-link-box">
                <a href="<?= htmlspecialchars($reset_link) ?>" target="_blank"><?= htmlspecialchars($reset_link) ?></a>
            </div>
            <p class="reset-note">
                <i class="bi bi-info-circle me-1"></i>ลิงก์นี้ใช้ได้ครั้งเดียว และหมดอายุใน 30 นาที
                (ในระบบปกติลิงก์จะถูกส่งทางอีเมล แต่ระบบภายในแสดงตรงนี้ให้คัดลอกได้เลย)
            </p>
        <?php else: ?>
            <form method="POST" onsubmit="this.querySelector('button[type=submit]').disabled = true;">
                <?= csrfInput() ?>
                <div class="mb-3">
                    <label class="form-label">ชื่อผู้ใช้ (Username)</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" name="username" class="form-control" placeholder="กรอกชื่อผู้ใช้" required autofocus>
                    </div>
                </div>
                <button type="submit" class="btn btn-login w-100 mb-2">
                    <i class="bi bi-envelope-paper me-2"></i>ขอลิงก์รีเซ็ตรหัสผ่าน
                </button>
                <a href="<?= BASE_URL ?>/auth/login.php" class="btn btn-outline-back w-100">
                    <i class="bi bi-arrow-left me-2"></i>กลับไปหน้าเข้าสู่ระบบ
                </a>
            </form>
        <?php endif; ?>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
