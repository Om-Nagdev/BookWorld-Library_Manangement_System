<?php
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../config/session.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';
$required_role = 'member';
require_once '../includes/auth_check.php';
$active = 'browse';
$page_title = 'Browse Books';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { set_flash('error', 'Invalid request.'); redirect('browse_books.php'); }
    $action = $_POST['action'] ?? '';
    $book_id = (int)($_POST['book_id'] ?? 0);

    if ($action === 'request_issue') {
        $plan = get_member_plan($conn, $current_user_id);
        if (!$plan) {
            set_flash('error', 'You need an active subscription to request books. Please subscribe first.');
            redirect('browse_books.php');
        }
        $active_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM book_issues WHERE member_id=$current_user_id AND status IN ('requested','issued')"))['c'];
        if ($active_count >= $plan['max_books']) {
            set_flash('error', "You've reached your plan's limit of {$plan['max_books']} book(s). Return a book to request another.");
            redirect('browse_books.php');
        }
        $dup = mysqli_fetch_assoc(mysqli_query($conn, "SELECT 1 FROM book_issues WHERE member_id=$current_user_id AND book_id=$book_id AND status IN ('requested','issued')"));
        if ($dup) {
            set_flash('error', 'You already have a request or active issue for this book.');
            redirect('browse_books.php');
        }
        $inv = mysqli_fetch_assoc(mysqli_query($conn, "SELECT available_quantity FROM book_inventory WHERE book_id=$book_id"));
        if (!$inv || $inv['available_quantity'] <= 0) {
            set_flash('error', 'This book is currently unavailable. You can reserve it instead.');
            redirect('browse_books.php');
        }

        $stmt = mysqli_prepare($conn, "INSERT INTO book_issues (member_id, book_id, request_date, status) VALUES (?, ?, NOW(), 'requested')");
        mysqli_stmt_bind_param($stmt, 'ii', $current_user_id, $book_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        log_activity($conn, 'member', $current_user_id, 'request_issue', "Requested book_id $book_id");
        set_flash('success', 'Your request has been sent to the librarian for approval.');
        redirect('browse_books.php');
    }

    if ($action === 'reserve') {
        $dup = mysqli_fetch_assoc(mysqli_query($conn, "SELECT 1 FROM reservations WHERE member_id=$current_user_id AND book_id=$book_id AND status='pending'"));
        if ($dup) {
            set_flash('error', 'You already have a pending reservation for this book.');
            redirect('browse_books.php');
        }
        $stmt = mysqli_prepare($conn, "INSERT INTO reservations (member_id, book_id, reservation_date, status) VALUES (?, ?, NOW(), 'pending')");
        mysqli_stmt_bind_param($stmt, 'ii', $current_user_id, $book_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        log_activity($conn, 'member', $current_user_id, 'reserve_book', "Reserved book_id $book_id");
        set_flash('success', "You'll be notified when this book becomes available.");
        redirect('browse_books.php');
    }
}

$search = trim($_GET['q'] ?? '');
$category_filter = $_GET['category'] ?? '';
$sql = "SELECT b.*, c.category_name, bi.available_quantity
        FROM books b LEFT JOIN categories c ON c.category_id=b.category_id LEFT JOIN book_inventory bi ON bi.book_id=b.book_id
        WHERE b.status='active'";
$params = []; $types = '';
if ($search !== '') { $sql .= " AND (b.title LIKE ? OR b.author LIKE ? OR b.isbn LIKE ?)"; $like="%$search%"; $params=[$like,$like,$like]; $types='sss'; }
if ($category_filter !== '') { $sql .= " AND b.category_id=?"; $params[]=$category_filter; $types.='i'; }
$sql .= " ORDER BY b.created_at DESC";
$stmt = mysqli_prepare($conn, $sql);
if ($params) mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$books = mysqli_stmt_get_result($stmt);
$categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY category_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Browse Books - BookWorld</title>
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

  <form class="row g-2 mb-4" method="GET">
    <div class="col-md-7"><input type="text" name="q" class="form-control bw-form-control" placeholder="Search by title, author or ISBN..." value="<?= clean($search) ?>"></div>
    <div class="col-md-3">
      <select name="category" class="form-select bw-form-control">
        <option value="">All categories</option>
        <?php while ($cat = mysqli_fetch_assoc($categories)): ?>
          <option value="<?= $cat['category_id'] ?>" <?= $category_filter == $cat['category_id'] ? 'selected' : '' ?>><?= clean($cat['category_name']) ?></option>
        <?php endwhile; ?>
      </select>
    </div>
    <div class="col-md-2"><button class="btn btn-bw-primary w-100" type="submit">Search</button></div>
  </form>

  <div class="row g-4">
    <?php if (mysqli_num_rows($books) === 0): ?>
      <div class="col-12 text-center text-muted py-5">No books matched your search.</div>
    <?php endif; ?>
    <?php while ($book = mysqli_fetch_assoc($books)):
      $available = ($book['available_quantity'] ?? 0) > 0; ?>
      <div class="col-6 col-md-4 col-lg-3">
        <div class="bw-book-card">
          <div class="bw-book-cover"><i class="fa-solid fa-book"></i></div>
          <div class="body">
            <div class="title"><?= clean($book['title']) ?></div>
            <div class="author"><?= clean($book['author']) ?></div>
            <div class="mb-2"><span class="badge-bw-navy"><?= clean($book['category_name'] ?? 'General') ?></span></div>
            <?php if ($available): ?>
              <form method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="request_issue">
                <input type="hidden" name="book_id" value="<?= $book['book_id'] ?>">
                <button type="submit" class="btn btn-bw-green btn-sm w-100">Request Issue</button>
              </form>
            <?php else: ?>
              <form method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="reserve">
                <input type="hidden" name="book_id" value="<?= $book['book_id'] ?>">
                <button type="submit" class="btn btn-bw-outline btn-sm w-100">Reserve (Out of Stock)</button>
              </form>
            <?php endif; ?>
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
