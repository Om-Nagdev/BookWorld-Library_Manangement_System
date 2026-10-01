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
$old = $_SESSION['old_input'] ?? [];
unset($_SESSION['old_input']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Join BookWorld</title>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
  body { background: var(--bw-paper-soft); padding: 3rem 0; }
  .pw-strength-bar { height: 5px; border-radius: 3px; background: var(--bw-line); overflow: hidden; }
  .pw-strength-fill { height: 100%; width: 0%; transition: all 0.25s ease; }
</style>
</head>
<body>
<div class="container">
  <div class="row justify-content-center">
    <div class="col-lg-7">
      <div class="text-center mb-4">
        <a href="../index.php" class="text-decoration-none"><i class="fa-solid fa-book-open fa-2x text-navy"></i></a>
        <h2 class="font-display mt-2">Create your member account</h2>
        <p class="text-muted">Join BookWorld to borrow, reserve, and buy books from our collection.</p>
      </div>
      <div class="bw-auth-card p-4 p-md-5">
        <form id="bw-register-form" action="process/register_process.php" method="POST" novalidate>
          <?= csrf_field() ?>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="bw-label" for="reg_name">Full Name</label>
              <input type="text" class="form-control bw-form-control" id="reg_name" name="name" value="<?= clean($old['name'] ?? '') ?>" required>
              <div class="invalid-feedback-bw"></div>
            </div>
            <div class="col-md-6">
              <label class="bw-label" for="reg_email">Email Address</label>
              <input type="email" class="form-control bw-form-control" id="reg_email" name="email" value="<?= clean($old['email'] ?? '') ?>" required>
              <div class="invalid-feedback-bw"></div>
            </div>
            <div class="col-md-6">
              <label class="bw-label" for="reg_phone">Phone Number</label>
              <input type="tel" maxlength="10" class="form-control bw-form-control" id="reg_phone" name="phone" value="<?= clean($old['phone'] ?? '') ?>" required>
              <div class="invalid-feedback-bw"></div>
            </div>
            <div class="col-md-6">
              <label class="bw-label" for="reg_address">Address</label>
              <input type="text" class="form-control bw-form-control" id="reg_address" name="address" value="<?= clean($old['address'] ?? '') ?>" required>
              <div class="invalid-feedback-bw"></div>
            </div>
            <div class="col-md-6">
              <label class="bw-label" for="reg_password">Password</label>
              <input type="password" class="form-control bw-form-control" id="reg_password" name="password" data-strength-meter="pw-fill" required>
              <div class="pw-strength-bar mt-2"><div class="pw-strength-fill" id="pw-fill"></div></div>
              <small id="pw-fill-label" class="text-muted"></small>
              <div class="invalid-feedback-bw"></div>
            </div>
            <div class="col-md-6">
              <label class="bw-label" for="reg_confirm_password">Confirm Password</label>
              <input type="password" class="form-control bw-form-control" id="reg_confirm_password" name="confirm_password" required>
              <div class="invalid-feedback-bw"></div>
            </div>
            <div class="col-12">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="reg_terms" name="terms">
                <label class="form-check-label small" for="reg_terms">I agree to the library's membership terms & conditions.</label>
              </div>
            </div>
          </div>
          <button type="submit" class="btn btn-bw-primary w-100 py-2 mt-4">Create Account</button>
          <p class="text-center mt-3 small text-muted mb-0">
            Already a member? <a href="login.php">Sign in here</a>
          </p>
        </form>
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
