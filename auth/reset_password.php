<?php
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../config/session.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';

$token = $_GET['token'] ?? '';
$flash = get_flash();
$valid_token = false;
$reset_row = null;

if ($token) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW() ORDER BY reset_id DESC LIMIT 1");
    mysqli_stmt_bind_param($stmt, 's', $token);
    mysqli_stmt_execute($stmt);
    $reset_row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    $valid_token = (bool) $reset_row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password - BookWorld</title>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="../assets/css/style.css">
<style>body{background:var(--bw-navy);min-height:100vh;display:flex;align-items:center;}</style>
</head>
<body>
<div class="container">
  <div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
      <div class="bw-auth-card p-4 p-md-5">
        <?php if (!$valid_token): ?>
          <div class="text-center">
            <i class="fa-solid fa-triangle-exclamation fa-2x text-danger mb-3"></i>
            <h4 class="font-display">Link expired or invalid</h4>
            <p class="text-muted small">This password reset link is no longer valid. Please request a new one.</p>
            <a href="forgot_password.php" class="btn btn-bw-primary mt-2">Request New Link</a>
          </div>
        <?php else: ?>
          <div class="text-center mb-3">
            <i class="fa-solid fa-lock-open fa-2x text-brass mb-2"></i>
            <h4 class="font-display">Set a new password</h4>
          </div>
          <form id="bw-reset-form" action="process/password_process.php" method="POST" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="do_reset">
            <input type="hidden" name="token" value="<?= clean($token) ?>">
            <label class="bw-label" for="reset_password">New Password</label>
            <input type="password" class="form-control bw-form-control" id="reset_password" name="password" required>
            <div class="invalid-feedback-bw"></div>
            <label class="bw-label mt-3" for="reset_confirm_password">Confirm New Password</label>
            <input type="password" class="form-control bw-form-control" id="reset_confirm_password" name="confirm_password" required>
            <div class="invalid-feedback-bw"></div>
            <button type="submit" class="btn btn-bw-primary w-100 py-2 mt-4">Reset Password</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>window.BW_FLASH = <?= $flash ? json_encode($flash) : 'null' ?>;</script>
<script src="../assets/js/validation.js"></script>
<script src="../assets/js/main.js"></script>
</body>
</html>
