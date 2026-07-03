<?php
session_start();
require_once '../config/db.php';
require_once '../config/helpers.php';
requireLogin('hod');

$db     = getDB();
$hod_id = $_SESSION['hod_id'];

// Stats
$counts = ['total'=>0,'pending'=>0,'hod_approved'=>0,'hod_rejected'=>0,'receptionist_verified'=>0];
$stmt = $db->prepare("SELECT status, COUNT(*) as cnt FROM applications WHERE hod_id=? GROUP BY status");
$stmt->bind_param("s", $hod_id);
$stmt->execute();
$rows = $stmt->get_result();
while ($r = $rows->fetch_assoc()) {
    $counts['total'] += $r['cnt'];
    $counts[$r['status']] = $r['cnt'];
}
$stmt->close();

// Recent pending
$stmt2 = $db->prepare("SELECT a.*, u.name as student_name, u.email as student_email FROM applications a JOIN users u ON a.student_id=u.id WHERE a.hod_id=? AND a.status='pending' ORDER BY a.created_at DESC LIMIT 5");
$stmt2->bind_param("s", $hod_id);
$stmt2->execute();
$pending = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt2->close();
$db->close();

$active = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>HOD Dashboard - University Medical System</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="layout">
<?php include '../config/sidebar.php'; ?>
<div class="main-content">
    <div class="topbar">
        <div class="topbar-left">
            <button class="menu-toggle" id="menuToggle">☰</button>
            <h2>HOD Dashboard</h2>
        </div>
        <div class="topbar-right">
            <span class="topbar-date" id="currentDate"></span>
        </div>
    </div>
    <div class="page-body">
        <p class="page-title">Welcome, <?= sanitize($_SESSION['name']) ?> 👋</p>
        <p class="page-subtitle">Department: <?= sanitize($_SESSION['department']??'N/A') ?> &nbsp;|&nbsp; HOD ID: <strong><?= sanitize($hod_id) ?></strong></p>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue">📋</div>
                <div class="stat-info">
                    <div class="stat-num"><?= $counts['total'] ?></div>
                    <div class="stat-label">Total Requests</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon yellow">⏳</div>
                <div class="stat-info">
                    <div class="stat-num"><?= $counts['pending'] ?></div>
                    <div class="stat-label">Awaiting Review</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green">✅</div>
                <div class="stat-info">
                    <div class="stat-num"><?= $counts['hod_approved'] ?></div>
                    <div class="stat-label">Approved</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon red">❌</div>
                <div class="stat-info">
                    <div class="stat-num"><?= $counts['hod_rejected'] ?></div>
                    <div class="stat-label">Rejected</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon teal">🏥</div>
                <div class="stat-info">
                    <div class="stat-num"><?= $counts['receptionist_verified'] ?></div>
                    <div class="stat-label">Hospital Visited</div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>⏳ Pending Applications</h3>
                <a href="manage_applications.php" class="btn btn-outline btn-sm">Manage All</a>
            </div>
            <div class="card-body" style="padding:0;">
                <?php if (empty($pending)): ?>
                <div class="empty-state">
                    <div class="empty-icon">🎉</div>
                    <h3>No pending applications</h3>
                    <p>All caught up! No applications awaiting your review.</p>
                </div>
                <?php else: ?>
                <div class="table-wrap">
                <table>
                    <thead><tr>
                        <th>#</th>
                        <th>Student</th>
                        <th>Reason</th>
                        <th>Leave Date</th>
                        <th>Time</th>
                        <th>Action</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($pending as $i => $app): ?>
                    <tr>
                        <td><?= $i+1 ?></td>
                        <td>
                            <strong><?= sanitize($app['student_name']) ?></strong><br>
                            <small class="text-muted"><?= sanitize($app['student_email']) ?></small>
                        </td>
                        <td><?= sanitize(substr($app['reason'],0,50)) ?>...</td>
                        <td><?= date('d M Y', strtotime($app['leave_date'])) ?></td>
                        <td><?= date('h:i A', strtotime($app['leave_time'])) ?></td>
                        <td>
                            <div class="flex-gap">
                                <a href="manage_applications.php?action=approve&id=<?= $app['id'] ?>" class="btn btn-success btn-sm" data-confirm="Approve this application?">✅ Approve</a>
                                <a href="manage_applications.php?action=reject&id=<?= $app['id'] ?>" class="btn btn-danger btn-sm" data-confirm="Reject this application?">❌ Reject</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</div>
<script src="../assets/js/script.js"></script>
</body>
</html>
