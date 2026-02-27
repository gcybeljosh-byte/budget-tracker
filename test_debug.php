<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
echo "PHP is working.<br>";
require_once 'includes/db.php';
if ($conn) {
    echo "Database connection successful!";
} else {
    echo "Database connection failed.";
}
