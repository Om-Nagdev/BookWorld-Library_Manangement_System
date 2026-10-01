/**
 * BookWorld - Client-side form validation
 * Mirrors server-side rules in includes/functions.php — server is the source of truth,
 * this just gives instant feedback.
 */

function bwShowError(input, message) {
  input.classList.add('is-invalid-bw');
  const feedback = input.parentElement.querySelector('.invalid-feedback-bw');
  if (feedback) {
    feedback.textContent = message;
    feedback.classList.add('show');
  }
}

function bwClearError(input) {
  input.classList.remove('is-invalid-bw');
  const feedback = input.parentElement.querySelector('.invalid-feedback-bw');
  if (feedback) feedback.classList.remove('show');
}

function bwValidateEmail(value) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
}

function bwValidatePassword(value) {
  // min 8 chars, 1 upper, 1 lower, 1 digit, 1 special
  return /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/.test(value);
}

function bwValidatePhone(value) {
  return /^[6-9]\d{9}$/.test(value);
}

document.addEventListener('DOMContentLoaded', function () {

  // ---- Registration form ----
  const registerForm = document.getElementById('bw-register-form');
  if (registerForm) {
    registerForm.addEventListener('submit', function (e) {
      let valid = true;

      const name = document.getElementById('reg_name');
      if (name.value.trim().length < 3) { bwShowError(name, 'Name must be at least 3 characters.'); valid = false; }
      else bwClearError(name);

      const email = document.getElementById('reg_email');
      if (!bwValidateEmail(email.value.trim())) { bwShowError(email, 'Enter a valid email address.'); valid = false; }
      else bwClearError(email);

      const phone = document.getElementById('reg_phone');
      if (!bwValidatePhone(phone.value.trim())) { bwShowError(phone, 'Enter a valid 10-digit mobile number.'); valid = false; }
      else bwClearError(phone);

      const password = document.getElementById('reg_password');
      if (!bwValidatePassword(password.value)) {
        bwShowError(password, 'Min 8 chars with uppercase, lowercase, number & symbol.');
        valid = false;
      } else bwClearError(password);

      const confirm = document.getElementById('reg_confirm_password');
      if (confirm.value !== password.value || confirm.value === '') {
        bwShowError(confirm, 'Passwords do not match.');
        valid = false;
      } else bwClearError(confirm);

      const address = document.getElementById('reg_address');
      if (address && address.value.trim().length < 5) { bwShowError(address, 'Please enter a valid address.'); valid = false; }
      else if (address) bwClearError(address);

      const terms = document.getElementById('reg_terms');
      if (terms && !terms.checked) {
        Swal.fire('Almost there!', 'Please accept the terms & conditions to continue.', 'warning');
        valid = false;
      }

      if (!valid) e.preventDefault();
    });
  }

  // ---- Login form ----
  const loginForm = document.getElementById('bw-login-form');
  if (loginForm) {
    loginForm.addEventListener('submit', function (e) {
      let valid = true;
      const email = document.getElementById('login_email');
      if (!bwValidateEmail(email.value.trim())) { bwShowError(email, 'Enter a valid email address.'); valid = false; }
      else bwClearError(email);

      const password = document.getElementById('login_password');
      if (password.value.trim() === '') { bwShowError(password, 'Password is required.'); valid = false; }
      else bwClearError(password);

      if (!valid) e.preventDefault();
    });
  }

  // ---- Forgot password ----
  const forgotForm = document.getElementById('bw-forgot-form');
  if (forgotForm) {
    forgotForm.addEventListener('submit', function (e) {
      const email = document.getElementById('forgot_email');
      if (!bwValidateEmail(email.value.trim())) {
        bwShowError(email, 'Enter a valid email address.');
        e.preventDefault();
      } else bwClearError(email);
    });
  }

  // ---- Reset password ----
  const resetForm = document.getElementById('bw-reset-form');
  if (resetForm) {
    resetForm.addEventListener('submit', function (e) {
      let valid = true;
      const password = document.getElementById('reset_password');
      if (!bwValidatePassword(password.value)) { bwShowError(password, 'Min 8 chars with uppercase, lowercase, number & symbol.'); valid = false; }
      else bwClearError(password);

      const confirm = document.getElementById('reset_confirm_password');
      if (confirm.value !== password.value || confirm.value === '') { bwShowError(confirm, 'Passwords do not match.'); valid = false; }
      else bwClearError(confirm);

      if (!valid) e.preventDefault();
    });
  }

  // ---- Add/Edit Book form ----
  const bookForm = document.getElementById('bw-book-form');
  if (bookForm) {
    bookForm.addEventListener('submit', function (e) {
      let valid = true;
      const title = document.getElementById('book_title');
      if (title.value.trim().length < 2) { bwShowError(title, 'Title is required.'); valid = false; }
      else bwClearError(title);

      const author = document.getElementById('book_author');
      if (author.value.trim().length < 2) { bwShowError(author, 'Author is required.'); valid = false; }
      else bwClearError(author);

      const isbn = document.getElementById('book_isbn');
      if (isbn && isbn.value.trim().length < 8) { bwShowError(isbn, 'Enter a valid ISBN.'); valid = false; }
      else if (isbn) bwClearError(isbn);

      const qty = document.getElementById('book_quantity');
      if (qty && (isNaN(qty.value) || Number(qty.value) < 1)) { bwShowError(qty, 'Quantity must be at least 1.'); valid = false; }
      else if (qty) bwClearError(qty);

      const price = document.getElementById('book_price');
      if (price && (isNaN(price.value) || Number(price.value) < 0)) { bwShowError(price, 'Enter a valid price.'); valid = false; }
      else if (price) bwClearError(price);

      if (!valid) e.preventDefault();
    });
  }

  // ---- Live password strength meter ----
  const pwFields = document.querySelectorAll('[data-strength-meter]');
  pwFields.forEach(function (field) {
    const meter = document.getElementById(field.getAttribute('data-strength-meter'));
    if (!meter) return;
    field.addEventListener('input', function () {
      const val = field.value;
      let score = 0;
      if (val.length >= 8) score++;
      if (/[A-Z]/.test(val)) score++;
      if (/[a-z]/.test(val)) score++;
      if (/\d/.test(val)) score++;
      if (/[\W_]/.test(val)) score++;
      const levels = ['#A93F35', '#A93F35', '#B4791F', '#B4791F', '#2F5233', '#2F5233'];
      const labels = ['Very weak', 'Weak', 'Fair', 'Good', 'Strong', 'Strong'];
      meter.style.width = (score * 20) + '%';
      meter.style.background = levels[score];
      const label = document.getElementById(field.getAttribute('data-strength-meter') + '-label');
      if (label) { label.textContent = val ? labels[score] : ''; label.style.color = levels[score]; }
    });
  });
});
