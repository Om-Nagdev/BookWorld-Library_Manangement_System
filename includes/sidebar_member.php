<?php $active = $active ?? ''; ?>
<aside class="bw-sidebar" id="bw-sidebar">
  <div class="brand"><i class="fa-solid fa-book-open"></i> BookWorld</div>
  <div class="role-tag">MY LIBRARY</div>
  <nav class="nav flex-column">
    <a class="nav-link <?= $active==='dashboard'?'active':'' ?>" href="<?= BASE_URL ?>member/dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
    <a class="nav-link <?= $active==='browse'?'active':'' ?>" href="<?= BASE_URL ?>member/browse_books.php"><i class="fa-solid fa-magnifying-glass"></i> Browse Books</a>
    <a class="nav-link <?= $active==='issues'?'active':'' ?>" href="<?= BASE_URL ?>member/my_issues.php"><i class="fa-solid fa-book-open-reader"></i> My Issued Books</a>
    <a class="nav-link <?= $active==='reservations'?'active':'' ?>" href="<?= BASE_URL ?>member/reservations.php"><i class="fa-solid fa-bookmark"></i> My Reservations</a>
    <a class="nav-link <?= $active==='purchase'?'active':'' ?>" href="<?= BASE_URL ?>member/purchase_book.php"><i class="fa-solid fa-cart-shopping"></i> Buy a Book</a>
    <a class="nav-link <?= $active==='donate'?'active':'' ?>" href="<?= BASE_URL ?>member/donate_book.php"><i class="fa-solid fa-hand-holding-heart"></i> Donate a Book</a>
    <a class="nav-link <?= $active==='subscription'?'active':'' ?>" href="<?= BASE_URL ?>member/subscription.php"><i class="fa-solid fa-crown"></i> Subscription</a>
    <a class="nav-link <?= $active==='feedback'?'active':'' ?>" href="<?= BASE_URL ?>member/feedback.php"><i class="fa-solid fa-comment-dots"></i> Feedback</a>
    <a class="nav-link <?= $active==='profile'?'active':'' ?>" href="<?= BASE_URL ?>member/profile.php"><i class="fa-solid fa-user"></i> My Profile</a>
    <div class="nav-divider">ACCOUNT</div>
    <a class="nav-link" href="<?= BASE_URL ?>index.php" target="_blank"><i class="fa-solid fa-globe"></i> View Site</a>
    <a class="nav-link" href="<?= BASE_URL ?>auth/logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
  </nav>
</aside>
