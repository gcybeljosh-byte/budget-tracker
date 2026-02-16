<?php
session_start();
require_once 'includes/db.php';

// Check if parameters exist (Assuming GAS redirects with query params for this setup)
// In production, use POST or JWT for security.
if (isset($_GET['email'])) {
    $email = trim($_GET['email']);
    $first_name = isset($_GET['first_name']) ? trim($_GET['first_name']) : 'Google';
    $last_name = isset($_GET['last_name']) ? trim($_GET['last_name']) : 'User';
    
    // 1. Check if email exists
    $stmt = $conn->prepare("SELECT id, currency FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        // --- LOGIN FLOW ---
        $stmt->bind_result($user_id, $currency);
        $stmt->fetch();
        
        $_SESSION['id'] = $user_id;
        $_SESSION['user_currency'] = $currency ?? 'PHP';
        $_SESSION['user_email'] = $email; // Optional
        
        // Redirect to Dashboard
        header("Location: index.php");
        exit;
        
    } else {
        // --- REGISTER FLOW ---
        // Generate Username (email prefix + random)
        $username = explode('@', $email)[0];
        // Ensure username is unique (simple append)
        $username .= '_' . rand(100, 999);
        
        // Generate Random Password
        $password = bin2hex(random_bytes(8));
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Default Contact
        $contact_number = "0000000000";
        
        $stmt_insert = $conn->prepare("INSERT INTO users (username, password, first_name, last_name, email, contact_number) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_insert->bind_param("ssssss", $username, $hashed_password, $first_name, $last_name, $email, $contact_number);
        
        if ($stmt_insert->execute()) {
            $new_user_id = $stmt_insert->insert_id;

            // Notify Admins
            require_once 'includes/NotificationHelper.php';
            $notifHelper = new NotificationHelper($conn);
            $adminStmt = $conn->prepare("SELECT id FROM users WHERE role = 'admin'");
            $adminStmt->execute();
            $admins = $adminStmt->get_result();
            while ($admin = $admins->fetch_assoc()) {
                $notifHelper->addNotification($admin['id'], 'new_user', "New user registered via Google: $first_name $last_name (@$username)");
            }
            $adminStmt->close();

            $_SESSION['id'] = $new_user_id;
            $_SESSION['user_currency'] = 'PHP'; // Default for new users
             header("Location: index.php");
             exit;
        } else {
            die("Error processing Google Login: " . $conn->error);
        }
    }
} else {
    // No email provided
    header("Location: login.php?error=google_auth_failed");
    exit;
}
?>
