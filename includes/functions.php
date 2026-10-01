<?php
/**
 * Shared helper functions
 * Requires: $conn (mysqli), constants.php
 */

/** Sanitize a string input */
function clean($str) {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

/** Escape for safe DB use in dynamic identifiers (not values - use prepared statements for values) */
function esc($conn, $str) {
    return mysqli_real_escape_string($conn, $str);
}

/** Validate email format */
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/** Validate password strength: min 8 chars, 1 upper, 1 lower, 1 digit, 1 special */
function is_strong_password($password) {
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $password);
}

/** Set a one-time flash message (used with SweetAlert2 on next page load) */
function set_flash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/** Pop and return the flash message (null if none) */
function get_flash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/** Redirect helper */
function redirect($url) {
    header("Location: $url");
    exit();
}

/** Format currency in INR */
function inr($amount) {
    return '₹' . number_format((float)$amount, 2);
}

/** Format date for display */
function fmt_date($date) {
    if (!$date || $date === '0000-00-00') return '-';
    return date('d M Y', strtotime($date));
}

/** Generate a unique transaction id */
function generate_transaction_id() {
    return 'TXN' . strtoupper(bin2hex(random_bytes(6)));
}

/** Log an activity into activity_logs */
function log_activity($conn, $user_type, $user_id, $action, $description = '') {
    $stmt = mysqli_prepare($conn, "INSERT INTO activity_logs (user_type, user_id, action, description) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, 'siss', $user_type, $user_id, $action, $description);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

/** Push a notification to admin/librarian */
function push_notification($conn, $recipient_type, $message, $type = 'system', $recipient_id = null) {
    $stmt = mysqli_prepare($conn, "INSERT INTO notifications (recipient_type, recipient_id, message, type) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, 'siss', $recipient_type, $recipient_id, $message, $type);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

/** Check inventory levels and notify if at/below threshold */
function check_low_stock($conn, $book_id) {
    $stmt = mysqli_prepare($conn, "SELECT b.title, bi.available_quantity FROM book_inventory bi JOIN books b ON b.book_id = bi.book_id WHERE bi.book_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $book_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if ($row && $row['available_quantity'] <= LOW_STOCK_THRESHOLD) {
        $msg = "Low stock alert: \"{$row['title']}\" has only {$row['available_quantity']} copies left.";
        push_notification($conn, 'admin', $msg, 'low_stock');
        push_notification($conn, 'librarian', $msg, 'low_stock');
    }
}

/** Handle a secure image upload, returns new filename or false */
function handle_image_upload($file, $destination_dir) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        return ['error' => 'File too large. Max 2MB allowed.'];
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_IMG_TYPES)) {
        return ['error' => 'Invalid file type. Allowed: jpg, jpeg, png, webp.'];
    }
    // Verify it's actually an image (not just a renamed file)
    $check = getimagesize($file['tmp_name']);
    if ($check === false) {
        return ['error' => 'Uploaded file is not a valid image.'];
    }
    $new_name = uniqid('img_', true) . '.' . $ext;
    $target = rtrim($destination_dir, '/') . '/' . $new_name;
    if (move_uploaded_file($file['tmp_name'], $target)) {
        return $new_name;
    }
    return ['error' => 'Failed to save uploaded file.'];
}

/** Get unread notification count for a role */
function get_unread_notification_count($conn, $role, $user_id) {
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS cnt FROM notifications WHERE recipient_type = ? AND (recipient_id IS NULL OR recipient_id = ?) AND is_read = 0");
    mysqli_stmt_bind_param($stmt, 'si', $role, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $row['cnt'] ?? 0;
}

/**
 * Calculate an overdue fine for an issue: ₹50/day past due_date.
 * If overdue > 30 days: also charge the book's price and cancel the member's subscription.
 * Returns array with fine details, or null if not overdue.
 */
function calculate_overdue_fine($conn, $issue_id) {
    $stmt = mysqli_prepare($conn, "SELECT bi.member_id, bi.book_id, bi.due_date, b.price, b.title
                                    FROM book_issues bi JOIN books b ON b.book_id = bi.book_id
                                    WHERE bi.issue_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $issue_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $issue = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$issue) return null;

    $today = new DateTime();
    $due = new DateTime($issue['due_date']);
    $diff = $today > $due ? $today->diff($due)->days : 0;

    if ($diff <= 0) return null;

    $amount = $diff * FINE_PER_DAY;
    $forfeited = false;

    if ($diff > OVERDUE_GRACE_DAYS) {
        // Book considered lost: charge full price + cancel subscription
        $amount += (float)$issue['price'];
        $forfeited = true;

        $cancel = mysqli_prepare($conn, "UPDATE subscriptions SET status = 'cancelled' WHERE member_id = ? AND status = 'active'");
        mysqli_stmt_bind_param($cancel, 'i', $issue['member_id']);
        mysqli_stmt_execute($cancel);
        mysqli_stmt_close($cancel);
    }

    return [
        'member_id' => $issue['member_id'],
        'overdue_days' => $diff,
        'amount' => $amount,
        'forfeited' => $forfeited,
        'book_title' => $issue['title']
    ];
}

/** Get a member's active subscription plan (max_books, loan_days), or null if none active */
function get_member_plan($conn, $member_id) {
    $stmt = mysqli_prepare($conn, "SELECT sp.* FROM subscriptions s
                                    JOIN subscription_plans sp ON sp.plan_id = s.plan_id
                                    WHERE s.member_id = ? AND s.status = 'active' AND s.end_date >= CURDATE()
                                    ORDER BY s.subscription_id DESC LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'i', $member_id);
    mysqli_stmt_execute($stmt);
    $plan = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    return $plan;
}

/**
 * Issue a book to a member: validates availability + subscription limit,
 * updates book_issues (existing request row, or creates a new direct-issue row) and inventory.
 * Returns true on success; on failure sets $error and returns false.
 */
function issue_the_book($conn, $issue_id_or_null, $member_id, $book_id, $librarian_id, &$error) {
    $stmt = mysqli_prepare($conn, "SELECT available_quantity FROM book_inventory WHERE book_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $book_id);
    mysqli_stmt_execute($stmt);
    $inv = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$inv || $inv['available_quantity'] <= 0) {
        $error = 'No copies of this book are currently available.';
        return false;
    }

    $plan = get_member_plan($conn, $member_id);
    if (!$plan) {
        $error = 'This member does not have an active subscription plan.';
        return false;
    }

    $active_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM book_issues WHERE member_id = $member_id AND status = 'issued'"))['c'];
    if ($active_count >= $plan['max_books']) {
        $error = "This member has reached their plan limit of {$plan['max_books']} book(s).";
        return false;
    }

    $due_date = date('Y-m-d', strtotime('+' . $plan['loan_days'] . ' days'));

    if ($issue_id_or_null) {
        $stmt = mysqli_prepare($conn, "UPDATE book_issues SET librarian_id=?, approval_date=NOW(), issue_date=CURDATE(), due_date=?, status='issued' WHERE issue_id=?");
        mysqli_stmt_bind_param($stmt, 'isi', $librarian_id, $due_date, $issue_id_or_null);
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO book_issues (member_id, book_id, librarian_id, request_date, approval_date, issue_date, due_date, status) VALUES (?,?,?,NOW(),NOW(),CURDATE(),?, 'issued')");
        mysqli_stmt_bind_param($stmt, 'iiis', $member_id, $book_id, $librarian_id, $due_date);
    }
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($conn, "UPDATE book_inventory SET available_quantity = available_quantity - 1, issued_quantity = issued_quantity + 1 WHERE book_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $book_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    check_low_stock($conn, $book_id);
    return true;
}

/**
 * Process a book return: calculates any overdue fine, restores inventory,
 * and auto-fulfills the earliest pending reservation for that book if one exists.
 */
function process_book_return($conn, $issue_id, $librarian_id) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM book_issues WHERE issue_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $issue_id);
    mysqli_stmt_execute($stmt);
    $issue = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$issue || $issue['status'] !== 'issued') {
        return ['success' => false, 'message' => 'This book is not currently issued.'];
    }

    $fine_info = calculate_overdue_fine($conn, $issue_id);
    $message_parts = [];

    if ($fine_info) {
        $fine_type = $fine_info['forfeited'] ? 'lost_book' : 'overdue';
        $stmt = mysqli_prepare($conn, "INSERT INTO fines (member_id, issue_id, fine_type, overdue_days, amount, status, remarks) VALUES (?,?,?,?,?, 'unpaid', ?)");
        $remarks = $fine_info['forfeited']
            ? 'Book not returned within 30 days: treated as lost, subscription cancelled, full price + late fee charged.'
            : 'Late return fine at ₹' . FINE_PER_DAY . '/day.';
        mysqli_stmt_bind_param($stmt, 'iisids', $fine_info['member_id'], $issue_id, $fine_type, $fine_info['overdue_days'], $fine_info['amount'], $remarks);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $message_parts[] = "A fine of " . inr($fine_info['amount']) . " has been applied (" . $fine_info['overdue_days'] . " day(s) overdue)" . ($fine_info['forfeited'] ? ', and the subscription was cancelled due to the 30+ day delay.' : '.');
    }

    // Mark the issue returned
    $stmt = mysqli_prepare($conn, "UPDATE book_issues SET status='returned', return_date=CURDATE() WHERE issue_id=?");
    mysqli_stmt_bind_param($stmt, 'i', $issue_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Update inventory: one less issued
    $stmt = mysqli_prepare($conn, "UPDATE book_inventory SET issued_quantity = issued_quantity - 1 WHERE book_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $issue['book_id']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if ($fine_info && $fine_info['forfeited']) {
        // Treated as lost — does not go back into the available pool
        $stmt = mysqli_prepare($conn, "UPDATE book_inventory SET lost_quantity = lost_quantity + 1 WHERE book_id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $issue['book_id']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    } else {
        // Check for a pending reservation on this book — auto-fulfill the earliest one
        $stmt = mysqli_prepare($conn, "SELECT * FROM reservations WHERE book_id = ? AND status = 'pending' ORDER BY reservation_date ASC LIMIT 1");
        mysqli_stmt_bind_param($stmt, 'i', $issue['book_id']);
        mysqli_stmt_execute($stmt);
        $reservation = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if ($reservation) {
            // Put the copy back in the pool momentarily, then immediately try to issue it to the reservation holder
            $stmt = mysqli_prepare($conn, "UPDATE book_inventory SET available_quantity = available_quantity + 1 WHERE book_id = ?");
            mysqli_stmt_bind_param($stmt, 'i', $issue['book_id']);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $err = '';
            if (issue_the_book($conn, null, $reservation['member_id'], $issue['book_id'], $librarian_id, $err)) {
                $stmt = mysqli_prepare($conn, "UPDATE reservations SET status='fulfilled', fulfilled_date=NOW() WHERE reservation_id=?");
                mysqli_stmt_bind_param($stmt, 'i', $reservation['reservation_id']);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                push_notification($conn, 'librarian', "Reservation auto-fulfilled for book_id {$issue['book_id']}.", 'system');
                $message_parts[] = 'The earliest pending reservation for this book was automatically fulfilled.';
            }
            // If issuing to the reservation holder failed (e.g. they have no active subscription),
            // the copy simply stays available for anyone to issue/reserve.
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE book_inventory SET available_quantity = available_quantity + 1 WHERE book_id = ?");
            mysqli_stmt_bind_param($stmt, 'i', $issue['book_id']);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }

    check_low_stock($conn, $issue['book_id']);

    return ['success' => true, 'message' => 'Book returned successfully. ' . implode(' ', $message_parts)];
}
