<?php
session_start();
require_once '../config/db.php';
require_once '../config/helpers.php';
requireLogin('student');

$db = getDB();
$uid = $_SESSION['user_id'];

// Counts
$counts = ['total'=>0,'pending'=>0,'hod_approved'=>0,'hod_rejected'=>0,'receptionist_verified'=>0];
$stmt = $db->prepare("SELECT status, COUNT(*) as cnt FROM applications WHERE student_id=? GROUP BY status");
$stmt->bind_param("i", $uid);
$stmt->execute();
$rows = $stmt->get_result();
while ($r = $rows->fetch_assoc()) {
    $counts['total'] += $r['cnt'];
    $counts[$r['status']] = $r['cnt'];
}
$stmt->close();

// Recent 5 applications
$stmt2 = $db->prepare("SELECT * FROM applications WHERE student_id=? ORDER BY created_at DESC LIMIT 5");
$stmt2->bind_param("i", $uid);
$stmt2->execute();
$recent = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt2->close();
$db->close();

$active = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Dashboard - University Medical System</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="layout">
<?php include '../config/sidebar.php'; ?>
<div class="main-content">
    <div class="topbar">
        <div class="topbar-left">
            <button class="menu-toggle" id="menuToggle">☰</button>
            <h2>Student Dashboard</h2>
        </div>
        <div class="topbar-right">
            <span class="topbar-date" id="currentDate"></span>
        </div>
    </div>
    <div class="page-body">
        <p class="page-title">Welcome, <?= sanitize($_SESSION['name']) ?> 👋</p>
        <p class="page-subtitle">Department: <?= sanitize($_SESSION['department'] ?? 'N/A') ?> &nbsp;|&nbsp; HOD ID: <strong><?= sanitize($_SESSION['hod_id'] ?? 'N/A') ?></strong></p>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue">📋</div>
                <div class="stat-info">
                    <div class="stat-num"><?= $counts['total'] ?></div>
                    <div class="stat-label">Total Applications</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon yellow">⏳</div>
                <div class="stat-info">
                    <div class="stat-num"><?= $counts['pending'] ?></div>
                    <div class="stat-label">Pending</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green">✅</div>
                <div class="stat-info">
                    <div class="stat-num"><?= $counts['hod_approved'] ?></div>
                    <div class="stat-label">HOD Approved</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon red">❌</div>
                <div class="stat-info">
                    <div class="stat-num"><?= $counts['hod_rejected'] ?></div>
                    <div class="stat-label">HOD Rejected</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon teal">🏥</div>
                <div class="stat-info">
                    <div class="stat-num"><?= $counts['receptionist_verified'] ?></div>
                    <div class="stat-label">Hospital Verified</div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>📋 Recent Applications</h3>
                <a href="history.php" class="btn btn-outline btn-sm">View All</a>
            </div>
            <div class="card-body" style="padding:0;">
                <?php if (empty($recent)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📭</div>
                    <h3>No applications yet</h3>
                    <p>Apply for medical leave to get started.</p>
                    <br><a href="apply.php" class="btn btn-primary" style="width:auto;padding:10px 24px;">Apply Now</a>
                </div>
                <?php else: ?>
                <div class="table-wrap">
                <table>
                    <thead><tr>
                        <th>#</th>
                        <th>Reason</th>
                        <th>Leave Date</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Applied On</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($recent as $i => $app): ?>
                    <tr>
                        <td><?= $i+1 ?></td>
                        <td><?= sanitize(substr($app['reason'],0,50)) . (strlen($app['reason'])>50?'...':'') ?></td>
                        <td><?= date('d M Y', strtotime($app['leave_date'])) ?></td>
                        <td><?= date('h:i A', strtotime($app['leave_time'])) ?></td>
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

        <div style="display:flex;gap:12px;flex-wrap:wrap;">
            <a href="apply.php" class="btn btn-primary" style="width:auto;padding:12px 28px;">📝 Apply for Medical Leave</a>
            <a href="history.php" class="btn btn-outline" style="padding:12px 28px;">📋 View All Applications</a>
        </div>
    </div>
</div>
</div>
<script src="../assets/js/script.js"></script>
</body>
</html>
