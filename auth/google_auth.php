<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';

// --- 1. Handle Modern GSI (POST with JWT) ---
$email = null;
$first_name_google = 'Google';
$last_name_google = 'User';
$auth_mode = $_POST['auth_mode'] ?? ($_GET['auth_mode'] ?? 'login');

if (isset($_POST['credential'])) {
    $jwt = $_POST['credential'];
    $parts = explode('.', $jwt);
    if (count($parts) === 3) {
        $payloadRaw = str_replace(['-', '_'], ['+', '/'], $parts[1]);
        $payload = json_decode(base64_decode($payloadRaw), true);

        if ($payload && isset($payload['email'])) {
            // --- SECURITY: HARDEN JWT VALIDATION ---
            $iss = $payload['iss'] ?? '';
            $aud = $payload['aud'] ?? '';
            $exp = $payload['exp'] ?? 0;

            $valid_iss = ($iss === 'https://accounts.google.com' || $iss === 'accounts.google.com');
            $valid_aud = ($aud === GOOGLE_CLIENT_ID);
            $not_expired = ($exp > time());

            if (!$valid_iss || !$valid_aud || !$not_expired) {
                error_log("Google Auth Security Fail: iss=$iss, aud=$aud, exp=$exp (vs " . time() . ")");
                header("Location: " . SITE_URL . "auth/login.php?error=secure_auth_failed");
                exit;
            }

            $email = trim($payload['email']);
            $first_name_google = trim($payload['given_name'] ?? ($payload['name'] ?? 'Google'));
            $last_name_google = trim($payload['family_name'] ?? 'User');
        }
    }
}
// --- 2. Handle Legacy Redirect (GET) ---
else if (isset($_GET['email'])) {
    $email = trim($_GET['email']);
    $first_name_google = isset($_GET['first_name']) ? trim($_GET['first_name']) : 'Google';
    $last_name_google = isset($_GET['last_name']) ? trim($_GET['last_name']) : 'User';
}

// Ensure auth_method column exists
ensureColumnExists($conn, 'users', 'auth_method', "VARCHAR(20) DEFAULT 'Local'");

if ($email) {
    // 1. Check if email exists
    $stmt = $conn->prepare("SELECT id, username, first_name, last_name, role, currency, status FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // --- ATTEMPTING TO REGISTER EXISTING ACCOUNT? ---
        if ($auth_mode === 'register') {
            header("Location: " . SITE_URL . "auth/register.php?error=account_exists");
            exit;
        }

        // --- LOGIN FLOW ---
        $stmt->bind_result($user_id, $username, $first_name, $last_name, $role, $currency, $status);
        $stmt->fetch();

        if ($status === 'inactive') {
            header("Location: " . SITE_URL . "auth/login.php?error=inactive_account");
            exit;
        }

        $_SESSION['id'] = $user_id;
        $_SESSION['username'] = $username;
        $_SESSION['first_name'] = $first_name;
        $_SESSION['last_name'] = $last_name;
        $_SESSION['role'] = $role;
        $_SESSION['user_currency'] = $currency ?? 'PHP';
        $_SESSION['login_time'] = date("Y-m-d H:i:s");

        // Redirect based on role
        if ($role === 'superadmin') {
            header("Location: " . SITE_URL . "admin/dashboard.php");
        } else if ($role === 'admin') {
            header("Location: " . SITE_URL . "core/dashboard.php");
        } else {
            header("Location: " . SITE_URL . "core/dashboard.php");
        }
        exit;
    } else {
        // --- ACCOUNT NOT REGISTERED? ---
        if ($auth_mode === 'login') {
            header("Location: " . SITE_URL . "auth/login.php?error=not_registered");
            exit;
        }

        // --- REGISTER FLOW ---
        $username = explode('@', $email)[0] . '_' . rand(100, 999);
        $password = bin2hex(random_bytes(8));
        $contact_number = "0000000000";

        $stmt_insert = $conn->prepare("INSERT INTO users (username, password, first_name, last_name, email, contact_number, role, auth_method, plaintext_password) VALUES (?, ?, ?, ?, ?, ?, 'user', 'Google', ?)");
        $stmt_insert->bind_param("sssssss", $username, $password, $first_name_google, $last_name_google, $email, $contact_number, $password);

        if ($stmt_insert->execute()) {
            $new_user_id = $stmt_insert->insert_id;

            // Notify Admins and Superadmins
            require_once '../includes/NotificationHelper.php';
            $notifHelper = new NotificationHelper($conn);
            $adminStmt = $conn->prepare("SELECT id FROM users WHERE role IN ('admin', 'superadmin')");
            $adminStmt->execute();
            $admins = $adminStmt->get_result();
            while ($admin = $admins->fetch_assoc()) {
                $notifHelper->addNotification($admin['id'], 'new_user', "New user registered via Google: $first_name_google $last_name_google (@$username)");
            }
            $adminStmt->close();

            $_SESSION['id'] = $new_user_id;
            $_SESSION['username'] = $username;
            $_SESSION['first_name'] = $first_name_google;
            $_SESSION['last_name'] = $last_name_google;
            $_SESSION['role'] = 'user';
            $_SESSION['user_currency'] = 'PHP';
            $_SESSION['login_time'] = date("Y-m-d H:i:s");

            // Temporary storage for credential display
            $_SESSION['google_registered_password'] = $password;

            // Redirect back to register.php to show credentials
            header("Location: " . SITE_URL . "auth/register.php?google_success=1");
            exit;
        } else {
            die("Error processing Google Login: " . $conn->error);
        }
    }
} else {
    header("Location: " . SITE_URL . "auth/login.php?error=google_auth_failed");
    exit;
}
