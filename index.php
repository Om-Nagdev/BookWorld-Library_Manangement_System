<?php
require_once 'config/database.php';
require_once 'config/constants.php';
require_once 'config/session.php';
require_once 'includes/functions.php';

// ---- Live stats for the stat strip ----
$total_books = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM books WHERE status='active'"))['c'];
$total_copies = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_quantity) c FROM book_inventory"))['c'] ?? 0;
$total_members = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM member WHERE status='active'"))['c'];
$total_categories = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM categories"))['c'];

// ---- New arrivals ----
$new_arrivals = mysqli_query($conn, "SELECT b.*, c.category_name FROM books b LEFT JOIN categories c ON c.category_id = b.category_id WHERE b.status='active' ORDER BY b.created_at DESC LIMIT 4");

// ---- Categories for chip scroller ----
$categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY category_name");

// ---- Subscription plans ----
$plans = mysqli_query($conn, "SELECT * FROM subscription_plans ORDER BY price ASC");

$active = 'home';
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>BookWorld — Your Public Library, Online</title>
<meta name="description" content="Browse, borrow, reserve, and own books from BookWorld public library.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/landing.css">
</head>
<body>

<div id="bw-loader">
  <div class="bw-loader-book"><i class="fa-solid fa-book-open-reader"></i></div>
  <div class="bw-loader-text">Opening BookWorld...</div>
</div>

<?php include 'includes/header.php'; ?>

<!-- ================= HERO ================= -->
<section class="bw-hero">
  <div class="container position-relative" style="z-index:2;">
    <div class="row align-items-center">
      <div class="col-lg-6">
        <div class="bw-eyebrow-plain">Vadodara Public Library Network</div>
        <h1>A library card that fits in your pocket, and a catalog that never closes.</h1>
        <p class="lead">Search thousands of titles, reserve what's checked out, and get new arrivals delivered to your dashboard — all from one BookWorld account.</p>
        <div class="d-flex gap-3 flex-wrap">
          <a href="auth/register.php" class="btn btn-bw-primary px-4 py-2">Become a member</a>
          <a href="browse_books.php" class="btn btn-bw-outline px-4 py-2" style="border-color:rgba(245,239,223,0.4); color:#F5EFDF;">Browse the catalog</a>
        </div>
      </div>
      <div class="col-lg-6 mt-5 mt-lg-0">
        <div class="bw-hero-shelf">
          <div class="bw-shelf-card">
            <div class="bw-shelf-row">
              <div class="bw-spine" style="background:#2F5233; height:150px;">CLEAN CODE</div>
              <div class="bw-spine" style="background:#C9A24B; height:180px;">ATOMIC HABITS</div>
              <div class="bw-spine" style="background:#0F2A3F; height:130px;">SAPIENS</div>
              <div class="bw-spine" style="background:#A93F35; height:165px;">THE ALCHEMIST</div>
              <div class="bw-spine" style="background:#5B6472; height:145px;">STEVE JOBS</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ================= STAT STRIP ================= -->
<section class="bw-stat-strip">
  <div class="container">
    <div class="row">
      <div class="col-6 col-md-3"><div class="bw-stat"><div class="num"><?= number_format($total_books) ?>+</div><div class="label">Titles in catalog</div></div></div>
      <div class="col-6 col-md-3"><div class="bw-stat"><div class="num"><?= number_format($total_copies) ?>+</div><div class="label">Physical copies</div></div></div>
      <div class="col-6 col-md-3"><div class="bw-stat"><div class="num"><?= number_format($total_members) ?>+</div><div class="label">Active members</div></div></div>
      <div class="col-6 col-md-3"><div class="bw-stat"><div class="num"><?= number_format($total_categories) ?></div><div class="label">Categories</div></div></div>
    </div>
  </div>
</section>

<!-- ================= FEATURES (alternating rows) ================= -->
<section id="about">
  <div class="container">
    <div class="bw-feature-row row align-items-center">
      <div class="col-md-6">
        <div class="bw-feature-icon"><i class="fa-solid fa-magnifying-glass"></i></div>
        <h3>Find exactly what you're looking for</h3>
        <p>Search and filter by title, author or category, and see live availability before you walk in. No more wasted trips for a book that's already checked out.</p>
      </div>
      <div class="col-md-6">
        <img src="https://images.unsplash.com/photo-1521587760476-6c12a4b040da?w=700&q=80" class="img-fluid rounded-4" alt="Library shelves" loading="lazy">
      </div>
    </div>
    <div class="bw-feature-row row align-items-center flex-md-row-reverse">
      <div class="col-md-6">
        <div class="bw-feature-icon"><i class="fa-solid fa-bookmark"></i></div>
        <h3>Reserve a book that's out on loan</h3>
        <p>Place a hold and we'll automatically notify you the moment it's returned — first come, first served, tracked for you automatically.</p>
      </div>
      <div class="col-md-6">
        <img src="https://images.unsplash.com/photo-1507842217343-583bb7270b66?w=700&q=80" class="img-fluid rounded-4" alt="Reading corner" loading="lazy">
      </div>
    </div>
    <div class="bw-feature-row row align-items-center">
      <div class="col-md-6">
        <div class="bw-feature-icon"><i class="fa-solid fa-hand-holding-heart"></i></div>
        <h3>Give a book a second home</h3>
        <p>Donate titles you've outgrown, or buy a copy to keep permanently — pay by cash, card or online, whichever is easiest for you.</p>
      </div>
      <div class="col-md-6">
        <img src="https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?w=700&q=80" class="img-fluid rounded-4" alt="Donated books" loading="lazy">
      </div>
    </div>
  </div>
</section>

<!-- ================= CATEGORIES ================= -->
<section class="py-5 bg-paper">
  <div class="container">
    <h3 class="mb-4">Explore by category</h3>
    <div class="bw-chip-scroll">
      <?php mysqli_data_seek($categories, 0); while ($cat = mysqli_fetch_assoc($categories)): ?>
        <a href="browse_books.php?category=<?= $cat['category_id'] ?>" class="bw-chip"><?= clean($cat['category_name']) ?></a>
      <?php endwhile; ?>
    </div>
  </div>
</section>

<!-- ================= NEW ARRIVALS ================= -->
<section class="py-5">
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h3 class="mb-0">New arrivals</h3>
      <a href="browse_books.php" class="small">View full catalog <i class="fa-solid fa-arrow-right-long ms-1"></i></a>
    </div>
    <div class="row g-4">
      <?php while ($book = mysqli_fetch_assoc($new_arrivals)): ?>
        <div class="col-6 col-md-3">
          <div class="bw-book-card">
            <div class="bw-book-cover"><i class="fa-solid fa-book"></i></div>
            <div class="body">
              <div class="title"><?= clean($book['title']) ?></div>
              <div class="author"><?= clean($book['author']) ?></div>
              <span class="badge-bw-navy"><?= clean($book['category_name'] ?? 'General') ?></span>
            </div>
          </div>
        </div>
      <?php endwhile; ?>
    </div>
  </div>
</section>

<!-- ================= MEMBERSHIP PLANS ================= -->
<section class="py-5 bg-paper" id="plans">
  <div class="container">
    <h3 class="text-center mb-2">Membership plans</h3>
    <p class="text-center text-muted mb-5">Choose a plan that matches how much you read.</p>
    <div class="row g-4 justify-content-center">
      <?php mysqli_data_seek($plans, 0); while ($plan = mysqli_fetch_assoc($plans)):
        $is_gold = $plan['plan_name'] === 'Gold'; ?>
        <div class="col-md-4">
          <div class="bw-auth-card p-4 h-100 <?= $is_gold ? 'border-2' : '' ?>" style="<?= $is_gold ? 'border-color:var(--bw-brass) !important;' : '' ?>">
            <?php if ($is_gold): ?><span class="badge-bw-warning mb-2">Most popular</span><?php endif; ?>
            <h4 class="font-display"><?= clean($plan['plan_name']) ?></h4>
            <div class="mb-3"><span class="fs-3 fw-bold text-navy"><?= inr($plan['price']) ?></span><span class="text-muted">/year</span></div>
            <ul class="list-unstyled small text-muted mb-4">
              <li class="mb-2"><i class="fa-solid fa-check text-green me-2"></i>Borrow up to <?= $plan['max_books'] ?> books at a time</li>
              <li class="mb-2"><i class="fa-solid fa-check text-green me-2"></i><?= $plan['loan_days'] ?>-day loan period</li>
              <li class="mb-2"><i class="fa-solid fa-check text-green me-2"></i>Reserve unavailable titles</li>
              <li class="mb-2"><i class="fa-solid fa-check text-green me-2"></i>Buy books to keep permanently</li>
            </ul>
            <a href="auth/register.php" class="btn <?= $is_gold ? 'btn-bw-primary' : 'btn-bw-outline' ?> w-100">Get Started</a>
          </div>
        </div>
      <?php endwhile; ?>
    </div>
  </div>
</section>

<?php include 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>window.BW_FLASH = <?= $flash ? json_encode($flash) : 'null' ?>;</script>
<script src="assets/js/main.js"></script>
</body>
</html>
