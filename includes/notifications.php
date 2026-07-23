<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * เพิ่มการแจ้งเตือนสำหรับผู้ใช้คนเดียว
 */
function addNotification($pdo, $user_id, $title, $message, $link = null, $ticket_id = null) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, ticket_id, title, message, link, is_read, created_at)
            VALUES (?, ?, ?, ?, ?, 0, NOW())
        ");
        $stmt->execute([$user_id, $ticket_id, $title, $message, $link]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * เพิ่มการแจ้งเตือนสำหรับผู้ใช้กลุ่มบทบาทเฉพาะ (เช่น Manager ทั้งหมด)
 */
function addNotificationToRole($pdo, $role, $title, $message, $link = null, $ticket_id = null) {
    try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE role = ? AND status = 'active'");
        $stmt->execute([$role]);
        $users = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($users as $uid) {
            addNotification($pdo, $uid, $title, $message, $link, $ticket_id);
        }
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * นับจำนวนการแจ้งเตือนที่ยังไม่อ่าน
 */
function getUnreadNotificationCount($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * ดึงรายการแจ้งเตือนล่าสุด
 */
function getLatestNotifications($pdo, $user_id, $limit = 5) {
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM notifications 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * อ่านการแจ้งเตือนทั้งหมดของผู้ใช้
 */
function markAllNotificationsAsRead($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->execute([$user_id]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}
?>
