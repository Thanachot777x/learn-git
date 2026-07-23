<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
requireRole('technician');

// ---------- กรณีกดปุ่ม "รับงาน" จากหน้า dashboard ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'accept') {
    verifyCsrfToken();

    $accept_id = (int) ($_POST['ticket_id'] ?? 0);

    if ($accept_id) {
        // ป้องกัน race condition: รับได้เฉพาะตั๋วที่ยังไม่มีใครรับ
        $stmt = $pdo->prepare("
            UPDATE tickets 
            SET assigned_to = ?, status = 'in_progress', updated_at = NOW() 
            WHERE id = ? AND assigned_to IS NULL
        ");
        $stmt->execute([$_SESSION['user_id'], $accept_id]);

        if ($stmt->rowCount() > 0) {
            $stmt2 = $pdo->prepare("
                INSERT INTO ticket_updates (ticket_id, updated_by, old_status, new_status, note, created_at)
                VALUES (?, ?, 'open', 'in_progress', 'รับงานเข้าดำเนินการ', NOW())
            ");
            $stmt2->execute([$accept_id, $_SESSION['user_id']]);

            header("Location: " . BASE_URL . "/technician/update_ticket.php?id=$accept_id&accepted=1");
            exit();
        } else {
            header("Location: " . BASE_URL . "/technician/dashboard.php?taken=1");
            exit();
        }
    }

    header("Location: " . BASE_URL . "/technician/dashboard.php");
    exit();
}

// ---------- โหลดหน้าอัปเดตตามปกติ (ต้องมี ?id=) ----------
$ticket_id = (int) ($_GET['id'] ?? 0);
if (!$ticket_id) {
    header("Location: " . BASE_URL . "/technician/dashboard.php");
    exit();
}

// ดึงข้อมูล ticket เฉพาะที่มอบหมายให้ช่างคนนี้
$stmt = $pdo->prepare("
    SELECT t.*, u.fullname as employee_name
    FROM tickets t
    JOIN users u ON t.user_id = u.id
    WHERE t.id = ? AND t.assigned_to = ?
");
$stmt->execute([$ticket_id, $_SESSION['user_id']]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    header("Location: " . BASE_URL . "/technician/dashboard.php");
    exit();
}

$errors = [];

// ---------- POST: บันทึกอัปเดต ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();

    $new_status = $_POST['status'] ?? '';
    $note       = trim($_POST['note'] ?? '');
    $old_status = $ticket['status'];

    $valid_status = ['open', 'in_progress', 'resolved', 'closed'];
    if (!in_array($new_status, $valid_status)) {
        $errors[] = "สถานะไม่ถูกต้อง";
    }
    if ($note === '') {
        $errors[] = "กรุณากรอกรายละเอียดการดำเนินงาน";
    }

    $image_name = null;

    // ---------- จัดการรูปแนบ ----------
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {

        $allowed_ext  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $allowed_mime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        $file_tmp  = $_FILES['image']['tmp_name'];
        $file_ext  = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $file_size = $_FILES['image']['size'];
        $file_mime = mime_content_type($file_tmp);

        if (!in_array($file_ext, $allowed_ext) || !in_array($file_mime, $allowed_mime)) {
            $errors[] = "ไฟล์ต้องเป็นรูปภาพเท่านั้น (jpg, png, gif, webp)";
        } elseif ($file_size > 5 * 1024 * 1024) {
            $errors[] = "ไฟล์ต้องมีขนาดไม่เกิน 5MB";
        } else {
            $image_name = uniqid('update_', true) . '.' . $file_ext;
            $upload_dir = __DIR__ . '/../uploads/updates/';

            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            if (!move_uploaded_file($file_tmp, $upload_dir . $image_name)) {
                $errors[] = "อัปโหลดไฟล์ไม่สำเร็จ กรุณาลองใหม่";
                $image_name = null;
            }
        }
    } elseif (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $errors[] = "เกิดข้อผิดพลาดในการอัปโหลดไฟล์";
    }

    // ---------- บันทึกลง DB ----------
    if (empty($errors)) {
        $pdo->beginTransaction();
        try {
            // 1. เพิ่มประวัติการอัปเดต
            $stmt = $pdo->prepare("
                INSERT INTO ticket_updates (ticket_id, updated_by, old_status, new_status, note, image, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$ticket_id, $_SESSION['user_id'], $old_status, $new_status, $note, $image_name]);

            // 2. อัปเดตสถานะ ticket หลัก
            $stmt = $pdo->prepare("
                UPDATE tickets SET status = ?, updated_at = NOW() WHERE id = ?
            ");
            $stmt->execute([$new_status, $ticket_id]);

            $pdo->commit();
            header("Location: " . BASE_URL . "/technician/update_ticket.php?id=$ticket_id&success=1");
            exit();

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง";
        }
    }
}

// ---------- โหลดประวัติล่าสุดเพื่อแสดงผล ----------
$history = $pdo->prepare("
    SELECT tu.*, u.fullname as updater
    FROM ticket_updates tu
    JOIN users u ON tu.updated_by = u.id
    WHERE tu.ticket_id = ?
    ORDER BY tu.created_at ASC
");
$history->execute([$ticket_id]);
$updates = $history->fetchAll(PDO::FETCH_ASSOC);

$status_text = ['open'=>'รอดำเนินการ','in_progress'=>'กำลังแก้ไข','resolved'=>'แก้ไขแล้ว','closed'=>'ปิดแล้ว'];
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="container-fluid" style="max-width: 900px;">

    <h4 class="mb-1"><?= htmlspecialchars($ticket['ticket_no']) ?> - <?= htmlspecialchars($ticket['title']) ?></h4>
    <p class="text-muted mb-4">แจ้งโดย: <?= htmlspecialchars($ticket['employee_name']) ?></p>

    <?php if (!empty($_GET['success'])): ?>
        <div class="alert alert-success">อัปเดตข้อมูลสำเร็จ</div>
    <?php endif; ?>

    <?php if (!empty($_GET['accepted'])): ?>
        <div class="alert alert-success">รับงานเรียบร้อย เริ่มดำเนินการได้เลย</div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

   <div class="card mb-4">
        <div class="card-body">
            <label class="form-label fw-bold">รายละเอียดปัญหา</label>
            <p class="mb-0"><?= nl2br(htmlspecialchars($ticket['description'])) ?></p>

            <?php if (!empty($ticket['device_type']) || !empty($ticket['device_name'])): ?>
            <hr>
            <div class="row small text-muted">
                <div class="col-6">ประเภทอุปกรณ์: <?= htmlspecialchars($ticket['device_type']) ?></div>
                <div class="col-6">ชื่ออุปกรณ์: <?= htmlspecialchars($ticket['device_name']) ?></div>
                <div class="col-6">Serial No.: <?= htmlspecialchars($ticket['serial_no']) ?></div>
                <div class="col-6">สถานที่: อาคาร <?= htmlspecialchars($ticket['building']) ?> ชั้น <?= htmlspecialchars($ticket['floor']) ?> ห้อง <?= htmlspecialchars($ticket['room']) ?></div>
            </div>
            <?php endif; ?>

            <?php if (!empty($ticket['image_path'])): ?>
            <div class="mt-3">
                <label class="form-label fw-bold">รูปภาพจากผู้แจ้ง</label><br>
                <a href="<?= BASE_URL ?>/<?= htmlspecialchars($ticket['image_path']) ?>" target="_blank">
                    <img src="<?= BASE_URL ?>/<?= htmlspecialchars($ticket['image_path']) ?>"
                         class="img-fluid rounded border" style="max-width:350px;">
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ฟอร์มอัปเดต -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="update_ticket.php?id=<?= $ticket_id ?>" method="POST" enctype="multipart/form-data">
                <?= csrfInput() ?>

                <div class="mb-3">
                    <label class="form-label">สถานะ</label>
                    <select name="status" class="form-select" required>
                        <?php foreach ($status_text as $key => $text): ?>
                            <option value="<?= $key ?>" <?= $ticket['status'] === $key ? 'selected' : '' ?>>
                                <?= $text ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">รายละเอียดการดำเนินงาน</label>
                    <textarea name="note" class="form-control" rows="3" required></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">แนบรูปหลักฐาน (ถ้ามี)</label>
                    <input type="file" name="image" class="form-control" accept="image/*">
                </div>

                <button type="submit" class="btn btn-primary">บันทึกการอัปเดต</button>
            </form>
        </div>
    </div>

    <!-- ประวัติ -->
    <h6 class="fw-bold mb-3">ประวัติการดำเนินงาน</h6>
    <?php if (empty($updates)): ?>
        <p class="text-muted">ยังไม่มีการดำเนินงาน</p>
    <?php else: ?>
        <?php foreach ($updates as $u): ?>
            <div class="border rounded p-3 mb-2">
                <div class="d-flex justify-content-between">
                    <strong><?= htmlspecialchars($u['updater']) ?></strong>
                    <small class="text-muted"><?= date('d/m/Y H:i', strtotime($u['created_at'])) ?></small>
                </div>
                <div class="mt-1"><?= nl2br(htmlspecialchars($u['note'])) ?></div>
                <?php if (!empty($u['image'])): ?>
                    <div class="mt-2">
                        <a href="<?= BASE_URL ?>/uploads/updates/<?= htmlspecialchars($u['image']) ?>" target="_blank">
                            <img src="<?= BASE_URL ?>/uploads/updates/<?= htmlspecialchars($u['image']) ?>" 
                                 style="max-width:180px; border-radius:8px; border:1px solid #e5e7eb;">
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>