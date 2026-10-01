<footer class="bw-footer" id="contact">
  <div class="container">
    <div class="row g-4">
      <div class="col-md-4">
        <h5><i class="fa-solid fa-book-open text-brass"></i> BookWorld</h5>
        <p class="small">A public library platform built to make discovering, borrowing and owning books effortless for every member of our community.</p>
        <div class="bw-social mt-3">
          <a href="#"><i class="fa-brands fa-facebook-f"></i></a>
          <a href="#"><i class="fa-brands fa-instagram"></i></a>
          <a href="#"><i class="fa-brands fa-x-twitter"></i></a>
        </div>
      </div>
      <div class="col-6 col-md-2">
        <h5>Quick Links</h5>
        <ul>
          <li><a href="<?= BASE_URL ?>index.php">Home</a></li>
          <li><a href="<?= BASE_URL ?>browse_books.php">Browse Books</a></li>
          <li><a href="<?= BASE_URL ?>index.php#plans">Membership Plans</a></li>
          <li><a href="<?= BASE_URL ?>index.php#about">About Us</a></li>
        </ul>
      </div>
      <div class="col-6 col-md-2">
        <h5>Account</h5>
        <ul>
          <li><a href="<?= BASE_URL ?>auth/login.php">Login</a></li>
          <li><a href="<?= BASE_URL ?>auth/register.php">Register</a></li>
          <li><a href="<?= BASE_URL ?>auth/forgot_password.php">Forgot Password</a></li>
        </ul>
      </div>
      <div class="col-md-4">
        <h5>Visit Us</h5>
        <ul>
          <li><i class="fa-solid fa-location-dot me-2 text-brass"></i>12 Reading Lane, Vadodara, Gujarat</li>
          <li><i class="fa-solid fa-phone me-2 text-brass"></i>+91 98765 43210</li>
          <li><i class="fa-solid fa-envelope me-2 text-brass"></i>hello@bookworld.com</li>
          <li><i class="fa-solid fa-clock me-2 text-brass"></i>Mon - Sat, 9:00 AM - 8:00 PM</li>
        </ul>
      </div>
    </div>
    <div class="bw-footer-bottom">
      &copy; <?= date('Y') ?> BookWorld Public Library. Built for readers, by readers.
    </div>
  </div>
</footer>
