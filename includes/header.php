<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    // Enforce HTTPS Redirect (InfinityFree / Render)
    $is_https = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
        (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    if (!$is_https && strpos($_SERVER['HTTP_HOST'], 'localhost') === false) {
        $https_url = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        header("Location: " . $https_url);
        exit;
    }
    ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    require_once __DIR__ . '/config.php';

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['id'])) {
        header("Location: " . SITE_URL . "auth/login.php");
        exit;
    }

    // Maintenance Guard
    include __DIR__ . '/db.php';
    if (isMaintenanceMode($conn)) {
        header("Location: " . SITE_URL . "auth/logout.php?maintenance=1");
        exit;
    }
    $user_id = $_SESSION['id'];
    $stmt = $conn->prepare("SELECT onboarding_completed, page_tutorials_json FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($onboarding_completed, $page_tutorials_json);
    $stmt->fetch();
    $stmt->close();

    $seen_tutorials = json_decode($page_tutorials_json, true) ?: [];

    $currentPage = basename($_SERVER['PHP_SELF']);
    $role = $_SESSION['role'] ?? 'user';
    if ($onboarding_completed == 0 && !in_array($role, ['superadmin', 'admin']) && $currentPage !== 'onboarding.php' && $currentPage !== 'logout.php') {
        header("Location: " . SITE_URL . "core/onboarding.php");
        exit;
    }

    if (!isset($appName)) {
        $appName = defined('APP_NAME') ? APP_NAME : 'Budget Tracker';
    }

    // --- Theme Persistence Fallback ---
    if (!isset($_SESSION['theme'])) {
        $themeStmt = $conn->prepare("SELECT theme FROM users WHERE id = ?");
        $themeStmt->bind_param("i", $_SESSION['id']);
        $themeStmt->execute();
        $themeResult = $themeStmt->get_result();
        if ($themeRow = $themeResult->fetch_assoc()) {
            $_SESSION['theme'] = $themeRow['theme'] ?? 'light';
        }
        $themeStmt->close();
    }

    // Update last activity for logged in users
    if (isset($_SESSION['id'])) {
        $current_time = date('Y-m-d H:i:s');
        $stmt_update = $conn->prepare("UPDATE users SET last_activity = ? WHERE id = ?");
        $stmt_update->bind_param("si", $current_time, $_SESSION['id']);
        $stmt_update->execute();
        $stmt_update->close();
    }
    ?>
    <!-- PWA Support -->
    <link rel="manifest" href="<?php echo SITE_URL; ?>manifest.json">
    <meta name="theme-color" content="#6366f1">
    <link rel="apple-touch-icon" href="<?php echo SITE_URL; ?>assets/images/favicon.png">

    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' . $appName : $appName; ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/style.css?v=<?php echo time(); ?>">
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo SITE_URL; ?>assets/images/favicon.png">
    <!-- Currency Configuration -->
    <?php
    require_once __DIR__ . '/CurrencyHelper.php';
    $currencyConfig = CurrencyHelper::getJSConfig($_SESSION['user_currency'] ?? 'PHP');
    ?>
    <script>
        window.SITE_URL = "<?php echo SITE_URL; ?>";
        window.userCurrency = <?php echo json_encode($currencyConfig); ?>;
        window.seenTutorials = <?php echo json_encode($seen_tutorials); ?>;
        window.currentPage = "<?php echo $currentPage; ?>";

        function markPageTutorialSeen(page) {
            return fetch(`<?php echo SITE_URL; ?>api/complete_tutorial.php?page=${page}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.seenTutorials[page] = 1;
                    }
                    return data;
                });
        }

        // Service Worker Registration
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('<?php echo SITE_URL; ?>sw.js')
                    .then(reg => console.log('PWA: Service Worker Registered', reg))
                    .catch(err => console.log('PWA: Service Worker Registration Failed', err));
            });
        }
    </script>
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">

</head>

<body class="bg-body-tertiary">
    <!-- Help Desk FAB -->
    <button onclick="toggleChatWidget()" class="ai-fab shadow-lg" title="Help Desk">
        <i class="fas fa-robot"></i>
    </button>
    <div class="d-flex" id="wrapper">