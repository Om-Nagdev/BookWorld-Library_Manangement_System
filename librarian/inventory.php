<?php
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../config/session.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';
$required_role = 'librarian';
require_once '../includes/auth_check.php';
$active = 'inventory';
$page_title = 'Book Inventory';

$inventory = mysqli_query($conn, "SELECT b.book_id, b.title, b.author, bi.* FROM book_inventory bi JOIN books b ON b.book_id = bi.book_id ORDER BY bi.available_quantity ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Inventory - BookWorld Librarian</title>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
<div class="bw-app">
<?php include '../includes/sidebar_librarian.php'; ?>
<main class="bw-main">
<?php include '../includes/dashboard_topbar.php'; ?>
<div class="bw-content">
  <div class="bw-panel">
    <div class="table-responsive">
      <table class="table table-bw align-middle">
        <thead><tr><th>Book</th><th>Total</th><th>Available</th><th>Issued</th><th>Damaged</th><th>Lost</th><th>Status</th></tr></thead>
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
                  <span class="badge-bw-danger">Low Stock — notify admin</span>
                <?php else: ?>
                  <span class="badge-bw-success">Healthy</span>
                <?php endif; ?>
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
<script src="../assets/js/main.js"></script>
</body>
</html>
