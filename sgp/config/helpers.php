<?php
// Shared helper functions

function redirect($url) {
    header("Location: $url");
    exit;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin($role = null) {
    if (!isset($_SESSION['user_id'])) {
        redirect('../auth/login.php');
    }
    if ($role && $_SESSION['role'] !== $role) {
        redirect('../index.php');
    }
}

function sanitize($str) {
    return htmlspecialchars(strip_tags(trim($str)));
}

function generateOTP() {
    return str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
}

function generateHodId($department) {
    $prefix = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $department), 0, 3));
    $num = str_pad(rand(100, 999), 3, '0', STR_PAD_LEFT);
    return 'HOD-' . $prefix . '-' . $num;
}

function statusBadge($status) {
    $map = [
        'pending'               => ['label' => '⏳ Pending',            'class' => 'badge-pending'],
        'hod_approved'          => ['label' => '✅ HOD Approved',        'class' => 'badge-approved'],
        'hod_rejected'          => ['label' => '❌ HOD Rejected',        'class' => 'badge-rejected'],
        'receptionist_verified' => ['label' => '🏥 Hospital Verified',   'class' => 'badge-verified'],
    ];
    $s = $map[$status] ?? ['label' => ucfirst($status), 'class' => 'badge-pending'];
    return '<span class="badge ' . $s['class'] . '">' . $s['label'] . '</span>';
}

function getInitials($name) {
    $parts = explode(' ', trim($name));
    $initials = '';
    foreach ($parts as $p) {
        $initials .= strtoupper(substr($p, 0, 1));
    }
    return substr($initials, 0, 2);
}

function roleName($role) {
    $map = ['student' => 'Student', 'hod' => 'Head of Department', 'receptionist' => 'Receptionist', 'admin' => 'Administrator'];
    return $map[$role] ?? ucfirst($role);
}
?>
