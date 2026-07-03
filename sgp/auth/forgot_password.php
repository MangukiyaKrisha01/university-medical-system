<?php
session_start();
require_once '../config/db.php';
require_once '../config/helpers.php';
require_once '../config/mailer.php';

if (isLoggedIn()) { redirect('../index.php'); }

$error   = '';
$success = '';
$step    = $_SESSION['fp_step'] ?? 'email';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── STEP 1: Find account & send OTP ──────────────────────
    if ($action === 'send_otp') {
        $email = trim($_POST['email'] ?? '');

        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            $db = getDB();
            $stmt = $db->prepare("SELECT id, name FROM users WHERE email=? AND is_verified=1");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$user) {
                $error = 'No verified account found with this email.';
            } else {
                $otp    = generateOTP();
                $expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                $stmt2  = $db->prepare("UPDATE users SET otp=?, otp_expiry=? WHERE id=?");
                $stmt2->bind_param("ssi", $otp, $expiry, $user['id']);
                $stmt2->execute();
                $stmt2->close();

                $sent = sendOTPEmail($email, $user['name'], $otp, 'reset');

                $_SESSION['fp_email'] = $email;
                $_SESSION['fp_name']  = $user['name'];
                $_SESSION['fp_step']  = 'otp';
                $step = 'otp';

                if (!$sent) {
                    $error = 'Failed to send OTP. Please check mailer.php configuration.';
                }
            }
            $db->close();
        }

    // ── STEP 2: Verify OTP ───────────────────────────────────
    } elseif ($action === 'verify_otp') {
        $otp_entered = trim($_POST['otp'] ?? '');
        $fp_email    = $_SESSION['fp_email'] ?? '';

        if (!$otp_entered || !$fp_email) {
            $error = 'Session expired. Please start again.';
            $step  = 'email';
            unset($_SESSION['fp_step'], $_SESSION['fp_email'], $_SESSION['fp_name']);
        } else {
            $db = getDB();
            $stmt = $db->prepare("SELECT id, otp, otp_expiry FROM users WHERE email=?");
            $stmt->bind_param("s", $fp_email);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$row || $row['otp'] !== $otp_entered) {
                $error = 'Incorrect OTP. Please try again.';
                $step  = 'otp';
            } elseif (strtotime($row['otp_expiry']) < time()) {
                $error = 'OTP has expired. Please start again.';
                unset($_SESSION['fp_step'], $_SESSION['fp_email'], $_SESSION['fp_name']);
                $step = 'email';
            } else {
                $_SESSION['fp_step'] = 'reset';
                $_SESSION['fp_uid']  = $row['id'];
                $step = 'reset';
            }
            $db->close();
        }

    // ── STEP 2b: Resend OTP ──────────────────────────────────
    } elseif ($action === 'resend_otp') {
        $fp_email = $_SESSION['fp_email'] ?? '';
        $fp_name  = $_SESSION['fp_name'] ?? 'User';

        if (!$fp_email) {
            $error = 'Session expired. Please start again.';
            $step  = 'email';
        } else {
            $db     = getDB();
            $otp    = generateOTP();
            $expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            $stmt = $db->prepare("UPDATE users SET otp=?, otp_expiry=? WHERE email=?");
            $stmt->bind_param("sss", $otp, $expiry, $fp_email);
            $stmt->execute();
            $stmt->close();
            $db->close();

            $sent  = sendOTPEmail($fp_email, $fp_name, $otp, 'reset');
            $step  = 'otp';
            $_SESSION['fp_step'] = 'otp';
            $success = $sent ? 'A new OTP has been sent to your email.' : 'Failed to resend OTP. Check mailer.php config.';
        }

    // ── STEP 3: Reset Password ───────────────────────────────
    } elseif ($action === 'reset_password') {
        $uid     = $_SESSION['fp_uid'] ?? 0;
        $newpass = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!$uid) {
            $error = 'Session expired. Please start again.';
            $step  = 'email';
            unset($_SESSION['fp_step'], $_SESSION['fp_email'], $_SESSION['fp_name'], $_SESSION['fp_uid']);
        } elseif (strlen($newpass) < 6) {
            $error = 'Password must be at least 6 characters.';
            $step  = 'reset';
        } elseif ($newpass !== $confirm) {
            $error = 'Passwords do not match.';
            $step  = 'reset';
        } else {
            $db     = getDB();
            $hashed = password_hash($newpass, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET password=?, otp=NULL, otp_expiry=NULL WHERE id=?");
            $stmt->bind_param("si", $hashed, $uid);
            $stmt->execute();
            $stmt->close();
            $db->close();

            unset($_SESSION['fp_step'], $_SESSION['fp_email'], $_SESSION['fp_name'], $_SESSION['fp_uid']);
            $success = 'Password reset successfully! You can now log in.';
            $step    = 'done';
        }
    }
}

function maskEmail($email) {
    $parts = explode('@', $email);
    if (count($parts) !== 2) return $email;
    $name    = $parts[0];
    $domain  = $parts[1];
    $visible = substr($name, 0, 3);
    $masked  = str_repeat('*', max(strlen($name) - 3, 3));
    return $visible . $masked . '@' . $domain;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password - University Medical System</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="auth-page">
<div class="auth-box">
    <div class="auth-logo">
        <div class="logo-icon">🏥</div>
        <div><h1>University Medical</h1><p>Application System</p></div>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-error">⚠️ <?= sanitize($error) ?></div>
    <?php endif; ?>
    <?php if ($success && $step !== 'otp'): ?>
    <div class="alert alert-success">✅ <?= sanitize($success) ?></div>
    <?php endif; ?>

    <?php if ($step === 'done'): ?>
    <!-- ── Done ── -->
    <div style="text-align:center;padding:10px 0 4px;">
        <div style="font-size:3rem;margin-bottom:10px;">🔓</div>
        <h2 style="color:var(--success);margin-bottom:6px;">Password Reset!</h2>
        <p class="subtitle">Your password has been updated successfully.</p>
        <a href="login.php" class="btn btn-primary" style="margin-top:10px;">Go to Login →</a>
    </div>

    <?php elseif ($step === 'email'): ?>
    <!-- ── Step 1: Enter Email ── -->
    <h2>Forgot Password</h2>
    <p class="subtitle">Enter your registered email to receive a reset OTP.</p>
    <form method="POST">
        <input type="hidden" name="action" value="send_otp">
        <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" placeholder="your@email.com" required autofocus>
        </div>
        <button type="submit" class="btn btn-primary">Send OTP to Email →</button>
    </form>
    <div class="auth-footer"><a href="login.php">← Back to Login</a></div>

    <?php elseif ($step === 'otp'): ?>
    <!-- ── Step 2: Enter OTP ── -->
    <h2>Enter OTP</h2>
    <p class="subtitle">Enter the OTP sent to your email address.</p>

    <?php if ($success): ?>
    <div class="alert alert-success">✅ <?= sanitize($success) ?></div>
    <?php endif; ?>

    <div style="background:#e8f4fc;border:1px solid #b8d9f0;border-radius:var(--radius-sm);padding:14px 16px;margin-bottom:20px;display:flex;align-items:flex-start;gap:10px;">
        <span style="font-size:1.5rem;line-height:1.2;">📧</span>
        <div>
            <div style="font-weight:700;font-size:0.92rem;color:var(--primary-dark);">OTP sent to your email</div>
            <div style="font-size:0.82rem;color:var(--text-muted);margin-top:3px;">
                Check inbox at <strong><?= maskEmail($_SESSION['fp_email'] ?? '') ?></strong><br>
                Also check <strong>Spam / Junk</strong> folder.
            </div>
        </div>
    </div>

    <form method="POST">
        <input type="hidden" name="action" value="verify_otp">
        <div class="form-group">
            <label>Enter 6-Digit OTP</label>
            <input type="text" name="otp" maxlength="6" placeholder="000000"
                   style="font-size:1.6rem;letter-spacing:0.4em;text-align:center;font-weight:800;"
                   inputmode="numeric" pattern="[0-9]{6}" required autofocus>
            <small style="color:var(--text-muted);font-size:0.78rem;margin-top:6px;display:block;">⏱ OTP expires in 15 minutes</small>
        </div>
        <button type="submit" class="btn btn-primary">Verify OTP →</button>
    </form>

    <div style="text-align:center;margin-top:16px;font-size:0.87rem;">
        <form method="POST" style="display:inline;">
            <input type="hidden" name="action" value="resend_otp">
            <button type="submit" style="background:none;border:none;color:var(--primary);font-size:0.87rem;cursor:pointer;font-weight:600;text-decoration:underline;padding:0;">
                🔄 Resend OTP
            </button>
        </form>
        &nbsp;&nbsp;|&nbsp;&nbsp;
        <a href="forgot_password.php" style="color:var(--text-muted);">← Start Over</a>
    </div>

    <?php elseif ($step === 'reset'): ?>
    <!-- ── Step 3: New Password ── -->
    <h2>Reset Password</h2>
    <p class="subtitle">Choose a strong new password.</p>
    <form method="POST">
        <input type="hidden" name="action" value="reset_password">
        <div class="form-group">
            <label>New Password</label>
            <input type="password" name="new_password" placeholder="Min. 6 characters" required autofocus>
        </div>
        <div class="form-group">
            <label>Confirm New Password</label>
            <input type="password" name="confirm_password" placeholder="Repeat new password" required>
        </div>
        <button type="submit" class="btn btn-primary">Reset Password →</button>
    </form>
    <?php endif; ?>
</div>
</body>
</html>