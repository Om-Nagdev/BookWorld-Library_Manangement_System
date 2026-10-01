<?php
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../config/session.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';
$required_role = 'admin';
require_once '../includes/auth_check.php';
$active = 'logs';
$page_title = 'Activity Logs';

$filter_role = $_GET['role'] ?? '';
$sql = "SELECT * FROM activity_logs WHERE 1=1";
$params = []; $types = '';
if (in_array($filter_role, ['admin','librarian','member'])) {
    $sql .= " AND user_type = ?";
    $params[] = $filter_role; $types .= 's';
}
$sql .= " ORDER BY date_time DESC LIMIT 200";
$stmt = mysqli_prepare($conn, $sql);
if ($params) mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$logs = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Activity Logs - BookWorld Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
<div class="bw-app">
<?php include '../includes/sidebar_admin.php'; ?>
<main class="bw-main">
<?php include '../includes/dashboard_topbar.php'; ?>
<div class="bw-content">
  <form class="d-flex gap-2 mb-3" method="GET">
    <select name="role" class="form-select bw-form-control" style="width:200px;" onchange="this.form.submit()">
      <option value="">All roles</option>
      <option value="admin" <?= $filter_role==='admin'?'selected':'' ?>>Admin</option>
      <option value="librarian" <?= $filter_role==='librarian'?'selected':'' ?>>Librarian</option>
      <option value="member" <?= $filter_role==='member'?'selected':'' ?>>Member</option>
    </select>
  </form>
  <div class="bw-panel">
    <div class="table-responsive">
      <table class="table table-bw align-middle">
        <thead><tr><th>Role</th><th>Action</th><th>Description</th><th>Date/Time</th></tr></thead>
        <tbody>
          <?php while ($log = mysqli_fetch_assoc($logs)): ?>
            <tr>
              <td><span class="badge-bw-navy text-capitalize"><?= clean($log['user_type']) ?></span></td>
              <td class="text-capitalize"><?= clean(str_replace('_', ' ', $log['action'])) ?></td>
              <td class="text-muted small"><?= clean($log['description']) ?></td>
              <td class="text-muted small"><?= date('d M Y, h:i A', strtotime($log['date_time'])) ?></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
</body>
</html>
