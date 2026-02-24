<?php
// includes/config.php

// ============================================================================
// AI CONFIGURATION
// ============================================================================

/**
 * AI_PROVIDER: Choose your AI provider
 * 
 * Options:
 * - 'simulation' : No API key needed. Basic pattern matching for testing.
 * - 'openai'     : OpenAI GPT models (GPT-3.5, GPT-4, GPT-4 Turbo, etc.)
 * - 'llm'        : Alias for OpenAI (use same as 'openai')
 * - 'gemini'     : Google Gemini AI models
 * 
 * Recommended: Start with 'simulation' to test, then switch to 'openai' or 'gemini'
 */
define('AI_PROVIDER', 'gemini');

/**
 * AI_API_KEY: Your API key for the selected provider
 * 
 * SECURITY NOTE: To prevent leaks, this file now attempts to load the key from:
 * 1. Server Environment Variables (Recommended for Render)
 * 2. includes/config.local.php (Recommended for Local/InfinityFree)
 * 
 * Do NOT hardcode your real key below if you plan to share this code.
 */
// Load local secrets if they exist
if (file_exists(__DIR__ . '/config.local.php')) {
    include_once __DIR__ . '/config.local.php';
}

$envKey = getenv('AI_API_KEY') ?: (getenv('GEMINI_API_KEY') ?: ($_SESSION['AI_API_KEY'] ?? null));
$localKey = (defined('AI_API_KEY_LOCAL') && AI_API_KEY_LOCAL !== 'YOUR_KEY_HERE') ? AI_API_KEY_LOCAL : null;
$finalKey = $localKey ?: $envKey;

define('AI_API_KEY', $finalKey ?: ''); // Keys are now loaded from Env Vars or config.local.php

/**
 * AI_MODEL: Specify which AI model to use
 * 
 * OpenAI Models (AI_PROVIDER = 'openai' or 'llm'):
 * - 'gpt-4o'           : Latest GPT-4 Optimized (Recommended, fast & smart)
 * - 'gpt-4o-mini'      : Faster, cheaper GPT-4 variant (Good for budget apps)
 * - 'gpt-4-turbo'      : GPT-4 Turbo (High quality, more expensive)
 * - 'gpt-4'            : Standard GPT-4 (High quality, expensive)
 * - 'gpt-3.5-turbo'    : Older, cheaper model (Still capable)
 * 
 * Gemini Models (AI_PROVIDER = 'gemini'):
 * - 'gemini-1.5-flash'       : Current, fast, free tier available (Recommended)
 * - 'gemini-1.5-pro'         : More capable for complex tasks
 * - 'gemini-2.0-flash'       : Latest, fastest
 * - 'gemini-2.0-pro'         : Most advanced (Deep reasoning)
 * 
 * Recommended for Budget Tracking: 'gpt-4o-mini' (best balance of cost/quality)
 */
define('AI_MODEL', 'gemini-2.0-flash');

/**
 * AI_PROXY_URL: Optional proxy for restrictive hosts (e.g., InfinityFree)
 * If defined, the PHP app will talk to this URL instead of Google directly.
 */
// define('AI_PROXY_URL', 'https://budget-tracker-x42m.onrender.com/proxy');
define('AI_PROXY_URL', ''); // Temporarily disabled for testing direct connection

/**
 * GOOGLE CONFIGURATION
 * Used for Sign-In with Google functionality.
 * Link: https://console.cloud.google.com/apis/credentials
 */
define('GOOGLE_CLIENT_ID', '818167411162-lashcfje1kv56ao030ee2salf5qnkioo.apps.googleusercontent.com');

// ============================================================================
// APP CONFIGURATION
// ============================================================================

define('APP_NAME', 'Budget Tracker');
define('APP_VERSION', '2.5.0');

// Path Configuration
if (!defined('SITE_URL')) {
    $is_https = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
        (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    $protocol = $is_https ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'] ?? '';

    // Auto-detect production vs local
    if (strpos($host, 'onrender.com') !== false || strpos($host, 'infinityfree') !== false || strpos($host, 'great-site.net') !== false) {
        // Enforce HTTPS for known production environments
        $baseUrl = "https://" . $host . "/";
    } else {
        // Local fallback
        $scriptPath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        $rootPath = preg_replace('/(\/api|\/core|\/includes|\/auth)$/', '', rtrim($scriptPath, '/'));
        $baseUrl = $protocol . "://" . $host . $rootPath . "/";
    }

    // Ensure single trailing slash
    $baseUrl = rtrim($baseUrl, '/') . '/';
    define('SITE_URL', $baseUrl);
}
define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);

// ============================================================================
// QUICK SETUP GUIDE
// ============================================================================
/*
 * STEP 1: Choose your AI provider above (AI_PROVIDER)
 *         Start with 'simulation' to test without an API key
 * 
 * STEP 2: Get an API key from your chosen provider
 *         OpenAI: https://platform.openai.com/api-keys
 *         Gemini: https://aistudio.google.com/app/apikey
 * 
 * STEP 3: Paste your API key in AI_API_KEY above
 *         Example: define('AI_API_KEY', 'sk-proj-your-key-here');
 * 
 * STEP 4: Select your preferred model (AI_MODEL)
 *         Recommended: 'gpt-4o-mini' for OpenAI
 * 
 * STEP 5: Save this file and test your AI assistant in the chat widget!
 */
