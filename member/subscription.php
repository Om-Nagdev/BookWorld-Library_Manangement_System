<?php
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../config/session.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';
$required_role = 'member';
require_once '../includes/auth_check.php';
$active = 'subscription';
$page_title = 'My Subscription';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { set_flash('error', 'Invalid request.'); redirect('subscription.php'); }
    $plan_id = (int)$_POST['plan_id'];
    $method = in_array($_POST['payment_method'] ?? '', ['cash','debit_card','online']) ? $_POST['payment_method'] : 'online';

    $stmt = mysqli_prepare($conn, "SELECT * FROM subscription_plans WHERE plan_id=?");
    mysqli_stmt_bind_param($stmt, 'i', $plan_id);
    mysqli_stmt_execute($stmt);
    $plan = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$plan) { set_flash('error', 'Invalid plan.'); redirect('subscription.php'); }

    mysqli_begin_transaction($conn);
    try {
        // Cancel any existing active subscription first
        $stmt = mysqli_prepare($conn, "UPDATE subscriptions SET status='cancelled' WHERE member_id=? AND status='active'");
        mysqli_stmt_bind_param($stmt, 'i', $current_user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $end_date = date('Y-m-d', strtotime('+' . $plan['duration_days'] . ' days'));
        $stmt = mysqli_prepare($conn, "INSERT INTO subscriptions (member_id, plan_id, start_date, end_date, status) VALUES (?, ?, CURDATE(), ?, 'active')");
        mysqli_stmt_bind_param($stmt, 'iis', $current_user_id, $plan_id, $end_date);
        mysqli_stmt_execute($stmt);
        $subscription_id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);

        $txn_id = generate_transaction_id();
        $stmt = mysqli_prepare($conn, "INSERT INTO payments (subscription_id, member_id, amount, payment_method, transaction_id, payment_date, status, payment_type) VALUES (?,?,?,?,?, NOW(), 'success', 'subscription')");
        mysqli_stmt_bind_param($stmt, 'iidss', $subscription_id, $current_user_id, $plan['price'], $method, $txn_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        mysqli_commit($conn);
        log_activity($conn, 'member', $current_user_id, 'subscribe', "Subscribed to {$plan['plan_name']} plan");
        set_flash('success', "You're now subscribed to the {$plan['plan_name']} plan! Transaction ID: $txn_id");
    } catch (Exception $e) {
        mysqli_rollback($conn);
        set_flash('error', 'Subscription failed. Please try again.');
    }
    redirect('subscription.php');
}

$current_sub = mysqli_fetch_assoc(mysqli_query($conn, "SELECT s.*, sp.plan_name, sp.max_books, sp.loan_days, sp.price FROM subscriptions s
                                                        JOIN subscription_plans sp ON sp.plan_id=s.plan_id
                                                        WHERE s.member_id=$current_user_id AND s.status='active' AND s.end_date>=CURDATE()
                                                        ORDER BY s.subscription_id DESC LIMIT 1"));
$plans = mysqli_query($conn, "SELECT * FROM subscription_plans ORDER BY price ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Subscription - BookWorld</title>
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

  <?php if ($current_sub): ?>
  <div class="bw-panel mb-4">
    <div class="panel-title">Current plan</div>
    <div class="d-flex justify-content-between align-items-center flex-wrap">
      <div>
        <span class="badge-bw-warning fs-6"><?= clean($current_sub['plan_name']) ?></span>
        <p class="text-muted small mt-2 mb-0">Up to <?= $current_sub['max_books'] ?> books · <?= $current_sub['loan_days'] ?>-day loans · Valid until <?= fmt_date($current_sub['end_date']) ?></p>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <div class="row g-4">
    <?php mysqli_data_seek($plans, 0); while ($plan = mysqli_fetch_assoc($plans)):
      $is_current = $current_sub && $current_sub['plan_id'] == $plan['plan_id'];
      $is_gold = $plan['plan_name'] === 'Gold'; ?>
      <div class="col-md-4">
        <div class="bw-auth-card p-4 h-100" style="<?= $is_gold ? 'border-color:var(--bw-brass) !important; border-width:2px;' : '' ?>">
          <?php if ($is_gold): ?><span class="badge-bw-warning mb-2">Most popular</span><?php endif; ?>
          <h4 class="font-display"><?= clean($plan['plan_name']) ?></h4>
          <div class="mb-3"><span class="fs-3 fw-bold text-navy"><?= inr($plan['price']) ?></span><span class="text-muted">/year</span></div>
          <ul class="list-unstyled small text-muted mb-4">
            <li class="mb-2"><i class="fa-solid fa-check text-green me-2"></i>Borrow up to <?= $plan['max_books'] ?> books</li>
            <li class="mb-2"><i class="fa-solid fa-check text-green me-2"></i><?= $plan['loan_days'] ?>-day loan period</li>
            <li class="mb-2"><i class="fa-solid fa-check text-green me-2"></i>Reserve unavailable titles</li>
          </ul>
          <?php if ($is_current): ?>
            <button class="btn btn-bw-outline w-100" disabled>Current Plan</button>
          <?php else: ?>
            <button class="btn <?= $is_gold ? 'btn-bw-primary' : 'btn-bw-outline' ?> w-100" data-bs-toggle="modal" data-bs-target="#sub<?= $plan['plan_id'] ?>">
              <?= $current_sub ? 'Switch to this plan' : 'Subscribe' ?>
            </button>
          <?php endif; ?>
        </div>
      </div>
      <div class="modal fade" id="sub<?= $plan['plan_id'] ?>" tabindex="-1">
        <div class="modal-dialog">
          <div class="modal-content">
            <form method="POST">
              <?= csrf_field() ?>
              <input type="hidden" name="plan_id" value="<?= $plan['plan_id'] ?>">
              <div class="modal-header"><h6 class="modal-title">Subscribe to <?= clean($plan['plan_name']) ?> — <?= inr($plan['price']) ?>/year</h6></div>
              <div class="modal-body">
                <label class="bw-label">Payment Method</label>
                <select class="form-select bw-form-control" name="payment_method">
                  <option value="online">Online / UPI</option>
                  <option value="debit_card">Debit Card</option>
                  <option value="cash">Cash (at counter)</option>
                </select>
                <div class="alert alert-light small mt-3 mb-0">This is a simulated payment for demo purposes.</div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-bw-outline" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-sm btn-bw-primary">Confirm & Pay</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    <?php endwhile; ?>
  </div>
</div>
</main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>window.BW_FLASH = <?= ($f = get_flash()) ? json_encode($f) : 'null' ?>;</script>
<script src="../assets/js/main.js"></script>
</body>
</html>
