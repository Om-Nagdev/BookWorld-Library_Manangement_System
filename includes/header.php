<?php
// Expects: $active (optional) = current nav key for active-state highlighting
$active = $active ?? '';
?>
<nav class="navbar navbar-expand-lg bw-navbar sticky-top">
  <div class="container">
    <a class="navbar-brand" href="<?= BASE_URL ?>index.php"><i class="fa-solid fa-book-open"></i>BookWorld</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#bwNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="bwNav">
      <ul class="navbar-nav mx-auto">
        <li class="nav-item"><a class="nav-link <?= $active === 'home' ? 'active' : '' ?>" href="<?= BASE_URL ?>index.php">Home</a></li>
        <li class="nav-item"><a class="nav-link <?= $active === 'browse' ? 'active' : '' ?>" href="<?= BASE_URL ?>browse_books.php">Browse Books</a></li>
        <li class="nav-item"><a class="nav-link <?= $active === 'plans' ? 'active' : '' ?>" href="<?= BASE_URL ?>index.php#plans">Membership</a></li>
        <li class="nav-item"><a class="nav-link <?= $active === 'about' ? 'active' : '' ?>" href="<?= BASE_URL ?>index.php#about">About</a></li>
        <li class="nav-item"><a class="nav-link <?= $active === 'contact' ? 'active' : '' ?>" href="<?= BASE_URL ?>index.php#contact">Contact</a></li>
      </ul>
      <div class="d-flex gap-2">
        <?php if (isset($_SESSION['user_id'])): ?>
          <a href="<?= BASE_URL . $_SESSION['role'] ?>/dashboard.php" class="btn btn-bw-outline btn-sm px-3">My Dashboard</a>
        <?php else: ?>
          <a href="<?= BASE_URL ?>auth/login.php" class="btn btn-bw-outline btn-sm px-3">Login</a>
          <a href="<?= BASE_URL ?>auth/register.php" class="btn btn-bw-primary btn-sm px-3">Join Now</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>
