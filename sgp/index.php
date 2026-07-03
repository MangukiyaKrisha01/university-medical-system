<?php
session_start();
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'];
    if ($role === 'student') header("Location: student/dashboard.php");
    elseif ($role === 'hod') header("Location: hod/dashboard.php");
    elseif ($role === 'receptionist') header("Location: receptionist/dashboard.php");
    elseif ($role === 'admin') header("Location: admin/dashboard.php");
    exit;
}
header("Location: auth/login.php");
exit;
?>
