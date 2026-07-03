<?php
// config/sidebar.php - called with $role, $active, $user
$base = str_repeat('../', substr_count($_SERVER['SCRIPT_NAME'], '/') - 2);
if (empty($base)) $base = '../';
?>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <div class="logo-icon">🏥</div>
        <div>
            <h2>UniMed</h2>
            <p>Medical System</p>
        </div>
    </div>

    <div class="sidebar-user">
        <span class="user-avatar"><?= getInitials($_SESSION['name']) ?></span>
        <div class="user-info">
            <div class="user-name"><?= sanitize($_SESSION['name']) ?></div>
            <div class="user-role"><?= roleName($_SESSION['role']) ?></div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <?php if ($_SESSION['role'] === 'student'): ?>
        <div class="nav-section-title">Student</div>
        <a href="dashboard.php" class="nav-item <?= $active==='dashboard'?'active':'' ?>"><span class="nav-icon">📊</span> Dashboard</a>
        <a href="apply.php" class="nav-item <?= $active==='apply'?'active':'' ?>"><span class="nav-icon">📝</span> Apply for Leave</a>
        <a href="history.php" class="nav-item <?= $active==='history'?'active':'' ?>"><span class="nav-icon">📋</span> My Applications</a>
        <a href="profile.php" class="nav-item <?= $active==='profile'?'active':'' ?>"><span class="nav-icon">👤</span> My Profile</a>

        <?php elseif ($_SESSION['role'] === 'hod'): ?>
        <div class="nav-section-title">HOD Panel</div>
        <a href="dashboard.php" class="nav-item <?= $active==='dashboard'?'active':'' ?>"><span class="nav-icon">📊</span> Dashboard</a>
        <a href="manage_applications.php" class="nav-item <?= $active==='manage'?'active':'' ?>"><span class="nav-icon">📋</span> Manage Applications</a>

        <?php elseif ($_SESSION['role'] === 'receptionist'): ?>
        <div class="nav-section-title">Receptionist</div>
        <a href="dashboard.php" class="nav-item <?= $active==='dashboard'?'active':'' ?>"><span class="nav-icon">📊</span> Dashboard</a>

        <?php elseif ($_SESSION['role'] === 'admin'): ?>
        <div class="nav-section-title">Administration</div>
        <a href="dashboard.php" class="nav-item <?= $active==='dashboard'?'active':'' ?>"><span class="nav-icon">📊</span> Dashboard</a>
        <a href="add_hod.php" class="nav-item <?= $active==='add_hod'?'active':'' ?>"><span class="nav-icon">➕</span> Add HOD</a>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <a href="../auth/logout.php">🚪 Logout</a>
    </div>
</aside>
