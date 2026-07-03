<?php
session_start();
require_once '../config/db.php';
require_once '../config/helpers.php';

if (isLoggedIn()) { redirect('../index.php'); }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role     = trim($_POST['role'] ?? '');

    if (!$email || !$password || !$role) {
        $error = 'All fields are required.';
    } else {
        $db = getDB();

        if ($role === 'hod') {
            $hod_id = trim($_POST['hod_id'] ?? '');
            if (!$hod_id) {
                $error = 'HOD ID is required for HOD login.';
            } else {
                $stmt = $db->prepare("SELECT * FROM users WHERE email=? AND role='hod' AND hod_id=? AND is_verified=1");
                $stmt->bind_param("ss", $email, $hod_id);
                $stmt->execute();
                $user = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if (!$user || !password_verify($password, $user['password'])) {
                    $error = 'Invalid credentials or unverified account.';
                }
            }
        } else {
            $stmt = $db->prepare("SELECT * FROM users WHERE email=? AND role=? AND is_verified=1");
            $stmt->bind_param("ss", $email, $role);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$user || !password_verify($password, $user['password'])) {
                $error = 'Invalid credentials or unverified account.';
            }
        }

        if (!$error && isset($user)) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['name']      = $user['name'];
            $_SESSION['email']     = $user['email'];
            $_SESSION['role']      = $user['role'];
            $_SESSION['hod_id']    = $user['hod_id'];
            $_SESSION['department']= $user['department'];

            if ($role === 'student')      redirect('../student/dashboard.php');
            elseif ($role === 'hod')      redirect('../hod/dashboard.php');
            elseif ($role === 'receptionist') redirect('../receptionist/dashboard.php');
            elseif ($role === 'admin')    redirect('../admin/dashboard.php');
        }

        if (isset($db)) $db->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - University Medical System</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="auth-page">
<div class="auth-box">
    <div class="auth-logo">
        <div class="logo-icon">🏥</div>
        <div>
            <h1>University Medical</h1>
            <p>Application System</p>
        </div>
    </div>

    <h2>Welcome Back</h2>
    <p class="subtitle">Sign in to your account to continue</p>

    <?php if ($error): ?>
    <div class="alert alert-error">⚠️ <?= sanitize($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label>Login As</label>
            <select name="role" id="roleSelect" required onchange="toggleHodField()">
                <option value="">-- Select Role --</option>
                <option value="student" <?= ($_POST['role']??'')==='student'?'selected':'' ?>>Student</option>
                <option value="hod" <?= ($_POST['role']??'')==='hod'?'selected':'' ?>>HOD (Head of Department)</option>
                <option value="receptionist" <?= ($_POST['role']??'')==='receptionist'?'selected':'' ?>>Receptionist</option>
                <option value="admin" <?= ($_POST['role']??'')==='admin'?'selected':'' ?>>Admin</option>
            </select>
        </div>

        <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" placeholder="Enter your email" value="<?= sanitize($_POST['email']??'') ?>" required>
        </div>

        <div class="form-group" id="hodIdField" style="display:none;">
            <label>HOD ID</label>
            <input type="text" name="hod_id" placeholder="e.g. HOD-CS-001" value="<?= sanitize($_POST['hod_id']??'') ?>">
        </div>

        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" placeholder="Enter your password" required>
        </div>

        <button type="submit" class="btn btn-primary">Sign In →</button>
    </form>

    <div class="auth-footer">
        <a href="forgot_password.php">Forgot Password?</a> &nbsp;|&nbsp;
        <a href="register.php">New Student? Register</a>
    </div>
</div>

<script>
function toggleHodField() {
    var role = document.getElementById('roleSelect').value;
    document.getElementById('hodIdField').style.display = role === 'hod' ? 'block' : 'none';
}
toggleHodField();
</script>
</body>
</html>
