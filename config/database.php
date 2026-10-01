<?php
/**
 * Database connection - MySQLi
 * BookWorld Library Management System
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'Krishn@2005');
define('DB_NAME', 'bookworld');

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");
