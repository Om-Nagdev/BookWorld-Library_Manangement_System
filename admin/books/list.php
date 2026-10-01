<?php
require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';
require_once '../../includes/csrf.php';
$required_role = 'admin';
require_once '../../includes/auth_check.php';
$active = 'books';
$page_title = 'Manage Books';

$search = trim($_GET['q'] ?? '');
$sql = "SELECT b.*, c.category_name, bi.total_quantity, bi.available_quantity, bi.issued_quantity
        FROM books b
        LEFT JOIN categories c ON c.category_id = b.category_id
        LEFT JOIN book_inventory bi ON bi.book_id = b.book_id
        WHERE 1=1";
$params = []; $types = '';
if ($search !== '') {
    $sql .= " AND (b.title LIKE ? OR b.author LIKE ? OR b.isbn LIKE ?)";
    $like = "%$search%"; $params = [$like, $like, $like]; $types = 'sss';
}
$sql .= " ORDER BY b.created_at DESC";
$stmt = mysqli_prepare($conn, $sql);
if ($params) mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$books = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Books - BookWorld Admin</title>
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

  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <form class="d-flex gap-2" method="GET">
      <input type="text" name="q" class="form-control bw-form-control" style="width:280px;" placeholder="Search books..." value="<?= clean($search) ?>">
      <button class="btn btn-bw-outline" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
    </form>
    <a href="add.php" class="btn btn-bw-primary"><i class="fa-solid fa-plus me-1"></i>Add New Book</a>
  </div>

  <div class="bw-panel">
    <div class="table-responsive">
      <table class="table table-bw align-middle">
        <thead>
          <tr>
            <th>Title</th><th>Author</th><th>Category</th><th>ISBN</th>
            <th>Price</th><th>Total</th><th>Available</th><th>Status</th><th></th>
          </tr>
        </thead>
        <tbody>
          <?php if (mysqli_num_rows($books) === 0): ?>
            <tr><td colspan="9" class="text-center text-muted py-4">No books found.</td></tr>
          <?php endif; ?>
          <?php while ($b = mysqli_fetch_assoc($books)): ?>
            <tr>
              <td class="fw-semibold text-navy"><?= clean($b['title']) ?></td>
              <td><?= clean($b['author']) ?></td>
              <td><span class="badge-bw-navy"><?= clean($b['category_name'] ?? 'Uncategorized') ?></span></td>
              <td class="text-muted small"><?= clean($b['isbn']) ?></td>
              <td><?= inr($b['price']) ?></td>
              <td><?= (int)$b['total_quantity'] ?></td>
              <td><?= (int)$b['available_quantity'] ?></td>
              <td>
                <?php if ($b['status'] === 'active'): ?>
                  <span class="badge-bw-success">Active</span>
                <?php else: ?>
                  <span class="badge-bw-danger">Inactive</span>
                <?php endif; ?>
              </td>
              <td class="text-end">
                <a href="edit.php?id=<?= $b['book_id'] ?>" class="btn btn-sm btn-bw-outline"><i class="fa-solid fa-pen"></i></a>
                <form action="delete.php" method="POST" class="d-inline">
                  <?= csrf_field() ?>
                  <input type="hidden" name="book_id" value="<?= $b['book_id'] ?>">
                  <button type="button" class="btn btn-sm btn-bw-danger bw-confirm-delete" data-item="<?= clean($b['title']) ?>"><i class="fa-solid fa-trash"></i></button>
                </form>
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>window.BW_FLASH = <?= ($f = get_flash()) ? json_encode($f) : 'null' ?>;</script>
<script src="../../assets/js/main.js"></script>
</body>
</html>
