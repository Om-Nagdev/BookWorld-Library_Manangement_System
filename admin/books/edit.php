<?php
require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';
require_once '../../includes/csrf.php';
$required_role = 'admin';
require_once '../../includes/auth_check.php';
$active = 'books';
$page_title = 'Edit Book';

$book_id = (int)($_GET['id'] ?? 0);

$stmt = mysqli_prepare($conn, "SELECT b.*, bi.total_quantity, bi.available_quantity, bi.issued_quantity, bi.damaged_quantity, bi.lost_quantity
                                FROM books b LEFT JOIN book_inventory bi ON bi.book_id = b.book_id WHERE b.book_id = ?");
mysqli_stmt_bind_param($stmt, 'i', $book_id);
mysqli_stmt_execute($stmt);
$book = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$book) {
    set_flash('error', 'Book not found.');
    redirect('list.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Invalid form submission.');
        redirect('edit.php?id=' . $book_id);
    }

    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $isbn = trim($_POST['isbn'] ?? '');
    $publisher = trim($_POST['publisher'] ?? '');
    $pub_year = (int)($_POST['publication_year'] ?? 0);
    $language = trim($_POST['language'] ?? 'English');
    $category_id = (int)($_POST['category_id'] ?? 0) ?: null;
    $price = (float)($_POST['price'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] === 'inactive' ? 'inactive' : 'active';
    $total_quantity = max(0, (int)($_POST['total_quantity'] ?? 0));

    $errors = [];
    if (strlen($title) < 2) $errors[] = 'Title is required.';
    if (strlen($author) < 2) $errors[] = 'Author is required.';
    if ($total_quantity < (int)$book['issued_quantity']) {
        $errors[] = 'Total quantity cannot be less than the number currently issued (' . $book['issued_quantity'] . ').';
    }

    $image_name = $book['book_image'];
    if (!empty($_FILES['book_image']['name'])) {
        $upload = handle_image_upload($_FILES['book_image'], '../../assets/uploads/books');
        if (is_array($upload) && isset($upload['error'])) $errors[] = $upload['error'];
        elseif ($upload) $image_name = $upload;
    }

    if (empty($errors)) {
        mysqli_begin_transaction($conn);
        try {
            $stmt = mysqli_prepare($conn, "UPDATE books SET title=?, author=?, isbn=?, publisher=?, publication_year=?, language=?, category_id=?, price=?, description=?, book_image=?, status=? WHERE book_id=?");
            mysqli_stmt_bind_param($stmt, 'ssssisidsssi', $title, $author, $isbn, $publisher, $pub_year, $language, $category_id, $price, $description, $image_name, $status, $book_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $new_available = max(0, $total_quantity - (int)$book['issued_quantity'] - (int)$book['damaged_quantity'] - (int)$book['lost_quantity']);
            $stmt = mysqli_prepare($conn, "UPDATE book_inventory SET total_quantity=?, available_quantity=? WHERE book_id=?");
            mysqli_stmt_bind_param($stmt, 'iii', $total_quantity, $new_available, $book_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            mysqli_commit($conn);
            check_low_stock($conn, $book_id);
            log_activity($conn, 'admin', $current_user_id, 'edit_book', "Updated book: $title");
            set_flash('success', "\"$title\" was updated successfully.");
            redirect('list.php');
        } catch (Exception $e) {
            mysqli_rollback($conn);
            set_flash('error', 'Failed to update book.');
        }
    } else {
        set_flash('error', implode(' ', $errors));
    }
}

$categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY category_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Book - BookWorld Admin</title>
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
  <div class="bw-panel" style="max-width:800px;">
    <div class="panel-title">Edit book details</div>
    <form id="bw-book-form" method="POST" enctype="multipart/form-data" novalidate>
      <?= csrf_field() ?>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="bw-label" for="book_title">Title</label>
          <input type="text" class="form-control bw-form-control" id="book_title" name="title" value="<?= clean($book['title']) ?>" required>
          <div class="invalid-feedback-bw"></div>
        </div>
        <div class="col-md-6">
          <label class="bw-label" for="book_author">Author</label>
          <input type="text" class="form-control bw-form-control" id="book_author" name="author" value="<?= clean($book['author']) ?>" required>
          <div class="invalid-feedback-bw"></div>
        </div>
        <div class="col-md-6">
          <label class="bw-label" for="book_isbn">ISBN</label>
          <input type="text" class="form-control bw-form-control" id="book_isbn" name="isbn" value="<?= clean($book['isbn']) ?>" readonly>
        </div>
        <div class="col-md-6">
          <label class="bw-label">Publisher</label>
          <input type="text" class="form-control bw-form-control" name="publisher" value="<?= clean($book['publisher']) ?>">
        </div>
        <div class="col-md-4">
          <label class="bw-label">Publication Year</label>
          <input type="number" class="form-control bw-form-control" name="publication_year" value="<?= clean($book['publication_year']) ?>">
        </div>
        <div class="col-md-4">
          <label class="bw-label">Language</label>
          <input type="text" class="form-control bw-form-control" name="language" value="<?= clean($book['language']) ?>">
        </div>
        <div class="col-md-4">
          <label class="bw-label">Category</label>
          <select class="form-select bw-form-control" name="category_id">
            <option value="">Select category</option>
            <?php while ($c = mysqli_fetch_assoc($categories)): ?>
              <option value="<?= $c['category_id'] ?>" <?= $book['category_id'] == $c['category_id'] ? 'selected' : '' ?>><?= clean($c['category_name']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="bw-label" for="book_price">Price (₹)</label>
          <input type="number" step="0.01" min="0" class="form-control bw-form-control" id="book_price" name="price" value="<?= clean($book['price']) ?>" required>
        </div>
        <div class="col-md-4">
          <label class="bw-label" for="book_quantity">Total Quantity</label>
          <input type="number" min="<?= (int)$book['issued_quantity'] ?>" class="form-control bw-form-control" id="book_quantity" name="total_quantity" value="<?= (int)$book['total_quantity'] ?>" required>
          <small class="text-muted"><?= $book['issued_quantity'] ?> currently issued</small>
        </div>
        <div class="col-md-4">
          <label class="bw-label">Status</label>
          <select class="form-select bw-form-control" name="status">
            <option value="active" <?= $book['status']==='active'?'selected':'' ?>>Active</option>
            <option value="inactive" <?= $book['status']==='inactive'?'selected':'' ?>>Inactive</option>
          </select>
        </div>
        <div class="col-12">
          <label class="bw-label">Description</label>
          <textarea class="form-control bw-form-control" name="description" rows="3"><?= clean($book['description']) ?></textarea>
        </div>
        <div class="col-12">
          <label class="bw-label">Replace Cover Image (optional)</label>
          <input type="file" class="form-control bw-form-control" name="book_image" accept=".jpg,.jpeg,.png,.webp">
        </div>
      </div>
      <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-bw-primary px-4">Update Book</button>
        <a href="list.php" class="btn btn-bw-outline px-4">Cancel</a>
      </div>
    </form>
  </div>
</div>
</main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>window.BW_FLASH = <?= ($f = get_flash()) ? json_encode($f) : 'null' ?>;</script>
<script src="../../assets/js/validation.js"></script>
<script src="../../assets/js/main.js"></script>
</body>
</html>
