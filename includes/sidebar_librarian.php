<?php $active = $active ?? ''; ?>
<aside class="bw-sidebar" id="bw-sidebar">
  <div class="brand"><i class="fa-solid fa-book-open"></i> BookWorld</div>
  <div class="role-tag">LIBRARIAN PANEL</div>
  <nav class="nav flex-column">
    <a class="nav-link <?= $active==='dashboard'?'active':'' ?>" href="<?= BASE_URL ?>librarian/dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
    <a class="nav-link <?= $active==='books'?'active':'' ?>" href="<?= BASE_URL ?>librarian/books/list.php"><i class="fa-solid fa-book"></i> Manage Books</a>
    <a class="nav-link <?= $active==='issue'?'active':'' ?>" href="<?= BASE_URL ?>librarian/issue_book.php"><i class="fa-solid fa-right-to-bracket"></i> Issue Book</a>
    <a class="nav-link <?= $active==='return'?'active':'' ?>" href="<?= BASE_URL ?>librarian/return_book.php"><i class="fa-solid fa-rotate-left"></i> Return Book</a>
    <a class="nav-link <?= $active==='inventory'?'active':'' ?>" href="<?= BASE_URL ?>librarian/inventory.php"><i class="fa-solid fa-boxes-stacked"></i> Inventory</a>
    <a class="nav-link <?= $active==='donations'?'active':'' ?>" href="<?= BASE_URL ?>librarian/donations.php"><i class="fa-solid fa-hand-holding-heart"></i> Donations</a>
    <a class="nav-link <?= $active==='members'?'active':'' ?>" href="<?= BASE_URL ?>librarian/members.php"><i class="fa-solid fa-users"></i> Members</a>
    <div class="nav-divider">ACCOUNT</div>
    <a class="nav-link" href="<?= BASE_URL ?>index.php" target="_blank"><i class="fa-solid fa-globe"></i> View Site</a>
    <a class="nav-link" href="<?= BASE_URL ?>auth/logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
  </nav>
</aside>
