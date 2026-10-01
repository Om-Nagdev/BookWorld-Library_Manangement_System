<?php
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../config/session.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';

if (isset($_SESSION['user_id'])) {
    redirect('../' . $_SESSION['role'] . '/dashboard.php');
}

$flash = get_flash();
$msg = $_GET['msg'] ?? '';
$expired = $_GET['expired'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - BookWorld</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
  body { background: var(--bw-navy); min-height: 100vh; display: flex; align-items: center; }
  .bw-auth-side { background: linear-gradient(160deg, var(--bw-navy), #16344c); color: var(--bw-paper); padding: 3rem; border-radius: var(--radius-md) 0 0 var(--radius-md); }
  .bw-auth-side i { color: var(--bw-brass); }
</style>
</head>
<body>
<div class="container">
  <div class="row justify-content-center">
    <div class="col-lg-9">
      <div class="row g-0 bw-auth-card overflow-hidden">
        <div class="col-md-5 bw-auth-side d-none d-md-flex flex-column justify-content-center">
          <i class="fa-solid fa-book-open fa-2x mb-3"></i>
          <h2 class="text-white font-display">Welcome back to BookWorld</h2>
          <p class="text-white-50">Sign in to browse the catalog, track your issued books, manage reservations and more.</p>
        </div>
        <div class="col-md-7 p-4 p-md-5">
          <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="font-display mb-0">Sign In</h3>
            <a href="../index.php" class="small text-muted"><i class="fa-solid fa-arrow-left me-1"></i>Back home</a>
          </div>

          <?php if ($expired): ?>
            <div class="alert alert-warning small">Your session expired. Please log in again.</div>
          <?php elseif ($msg === 'login_required'): ?>
            <div class="alert alert-warning small">Please log in to continue.</div>
          <?php elseif ($msg === 'unauthorized'): ?>
            <div class="alert alert-warning small">You don't have access to that area.</div>
          <?php elseif ($msg === 'registered'): ?>
            <div class="alert alert-success small">Registration successful! You can now log in.</div>
          <?php elseif ($msg === 'reset_success'): ?>
            <div class="alert alert-success small">Password reset successful. Please log in.</div>
          <?php endif; ?>

          <form id="bw-login-form" action="process/login_process.php" method="POST" novalidate>
            <?= csrf_field() ?>
            <div class="mb-3">
              <label class="bw-label" for="login_email">Email Address</label>
              <input type="email" class="form-control bw-form-control" id="login_email" name="email" placeholder="you@example.com" required>
              <div class="invalid-feedback-bw">Enter a valid email address.</div>
            </div>
            <div class="mb-2">
              <label class="bw-label" for="login_password">Password</label>
              <div class="input-group">
                <input type="password" class="form-control bw-form-control" id="login_password" name="password" placeholder="Enter your password" required>
                <button class="btn btn-outline-secondary" type="button" onclick="bwTogglePw('login_password', this)"><i class="fa-solid fa-eye"></i></button>
              </div>
              <div class="invalid-feedback-bw">Password is required.</div>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-3">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="remember" name="remember">
                <label class="form-check-label small" for="remember">Remember me</label>
              </div>
              <a href="forgot_password.php" class="small">Forgot password?</a>
            </div>
            <button type="submit" class="btn btn-bw-primary w-100 py-2">Sign In</button>
          </form>

          <p class="text-center mt-4 small text-muted">
            New to BookWorld? <a href="register.php">Create a member account</a>
          </p>

          <div class="mt-4 p-3 bg-light rounded small">
            <strong>Demo accounts</strong><br>
            Admin: admin@bookworld.com / Admin@123<br>
            Librarian: riya.librarian@bookworld.com / Lib@123<br>
            Member: aarav@example.com / Member@123
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
window.BW_FLASH = <?= $flash ? json_encode($flash) : 'null' ?>;
function bwTogglePw(id, btn) {
  const field = document.getElementById(id);
  const icon = btn.querySelector('i');
  if (field.type === 'password') { field.type = 'text'; icon.className = 'fa-solid fa-eye-slash'; }
  else { field.type = 'password'; icon.className = 'fa-solid fa-eye'; }
}
</script>
<script src="../assets/js/validation.js"></script>
<script src="../assets/js/main.js"></script>
</body>
</html>
