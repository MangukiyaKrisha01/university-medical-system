<?php
session_start();
require_once '../config/db.php';
require_once '../config/helpers.php';
requireLogin('student');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reason     = trim($_POST['reason'] ?? '');
    $leave_date = trim($_POST['leave_date'] ?? '');
    $leave_time = trim($_POST['leave_time'] ?? '');
    $uid        = $_SESSION['user_id'];
    $hod_id     = $_SESSION['hod_id'];

    if (!$reason || !$leave_date || !$leave_time) {
        $error = 'All fields are required.';
    } elseif (!$hod_id) {
        $error = 'Your account is not linked to a HOD. Please contact admin.';
    } elseif (strtotime($leave_date) < strtotime(date('Y-m-d'))) {
        $error = 'Leave date cannot be in the past.';
    } else {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO applications (student_id, hod_id, reason, leave_date, leave_time, status) VALUES (?,?,?,?,?,'pending')");
        $stmt->bind_param("issss", $uid, $hod_id, $reason, $leave_date, $leave_time);
        if ($stmt->execute()) {
            $success = 'Application submitted successfully! Your HOD will review it.';
        } else {
            $error = 'Failed to submit application. Please try again.';
        }
        $stmt->close();
        $db->close();
    }
}

$active = 'apply';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Apply for Leave - University Medical System</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="layout">
<?php include '../config/sidebar.php'; ?>
<div class="main-content">
    <div class="topbar">
        <div class="topbar-left">
            <button class="menu-toggle" id="menuToggle">☰</button>
            <h2>Apply for Medical Leave</h2>
        </div>
        <div class="topbar-right">
            <span class="topbar-date" id="currentDate"></span>
        </div>
    </div>
    <div class="page-body">
        <p class="page-title">New Medical Leave Application</p>
        <p class="page-subtitle">Fill in the details below. Your HOD will review and approve or reject.</p>

        <?php if ($success): ?><div class="alert alert-success">✅ <?= sanitize($success) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error">⚠️ <?= sanitize($error) ?></div><?php endif; ?>

        <div class="form-card">
            <form method="POST">
                <div class="form-group">
                    <label>Reason for Medical Leave *</label>
                    <textarea name="reason" rows="4" placeholder="Describe your medical condition or reason for hospital visit..." required><?= sanitize($_POST['reason']??'') ?></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Leave Date *</label>
                        <input type="date" name="leave_date" id="leave_date" value="<?= sanitize($_POST['leave_date']??'') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Preferred Time *</label>
                        <input type="time" name="leave_time" value="<?= sanitize($_POST['leave_time']??'') ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Your Department</label>
                    <input type="text" value="<?= sanitize($_SESSION['department']??'N/A') ?>" disabled>
                </div>
                <div class="form-group">
                    <label>HOD ID</label>
                    <input type="text" value="<?= sanitize($_SESSION['hod_id']??'N/A') ?>" disabled>
                </div>
                <hr class="divider">
                <div style="display:flex;gap:12px;">
                    <button type="submit" class="btn btn-primary" style="width:auto;padding:12px 32px;">Submit Application →</button>
                    <a href="dashboard.php" class="btn btn-secondary" style="padding:12px 24px;">Cancel</a>
                </div>
            </form>
        </div>

        <div class="card mt-2">
            <div class="card-header"><h3>ℹ️ Application Process</h3></div>
            <div class="card-body">
                <div style="display:flex;flex-wrap:wrap;gap:16px;">
                    <div style="flex:1;min-width:180px;text-align:center;padding:16px;background:var(--bg);border-radius:var(--radius-sm);">
                        <div style="font-size:1.8rem;">📝</div>
                        <div style="font-weight:700;margin-top:8px;">1. Submit</div>
                        <div style="font-size:0.82rem;color:var(--text-muted);margin-top:4px;">You submit the application</div>
                    </div>
                    <div style="flex:1;min-width:180px;text-align:center;padding:16px;background:var(--bg);border-radius:var(--radius-sm);">
                        <div style="font-size:1.8rem;">👨‍🏫</div>
                        <div style="font-weight:700;margin-top:8px;">2. HOD Review</div>
                        <div style="font-size:0.82rem;color:var(--text-muted);margin-top:4px;">HOD approves or rejects</div>
                    </div>
                    <div style="flex:1;min-width:180px;text-align:center;padding:16px;background:var(--bg);border-radius:var(--radius-sm);">
                        <div style="font-size:1.8rem;">🏥</div>
                        <div style="font-weight:700;margin-top:8px;">3. Hospital Visit</div>
                        <div style="font-size:0.82rem;color:var(--text-muted);margin-top:4px;">Receptionist marks as visited</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<script src="../assets/js/script.js"></script>
</body>
</html>
