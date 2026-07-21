<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir  = basename(dirname($_SERVER['PHP_SELF']));
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Support Helpdesk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #f3f4f6; font-family: 'Segoe UI', sans-serif; display: flex; min-height: 100vh; }

        /* Sidebar */
        .sidebar {
            width: 210px;
            min-height: 100vh;
            background: #1e2235;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0;
            z-index: 200;
            transition: transform 0.25s ease;
        }
        .sidebar-logo {
            padding: 20px 20px 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.07);
        }
        .sidebar-logo .logo-icon {
            width: 32px; height: 32px;
            background: #3b82f6;
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 16px;
        }
        .sidebar-logo span { color: #fff; font-size: 15px; font-weight: 600; letter-spacing: 0.3px; }
        .sidebar-section {
            padding: 14px 12px 6px;
            font-size: 10px;
            color: rgba(255,255,255,0.3);
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }
        .sidebar-menu { padding: 0 10px; flex: 1; }
        .sidebar-item {
            display: flex; align-items: center; gap: 10px;
            padding: 9px 12px;
            border-radius: 8px;
            color: rgba(255,255,255,0.55);
            font-size: 13px;
            text-decoration: none;
            margin-bottom: 2px;
            transition: background 0.15s, color 0.15s;
        }
        .sidebar-item:hover { background: rgba(255,255,255,0.07); color: #fff; }
        .sidebar-item.active { background: #3b82f6; color: #fff; }
        .sidebar-item i { font-size: 16px; width: 18px; text-align: center; }
        .sidebar-bottom { padding: 12px 10px; border-top: 1px solid rgba(255,255,255,0.07); }
        .sidebar-user { display: flex; align-items: center; gap: 10px; padding: 8px 12px; border-radius: 8px; }
        .sidebar-avatar {
            width: 32px; height: 32px; border-radius: 50%;
            background: #3b82f6; color: #fff;
            font-size: 12px; font-weight: 500;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .sidebar-user-info { flex: 1; overflow: hidden; }
        .sidebar-user-name { font-size: 12px; color: #fff; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .sidebar-user-role { font-size: 11px; color: rgba(255,255,255,0.4); }

        /* Overlay (mobile) */
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.45);
            z-index: 199;
        }
        .sidebar-overlay.show { display: block; }

        /* Topbar */
        .topbar {
            position: fixed;
            top: 0; left: 210px; right: 0;
            height: 52px;
            background: #fff;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding: 0 24px;
            gap: 16px;
            z-index: 99;
            transition: left 0.25s ease;
        }
        .topbar-greeting { font-size: 13px; color: #6b7280; margin-right: auto; }
        .topbar-greeting strong { color: #111827; }
        .topbar-btn {
            display: flex; align-items: center; gap: 6px;
            font-size: 13px; color: #6b7280;
            background: none; border: none;
            cursor: pointer; padding: 6px 10px;
            border-radius: 7px; text-decoration: none;
            transition: background 0.15s;
        }
        .topbar-btn:hover { background: #f3f4f6; color: #111827; }
        .topbar-btn.logout { color: #ef4444; }
        .topbar-btn.logout:hover { background: #fef2f2; }
        .topbar-divider { width: 1px; height: 20px; background: #e5e7eb; }
        .btn-hamburger {
            display: none;
            background: none; border: none;
            font-size: 20px; color: #374151;
            cursor: pointer; padding: 4px 8px;
            border-radius: 6px;
            margin-right: 4px;
        }
        .btn-hamburger:hover { background: #f3f4f6; }

        /* Main content */
        .main-wrap { margin-left: 210px; padding-top: 52px; flex: 1; min-height: 100vh; transition: margin-left 0.25s ease; }
        .main-content { padding: 24px; }

        /* Mobile */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .topbar { left: 0; padding: 0 16px; }
            .main-wrap { margin-left: 0; }
            .btn-hamburger { display: block; }
            .main-content { padding: 16px; }
        }
    </style>
</head>
<body>

<!-- Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <div class="logo-icon"><i class="bi bi-pc-display"></i></div>
        <span>HelpDesk</span>
    </div>

    <div class="sidebar-menu">
        <?php if (isset($_SESSION['role'])): ?>

        <?php if ($_SESSION['role'] === 'employee'): ?>
            <div class="sidebar-section">เมนู</div>
            <a href="<?= BASE_URL ?>/employee/dashboard.php"
               class="sidebar-item <?= $current_page==='dashboard.php' && $current_dir==='employee' ? 'active':'' ?>">
                <i class="bi bi-house"></i> หน้าหลัก
            </a>
            <a href="<?= BASE_URL ?>/employee/submit_ticket.php"
               class="sidebar-item <?= $current_page==='submit_ticket.php' ? 'active':'' ?>">
                <i class="bi bi-plus-circle"></i> แจ้งปัญหา
            </a>

        <?php elseif ($_SESSION['role'] === 'technician'): ?>
            <div class="sidebar-section">เมนู</div>
            <a href="<?= BASE_URL ?>/technician/dashboard.php"
               class="sidebar-item <?= $current_page==='dashboard.php' && $current_dir==='technician' ? 'active':'' ?>">
                <i class="bi bi-house"></i> หน้าหลัก
            </a>
           <a href="<?= BASE_URL ?>/technician/my_tickets.php"
   class="sidebar-item <?= $current_page==='my_tickets.php' ? 'active':'' ?>">
    <i class="bi bi-tools"></i> Ticket 
</a>

        <?php elseif ($_SESSION['role'] === 'admin'): ?>
            <div class="sidebar-section">ภาพรวม</div>
            <a href="<?= BASE_URL ?>/admin/dashboard.php"
               class="sidebar-item <?= $current_page==='dashboard.php' && $current_dir==='admin' ? 'active':'' ?>">
                <i class="bi bi-speedometer2"></i> แดชบอร์ด
            </a>
            <div class="sidebar-section">จัดการ</div>
            <a href="<?= BASE_URL ?>/admin/manage_users.php"
               class="sidebar-item <?= $current_page==='manage_users.php' ? 'active':'' ?>">
                <i class="bi bi-people"></i> ผู้ใช้งาน
            </a>
            <a href="<?= BASE_URL ?>/admin/manage_tickets.php"
               class="sidebar-item <?= $current_page==='manage_tickets.php' ? 'active':'' ?>">
                <i class="bi bi-ticket-detailed"></i> Ticket
            </a>
            <div class="sidebar-section">ตั้งค่าระบบ</div>
            <a href="<?= BASE_URL ?>/admin/manage_departments.php"
               class="sidebar-item <?= $current_page==='manage_departments.php' ? 'active':'' ?>">
                <i class="bi bi-diagram-3"></i> จัดการข้อมูลแผนก
            </a>
            <a href="<?= BASE_URL ?>/admin/manage_device_types.php"
               class="sidebar-item <?= $current_page==='manage_device_types.php' ? 'active':'' ?>">
                <i class="bi bi-pc-display"></i> จัดการข้อมูลอุปกรณ์
            </a>
            <a href="<?= BASE_URL ?>/admin/manage_buildings.php"
               class="sidebar-item <?= $current_page==='manage_buildings.php' ? 'active':'' ?>">
                <i class="bi bi-building"></i> จัดการข้อมูลอาคาร / ตึก
            </a>

        <?php elseif ($_SESSION['role'] === 'manager'): ?>
            <div class="sidebar-section">ภาพรวม</div>
            <a href="<?= BASE_URL ?>/manager/dashboard.php"
               class="sidebar-item <?= $current_page==='dashboard.php' && $current_dir==='manager' ? 'active':'' ?>">
                <i class="bi bi-speedometer2"></i> แดชบอร์ด
            </a>
            <div class="sidebar-section">จัดการ</div>
            <a href="<?= BASE_URL ?>/manager/assign_tickets.php"
               class="sidebar-item <?= $current_page==='assign_tickets.php' ? 'active':'' ?>">
                <i class="bi bi-person-check"></i> มอบหมายงาน
            </a>
        <?php endif; ?>

        <?php endif; ?>
    </div>

    <div class="sidebar-bottom">
        <?php if (isset($_SESSION['fullname'])): ?>
        <div class="sidebar-user">
            <div class="sidebar-avatar">
                <?= mb_substr($_SESSION['fullname'], 0, 2, 'UTF-8') ?>
            </div>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name"><?= htmlspecialchars($_SESSION['fullname']) ?></div>
                <div class="sidebar-user-role"><?= htmlspecialchars($_SESSION['role']) ?></div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Topbar -->
<div class="topbar">
    <button class="btn-hamburger" onclick="toggleSidebar()">
        <i class="bi bi-list"></i>
    </button>
    <span class="topbar-greeting">
        ยินดีต้อนรับ, <strong><?= htmlspecialchars($_SESSION['fullname'] ?? '') ?></strong>
    </span>
    <a href="#" class="topbar-btn"><i class="bi bi-bell"></i> แจ้งเตือน</a>
    <div class="topbar-divider"></div>
    <a href="<?= BASE_URL ?>/auth/logout.php" class="topbar-btn logout">
        <i class="bi bi-box-arrow-right"></i> ออกจากระบบ
    </a>
</div>

<!-- Main -->
<div class="main-wrap">
    <div class="main-content">

<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('sidebarOverlay').classList.toggle('show');
}
function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').classList.remove('show');
}
</script>