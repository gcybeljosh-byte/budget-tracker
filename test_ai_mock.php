<?php
// test_ai_mock.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Mock Constants
define('AI_PROVIDER', 'gemini');
define('AI_API_KEY', 'AIzaSyBxDe2cpzoliPWBsZAnP-IpPMDft8fD0GQ');
define('AI_MODEL', 'gemini-2.0-flash');
define('SITE_URL', 'http://localhost/');

echo "Mock environment setup.\n";

// Mock Database Connection
class MockConn
{
    public function query($q)
    {
        return true;
    }
    public function prepare($q)
    {
        return new MockStmt();
    }
}
class MockStmt
{
    public function bind_param(...$args)
    {
        return true;
    }
    public function execute()
    {
        return true;
    }
    public function close()
    {
        return true;
    }
    public function get_result()
    {
        return new MockResult();
    }
}
class MockResult
{
    public function fetch_assoc()
    {
        return null;
    }
    public function fetch_row()
    {
        return [0];
    }
    public function fetch_all($mode)
    {
        return [];
    }
}

$conn = new MockConn();
$user_id = 1;

echo "Including AiHelper.php...\n";
require_once 'includes/AiHelper.php';

echo "Initializing AiHelper...\n";
$aiHelper = new AiHelper($conn, $user_id);

echo "Testing getResponse...\n";
$response = $aiHelper->getResponse("Hello");

echo "Response:\n";
print_r($response);
echo "\nTest complete.\n";
