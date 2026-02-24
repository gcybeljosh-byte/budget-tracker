<?php
// test_chat_api.php
$_SESSION['id'] = 1; // Simulate logged in user
$_SESSION['role'] = 'superadmin';
$_SERVER['REQUEST_METHOD'] = 'POST';

// Mock php://input
function mock_php_input($data)
{
    $temp = tmpfile();
    fwrite($temp, json_encode($data));
    fseek($temp, 0);
    return $temp;
}

// We can't easily mock php://input for the real script without using a wrapper
// So we'll just require it and see if it fails at the start.

echo "Starting test...\n";
chdir('api');
if (!file_exists('chat.php')) {
    die("Error: api/chat.php not found from current dir (" . getcwd() . ")\n");
}
echo "Requiring chat.php...\n";
require_once 'chat.php';
echo "\nTest finished.\n";
