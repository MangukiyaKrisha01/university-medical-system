<?php
session_start();
require_once '../config/db.php';
require_once '../config/helpers.php';
requireLogin('student');

$db  = getDB();
$uid = $_SESSION['user_id'];

$stmt = $db->prepare("SELECT * FROM applications WHERE student_id=? ORDER BY created_at DESC");
$stmt->bind_param("i", $uid);
$stmt->execute();
$apps = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$db->close();

$active = 'history';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Applications - University Medical System</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="layout">
<?php include '../config/sidebar.php'; ?>
<div class="main-content">
    <div class="topbar">
        <div class="topbar-left">
            <button class="menu-toggle" id="menuToggle">☰</button>
            <h2>My Applications</h2>
        </div>
        <div class="topbar-right">
            <span class="topbar-date" id="currentDate"></span>
        </div>
    </div>
    <div class="page-body">
        <div class="section-header">
            <div>
                <p class="page-title">Application History</p>
                <p class="page-subtitle">All your submitted medical leave applications</p>
            </div>
            <a href="apply.php" class="btn btn-primary" style="width:auto;padding:10px 22px;">+ New Application</a>
        </div>

        <div class="card" style="margin-bottom:16px;">
            <div class="card-body" style="padding:14px 22px;display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
                <input type="text" id="tableSearch" placeholder="🔍 Search applications..." style="flex:1;min-width:200px;padding:9px 14px;border:1.5px solid var(--border);border-radius:var(--radius-sm);font-family:inherit;font-size:0.88rem;">
                <select id="statusFilter" style="padding:9px 14px;border:1.5px solid var(--border);border-radius:var(--radius-sm);font-family:inherit;font-size:0.88rem;background:white;">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="hod approved">HOD Approved</option>
                    <option value="hod rejected">HOD Rejected</option>
                    <option value="verified">Hospital Verified</option>
                </select>
            </div>
        </div>

        <div class="card">
            <div class="card-body" style="padding:0;">
                <?php if (empty($apps)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📭</div>
                    <h3>No applications found</h3>
                    <p>You haven't submitted any medical leave applications yet.</p>
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
                        <th>HOD Remark</th>
                        <th>Receptionist Note</th>
                        <th>Applied On</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($apps as $i => $app): ?>
                    <tr>
                        <td><?= $i+1 ?></td>
                        <td style="max-width:200px;"><?= sanitize($app['reason']) ?></td>
                        <td><?= date('d M Y', strtotime($app['leave_date'])) ?></td>
                        <td><?= date('h:i A', strtotime($app['leave_time'])) ?></td>
                        <td><?= statusBadge($app['status']) ?></td>
                        <td>
                            <?php if ($app['hod_remark']): ?>
                            <div class="remark-box"><?= sanitize($app['hod_remark']) ?></div>
                            <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                        </td>
                        <td>
                            <?php if ($app['receptionist_remark']): ?>
                            <div class="remark-box"><?= sanitize($app['receptionist_remark']) ?></div>
                            <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                        </td>
                        <td><?= date('d M Y', strtotime($app['created_at'])) ?></td>
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
