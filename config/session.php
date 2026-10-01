<?php
/**
 * Secure session bootstrap - include this BEFORE any output on every page
 */

if (session_status() === PHP_SESSION_NONE) {
    // Harden session cookie
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,   // JS can't read the session cookie
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Session timeout - 30 minutes of inactivity
define('SESSION_TIMEOUT', 1800);

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
    session_unset();
    session_destroy();
    $login_url = defined('BASE_URL') ? rtrim(BASE_URL, '/') . '/auth/login.php?expired=1' : 'login.php?expired=1';
    header("Location: $login_url");
    exit();
}
$_SESSION['last_activity'] = time();

// Regenerate session id periodically to prevent fixation
if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
} elseif (time() - $_SESSION['created'] > 900) {
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}
