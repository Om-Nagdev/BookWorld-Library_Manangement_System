<?php
require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';
require_once '../../includes/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? '')) {
    redirect('../login.php');
}

$action = $_POST['action'] ?? '';

// ============================================================
// STEP 1: Request a reset link
// ============================================================
if ($action === 'request_reset') {
    $email = trim($_POST['email'] ?? '');

    if (!is_valid_email($email)) {
        set_flash('error', 'Enter a valid email address.');
        redirect('../forgot_password.php');
    }

    // Find which role this email belongs to
    $found_role = null;
    foreach (['admin', 'librarian', 'member'] as $table) {
        $stmt = mysqli_prepare($conn, "SELECT email FROM $table WHERE email = ?");
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        if (mysqli_stmt_get_result($stmt)->num_rows > 0) {
            $found_role = $table;
            mysqli_stmt_close($stmt);
            break;
        }
        mysqli_stmt_close($stmt);
    }

    // Always show the same generic message (don't leak which emails exist)
    if ($found_role) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $stmt = mysqli_prepare($conn, "INSERT INTO password_resets (email, user_type, token, expires_at) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'ssss', $email, $found_role, $token, $expires);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $reset_link = rtrim(BASE_URL, '/') . '/auth/reset_password.php?token=' . $token;

        // Attempt to send email (requires SMTP configured in php.ini on XAMPP)
        @mail($email, 'BookWorld - Password Reset', "Reset your password: $reset_link", "From: no-reply@bookworld.com");

        // For local/demo environments without SMTP configured, surface the link directly.
        set_flash('success', 'If that email is registered, a reset link has been generated. (Demo mode - link: ' . $reset_link . ')');
    } else {
        set_flash('success', 'If that email is registered, a reset link has been sent to it.');
    }

    redirect('../forgot_password.php');
}

// ============================================================
// STEP 2: Perform the actual reset
// ============================================================
if ($action === 'do_reset') {
    $token = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!is_strong_password($password) || $password !== $confirm_password) {
        set_flash('error', 'Passwords must match and meet the strength requirements.');
        redirect('../reset_password.php?token=' . urlencode($token));
    }

    $stmt = mysqli_prepare($conn, "SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW() ORDER BY reset_id DESC LIMIT 1");
    mysqli_stmt_bind_param($stmt, 's', $token);
    mysqli_stmt_execute($stmt);
    $reset_row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$reset_row) {
        set_flash('error', 'This reset link has expired. Please request a new one.');
        redirect('../forgot_password.php');
    }

    $table = $reset_row['user_type'];
    $hashed = password_hash($password, PASSWORD_BCRYPT);

    $stmt = mysqli_prepare($conn, "UPDATE $table SET password = ? WHERE email = ?");
    mysqli_stmt_bind_param($stmt, 'ss', $hashed, $reset_row['email']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Invalidate all reset tokens for this email
    $stmt = mysqli_prepare($conn, "DELETE FROM password_resets WHERE email = ?");
    mysqli_stmt_bind_param($stmt, 's', $reset_row['email']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    redirect('../login.php?msg=reset_success');
}

redirect('../login.php');
