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
            // ป้องกัน session fixation: สร้าง session id ใหม่ทุกครั้งที่ล็อกอินสำเร็จ
            session_regenerate_id(true);

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
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.4);
            overflow: hidden;
        }

        .login-header {
            background: #2563eb;
            color: white;
            padding: 30px 24px;
            text-align: center;
        }

        .login-header .logo-badge {
            width: 56px;
            height: 56px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            color: #ffffff;
            margin-bottom: 12px;
        }

        .login-header h4 {
            font-weight: 700;
            margin: 0;
            font-size: 18px;
        }

        .login-header p {
            font-size: 13px;
            opacity: 0.85;
            margin: 4px 0 0;
        }

        .login-clock {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            font-size: 12px;
        }
        .login-clock .lc-time {
            font-size: 15px;
            font-weight: 600;
        }
        .login-clock .lc-date {
            margin-top: 2px;
            font-size: 11px;
            opacity: 0.8;
        }

        .card-body {
            padding: 26px 28px 30px;
        }

        .form-label {
            font-size: 13px;
            font-weight: 500;
            color: #475569;
            margin-bottom: 6px;
        }

        .input-group {
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #cbd5e1;
            transition: all 0.2s ease;
        }

        .input-group:focus-within {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        .input-group-text {
            background-color: #f8fafc;
            border: none;
            color: #64748b;
            padding-left: 12px;
            padding-right: 12px;
        }

        .form-control {
            border: none !important;
            box-shadow: none !important;
            padding: 10px 12px;
            font-size: 14px;
            color: #0f172a;
        }

        .btn-login {
            background: #2563eb;
            border: none;
            border-radius: 8px;
            padding: 11px;
            font-size: 14.5px;
            font-weight: 600;
            color: #ffffff;
            transition: all 0.2s ease;
        }

        .btn-login:hover {
            background: #1d4ed8;
        }

        .alert {
            border-radius: 8px;
            font-size: 13px;
            padding: 11px 14px;
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
        <div class="login-clock">
            <div class="lc-time"><i class="bi bi-clock"></i> <span id="loginTime">--:--:--</span> น.</div>
            <div class="lc-date" id="loginDate"></div>
        </div>
    </div>
    <div class="card-body">

        <?php if ($error): ?>
            <div class="alert alert-danger mb-4">
                <i class="bi bi-exclamation-circle-fill me-2"></i><?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['flash_error'])): ?>
            <div class="alert alert-warning mb-4">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($_SESSION['flash_error']) ?>
            </div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>

        <form method="POST" onsubmit="this.querySelector('button[type=submit]').disabled = true;">
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
                    <input type="password" name="password" id="loginPwd" class="form-control" placeholder="กรอกรหัสผ่าน" required>
                    <button class="input-group-text" type="button" onclick="togglePwd('loginPwd', this)" style="cursor:pointer; border-left:none;">
                        <i class="bi bi-eye-slash"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-login w-100">
                <i class="bi bi-box-arrow-in-right me-2"></i>เข้าสู่ระบบ
            </button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// แสดงเวลา/วันที่แบบไทย real-time บนหน้า login
function updateLoginClock() {
    const now = new Date();
    const timeEl = document.getElementById('loginTime');
    const dateEl = document.getElementById('loginDate');
    if (!timeEl || !dateEl) return;
    timeEl.textContent = new Intl.DateTimeFormat('th-TH', {
        hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false
    }).format(now);
    dateEl.textContent = new Intl.DateTimeFormat('th-TH', {
        weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
    }).format(now);
}
updateLoginClock();
setInterval(updateLoginClock, 1000);

function togglePwd(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye-slash';
    }
}
</script>
</body>
</html>
