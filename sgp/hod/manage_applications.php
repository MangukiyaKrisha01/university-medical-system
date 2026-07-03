<?php
session_start();
require_once '../config/db.php';
require_once '../config/helpers.php';
requireLogin('hod');

$db     = getDB();
$hod_id = $_SESSION['hod_id'];
$msg    = '';
$msgType = 'success';

// Handle quick actions from dashboard
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id     = (int)$_GET['id'];
    if ($action === 'approve') {
        $remark = 'Approved.';
        $stmt = $db->prepare("UPDATE applications SET status='hod_approved', hod_remark=?, hod_action_at=NOW() WHERE id=? AND hod_id=?");
        $stmt->bind_param("sis", $remark, $id, $hod_id);
        $stmt->execute(); $stmt->close();
        $msg = 'Application approved successfully.';
    } elseif ($action === 'reject') {
        $remark = 'Rejected by HOD.';
        $stmt = $db->prepare("UPDATE applications SET status='hod_rejected', hod_remark=?, hod_action_at=NOW() WHERE id=? AND hod_id=?");
        $stmt->bind_param("sis", $remark, $id, $hod_id);
        $stmt->execute(); $stmt->close();
        $msg = 'Application rejected.';
        $msgType = 'warning';
    }
}

// Handle POST with remark
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id     = (int)($_POST['app_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $remark = trim($_POST['remark'] ?? '');

    if ($id && $action) {
        $status = ($action === 'approve') ? 'hod_approved' : 'hod_rejected';
        $remark = $remark ?: ($action === 'approve' ? 'Approved by HOD.' : 'Rejected by HOD.');
        $stmt = $db->prepare("UPDATE applications SET status=?, hod_remark=?, hod_action_at=NOW() WHERE id=? AND hod_id=?");
        $stmt->bind_param("ssis", $status, $remark, $id, $hod_id);
        $stmt->execute(); $stmt->close();
        $msg = ($action === 'approve') ? 'Application approved.' : 'Application rejected.';
        $msgType = ($action === 'approve') ? 'success' : 'warning';
    }
}

// Fetch all applications for this HOD
$filter = $_GET['filter'] ?? 'all';
$where  = "WHERE a.hod_id=?";
$params = "s";
$vals   = [$hod_id];
if ($filter !== 'all') {
    $where .= " AND a.status=?";
    $params .= "s";
    $vals[] = $filter;
}

$stmt = $db->prepare("SELECT a.*, u.name as student_name, u.email as student_email, u.phone as student_phone FROM applications a JOIN users u ON a.student_id=u.id $where ORDER BY a.created_at DESC");
$stmt->bind_param($params, ...$vals);
$stmt->execute();
$apps = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$db->close();

$active = 'manage';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Applications - University Medical System</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="layout">
<?php include '../config/sidebar.php'; ?>
<div class="main-content">
    <div class="topbar">
        <div class="topbar-left">
            <button class="menu-toggle" id="menuToggle">☰</button>
            <h2>Manage Applications</h2>
        </div>
        <div class="topbar-right">
            <span class="topbar-date" id="currentDate"></span>
        </div>
    </div>
    <div class="page-body">
        <div class="section-header">
            <div>
                <p class="page-title">Student Applications</p>
                <p class="page-subtitle">Review and take action on medical leave requests from your department</p>
            </div>
        </div>

        <?php if ($msg): ?>
        <div class="alert alert-<?= $msgType === 'warning' ? 'warning' : 'success' ?>">
            <?= $msgType === 'success' ? '✅' : '⚠️' ?> <?= sanitize($msg) ?>
        </div>
        <?php endif; ?>

        <!-- Filter Tabs -->
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:18px;">
            <?php foreach (['all'=>'All','pending'=>'Pending','hod_approved'=>'Approved','hod_rejected'=>'Rejected','receptionist_verified'=>'Hospital Verified'] as $k=>$v): ?>
            <a href="?filter=<?= $k ?>" class="btn btn-sm <?= $filter===$k ? 'btn-primary' : 'btn-secondary' ?>" style="width:auto;"><?= $v ?></a>
            <?php endforeach; ?>
        </div>

        <div style="margin-bottom:14px;">
            <input type="text" id="tableSearch" placeholder="🔍 Search by name, email, reason..." style="width:100%;max-width:400px;padding:9px 14px;border:1.5px solid var(--border);border-radius:var(--radius-sm);font-family:inherit;font-size:0.88rem;">
        </div>

        <div class="card">
            <div class="card-body" style="padding:0;">
                <?php if (empty($apps)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📭</div>
                    <h3>No applications found</h3>
                    <p>No applications match the current filter.</p>
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
                        <th>Status</th>
                        <th>HOD Remark</th>
                        <th>Receptionist</th>
                        <th>Action</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($apps as $i => $app): ?>
                    <tr>
                        <td><?= $i+1 ?></td>
                        <td>
                            <strong><?= sanitize($app['student_name']) ?></strong><br>
                            <small class="text-muted"><?= sanitize($app['student_email']) ?></small><br>
                            <?php if ($app['student_phone']): ?><small class="text-muted">📞 <?= sanitize($app['student_phone']) ?></small><?php endif; ?>
                        </td>
                        <td style="max-width:180px;"><?= sanitize($app['reason']) ?></td>
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
                        <td>
                            <?php if ($app['status'] === 'pending'): ?>
                            <button class="btn btn-success btn-sm" onclick="openActionModal(<?= $app['id'] ?>, 'approve')">✅ Approve</button>
                            <button class="btn btn-danger btn-sm" onclick="openActionModal(<?= $app['id'] ?>, 'reject')" style="margin-top:4px;">❌ Reject</button>
                            <?php else: ?>
                            <span class="text-muted" style="font-size:0.8rem;">Reviewed</span>
                            <?php endif; ?>
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

<!-- Action Modal -->
<div id="actionModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;">
    <div style="background:white;border-radius:16px;padding:32px;width:100%;max-width:420px;margin:16px;">
        <h3 id="modalTitle" style="margin-bottom:16px;"></h3>
        <form method="POST">
            <input type="hidden" name="app_id" id="modalAppId">
            <input type="hidden" name="action" id="modalAction">
            <div class="form-group">
                <label>Remark (optional)</label>
                <textarea name="remark" id="modalRemark" rows="3" placeholder="Add a note for the student..."></textarea>
            </div>
            <div style="display:flex;gap:10px;">
                <button type="submit" id="modalBtn" class="btn" style="flex:1;padding:11px;">Confirm</button>
                <button type="button" onclick="closeModal()" class="btn btn-secondary" style="flex:1;padding:11px;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script src="../assets/js/script.js"></script>
<script>
function openActionModal(id, action) {
    document.getElementById('modalAppId').value = id;
    document.getElementById('modalAction').value = action;
    document.getElementById('modalTitle').textContent = action === 'approve' ? '✅ Approve Application' : '❌ Reject Application';
    var btn = document.getElementById('modalBtn');
    btn.className = 'btn ' + (action === 'approve' ? 'btn-success' : 'btn-danger');
    btn.textContent = action === 'approve' ? 'Approve' : 'Reject';
    document.getElementById('modalRemark').value = '';
    document.getElementById('actionModal').style.display = 'flex';
}
function closeModal() {
    document.getElementById('actionModal').style.display = 'none';
}
document.getElementById('actionModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>
</body>
</html>
