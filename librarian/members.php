<?php
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../config/session.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';
$required_role = 'librarian';
require_once '../includes/auth_check.php';
$active = 'members';
$page_title = 'Members';

$search = trim($_GET['q'] ?? '');
$sql = "SELECT m.*, (SELECT COUNT(*) FROM book_issues WHERE member_id=m.member_id AND status='issued') AS active_issues
        FROM member m WHERE 1=1";
$params = []; $types = '';
if ($search !== '') { $sql .= " AND (m.name LIKE ? OR m.email LIKE ?)"; $like="%$search%"; $params=[$like,$like]; $types='ss'; }
$sql .= " ORDER BY m.registration_date DESC";
$stmt = mysqli_prepare($conn, $sql);
if ($params) mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$members = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Members - BookWorld Librarian</title>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
<div class="bw-app">
<?php include '../includes/sidebar_librarian.php'; ?>
<main class="bw-main">
<?php include '../includes/dashboard_topbar.php'; ?>
<div class="bw-content">
  <form class="d-flex gap-2 mb-3" method="GET">
    <input type="text" name="q" class="form-control bw-form-control" style="width:300px;" placeholder="Search members..." value="<?= clean($search) ?>">
    <button class="btn btn-bw-outline" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
  </form>
  <div class="bw-panel">
    <div class="table-responsive">
      <table class="table table-bw align-middle">
        <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Joined</th><th>Active Issues</th><th>Status</th></tr></thead>
        <tbody>
          <?php while ($m = mysqli_fetch_assoc($members)): ?>
            <tr>
              <td class="fw-semibold text-navy"><?= clean($m['name']) ?></td>
              <td><?= clean($m['email']) ?></td>
              <td><?= clean($m['phone']) ?></td>
              <td><?= fmt_date($m['registration_date']) ?></td>
              <td><?= (int)$m['active_issues'] ?></td>
              <td><?= $m['status']==='active' ? '<span class="badge-bw-success">Active</span>' : '<span class="badge-bw-danger">'.ucfirst($m['status']).'</span>' ?></td>
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
