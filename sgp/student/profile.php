<?php
session_start();
require_once '../config/db.php';
require_once '../config/helpers.php';
requireLogin('student');

$db  = getDB();
$uid = $_SESSION['user_id'];

$error = '';
$success = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'update_profile') {
        $name  = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        if (!$name) {
            $error = 'Name cannot be empty.';
        } else {
            $stmt = $db->prepare("UPDATE users SET name=?, phone=? WHERE id=?");
            $stmt->bind_param("ssi", $name, $phone, $uid);
            $stmt->execute();
            $stmt->close();
            $_SESSION['name'] = $name;
            $success = 'Profile updated successfully.';
        }
    } elseif ($action === 'change_password') {
        $old = $_POST['old_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $cnf = $_POST['confirm_password'] ?? '';

        $stmt = $db->prepare("SELECT password FROM users WHERE id=?");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!password_verify($old, $row['password'])) {
            $error = 'Current password is incorrect.';
        } elseif (strlen($new) < 6) {
            $error = 'New password must be at least 6 characters.';
        } elseif ($new !== $cnf) {
            $error = 'Passwords do not match.';
        } else {
            $hashed = password_hash($new, PASSWORD_DEFAULT);
            $stmt2 = $db->prepare("UPDATE users SET password=? WHERE id=?");
            $stmt2->bind_param("si", $hashed, $uid);
            $stmt2->execute();
            $stmt2->close();
            $success = 'Password changed successfully.';
        }
    }
}

// Fetch user
$stmt = $db->prepare("SELECT * FROM users WHERE id=?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Stats
$stmt2 = $db->prepare("SELECT COUNT(*) as total FROM applications WHERE student_id=?");
$stmt2->bind_param("i", $uid);
$stmt2->execute();
$total = $stmt2->get_result()->fetch_assoc()['total'];
$stmt2->close();
$db->close();

$active = 'profile';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Profile - University Medical System</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="layout">
<?php include '../config/sidebar.php'; ?>
<div class="main-content">
    <div class="topbar">
        <div class="topbar-left">
            <button class="menu-toggle" id="menuToggle">☰</button>
            <h2>My Profile</h2>
        </div>
        <div class="topbar-right">
            <span class="topbar-date" id="currentDate"></span>
        </div>
    </div>
    <div class="page-body">
        <?php if ($error): ?><div class="alert alert-error">⚠️ <?= sanitize($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success">✅ <?= sanitize($success) ?></div><?php endif; ?>

        <div class="profile-header">
            <div class="profile-avatar"><?= getInitials($user['name']) ?></div>
            <div>
                <h2><?= sanitize($user['name']) ?></h2>
                <p>📧 <?= sanitize($user['email']) ?> &nbsp;|&nbsp; 🎓 Student</p>
                <p style="margin-top:6px;">🏫 <?= sanitize($user['department']??'N/A') ?> &nbsp;|&nbsp; 🆔 <?= sanitize($user['hod_id']??'N/A') ?></p>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;flex-wrap:wrap;">
            <!-- Profile Info -->
            <div class="card">
                <div class="card-header"><h3>👤 Account Information</h3></div>
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Full Name</div>
                            <div class="info-value"><?= sanitize($user['name']) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Email</div>
                            <div class="info-value"><?= sanitize($user['email']) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Phone</div>
                            <div class="info-value"><?= sanitize($user['phone']??'Not provided') ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Department</div>
                            <div class="info-value"><?= sanitize($user['department']??'N/A') ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">HOD ID</div>
                            <div class="info-value"><?= sanitize($user['hod_id']??'N/A') ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Total Applications</div>
                            <div class="info-value"><?= $total ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Member Since</div>
                            <div class="info-value"><?= date('d M Y', strtotime($user['created_at'])) ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit Profile -->
            <div class="card">
                <div class="card-header"><h3>✏️ Edit Profile</h3></div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_profile">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="name" value="<?= sanitize($user['name']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="tel" name="phone" value="<?= sanitize($user['phone']??'') ?>" placeholder="10-digit mobile">
                        </div>
                        <button type="submit" class="btn btn-primary" style="width:auto;padding:10px 24px;">Save Changes</button>
                    </form>
                </div>
            </div>

            <!-- Change Password -->
            <div class="card">
                <div class="card-header"><h3>🔒 Change Password</h3></div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="change_password">
                        <div class="form-group">
                            <label>Current Password</label>
                            <input type="password" name="old_password" required>
                        </div>
                        <div class="form-group">
                            <label>New Password</label>
                            <input type="password" name="new_password" placeholder="Min. 6 characters" required>
                        </div>
                        <div class="form-group">
                            <label>Confirm New Password</label>
                            <input type="password" name="confirm_password" required>
                        </div>
                        <button type="submit" class="btn btn-warning" style="width:auto;padding:10px 24px;">Change Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<script src="../assets/js/script.js"></script>
</body>
</html>
