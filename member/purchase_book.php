<?php
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../config/session.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';
$required_role = 'member';
require_once '../includes/auth_check.php';
$active = 'purchase';
$page_title = 'Buy a Book';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { set_flash('error', 'Invalid request.'); redirect('purchase_book.php'); }

    $book_id = (int)$_POST['book_id'];
    $quantity = max(1, (int)$_POST['quantity']);
    $method = in_array($_POST['payment_method'] ?? '', ['cash','debit_card','online']) ? $_POST['payment_method'] : 'online';

    $stmt = mysqli_prepare($conn, "SELECT title, price FROM books WHERE book_id=? AND status='active'");
    mysqli_stmt_bind_param($stmt, 'i', $book_id);
    mysqli_stmt_execute($stmt);
    $book = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$book) { set_flash('error', 'Book not found.'); redirect('purchase_book.php'); }

    $total = $book['price'] * $quantity;

    mysqli_begin_transaction($conn);
    try {
        $stmt = mysqli_prepare($conn, "INSERT INTO book_purchases (member_id, book_id, quantity, price, total_amount, purchase_date, status) VALUES (?,?,?,?,?, NOW(), 'completed')");
        mysqli_stmt_bind_param($stmt, 'iiidd', $current_user_id, $book_id, $quantity, $book['price'], $total);
        mysqli_stmt_execute($stmt);
        $purchase_id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);

        $txn_id = generate_transaction_id();
        $stmt = mysqli_prepare($conn, "INSERT INTO payments (purchase_id, member_id, amount, payment_method, transaction_id, payment_date, status, payment_type) VALUES (?,?,?,?,?, NOW(), 'success', 'purchase')");
        mysqli_stmt_bind_param($stmt, 'iidss', $purchase_id, $current_user_id, $total, $method, $txn_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        mysqli_commit($conn);
        log_activity($conn, 'member', $current_user_id, 'purchase_book', "Purchased {$quantity}x {$book['title']}");
        set_flash('success', "Purchase successful! You bought {$quantity}x \"{$book['title']}\" for " . inr($total) . ". Transaction ID: $txn_id");
    } catch (Exception $e) {
        mysqli_rollback($conn);
        set_flash('error', 'Payment failed. Please try again.');
    }
    redirect('purchase_book.php');
}

$search = trim($_GET['q'] ?? '');
$sql = "SELECT * FROM books WHERE status='active'";
$params = []; $types = '';
if ($search !== '') { $sql .= " AND (title LIKE ? OR author LIKE ?)"; $like="%$search%"; $params=[$like,$like]; $types='ss'; }
$sql .= " ORDER BY title ASC";
$stmt = mysqli_prepare($conn, $sql);
if ($params) mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$books = mysqli_stmt_get_result($stmt);

$my_purchases = mysqli_query($conn, "SELECT bp.*, b.title FROM book_purchases bp JOIN books b ON b.book_id=bp.book_id WHERE bp.member_id=$current_user_id ORDER BY bp.purchase_date DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Buy a Book - BookWorld</title>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/dashboard.css">
<link rel="stylesheet" href="../assets/css/landing.css">
</head>
<body>
<div class="bw-app">
<?php include '../includes/sidebar_member.php'; ?>
<main class="bw-main">
<?php include '../includes/dashboard_topbar.php'; ?>
<div class="bw-content">

  <form class="d-flex gap-2 mb-4" method="GET">
    <input type="text" name="q" class="form-control bw-form-control" style="width:320px;" placeholder="Search books to buy..." value="<?= clean($search) ?>">
    <button class="btn btn-bw-outline" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
  </form>

  <div class="row g-4 mb-4">
    <?php while ($b = mysqli_fetch_assoc($books)): ?>
      <div class="col-6 col-md-4 col-lg-3">
        <div class="bw-book-card">
          <div class="bw-book-cover"><i class="fa-solid fa-book"></i></div>
          <div class="body">
            <div class="title"><?= clean($b['title']) ?></div>
            <div class="author"><?= clean($b['author']) ?></div>
            <div class="fw-bold text-navy mb-2"><?= inr($b['price']) ?></div>
            <button class="btn btn-bw-primary btn-sm w-100" data-bs-toggle="modal" data-bs-target="#buy<?= $b['book_id'] ?>">Buy Now</button>
          </div>
        </div>
      </div>
      <div class="modal fade" id="buy<?= $b['book_id'] ?>" tabindex="-1">
        <div class="modal-dialog">
          <div class="modal-content">
            <form method="POST">
              <?= csrf_field() ?>
              <input type="hidden" name="book_id" value="<?= $b['book_id'] ?>">
              <div class="modal-header"><h6 class="modal-title">Buy "<?= clean($b['title']) ?>"</h6></div>
              <div class="modal-body">
                <label class="bw-label">Quantity</label>
                <input type="number" min="1" value="1" name="quantity" class="form-control bw-form-control mb-3" required>
                <label class="bw-label">Payment Method</label>
                <select class="form-select bw-form-control" name="payment_method">
                  <option value="online">Online / UPI</option>
                  <option value="debit_card">Debit Card</option>
                  <option value="cash">Cash (at counter)</option>
                </select>
                <div class="alert alert-light small mt-3 mb-0">Price per copy: <?= inr($b['price']) ?>. This is a simulated payment for demo purposes — no real transaction occurs.</div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-bw-outline" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-sm btn-bw-primary">Confirm Purchase</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    <?php endwhile; ?>
  </div>

  <div class="bw-panel">
    <div class="panel-title">Your purchase history</div>
    <table class="table table-bw">
      <thead><tr><th>Book</th><th>Qty</th><th>Total</th><th>Date</th></tr></thead>
      <tbody>
        <?php if (mysqli_num_rows($my_purchases) === 0): ?>
          <tr><td colspan="4" class="text-center text-muted py-3">No purchases yet.</td></tr>
        <?php endif; ?>
        <?php while ($p = mysqli_fetch_assoc($my_purchases)): ?>
          <tr><td><?= clean($p['title']) ?></td><td><?= $p['quantity'] ?></td><td><?= inr($p['total_amount']) ?></td><td class="text-muted small"><?= fmt_date($p['purchase_date']) ?></td></tr>
        <?php endwhile; ?>
      </tbody>
    </table>
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
