<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: " . BASE_URL . "/auth/login.php");
        exit();
    }
}

function requireRole($role) {
    requireLogin();
    $allowed = is_array($role) ? $role : [$role];
    if (!in_array($_SESSION['role'], $allowed)) {
        header("Location: " . BASE_URL . "/" . $_SESSION['role'] . "/dashboard.php");
        exit();
    }
}

function getUserRole() {
    return $_SESSION['role'] ?? null;
}
?>