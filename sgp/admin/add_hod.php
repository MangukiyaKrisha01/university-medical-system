<?php
session_start();
require_once '../config/db.php';
require_once '../config/helpers.php';
requireLogin('admin');

$db      = getDB();
$error   = '';
$success = '';
$newHodId = '';

// Handle add HOD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name       = trim($_POST['name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $password   = $_POST['password'] ?? '';
    $confirm    = $_POST['confirm_password'] ?? '';

    if (!$name || !$email || !$department || !$password) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        // Check email unique
        $stmt = $db->prepare("SELECT id FROM users WHERE email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($existing) {
            $error = 'This email is already registered.';
        } else {
            // Generate unique HOD ID
            do {
                $hodId = generateHodId($department);
                $stmt2 = $db->prepare("SELECT id FROM hods WHERE hod_id=?");
                $stmt2->bind_param("s", $hodId);
                $stmt2->execute();
                $dupCheck = $stmt2->get_result()->fetch_assoc();
                $stmt2->close();
            } while ($dupCheck);

            $hashed = password_hash($password, PASSWORD_DEFAULT);

            // Insert user
            $stmt3 = $db->prepare("INSERT INTO users (name, email, password, role, hod_id, department, is_verified) VALUES (?,?,'hod',?,?,?,1)");
            // Rewrite with correct param binding
            $stmt3->close();

            $stmt3 = $db->prepare("INSERT INTO users (name, email, password, role, hod_id, department, is_verified) VALUES (?,?,?,'hod',?,?,1)");
            $stmt3->bind_param("sssss", $name, $email, $hashed, $hodId, $department);
            $stmt3->execute();
            $userId = $db->insert_id;
            $stmt3->close();

            // Insert HOD record
            $stmt4 = $db->prepare("INSERT INTO hods (user_id, hod_id, department) VALUES (?,?,?)");
            $stmt4->bind_param("iss", $userId, $hodId, $department);
            $stmt4->execute();
            $stmt4->close();

            $newHodId = $hodId;
            $success  = "HOD '{$name}' added successfully for department '{$department}'.";
        }
    }
}

// Fetch all HODs
$stmt5 = $db->prepare("SELECT u.id, u.name, u.email, u.department, h.hod_id, u.created_at FROM users u JOIN hods h ON u.id=h.user_id WHERE u.role='hod' ORDER BY u.created_at DESC");
$stmt5->execute();
$hods = $stmt5->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt5->close();
$db->close();

$active = 'add_hod';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add HOD - University Medical System</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="layout">
<?php include '../config/sidebar.php'; ?>
<div class="main-content">
    <div class="topbar">
        <div class="topbar-left">
            <button class="menu-toggle" id="menuToggle">☰</button>
            <h2>Add HOD</h2>
        </div>
        <div class="topbar-right">
            <span class="topbar-date" id="currentDate"></span>
        </div>
    </div>
    <div class="page-body">
        <p class="page-title">Manage Heads of Department</p>
        <p class="page-subtitle">Add new HODs and view existing ones. HOD IDs are auto-generated.</p>

        <?php if ($error): ?><div class="alert alert-error">⚠️ <?= sanitize($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success">✅ <?= sanitize($success) ?></div><?php endif; ?>

        <?php if ($newHodId): ?>
        <div class="hod-id-display">
            <div style="font-size:0.85rem;font-weight:600;color:#6b3a00;margin-bottom:6px;">🎉 Generated HOD ID</div>
            <div class="hod-id-code"><?= sanitize($newHodId) ?></div>
            <p>Share this ID with the HOD — students will use it during registration.</p>
        </div>
        <?php endif; ?>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
            <!-- Add HOD Form -->
            <div class="card">
                <div class="card-header"><h3>➕ Add New HOD</h3></div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-group">
                            <label>Full Name *</label>
                            <input type="text" name="name" placeholder="Dr. Full Name" value="<?= sanitize($_POST['name']??'') ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email Address *</label>
                            <input type="email" name="email" placeholder="hod@university.edu" value="<?= sanitize($_POST['email']??'') ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Department *</label>
                            <input type="text" name="department" placeholder="e.g. Computer Science" value="<?= sanitize($_POST['department']??'') ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Password *</label>
                            <input type="password" name="password" placeholder="Min. 6 characters" required>
                        </div>
                        <div class="form-group">
                            <label>Confirm Password *</label>
                            <input type="password" name="confirm_password" placeholder="Repeat password" required>
                        </div>
                        <div class="alert alert-info" style="font-size:0.84rem;">
                            ℹ️ A unique HOD ID will be automatically generated. Share it with the HOD so students can register under their department.
                        </div>
                        <button type="submit" class="btn btn-primary">Generate HOD ID & Add →</button>
                    </form>
                </div>
            </div>

            <!-- HODs List -->
            <div class="card">
                <div class="card-header">
                    <h3>👨‍🏫 Existing HODs</h3>
                    <span style="background:var(--primary);color:white;padding:4px 12px;border-radius:20px;font-size:0.8rem;font-weight:700;"><?= count($hods) ?></span>
                </div>
                <div class="card-body" style="padding:0;">
                    <?php if (empty($hods)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">👨‍🏫</div>
                        <h3>No HODs yet</h3>
                        <p>Add your first HOD using the form.</p>
                    </div>
                    <?php else: ?>
                    <?php foreach ($hods as $hod): ?>
                    <div style="padding:16px 20px;border-bottom:1px solid var(--bg-dark);display:flex;align-items:center;gap:14px;">
                        <div style="width:42px;height:42px;background:linear-gradient(135deg,var(--primary),var(--primary-light));border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:0.9rem;flex-shrink:0;">
                            <?= getInitials($hod['name']) ?>
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div style="font-weight:700;font-size:0.92rem;"><?= sanitize($hod['name']) ?></div>
                            <div style="font-size:0.8rem;color:var(--text-muted);"><?= sanitize($hod['email']) ?></div>
                            <div style="font-size:0.8rem;color:var(--text-muted);"><?= sanitize($hod['department']) ?></div>
                        </div>
                        <div style="text-align:right;flex-shrink:0;">
                            <div style="font-family:'Courier New',monospace;font-size:0.82rem;font-weight:700;color:var(--primary);background:var(--bg);padding:4px 8px;border-radius:6px;border:1px solid var(--border);">
                                <?= sanitize($hod['hod_id']) ?>
                            </div>
                            <div style="font-size:0.72rem;color:var(--text-muted);margin-top:4px;"><?= date('d M Y', strtotime($hod['created_at'])) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<script src="../assets/js/script.js"></script>
</body>
</html>
