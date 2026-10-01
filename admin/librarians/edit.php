<?php
require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';
require_once '../../includes/csrf.php';
$required_role = 'admin';
require_once '../../includes/auth_check.php';
$active = 'librarians';
$page_title = 'Edit Librarian';

$id = (int)($_GET['id'] ?? 0);
$stmt = mysqli_prepare($conn, "SELECT * FROM librarian WHERE librarian_id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$lib = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$lib) { set_flash('error', 'Librarian not found.'); redirect('list.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { set_flash('error', 'Invalid form submission.'); redirect('edit.php?id=' . $id); }

    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $status = $_POST['status'] === 'inactive' ? 'inactive' : 'active';

    $errors = [];
    if (strlen($name) < 3) $errors[] = 'Name must be at least 3 characters.';
    if (!preg_match('/^[6-9]\d{9}$/', $phone)) $errors[] = 'Enter a valid 10-digit mobile number.';

    if (empty($errors)) {
        $stmt = mysqli_prepare($conn, "UPDATE librarian SET name=?, phone=?, address=?, status=? WHERE librarian_id=?");
        mysqli_stmt_bind_param($stmt, 'ssssi', $name, $phone, $address, $status, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        log_activity($conn, 'admin', $current_user_id, 'edit_librarian', "Updated librarian: $name");
        set_flash('success', "$name's details were updated.");
        redirect('list.php');
    } else {
        set_flash('error', implode(' ', $errors));
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Librarian - BookWorld Admin</title>
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
  <div class="bw-panel" style="max-width:700px;">
    <div class="panel-title">Edit librarian</div>
    <form method="POST" novalidate>
      <?= csrf_field() ?>
      <div class="row g-3">
        <div class="col-md-6"><label class="bw-label">Full Name</label><input type="text" class="form-control bw-form-control" name="name" value="<?= clean($lib['name']) ?>" required></div>
        <div class="col-md-6"><label class="bw-label">Email</label><input type="email" class="form-control bw-form-control" value="<?= clean($lib['email']) ?>" readonly></div>
        <div class="col-md-6"><label class="bw-label">Phone</label><input type="tel" maxlength="10" class="form-control bw-form-control" name="phone" value="<?= clean($lib['phone']) ?>" required></div>
        <div class="col-md-6"><label class="bw-label">Address</label><input type="text" class="form-control bw-form-control" name="address" value="<?= clean($lib['address']) ?>" required></div>
        <div class="col-md-6">
          <label class="bw-label">Status</label>
          <select class="form-select bw-form-control" name="status">
            <option value="active" <?= $lib['status']==='active'?'selected':'' ?>>Active</option>
            <option value="inactive" <?= $lib['status']==='inactive'?'selected':'' ?>>Inactive</option>
          </select>
        </div>
      </div>
      <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-bw-primary px-4">Update Librarian</button>
        <a href="list.php" class="btn btn-bw-outline px-4">Cancel</a>
      </div>
    </form>
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
