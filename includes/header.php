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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #f8fafc;
            font-family: 'Prompt', 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            display: flex;
            min-height: 100vh;
            color: #334155;
        }

        /* Sidebar */
        .sidebar {
            width: 240px;
            min-height: 100vh;
            background: linear-gradient(180deg, #0f172a 0%, #1e293b 100%);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0;
            z-index: 200;
            transition: transform 0.25s ease;
            box-shadow: 4px 0 24px rgba(0,0,0,0.12);
        }
        .sidebar-logo {
            padding: 24px 20px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        .sidebar-logo .logo-icon {
            width: 36px; height: 36px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 18px;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.35);
        }
        .sidebar-logo span { color: #f8fafc; font-size: 17px; font-weight: 700; letter-spacing: 0.5px; }
        .sidebar-section {
            padding: 16px 16px 6px;
            font-size: 11px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }
        .sidebar-menu { padding: 8px 12px; flex: 1; overflow-y: auto; }
        .sidebar-item {
            display: flex; align-items: center; gap: 12px;
            padding: 10px 14px;
            border-radius: 10px;
            color: #94a3b8;
            font-size: 13.5px;
            font-weight: 500;
            text-decoration: none;
            margin-bottom: 4px;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .sidebar-item:hover { background: rgba(255,255,255,0.06); color: #f8fafc; transform: translateX(3px); }
        .sidebar-item.active {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #fff;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
            font-weight: 600;
        }
        .sidebar-item i { font-size: 17px; width: 20px; text-align: center; }
        .sidebar-bottom { padding: 14px 12px; border-top: 1px solid rgba(255,255,255,0.06); }
        .sidebar-user { display: flex; align-items: center; gap: 12px; padding: 10px 12px; border-radius: 10px; background: rgba(255,255,255,0.04); }
        .sidebar-avatar {
            width: 36px; height: 36px; border-radius: 50%;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: #fff;
            font-size: 13px; font-weight: 600;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.3);
        }
        .sidebar-user-info { flex: 1; overflow: hidden; }
        .sidebar-user-name { font-size: 13px; color: #f8fafc; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .sidebar-user-role { font-size: 11px; color: #94a3b8; text-transform: capitalize; }

        /* Overlay (mobile) */
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            z-index: 199;
        }
        .sidebar-overlay.show { display: block; }

        /* Topbar */
        .topbar {
            position: fixed;
            top: 0; left: 240px; right: 0;
            height: 60px;
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding: 0 28px;
            gap: 16px;
            z-index: 99;
            transition: left 0.25s ease;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }
        .topbar-greeting { font-size: 13.5px; color: #64748b; margin-right: auto; }
        .topbar-greeting strong { color: #0f172a; font-weight: 600; }
        .topbar-btn {
            display: flex; align-items: center; gap: 8px;
            font-size: 13px; color: #64748b; font-weight: 500;
            background: none; border: none;
            cursor: pointer; padding: 7px 12px;
            border-radius: 8px; text-decoration: none;
            transition: all 0.15s ease;
        }
        .topbar-btn:hover { background: #f1f5f9; color: #0f172a; }
        .topbar-btn.logout { color: #ef4444; }
        .topbar-btn.logout:hover { background: #fef2f2; color: #dc2626; }
        .topbar-divider { width: 1px; height: 22px; background: #cbd5e1; }
        .btn-hamburger {
            display: none;
            background: none; border: none;
            font-size: 22px; color: #334155;
            cursor: pointer; padding: 6px 10px;
            border-radius: 8px;
            margin-right: 8px;
        }
        .btn-hamburger:hover { background: #f1f5f9; }

        /* Main content */
        .main-wrap { margin-left: 240px; padding-top: 60px; flex: 1; min-height: 100vh; transition: margin-left 0.25s ease; }
        .main-content { padding: 28px; max-width: 1400px; margin: 0 auto; }

        /* Mobile */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .topbar { left: 0; padding: 0 18px; }
            .main-wrap { margin-left: 0; }
            .btn-hamburger { display: block; }
            .main-content { padding: 18px; }
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
            <a href="<?= BASE_URL ?>/technician/dashboard.php?filter=assigned"
               class="sidebar-item <?= isset($_GET['filter']) && $_GET['filter']==='assigned' ? 'active':'' ?>">
                <i class="bi bi-tools"></i> Ticket ของฉัน
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