<?php
// ⚠️ เปิดเฉพาะตอน Development เท่านั้น เมื่อขึ้น Production ให้เปลี่ยนเป็น false
ini_set('display_errors', 0);
error_reporting(E_ALL);
session_start();
require_once __DIR__ . '/../config/db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/" . $_SESSION['role'] . "/dashboard.php");
    exit();
}
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND status = 'active'");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['role']     = $user['role'];

            header("Location: " . BASE_URL . "/" . $user['role'] . "/dashboard.php");
            exit();
        } else {
            $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - IT Support Helpdesk</title>
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

        /* Floating background glow elements */
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
            padding: 36px 30px;
            text-align: center;
            position: relative;
        }

        .login-header .logo-badge {
            width: 64px;
            height: 64px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(8px);
            border-radius: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            color: #ffffff;
            margin-bottom: 14px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }

        .login-header h4 {
            font-weight: 700;
            margin: 0;
            font-size: 20px;
            letter-spacing: 0.5px;
        }

        .login-header p {
            font-size: 13px;
            opacity: 0.85;
            margin: 4px 0 0;
            font-weight: 300;
        }

        .card-body {
            padding: 32px 30px 36px;
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
    </style>
</head>
<body>

<div class="login-card">
    <div class="login-header">
        <div class="logo-badge">
            <i class="bi bi-pc-display"></i>
        </div>
        <h4>IT Support Helpdesk</h4>
        <p>ระบบแจ้งซ่อมและจัดการงานไอที</p>
    </div>
    <div class="card-body">

        <?php if ($error): ?>
            <div class="alert alert-danger mb-4">
                <i class="bi bi-exclamation-circle-fill me-2"></i><?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <?= csrfInput() ?>
            <div class="mb-3">
                <label class="form-label">ชื่อผู้ใช้ (Username)</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text" name="username" class="form-control" placeholder="กรอกชื่อผู้ใช้" required autofocus>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label">รหัสผ่าน (Password)</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" class="form-control" placeholder="กรอกรหัสผ่าน" required>
                </div>
            </div>
            <button type="submit" class="btn btn-login w-100">
                <i class="bi bi-box-arrow-in-right me-2"></i>เข้าสู่ระบบ
            </button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>