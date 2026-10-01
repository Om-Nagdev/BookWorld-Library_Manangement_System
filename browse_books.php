<?php
require_once 'config/database.php';
require_once 'config/constants.php';
require_once 'config/session.php';
require_once 'includes/functions.php';

$search = trim($_GET['q'] ?? '');
$category_filter = $_GET['category'] ?? '';

$sql = "SELECT b.*, c.category_name, bi.available_quantity
        FROM books b
        LEFT JOIN categories c ON c.category_id = b.category_id
        LEFT JOIN book_inventory bi ON bi.book_id = b.book_id
        WHERE b.status = 'active'";
$params = [];
$types = '';

if ($search !== '') {
    $sql .= " AND (b.title LIKE ? OR b.author LIKE ? OR b.isbn LIKE ?)";
    $like = "%$search%";
    $params[] = $like; $params[] = $like; $params[] = $like;
    $types .= 'sss';
}
if ($category_filter !== '') {
    $sql .= " AND b.category_id = ?";
    $params[] = $category_filter;
    $types .= 'i';
}
$sql .= " ORDER BY b.created_at DESC";

$stmt = mysqli_prepare($conn, $sql);
if ($params) mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$books = mysqli_stmt_get_result($stmt);

$categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY category_name");
$active = 'browse';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Browse Books - BookWorld</title>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/landing.css">
</head>
<body>
<?php include 'includes/header.php'; ?>

<section class="py-5 bg-paper">
  <div class="container">
    <h2 class="mb-4">Browse the catalog</h2>
    <form class="row g-2 mb-2" method="GET">
      <div class="col-md-7 position-relative">
        <input type="text" name="q" id="bw-live-search" autocomplete="off" class="form-control bw-form-control" placeholder="Search by title, author or ISBN..." value="<?= clean($search) ?>">
        <div id="bw-search-suggestions" class="list-group position-absolute w-100 shadow-sm" style="z-index:50; display:none;"></div>
      </div>
      <div class="col-md-3">
        <select name="category" class="form-select bw-form-control">
          <option value="">All categories</option>
          <?php while ($cat = mysqli_fetch_assoc($categories)): ?>
            <option value="<?= $cat['category_id'] ?>" <?= $category_filter == $cat['category_id'] ? 'selected' : '' ?>><?= clean($cat['category_name']) ?></option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="col-md-2">
        <button class="btn btn-bw-primary w-100" type="submit">Search</button>
      </div>
    </form>
  </div>
</section>

<section class="py-5">
  <div class="container">
    <div class="row g-4">
      <?php if (mysqli_num_rows($books) === 0): ?>
        <div class="col-12 text-center text-muted py-5">
          <i class="fa-solid fa-book-skull fa-2x mb-3"></i>
          <p>No books matched your search. Try a different keyword.</p>
        </div>
      <?php endif; ?>
      <?php while ($book = mysqli_fetch_assoc($books)): ?>
        <div class="col-6 col-md-3">
          <div class="bw-book-card">
            <div class="bw-book-cover"><i class="fa-solid fa-book"></i></div>
            <div class="body">
              <div class="title"><?= clean($book['title']) ?></div>
              <div class="author"><?= clean($book['author']) ?></div>
              <div class="d-flex justify-content-between align-items-center">
                <span class="badge-bw-navy"><?= clean($book['category_name'] ?? 'General') ?></span>
                <?php if (($book['available_quantity'] ?? 0) > 0): ?>
                  <span class="badge-bw-success">Available</span>
                <?php else: ?>
                  <span class="badge-bw-danger">On loan</span>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      <?php endwhile; ?>
    </div>
    <?php if (!isset($_SESSION['user_id'])): ?>
      <div class="text-center mt-5 p-4 bw-auth-card">
        <p class="mb-2">Want to issue, reserve or buy a book?</p>
        <a href="auth/login.php" class="btn btn-bw-outline me-2">Login</a>
        <a href="auth/register.php" class="btn btn-bw-primary">Join BookWorld</a>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
<script>
// Live search suggestions (AJAX)
const bwSearchInput = document.getElementById('bw-live-search');
const bwSuggestBox = document.getElementById('bw-search-suggestions');
if (bwSearchInput) {
  bwSearchInput.addEventListener('input', bwDebounce(function () {
    const q = bwSearchInput.value.trim();
    if (q.length < 2) { bwSuggestBox.style.display = 'none'; return; }
    fetch('api/search_books.php?q=' + encodeURIComponent(q))
      .then(r => r.json())
      .then(books => {
        if (!books.length) { bwSuggestBox.style.display = 'none'; return; }
        bwSuggestBox.innerHTML = books.map(b =>
          `<a href="browse_books.php?q=${encodeURIComponent(b.title)}" class="list-group-item list-group-item-action d-flex justify-content-between">
             <span>${b.title} <span class="text-muted small">— ${b.author}</span></span>
             ${b.available ? '<span class="badge-bw-success">Available</span>' : '<span class="badge-bw-danger">On loan</span>'}
           </a>`).join('');
        bwSuggestBox.style.display = 'block';
      });
  }, 300));
  document.addEventListener('click', function (e) {
    if (!bwSearchInput.contains(e.target) && !bwSuggestBox.contains(e.target)) bwSuggestBox.style.display = 'none';
  });
}
</script>
</body>
</html>
