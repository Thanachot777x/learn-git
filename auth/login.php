<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require_once __DIR__ . '/../config/db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/" . $_SESSION['role'] . "/dashboard.php");
    exit();
}
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    <title>เข้าสู่ระบบ - IT Support</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f0f2f5;
        }
        .login-card {
            max-width: 420px;
            margin: 80px auto;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .login-header {
            background-color: #0d6efd;
            color: white;
            border-radius: 12px 12px 0 0;
            padding: 30px;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="login-card card border-0">
    <div class="login-header">
        <i class="bi bi-pc-display fs-1"></i>
       
    </div>
    <div class="card-body p-4">
        <h5 class="mb-4 text-center text-muted">เข้าสู่ระบบ</h5>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-circle me-1"></i><?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label fw-semibold">ชื่อผู้ใช้</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text" name="username" class="form-control" placeholder="กรอกชื่อผู้ใช้" required>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">รหัสผ่าน</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" class="form-control" placeholder="กรอกรหัสผ่าน" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                <i class="bi bi-box-arrow-in-right me-1"></i>เข้าสู่ระบบ
            </button>
        </form>

        <hr class="my-4">
        
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>