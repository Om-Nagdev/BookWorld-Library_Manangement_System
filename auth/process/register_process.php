<?php
require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';
require_once '../../includes/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../register.php');
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    set_flash('error', 'Invalid form submission. Please try again.');
    redirect('../register.php');
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$terms = isset($_POST['terms']);

$_SESSION['old_input'] = ['name' => $name, 'email' => $email, 'phone' => $phone, 'address' => $address];

$errors = [];
if (strlen($name) < 3) $errors[] = 'Name must be at least 3 characters.';
if (!is_valid_email($email)) $errors[] = 'Enter a valid email address.';
if (!preg_match('/^[6-9]\d{9}$/', $phone)) $errors[] = 'Enter a valid 10-digit mobile number.';
if (strlen($address) < 5) $errors[] = 'Please enter a valid address.';
if (!is_strong_password($password)) $errors[] = 'Password must be 8+ characters with uppercase, lowercase, number and symbol.';
if ($password !== $confirm_password) $errors[] = 'Passwords do not match.';
if (!$terms) $errors[] = 'You must accept the terms & conditions.';

if (!empty($errors)) {
    set_flash('error', implode(' ', $errors));
    redirect('../register.php');
}

// Check for duplicate email across all role tables
$dupe_check_tables = ['admin' => 'admin_id', 'librarian' => 'librarian_id', 'member' => 'member_id'];
foreach ($dupe_check_tables as $table => $idcol) {
    $stmt = mysqli_prepare($conn, "SELECT $idcol FROM $table WHERE email = ?");
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    if (mysqli_stmt_get_result($stmt)->num_rows > 0) {
        mysqli_stmt_close($stmt);
        set_flash('error', 'An account with this email already exists. Please log in instead.');
        redirect('../register.php');
    }
    mysqli_stmt_close($stmt);
}

$hashed_password = password_hash($password, PASSWORD_BCRYPT);

$stmt = mysqli_prepare($conn, "INSERT INTO member (name, email, password, phone, address, registration_date, status) VALUES (?, ?, ?, ?, ?, NOW(), 'active')");
mysqli_stmt_bind_param($stmt, 'sssss', $name, $email, $hashed_password, $phone, $address);

if (mysqli_stmt_execute($stmt)) {
    $member_id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    log_activity($conn, 'member', $member_id, 'register', 'New member self-registered');
    unset($_SESSION['old_input']);

    redirect('../login.php?msg=registered');
} else {
    mysqli_stmt_close($stmt);
    set_flash('error', 'Something went wrong. Please try again.');
    redirect('../register.php');
}
