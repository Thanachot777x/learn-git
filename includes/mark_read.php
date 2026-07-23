<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
requireLogin();

if (isset($_GET['all'])) {
    markAllNotificationsAsRead($pdo, $_SESSION['user_id']);
}

if (isset($_GET['id'])) {
    $nid = (int)$_GET['id'];
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->execute([$nid, $_SESSION['user_id']]);
}

$redirect = !empty($_GET['redirect']) ? $_GET['redirect'] : (BASE_URL . '/' . ($_SESSION['role'] ?? 'employee') . '/dashboard.php');
header("Location: " . $redirect);
exit();
?>
