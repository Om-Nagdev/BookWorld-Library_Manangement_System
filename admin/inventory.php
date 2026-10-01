<?php
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../config/session.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';
$required_role = 'admin';
require_once '../includes/auth_check.php';
$active = 'inventory';
$page_title = 'Book Inventory';

// Quick "restock" action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restock_book_id'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Invalid request.');
        redirect('inventory.php');
    }
    $book_id = (int)$_POST['restock_book_id'];
    $add_qty = max(1, (int)$_POST['add_quantity']);

    $stmt = mysqli_prepare($conn, "UPDATE book_inventory SET total_quantity = total_quantity + ?, available_quantity = available_quantity + ? WHERE book_id = ?");
    mysqli_stmt_bind_param($stmt, 'iii', $add_qty, $add_qty, $book_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    log_activity($conn, 'admin', $current_user_id, 'restock', "Added $add_qty cop(ies) to book #$book_id");
    set_flash('success', "Added $add_qty copies to inventory.");
    redirect('inventory.php');
}

$inventory = mysqli_query($conn, "SELECT b.book_id, b.title, b.author, bi.* FROM book_inventory bi JOIN books b ON b.book_id = bi.book_id ORDER BY bi.available_quantity ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Inventory - BookWorld Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
<div class="bw-app">
<?php include '../includes/sidebar_admin.php'; ?>
<main class="bw-main">
<?php include '../includes/dashboard_topbar.php'; ?>
<div class="bw-content">
  <div class="bw-panel">
    <div class="table-responsive">
      <table class="table table-bw align-middle">
        <thead>
          <tr><th>Book</th><th>Total</th><th>Available</th><th>Issued</th><th>Damaged</th><th>Lost</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
          <?php while ($inv = mysqli_fetch_assoc($inventory)): ?>
            <tr>
              <td class="fw-semibold text-navy"><?= clean($inv['title']) ?><div class="small text-muted fw-normal"><?= clean($inv['author']) ?></div></td>
              <td><?= $inv['total_quantity'] ?></td>
              <td><?= $inv['available_quantity'] ?></td>
              <td><?= $inv['issued_quantity'] ?></td>
              <td><?= $inv['damaged_quantity'] ?></td>
              <td><?= $inv['lost_quantity'] ?></td>
              <td>
                <?php if ($inv['available_quantity'] <= LOW_STOCK_THRESHOLD): ?>
                  <span class="badge-bw-danger">Low Stock</span>
                <?php else: ?>
                  <span class="badge-bw-success">Healthy</span>
                <?php endif; ?>
              </td>
              <td class="text-end">
                <button class="btn btn-sm btn-bw-outline" data-bs-toggle="modal" data-bs-target="#restock<?= $inv['book_id'] ?>">Restock</button>
              </td>
            </tr>
            <div class="modal fade" id="restock<?= $inv['book_id'] ?>" tabindex="-1">
              <div class="modal-dialog modal-sm">
                <div class="modal-content">
                  <form method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="restock_book_id" value="<?= $inv['book_id'] ?>">
                    <div class="modal-header"><h6 class="modal-title">Restock "<?= clean($inv['title']) ?>"</h6></div>
                    <div class="modal-body">
                      <label class="bw-label">Copies to add</label>
                      <input type="number" min="1" name="add_quantity" class="form-control bw-form-control" value="5" required>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-sm btn-bw-outline" data-bs-dismiss="modal">Cancel</button>
                      <button type="submit" class="btn btn-sm btn-bw-primary">Add Stock</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
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
<script src="../assets/js/main.js"></script>
</body>
</html>
