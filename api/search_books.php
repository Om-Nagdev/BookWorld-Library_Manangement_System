<?php
/**
 * AJAX endpoint: live search suggestions as the user types.
 * GET /api/search_books.php?q=harry
 * Returns: JSON array of { book_id, title, author, available }
 */
require_once '../config/database.php';
require_once '../config/constants.php';
header('Content-Type: application/json');

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

$like = "%$q%";
$stmt = mysqli_prepare($conn, "SELECT b.book_id, b.title, b.author, COALESCE(bi.available_quantity,0) AS available
                                FROM books b LEFT JOIN book_inventory bi ON bi.book_id=b.book_id
                                WHERE b.status='active' AND (b.title LIKE ? OR b.author LIKE ?)
                                ORDER BY b.title ASC LIMIT 8");
mysqli_stmt_bind_param($stmt, 'ss', $like, $like);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$books = [];
while ($row = mysqli_fetch_assoc($result)) {
    $books[] = [
        'book_id' => (int)$row['book_id'],
        'title' => $row['title'],
        'author' => $row['author'],
        'available' => (int)$row['available'] > 0
    ];
}
mysqli_stmt_close($stmt);
echo json_encode($books);
