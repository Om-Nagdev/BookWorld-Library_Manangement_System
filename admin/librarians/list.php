<?php
require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';
require_once '../../includes/csrf.php';
$required_role = 'admin';
require_once '../../includes/auth_check.php';
$active = 'librarians';
$page_title = 'Manage Librarians';

$librarians = mysqli_query($conn, "SELECT * FROM librarian ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Librarians - BookWorld Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="../../assets/css/style.css">
<link rel="stylesheet" href="../../assets/css/dashboard.css">
</head>
<body>
<div class="bw-app">
<?php include '../../includes/sidebar_admin.php'; ?>
<main class="bw-main">
<?php include '../../includes/dashboard_topbar.php'; ?>
<div class="bw-content">
  <div class="d-flex justify-content-end mb-3">
    <a href="add.php" class="btn btn-bw-primary"><i class="fa-solid fa-plus me-1"></i>Add Librarian</a>
  </div>
  <div class="bw-panel">
    <div class="table-responsive">
      <table class="table table-bw align-middle">
        <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Joining Date</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <?php if (mysqli_num_rows($librarians) === 0): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">No librarians added yet.</td></tr>
          <?php endif; ?>
          <?php while ($l = mysqli_fetch_assoc($librarians)): ?>
            <tr>
              <td class="fw-semibold text-navy"><?= clean($l['name']) ?></td>
              <td><?= clean($l['email']) ?></td>
              <td><?= clean($l['phone']) ?></td>
              <td><?= fmt_date($l['joining_date']) ?></td>
              <td><?= $l['status']==='active' ? '<span class="badge-bw-success">Active</span>' : '<span class="badge-bw-danger">Inactive</span>' ?></td>
              <td class="text-end">
                <a href="edit.php?id=<?= $l['librarian_id'] ?>" class="btn btn-sm btn-bw-outline"><i class="fa-solid fa-pen"></i></a>
                <form action="delete.php" method="POST" class="d-inline">
                  <?= csrf_field() ?>
                  <input type="hidden" name="librarian_id" value="<?= $l['librarian_id'] ?>">
                  <button type="button" class="btn btn-sm btn-bw-danger bw-confirm-delete" data-item="<?= clean($l['name']) ?>"><i class="fa-solid fa-trash"></i></button>
                </form>
              </td>
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>window.BW_FLASH = <?= ($f = get_flash()) ? json_encode($f) : 'null' ?>;</script>
<script src="../../assets/js/main.js"></script>
</body>
</html>
