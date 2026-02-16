<?php
// includes/db.php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "budget_tracker";

$conn = mysqli_connect($host, $user, $pass, $dbname);
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// After establishing the database connection
date_default_timezone_set('Asia/Manila');
mysqli_query($conn, "SET time_zone = '+08:00'");
