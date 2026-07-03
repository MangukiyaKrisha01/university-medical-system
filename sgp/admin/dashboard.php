<?php
session_start();
require_once '../config/db.php';
require_once '../config/helpers.php';
requireLogin('admin');

$db = getDB();

// Stats
$stats = [];
$result = $db->query("SELECT role, COUNT(*) as cnt FROM users GROUP BY role");
$roleCounts = [];
while ($r = $result->fetch_assoc()) $roleCounts[$r['role']] = $r['cnt'];
$stats['students']      = $roleCounts['student'] ?? 0;
$stats['hods']          = $roleCounts['hod'] ?? 0;
$stats['receptionists'] = $roleCounts['receptionist'] ?? 0;

$result2 = $db->query("SELECT status, COUNT(*) as cnt FROM applications GROUP BY status");
$appCounts = [];
while ($r = $result2->fetch_assoc()) $appCounts[$r['status']] = $r['cnt'];
$stats['total_apps']    = array_sum($appCounts);
$stats['pending']       = $appCounts['pending'] ?? 0;
$stats['approved']      = $appCounts['hod_approved'] ?? 0;
$stats['rejected']      = $appCounts['hod_rejected'] ?? 0;
$stats['verified']      = $appCounts['receptionist_verified'] ?? 0;

// Recent applications
$stmt = $db->prepare("SELECT a.*, u.name as student_name, u.department FROM applications a JOIN users u ON a.student_id=u.id ORDER BY a.created_at DESC LIMIT 8");
$stmt->execute();
$recentApps = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// All users
$stmt2 = $db->prepare("SELECT id, name, email, role, department, hod_id, is_verified, created_at FROM users ORDER BY created_at DESC LIMIT 10");
$stmt2->execute();
$recentUsers = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt2->close();

$db->close();
$active = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard - University Medical System</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="layout">
<?php include '../config/sidebar.php'; ?>
<div class="main-content">
    <div class="topbar">
        <div class="topbar-left">
            <button class="menu-toggle" id="menuToggle">☰</button>
            <h2>Admin Dashboard</h2>
        </div>
        <div class="topbar-right">
            <span class="topbar-date" id="currentDate"></span>
        </div>
    </div>
    <div class="page-body">
        <p class="page-title">System Overview</p>
        <p class="page-subtitle">Monitor all users, applications, and system activity</p>

        <!-- User Stats -->
        <p style="font-size:0.78rem;text-transform:uppercase;letter-spacing:0.09em;color:var(--text-muted);font-weight:700;margin-bottom:12px;">👥 User Statistics</p>
        <div class="stats-grid" style="margin-bottom:12px;">
            <div class="stat-card">
                <div class="stat-icon blue">🎓</div>
                <div class="stat-info">
                    <div class="stat-num"><?= $stats['students'] ?></div>
                    <div class="stat-label">Students</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon purple">👨‍🏫</div>
                <div class="stat-info">
                    <div class="stat-num"><?= $stats['hods'] ?></div>
                    <div class="stat-label">HODs</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon teal">🏥</div>
                <div class="stat-info">
                    <div class="stat-num"><?= $stats['receptionists'] ?></div>
                    <div class="stat-label">Receptionists</div>
                </div>
            </div>
        </div>

        <!-- Application Stats -->
        <p style="font-size:0.78rem;text-transform:uppercase;letter-spacing:0.09em;color:var(--text-muted);font-weight:700;margin-bottom:12px;margin-top:8px;">📋 Application Statistics</p>
        <div class="stats-grid" style="margin-bottom:28px;">
            <div class="stat-card">
                <div class="stat-icon blue">📋</div>
                <div class="stat-info">
                    <div class="stat-num"><?= $stats['total_apps'] ?></div>
                    <div class="stat-label">Total Applications</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon yellow">⏳</div>
                <div class="stat-info">
                    <div class="stat-num"><?= $stats['pending'] ?></div>
                    <div class="stat-label">Pending</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green">✅</div>
                <div class="stat-info">
                    <div class="stat-num"><?= $stats['approved'] ?></div>
                    <div class="stat-label">HOD Approved</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon red">❌</div>
                <div class="stat-info">
                    <div class="stat-num"><?= $stats['rejected'] ?></div>
                    <div class="stat-label">Rejected</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon teal">🏥</div>
                <div class="stat-info">
                    <div class="stat-num"><?= $stats['verified'] ?></div>
                    <div class="stat-label">Hospital Verified</div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:24px;">
            <a href="add_hod.php" class="btn btn-primary" style="width:auto;padding:12px 28px;">➕ Add New HOD</a>
        </div>

        <!-- Recent Applications -->
        <div class="card">
            <div class="card-header">
                <h3>📋 Recent Applications</h3>
                <span style="color:var(--text-muted);font-size:0.82rem;">Last 8 entries</span>
            </div>
            <div class="card-body" style="padding:0;">
                <?php if (empty($recentApps)): ?>
                <div class="empty-state"><div class="empty-icon">📭</div><h3>No applications yet</h3></div>
                <?php else: ?>
                <div class="table-wrap">
                <table>
                    <thead><tr>
                        <th>#</th>
                        <th>Student</th>
                        <th>Department</th>
                        <th>Reason</th>
                        <th>Leave Date</th>
                        <th>Status</th>
                        <th>Applied On</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($recentApps as $i => $app): ?>
                    <tr>
                        <td><?= $i+1 ?></td>
                        <td><?= sanitize($app['student_name']) ?></td>
                        <td><?= sanitize($app['department']) ?></td>
                        <td style="max-width:180px;"><?= sanitize(substr($app['reason'],0,50)) ?>...</td>
                        <td><?= date('d M Y', strtotime($app['leave_date'])) ?></td>
                        <td><?= statusBadge($app['status']) ?></td>
                        <td><?= date('d M Y', strtotime($app['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Users -->
        <div class="card">
            <div class="card-header">
                <h3>👥 Recent Users</h3>
                <span style="color:var(--text-muted);font-size:0.82rem;">Last 10 registered</span>
            </div>
            <div class="card-body" style="padding:0;">
                <div class="table-wrap">
                <table>
                    <thead><tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Department</th>
                        <th>HOD ID</th>
                        <th>Verified</th>
                        <th>Joined</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($recentUsers as $i => $u): ?>
                    <tr>
                        <td><?= $i+1 ?></td>
                        <td><?= sanitize($u['name']) ?></td>
                        <td><?= sanitize($u['email']) ?></td>
                        <td><span class="badge" style="background:var(--bg);color:var(--text);border:1px solid var(--border);"><?= roleName($u['role']) ?></span></td>
                        <td><?= sanitize($u['department']??'—') ?></td>
                        <td><?= sanitize($u['hod_id']??'—') ?></td>
                        <td><?= $u['is_verified'] ? '<span style="color:var(--success-light);">✅</span>' : '<span style="color:var(--danger-light);">❌</span>' ?></td>
                        <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<script src="../assets/js/script.js"></script>
</body>
</html>
