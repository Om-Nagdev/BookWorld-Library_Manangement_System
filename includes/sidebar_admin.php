<?php $active = $active ?? ''; ?>
<aside class="bw-sidebar" id="bw-sidebar">
  <div class="brand"><i class="fa-solid fa-book-open"></i> BookWorld</div>
  <div class="role-tag">ADMIN PANEL</div>
  <nav class="nav flex-column">
    <a class="nav-link <?= $active==='dashboard'?'active':'' ?>" href="<?= BASE_URL ?>admin/dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
    <a class="nav-link <?= $active==='books'?'active':'' ?>" href="<?= BASE_URL ?>admin/books/list.php"><i class="fa-solid fa-book"></i> Manage Books</a>
    <a class="nav-link <?= $active==='inventory'?'active':'' ?>" href="<?= BASE_URL ?>admin/inventory.php"><i class="fa-solid fa-boxes-stacked"></i> Inventory</a>
    <a class="nav-link <?= $active==='librarians'?'active':'' ?>" href="<?= BASE_URL ?>admin/librarians/list.php"><i class="fa-solid fa-user-tie"></i> Manage Librarians</a>
    <a class="nav-link <?= $active==='members'?'active':'' ?>" href="<?= BASE_URL ?>admin/members/list.php"><i class="fa-solid fa-users"></i> Manage Members</a>
    <a class="nav-link <?= $active==='donations'?'active':'' ?>" href="<?= BASE_URL ?>admin/donations.php"><i class="fa-solid fa-hand-holding-heart"></i> Donations</a>
    <a class="nav-link <?= $active==='reports'?'active':'' ?>" href="<?= BASE_URL ?>admin/reports.php"><i class="fa-solid fa-chart-line"></i> Reports</a>
    <a class="nav-link <?= $active==='logs'?'active':'' ?>" href="<?= BASE_URL ?>admin/activity_logs.php"><i class="fa-solid fa-clock-rotate-left"></i> Activity Logs</a>
    <div class="nav-divider">ACCOUNT</div>
    <a class="nav-link" href="<?= BASE_URL ?>index.php" target="_blank"><i class="fa-solid fa-globe"></i> View Site</a>
    <a class="nav-link" href="<?= BASE_URL ?>auth/logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
  </nav>
</aside>
