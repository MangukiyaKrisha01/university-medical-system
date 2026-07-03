<?php
session_start();
require_once '../config/db.php';
require_once '../config/helpers.php';
require_once '../config/mailer.php';

if (isLoggedIn()) { redirect('../index.php'); }

$error         = '';
$success       = '';
$show_otp_form = false;

// ── Handle "Start Over" — clear everything and show fresh form ──
if (isset($_GET['reset'])) {
    // Delete unverified user from DB if exists
    if (isset($_SESSION['reg_email'])) {
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM users WHERE email=? AND is_verified=0");
        $stmt->bind_param("s", $_SESSION['reg_email']);
        $stmt->execute();
        $stmt->close();
        $db->close();
    }
    // Clear all registration session variables
    unset($_SESSION['reg_email'], $_SESSION['reg_name']);
    // Redirect to fresh register page
    header("Location: register.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $step = $_POST['step'] ?? 'register';

    // ── STEP 1: Register & Send OTP via Email ────────────────
    if ($step === 'register') {
        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $hod_id   = strtoupper(trim($_POST['hod_id'] ?? ''));
        $phone    = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        if (!$name || !$email || !$hod_id || !$password) {
            $error = 'All required fields must be filled.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email address.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            $db = getDB();

            // Check HOD exists
            $stmt = $db->prepare("SELECT id FROM hods WHERE hod_id=?");
            $stmt->bind_param("s", $hod_id);
            $stmt->execute();
            $hod = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$hod) {
                $error = 'HOD ID not found. Please contact your department.';
            } else {
                // Check email unique
                $stmt2 = $db->prepare("SELECT id, is_verified FROM users WHERE email=?");
                $stmt2->bind_param("s", $email);
                $stmt2->execute();
                $existing = $stmt2->get_result()->fetch_assoc();
                $stmt2->close();

                if ($existing && $existing['is_verified'] == 1) {
                    $error = 'This email is already registered. Please login.';
                } else {
                    // Get department
                    $stmt3 = $db->prepare("SELECT department FROM hods WHERE hod_id=?");
                    $stmt3->bind_param("s", $hod_id);
                    $stmt3->execute();
                    $hodRow = $stmt3->get_result()->fetch_assoc();
                    $stmt3->close();

                    $otp    = generateOTP();
                    $expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                    $hashed = password_hash($password, PASSWORD_DEFAULT);
                    $dept   = $hodRow['department'];
                    $phone  = preg_replace('/[^0-9]/', '', $phone);

                    if ($existing && $existing['is_verified'] == 0) {
                        // Update existing unverified record
                        $stmt4 = $db->prepare("UPDATE users SET name=?, password=?, hod_id=?, department=?, phone=?, otp=?, otp_expiry=? WHERE email=?");
                        $stmt4->bind_param("ssssssss", $name, $hashed, $hod_id, $dept, $phone, $otp, $expiry, $email);
                        $stmt4->execute();
                        $stmt4->close();
                    } else {
                        // Insert new user
                        $stmt4 = $db->prepare("INSERT INTO users (name, email, password, role, hod_id, department, phone, otp, otp_expiry, is_verified) VALUES (?,?,?,'student',?,?,?,?,?,0)");
                        $stmt4->bind_param("ssssssss", $name, $email, $hashed, $hod_id, $dept, $phone, $otp, $expiry);
                        $stmt4->execute();
                        $stmt4->close();
                    }

                    // Try sending OTP email
                    $sent = sendOTPEmail($email, $name, $otp, 'registration');

                    if (!$sent) {
                        // ── MAIL FAILED ──
                        // Delete the unverified user so they can retry cleanly
                        $stmtDel = $db->prepare("DELETE FROM users WHERE email=? AND is_verified=0");
                        $stmtDel->bind_param("s", $email);
                        $stmtDel->execute();
                        $stmtDel->close();

                        // Clear any existing session
                        unset($_SESSION['reg_email'], $_SESSION['reg_name']);

                        // Show error on registration form (not OTP form)
                        $error = 'Failed to send OTP email. Please check your mailer.php Gmail configuration and try again.';
                        // show_otp_form stays false → registration form shown again
                    } else {
                        // ── MAIL SENT SUCCESSFULLY ──
                        $_SESSION['reg_email'] = $email;
                        $_SESSION['reg_name']  = $name;
                        $show_otp_form = true;
                    }
                }
            }
            $db->close();
        }

    // ── STEP 2: Verify OTP ───────────────────────────────────
    } elseif ($step === 'verify_otp') {
        $otp_entered = trim($_POST['otp'] ?? '');
        $reg_email   = $_SESSION['reg_email'] ?? '';

        if (!$otp_entered || !$reg_email) {
            $error = 'Session expired. Please register again.';
            unset($_SESSION['reg_email'], $_SESSION['reg_name']);
        } else {
            $db = getDB();
            $stmt = $db->prepare("SELECT id, otp, otp_expiry FROM users WHERE email=? AND is_verified=0");
            $stmt->bind_param("s", $reg_email);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$row) {
                $error = 'Account not found or already verified.';
                unset($_SESSION['reg_email'], $_SESSION['reg_name']);
            } elseif ($row['otp'] !== $otp_entered) {
                $error = 'Incorrect OTP. Please try again.';
                $show_otp_form = true;
            } elseif (strtotime($row['otp_expiry']) < time()) {
                $error = 'OTP has expired. Please register again.';
                // Delete expired unverified user
                $stmtDel = $db->prepare("DELETE FROM users WHERE email=? AND is_verified=0");
                $stmtDel->bind_param("s", $reg_email);
                $stmtDel->execute();
                $stmtDel->close();
                unset($_SESSION['reg_email'], $_SESSION['reg_name']);
            } else {
                // ── OTP CORRECT ──
                $stmt2 = $db->prepare("UPDATE users SET is_verified=1, otp=NULL, otp_expiry=NULL WHERE id=?");
                $stmt2->bind_param("i", $row['id']);
                $stmt2->execute();
                $stmt2->close();
                unset($_SESSION['reg_email'], $_SESSION['reg_name']);
                $success = 'Email verified! Your account is ready. You can now log in.';
            }
            $db->close();
        }

    // ── STEP 3: Resend OTP ───────────────────────────────────
    } elseif ($step === 'resend_otp') {
        $reg_email = $_SESSION['reg_email'] ?? '';
        $reg_name  = $_SESSION['reg_name'] ?? 'Student';

        if (!$reg_email) {
            $error = 'Session expired. Please register again.';
            unset($_SESSION['reg_email'], $_SESSION['reg_name']);
        } else {
            $db     = getDB();
            $otp    = generateOTP();
            $expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            $stmt = $db->prepare("UPDATE users SET otp=?, otp_expiry=? WHERE email=? AND is_verified=0");
            $stmt->bind_param("sss", $otp, $expiry, $reg_email);
            $stmt->execute();
            $stmt->close();
            $db->close();

            $sent = sendOTPEmail($reg_email, $reg_name, $otp, 'registration');

            if ($sent) {
                $show_otp_form = true;
                $success = 'A new OTP has been sent to your email.';
            } else {
                // Resend also failed — clean up and go back to register form
                $db2 = getDB();
                $stmtDel = $db2->prepare("DELETE FROM users WHERE email=? AND is_verified=0");
                $stmtDel->bind_param("s", $reg_email);
                $stmtDel->execute();
                $stmtDel->close();
                $db2->close();
                unset($_SESSION['reg_email'], $_SESSION['reg_name']);
                $error = 'Failed to send OTP email again. Please check your mailer.php configuration and try registering again.';
                // show_otp_form stays false → back to register form
            }
        }
    }
}

// Keep OTP form visible if session active and no errors on register form
if (!$show_otp_form && isset($_SESSION['reg_email']) && !$success && empty($error)) {
    $show_otp_form = true;
}

// Mask email for display
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
<title>Register - University Medical System</title>
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

    <?php if ($success && !$show_otp_form): ?>
    <!-- ── Success Screen ── -->
    <div style="text-align:center;padding:10px 0 4px;">
        <div style="font-size:3rem;margin-bottom:10px;">🎉</div>
        <h2 style="color:var(--success);margin-bottom:6px;">Verified!</h2>
        <p class="subtitle"><?= sanitize($success) ?></p>
        <a href="login.php" class="btn btn-primary" style="margin-top:10px;">Go to Login →</a>
    </div>

    <?php elseif ($show_otp_form): ?>
    <!-- ── OTP Verification Form ── -->
    <h2>Verify Your Email</h2>
    <p class="subtitle">Enter the OTP sent to your email address.</p>

    <?php if ($error): ?>
    <div class="alert alert-error">⚠️ <?= sanitize($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
    <div class="alert alert-success">✅ <?= sanitize($success) ?></div>
    <?php endif; ?>

    <!-- Email sent notice -->
    <div style="background:#e8f4fc;border:1px solid #b8d9f0;border-radius:var(--radius-sm);padding:14px 16px;margin-bottom:20px;display:flex;align-items:flex-start;gap:10px;">
        <span style="font-size:1.5rem;line-height:1.2;">📧</span>
        <div>
            <div style="font-weight:700;font-size:0.92rem;color:var(--primary-dark);">OTP sent to your email</div>
            <div style="font-size:0.82rem;color:var(--text-muted);margin-top:3px;">
                Check inbox at <strong><?= maskEmail($_SESSION['reg_email'] ?? '') ?></strong><br>
                Also check <strong>Spam / Junk</strong> folder.
            </div>
        </div>
    </div>

    <form method="POST">
        <input type="hidden" name="step" value="verify_otp">
        <div class="form-group">
            <label>Enter 6-Digit OTP</label>
            <input type="text" name="otp" maxlength="6" placeholder="000000"
                   style="font-size:1.6rem;letter-spacing:0.4em;text-align:center;font-weight:800;"
                   inputmode="numeric" pattern="[0-9]{6}" required autofocus>
            <small style="color:var(--text-muted);font-size:0.78rem;margin-top:6px;display:block;">
                ⏱ OTP expires in 15 minutes
            </small>
        </div>
        <button type="submit" class="btn btn-primary">Verify & Activate Account →</button>
    </form>

    <div style="text-align:center;margin-top:16px;font-size:0.87rem;">
        <!-- Resend OTP -->
        <form method="POST" style="display:inline;">
            <input type="hidden" name="step" value="resend_otp">
            <button type="submit" style="background:none;border:none;color:var(--primary);font-size:0.87rem;cursor:pointer;font-weight:600;text-decoration:underline;padding:0;">
                🔄 Resend OTP
            </button>
        </form>
        &nbsp;&nbsp;|&nbsp;&nbsp;
        <!-- Start Over — clears session + DB and opens fresh form -->
        <a href="register.php?reset=1" style="color:var(--text-muted);font-size:0.87rem;">
            ← Register with Different Email
        </a>
    </div>

    <?php else: ?>
    <!-- ── Registration Form ── -->
    <h2>Create Account</h2>
    <p class="subtitle">Register as a student</p>

    <?php if ($error): ?>
    <div class="alert alert-error">⚠️ <?= sanitize($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="step" value="register">
        <div class="form-group">
            <label>Full Name *</label>
            <input type="text" name="name" placeholder="Your full name"
                   value="<?= sanitize($_POST['name'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label>Email Address * <small style="color:var(--text-muted);font-weight:400;">(OTP will be sent here)</small></label>
            <input type="email" name="email" placeholder="your@email.com"
                   value="<?= sanitize($_POST['email'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label>Phone Number</label>
            <input type="tel" name="phone" placeholder="10-digit mobile number"
                   value="<?= sanitize($_POST['phone'] ?? '') ?>" maxlength="10">
        </div>
        <div class="form-group">
            <label>HOD ID (from your department) *</label>
            <input type="text" name="hod_id" placeholder="e.g. HOD-CS-001"
                   value="<?= sanitize($_POST['hod_id'] ?? '') ?>" required>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Password *</label>
                <input type="password" name="password" placeholder="Min. 6 chars" required>
            </div>
            <div class="form-group">
                <label>Confirm Password *</label>
                <input type="password" name="confirm_password" placeholder="Repeat password" required>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Register & Send OTP →</button>
    </form>
    <div class="auth-footer">Already registered? <a href="login.php">Sign In</a></div>
    <?php endif; ?>
</div>
</body>
</html>