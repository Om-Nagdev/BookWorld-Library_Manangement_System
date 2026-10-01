<?php
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../config/session.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';
$required_role = 'member';
require_once '../includes/auth_check.php';
$active = 'profile';
$page_title = 'My Profile';

$stmt = mysqli_prepare($conn, "SELECT * FROM member WHERE member_id = ?");
mysqli_stmt_bind_param($stmt, 'i', $current_user_id);
mysqli_stmt_execute($stmt);
$member = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { set_flash('error', 'Invalid request.'); redirect('profile.php'); }
    $form = $_POST['form'] ?? '';

    if ($form === 'update_profile') {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');

        $errors = [];
        if (strlen($name) < 3) $errors[] = 'Name must be at least 3 characters.';
        if (!preg_match('/^[6-9]\d{9}$/', $phone)) $errors[] = 'Enter a valid 10-digit mobile number.';

        $photo = $member['profile_photo'];
        if (!empty($_FILES['profile_photo']['name'])) {
            $upload = handle_image_upload($_FILES['profile_photo'], '../assets/uploads/profiles');
            if (is_array($upload) && isset($upload['error'])) $errors[] = $upload['error'];
            elseif ($upload) $photo = $upload;
        }

        if (empty($errors)) {
            $stmt = mysqli_prepare($conn, "UPDATE member SET name=?, phone=?, address=?, profile_photo=? WHERE member_id=?");
            mysqli_stmt_bind_param($stmt, 'ssssi', $name, $phone, $address, $photo, $current_user_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $_SESSION['name'] = $name;
            log_activity($conn, 'member', $current_user_id, 'update_profile', 'Updated profile details');
            set_flash('success', 'Your profile was updated successfully.');
        } else {
            set_flash('error', implode(' ', $errors));
        }
        redirect('profile.php');
    }

    if ($form === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!password_verify($current, $member['password'])) {
            set_flash('error', 'Your current password is incorrect.');
        } elseif (!is_strong_password($new)) {
            set_flash('error', 'New password must be 8+ chars with uppercase, lowercase, number & symbol.');
        } elseif ($new !== $confirm) {
            set_flash('error', 'New passwords do not match.');
        } else {
            $hashed = password_hash($new, PASSWORD_BCRYPT);
            $stmt = mysqli_prepare($conn, "UPDATE member SET password=? WHERE member_id=?");
            mysqli_stmt_bind_param($stmt, 'si', $hashed, $current_user_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            log_activity($conn, 'member', $current_user_id, 'change_password', 'Changed account password');
            set_flash('success', 'Password changed successfully.');
        }
        redirect('profile.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Profile - BookWorld</title>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
<div class="bw-app">
<?php include '../includes/sidebar_member.php'; ?>
<main class="bw-main">
<?php include '../includes/dashboard_topbar.php'; ?>
<div class="bw-content">
  <div class="row g-4">
    <div class="col-lg-6">
      <div class="bw-panel">
        <div class="panel-title">Profile details</div>
        <form method="POST" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <input type="hidden" name="form" value="update_profile">
          <div class="mb-3 text-center">
            <span class="rounded-circle bg-navy text-white d-inline-flex align-items-center justify-content-center" style="width:80px;height:80px;background:var(--bw-navy);font-size:2rem;">
              <?= strtoupper(substr($member['name'],0,1)) ?>
            </span>
          </div>
          <label class="bw-label">Full Name</label>
          <input type="text" class="form-control bw-form-control mb-3" name="name" value="<?= clean($member['name']) ?>" required>
          <label class="bw-label">Email</label>
          <input type="email" class="form-control bw-form-control mb-3" value="<?= clean($member['email']) ?>" readonly>
          <label class="bw-label">Phone</label>
          <input type="tel" maxlength="10" class="form-control bw-form-control mb-3" name="phone" value="<?= clean($member['phone']) ?>" required>
          <label class="bw-label">Address</label>
          <input type="text" class="form-control bw-form-control mb-3" name="address" value="<?= clean($member['address']) ?>" required>
          <label class="bw-label">Profile Photo</label>
          <input type="file" class="form-control bw-form-control mb-3" name="profile_photo" accept=".jpg,.jpeg,.png,.webp">
          <button type="submit" class="btn btn-bw-primary w-100">Save Changes</button>
        </form>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="bw-panel">
        <div class="panel-title">Change password</div>
        <form method="POST">
          <?= csrf_field() ?>
          <input type="hidden" name="form" value="change_password">
          <label class="bw-label">Current Password</label>
          <input type="password" class="form-control bw-form-control mb-3" name="current_password" required>
          <label class="bw-label">New Password</label>
          <input type="password" class="form-control bw-form-control mb-3" name="new_password" required>
          <label class="bw-label">Confirm New Password</label>
          <input type="password" class="form-control bw-form-control mb-3" name="confirm_password" required>
          <button type="submit" class="btn btn-bw-outline w-100">Update Password</button>
        </form>
        <hr>
        <p class="small text-muted mb-1">Member since: <?= fmt_date($member['registration_date']) ?></p>
        <p class="small text-muted mb-0">Account status: <span class="badge-bw-success text-capitalize"><?= clean($member['status']) ?></span></p>
      </div>
    </div>
  </div>
</div>
</main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>window.BW_FLASH = <?= ($f = get_flash()) ? json_encode($f) : 'null' ?>;</script>
<script src="../assets/js/validation.js"></script>
<script src="../assets/js/main.js"></script>
</body>
</html>
