<?php
session_start();
require_once '../config/db.php';
require_once '../config/helpers.php';
requireLogin('receptionist');

$db  = getDB();
$msg = '';

// Handle mark as visited
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id     = (int)($_POST['app_id'] ?? 0);
    $remark = trim($_POST['remark'] ?? 'Student visited hospital.');
    if ($id) {
        $stmt = $db->prepare("UPDATE applications SET status='receptionist_verified', receptionist_remark=?, receptionist_action_at=NOW() WHERE id=? AND status='hod_approved'");
        $stmt->bind_param("si", $remark, $id);
        $stmt->execute(); $stmt->close();
        $msg = 'Application marked as hospital visited.';
    }
}

// Stats
$counts = ['hod_approved'=>0,'receptionist_verified'=>0,'total'=>0];
$result = $db->query("SELECT status, COUNT(*) as cnt FROM applications GROUP BY status");
while ($r = $result->fetch_assoc()) {
    $counts['total'] += $r['cnt'];
    if (isset($counts[$r['status']])) $counts[$r['status']] = $r['cnt'];
}

// HOD-approved applications
$stmt2 = $db->prepare("SELECT a.*, u.name as student_name, u.email as student_email, u.phone as student_phone, u.department, h.name as hod_name FROM applications a JOIN users u ON a.student_id=u.id LEFT JOIN users h ON a.hod_id=h.hod_id AND h.role='hod' WHERE a.status='hod_approved' ORDER BY a.hod_action_at DESC");
$stmt2->execute();
$approved = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt2->close();

// Recently verified
$stmt3 = $db->prepare("SELECT a.*, u.name as student_name, u.department FROM applications a JOIN users u ON a.student_id=u.id WHERE a.status='receptionist_verified' ORDER BY a.receptionist_action_at DESC LIMIT 10");
$stmt3->execute();
$verified = $stmt3->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt3->close();

$db->close();
$active = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Receptionist Dashboard - University Medical System</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="layout">
<?php include '../config/sidebar.php'; ?>
<div class="main-content">
    <div class="topbar">
        <div class="topbar-left">
            <button class="menu-toggle" id="menuToggle">☰</button>
            <h2>Receptionist Dashboard</h2>
        </div>
        <div class="topbar-right">
            <span class="topbar-date" id="currentDate"></span>
        </div>
    </div>
    <div class="page-body">
        <p class="page-title">Welcome, <?= sanitize($_SESSION['name']) ?> 👋</p>
        <p class="page-subtitle">Verify hospital visits for HOD-approved student applications</p>

        <?php if ($msg): ?>
        <div class="alert alert-success">✅ <?= sanitize($msg) ?></div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue">📋</div>
                <div class="stat-info">
                    <div class="stat-num"><?= $counts['total'] ?></div>
                    <div class="stat-label">Total Applications</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon yellow">✅</div>
                <div class="stat-info">
                    <div class="stat-num"><?= $counts['hod_approved'] ?></div>
                    <div class="stat-label">Awaiting Hospital Visit</div>
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

        <!-- Awaiting Verification -->
        <div class="card">
            <div class="card-header">
                <h3>✅ HOD-Approved — Awaiting Hospital Visit</h3>
                <span style="background:var(--accent);color:white;padding:4px 12px;border-radius:20px;font-size:0.8rem;font-weight:700;"><?= count($approved) ?> pending</span>
            </div>
            <div class="card-body" style="padding:0;">
                <?php if (empty($approved)): ?>
                <div class="empty-state">
                    <div class="empty-icon">🎉</div>
                    <h3>No pending verifications</h3>
                    <p>No HOD-approved applications awaiting hospital visit confirmation.</p>
                </div>
                <?php else: ?>
                <div class="table-wrap">
                <table>
                    <thead><tr>
                        <th>#</th>
                        <th>Student</th>
                        <th>Department</th>
                        <th>Reason</th>
                        <th>Leave Date</th>
                        <th>Time</th>
                        <th>HOD Approved On</th>
                        <th>Action</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($approved as $i => $app): ?>
                    <tr>
                        <td><?= $i+1 ?></td>
                        <td>
                            <strong><?= sanitize($app['student_name']) ?></strong><br>
                            <small class="text-muted"><?= sanitize($app['student_email']) ?></small>
                            <?php if ($app['student_phone']): ?><br><small class="text-muted">📞 <?= sanitize($app['student_phone']) ?></small><?php endif; ?>
                        </td>
                        <td><?= sanitize($app['department']) ?></td>
                        <td style="max-width:160px;"><?= sanitize($app['reason']) ?></td>
                        <td><?= date('d M Y', strtotime($app['leave_date'])) ?></td>
                        <td><?= date('h:i A', strtotime($app['leave_time'])) ?></td>
                        <td><?= $app['hod_action_at'] ? date('d M Y', strtotime($app['hod_action_at'])) : '—' ?></td>
                        <td>
                            <button class="btn btn-info btn-sm" onclick="openVisitModal(<?= $app['id'] ?>, '<?= addslashes(sanitize($app['student_name'])) ?>')">🏥 Mark Visited</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recently Verified -->
        <?php if (!empty($verified)): ?>
        <div class="card">
            <div class="card-header"><h3>🏥 Recently Verified</h3></div>
            <div class="card-body" style="padding:0;">
                <div class="table-wrap">
                <table>
                    <thead><tr>
                        <th>#</th>
                        <th>Student</th>
                        <th>Department</th>
                        <th>Leave Date</th>
                        <th>Verified On</th>
                        <th>Note</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($verified as $i => $app): ?>
                    <tr>
                        <td><?= $i+1 ?></td>
                        <td><?= sanitize($app['student_name']) ?></td>
                        <td><?= sanitize($app['department']) ?></td>
                        <td><?= date('d M Y', strtotime($app['leave_date'])) ?></td>
                        <td><?= $app['receptionist_action_at'] ? date('d M Y, h:i A', strtotime($app['receptionist_action_at'])) : '—' ?></td>
                        <td><?= sanitize($app['receptionist_remark']??'—') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
</div>

<!-- Visit Modal -->
<div id="visitModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;">
    <div style="background:white;border-radius:16px;padding:32px;width:100%;max-width:420px;margin:16px;">
        <h3 style="margin-bottom:8px;">🏥 Mark Hospital Visit</h3>
        <p id="visitStudentName" style="color:var(--text-muted);margin-bottom:18px;font-size:0.9rem;"></p>
        <form method="POST">
            <input type="hidden" name="app_id" id="visitAppId">
            <div class="form-group">
                <label>Receptionist Note</label>
                <textarea name="remark" rows="3" placeholder="e.g. Student visited hospital and presented prescription...">Student visited hospital.</textarea>
            </div>
            <div style="display:flex;gap:10px;">
                <button type="submit" class="btn btn-info" style="flex:1;">Confirm Visit</button>
                <button type="button" onclick="closeVisitModal()" class="btn btn-secondary" style="flex:1;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script src="../assets/js/script.js"></script>
<script>
function openVisitModal(id, name) {
    document.getElementById('visitAppId').value = id;
    document.getElementById('visitStudentName').textContent = 'Student: ' + name;
    document.getElementById('visitModal').style.display = 'flex';
}
function closeVisitModal() {
    document.getElementById('visitModal').style.display = 'none';
}
document.getElementById('visitModal').addEventListener('click', function(e) {
    if (e.target === this) closeVisitModal();
});
</script>
</body>
</html>
