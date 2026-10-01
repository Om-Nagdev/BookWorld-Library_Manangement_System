<?php
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../config/session.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password - BookWorld</title>
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
      <div class="bw-auth-card p-4 p-md-5 text-center">
        <i class="fa-solid fa-key fa-2x text-brass mb-3"></i>
        <h3 class="font-display">Forgot your password?</h3>
        <p class="text-muted small">Enter your registered email and we'll send you a reset link.</p>
        <form id="bw-forgot-form" action="process/password_process.php" method="POST" class="text-start mt-4" novalidate>
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="request_reset">
          <label class="bw-label" for="forgot_email">Email Address</label>
          <input type="email" class="form-control bw-form-control" id="forgot_email" name="email" required>
          <div class="invalid-feedback-bw">Enter a valid email address.</div>
          <button type="submit" class="btn btn-bw-primary w-100 py-2 mt-3">Send Reset Link</button>
        </form>
        <p class="mt-4 small"><a href="login.php"><i class="fa-solid fa-arrow-left me-1"></i>Back to login</a></p>
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
