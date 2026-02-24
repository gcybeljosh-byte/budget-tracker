<?php
// includes/config.example.php
// Copy this file to includes/config.php and fill in your actual values.

// ============================================================================
// AI CONFIGURATION
// ============================================================================

define('AI_PROVIDER', 'gemini'); // Options: 'simulation', 'openai', 'gemini'
define('AI_API_KEY', 'YOUR_API_KEY_HERE');
define('AI_MODEL', 'gemini-2.0-flash');

// ============================================================================
// APP CONFIGURATION
// ============================================================================

define('APP_NAME', 'Budget Tracker');
define('APP_VERSION', '2.5.0');
define('SITE_URL', 'https://your-site-url.com/');
define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
