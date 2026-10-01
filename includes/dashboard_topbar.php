<?php
// Expects: $conn, $current_user_role, $current_user_id, $current_user_name, $page_title
$notif_count = 0;
$notifications = [];
if (in_array($current_user_role, ['admin', 'librarian'])) {
    $notif_count = get_unread_notification_count($conn, $current_user_role, $current_user_id);
    $stmt = mysqli_prepare($conn, "SELECT * FROM notifications WHERE recipient_type = ? AND (recipient_id IS NULL OR recipient_id = ?) ORDER BY created_at DESC LIMIT 6");
    mysqli_stmt_bind_param($stmt, 'si', $current_user_role, $current_user_id);
    mysqli_stmt_execute($stmt);
    $notifications = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
}
?>
<header class="bw-topbar">
  <div class="d-flex align-items-center gap-3">
    <button class="btn btn-sm d-lg-none border" id="bw-sidebar-toggle"><i class="fa-solid fa-bars"></i></button>
    <h1 class="page-title"><?= clean($page_title ?? 'Dashboard') ?></h1>
  </div>
  <div class="d-flex align-items-center gap-2">
    <?php if (in_array($current_user_role, ['admin', 'librarian'])): ?>
    <div class="dropdown">
      <button class="icon-btn" data-bs-toggle="dropdown">
        <i class="fa-solid fa-bell"></i>
        <?php if ($notif_count > 0): ?><span class="notif-dot"></span><?php endif; ?>
      </button>
      <div class="dropdown-menu dropdown-menu-end p-0" style="width:320px; max-height:380px; overflow-y:auto;">
        <div class="p-3 border-bottom fw-semibold text-navy">Notifications</div>
        <?php if (empty($notifications)): ?>
          <div class="p-3 text-center text-muted small">You're all caught up.</div>
        <?php else: foreach ($notifications as $n): ?>
          <div class="p-3 border-bottom small">
            <div><?= clean($n['message']) ?></div>
            <div class="text-muted mt-1" style="font-size:0.75rem;"><?= date('d M, h:i A', strtotime($n['created_at'])) ?></div>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
    <?php endif; ?>
    <div class="dropdown">
      <button class="d-flex align-items-center gap-2 btn border" data-bs-toggle="dropdown">
        <span class="rounded-circle bg-navy text-white d-flex align-items-center justify-content-center" style="width:32px;height:32px;background:var(--bw-navy);font-size:0.85rem;">
          <?= strtoupper(substr($current_user_name, 0, 1)) ?>
        </span>
        <span class="small fw-semibold text-navy d-none d-md-inline"><?= clean($current_user_name) ?></span>
      </button>
      <ul class="dropdown-menu dropdown-menu-end">
        <?php if ($current_user_role === 'member'): ?>
        <li><a class="dropdown-item" href="<?= BASE_URL ?>member/profile.php"><i class="fa-solid fa-user me-2"></i>My Profile</a></li>
        <?php endif; ?>
        <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>auth/logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a></li>
      </ul>
    </div>
  </div>
</header>
