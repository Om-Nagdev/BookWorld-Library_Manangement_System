<?php
require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';
require_once '../../includes/csrf.php';
$required_role = 'librarian';
require_once '../../includes/auth_check.php';
$active = 'books';
$page_title = 'Add New Book';

// ---- Handle form submission ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Invalid form submission.');
        redirect('add.php');
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
    $quantity = max(1, (int)($_POST['quantity'] ?? 1));

    $errors = [];
    if (strlen($title) < 2) $errors[] = 'Title is required.';
    if (strlen($author) < 2) $errors[] = 'Author is required.';
    if (strlen($isbn) < 8) $errors[] = 'A valid ISBN is required.';
    if ($price < 0) $errors[] = 'Price cannot be negative.';

    $image_name = 'default-book.png';
    if (!empty($_FILES['book_image']['name'])) {
        $upload = handle_image_upload($_FILES['book_image'], '../../assets/uploads/books');
        if (is_array($upload) && isset($upload['error'])) {
            $errors[] = $upload['error'];
        } elseif ($upload) {
            $image_name = $upload;
        }
    }

    if (empty($errors)) {
        $stmt = mysqli_prepare($conn, "SELECT book_id FROM books WHERE isbn = ?");
        mysqli_stmt_bind_param($stmt, 's', $isbn);
        mysqli_stmt_execute($stmt);
        if (mysqli_stmt_get_result($stmt)->num_rows > 0) $errors[] = 'A book with this ISBN already exists.';
        mysqli_stmt_close($stmt);
    }

    if (empty($errors)) {
        mysqli_begin_transaction($conn);
        try {
            $stmt = mysqli_prepare($conn, "INSERT INTO books (title, author, isbn, publisher, publication_year, language, category_id, price, description, book_image, status) VALUES (?,?,?,?,?,?,?,?,?,?,'active')");
            mysqli_stmt_bind_param($stmt, 'ssssisidss', $title, $author, $isbn, $publisher, $pub_year, $language, $category_id, $price, $description, $image_name);
            mysqli_stmt_execute($stmt);
            $book_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);

            $stmt = mysqli_prepare($conn, "INSERT INTO book_inventory (book_id, total_quantity, available_quantity, issued_quantity, damaged_quantity, lost_quantity) VALUES (?, ?, ?, 0, 0, 0)");
            mysqli_stmt_bind_param($stmt, 'iii', $book_id, $quantity, $quantity);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            mysqli_commit($conn);
            log_activity($conn, 'librarian', $current_user_id, 'add_book', "Added book: $title");
            push_notification($conn, 'librarian', "New book added: \"$title\" ($quantity copies).", 'system');
            set_flash('success', "\"$title\" was added to the catalog with $quantity cop" . ($quantity == 1 ? 'y' : 'ies') . ".");
            redirect('list.php');
        } catch (Exception $e) {
            mysqli_rollback($conn);
            set_flash('error', 'Failed to add book. Please try again.');
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
<title>Add Book - BookWorld Librarian</title>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="../../assets/css/style.css">
<link rel="stylesheet" href="../../assets/css/dashboard.css">
</head>
<body>
<div class="bw-app">
<?php include '../../includes/sidebar_librarian.php'; ?>
<main class="bw-main">
<?php include '../../includes/dashboard_topbar.php'; ?>
<div class="bw-content">
  <div class="bw-panel" style="max-width:800px;">
    <div class="panel-title">Book details</div>
    <form id="bw-book-form" method="POST" enctype="multipart/form-data" novalidate>
      <?= csrf_field() ?>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="bw-label" for="book_title">Title</label>
          <input type="text" class="form-control bw-form-control" id="book_title" name="title" required>
          <div class="invalid-feedback-bw"></div>
        </div>
        <div class="col-md-6">
          <label class="bw-label" for="book_author">Author</label>
          <input type="text" class="form-control bw-form-control" id="book_author" name="author" required>
          <div class="invalid-feedback-bw"></div>
        </div>
        <div class="col-md-6">
          <label class="bw-label" for="book_isbn">ISBN</label>
          <input type="text" class="form-control bw-form-control" id="book_isbn" name="isbn" required>
          <div class="invalid-feedback-bw"></div>
        </div>
        <div class="col-md-6">
          <label class="bw-label">Publisher</label>
          <input type="text" class="form-control bw-form-control" name="publisher">
        </div>
        <div class="col-md-4">
          <label class="bw-label">Publication Year</label>
          <input type="number" min="1500" max="<?= date('Y') ?>" class="form-control bw-form-control" name="publication_year">
        </div>
        <div class="col-md-4">
          <label class="bw-label">Language</label>
          <input type="text" class="form-control bw-form-control" name="language" value="English">
        </div>
        <div class="col-md-4">
          <label class="bw-label">Category</label>
          <select class="form-select bw-form-control" name="category_id">
            <option value="">Select category</option>
            <?php while ($c = mysqli_fetch_assoc($categories)): ?>
              <option value="<?= $c['category_id'] ?>"><?= clean($c['category_name']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="bw-label" for="book_price">Price (₹)</label>
          <input type="number" step="0.01" min="0" class="form-control bw-form-control" id="book_price" name="price" required>
          <div class="invalid-feedback-bw"></div>
        </div>
        <div class="col-md-6">
          <label class="bw-label" for="book_quantity">Initial Quantity</label>
          <input type="number" min="1" class="form-control bw-form-control" id="book_quantity" name="quantity" value="1" required>
          <div class="invalid-feedback-bw"></div>
        </div>
        <div class="col-12">
          <label class="bw-label">Description</label>
          <textarea class="form-control bw-form-control" name="description" rows="3"></textarea>
        </div>
        <div class="col-12">
          <label class="bw-label">Cover Image (optional, max 2MB)</label>
          <input type="file" class="form-control bw-form-control" name="book_image" accept=".jpg,.jpeg,.png,.webp">
        </div>
      </div>
      <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-bw-primary px-4">Save Book</button>
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
