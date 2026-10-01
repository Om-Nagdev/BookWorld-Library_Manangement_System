<?php
require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';
require_once '../../includes/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../login.php');
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    set_flash('error', 'Invalid form submission. Please try again.');
    redirect('../login.php');
}

// ---- Basic brute-force throttle ----
if (!isset($_SESSION['login_attempts'])) $_SESSION['login_attempts'] = 0;
if (!isset($_SESSION['login_lockout_until'])) $_SESSION['login_lockout_until'] = 0;

if (time() < $_SESSION['login_lockout_until']) {
    set_flash('error', 'Too many failed attempts. Please try again in a minute.');
    redirect('../login.php');
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (!is_valid_email($email) || $password === '') {
    set_flash('error', 'Please enter a valid email and password.');
    redirect('../login.php');
}

// ---- Check across role tables: admin, librarian, member ----
$roles = [
    'admin'     => ['table' => 'admin',     'id_col' => 'admin_id'],
    'librarian' => ['table' => 'librarian', 'id_col' => 'librarian_id'],
    'member'    => ['table' => 'member',    'id_col' => 'member_id'],
];

$authenticated = false;

foreach ($roles as $role => $meta) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM {$meta['table']} WHERE email = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if ($user && password_verify($password, $user['password'])) {
        if ($user['status'] !== 'active') {
            set_flash('error', 'Your account is currently inactive. Please contact the library administration.');
            redirect('../login.php');
        }

        // Success
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user[$meta['id_col']];
        $_SESSION['role'] = $role;
        $_SESSION['name'] = $user['name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['login_attempts'] = 0;

        log_activity($conn, $role, $user[$meta['id_col']], 'login', 'User logged in successfully');
        set_flash('success', 'Welcome back, ' . $user['name'] . '! You have successfully logged in.');

        redirect('../../' . $role . '/dashboard.php');
        $authenticated = true;
        break;
    }
}

if (!$authenticated) {
    $_SESSION['login_attempts']++;
    if ($_SESSION['login_attempts'] >= 5) {
        $_SESSION['login_lockout_until'] = time() + 60;
        $_SESSION['login_attempts'] = 0;
    }
    set_flash('error', 'Invalid email or password.');
    redirect('../login.php');
}
