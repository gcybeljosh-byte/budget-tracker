<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php 
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Redirect if not logged in
    if (!isset($_SESSION['id'])) {
        header("Location: login.php");
        exit;
    }

    // Onboarding Guard
    include 'includes/db.php';
    $user_id = $_SESSION['id'];
    $stmt = $conn->prepare("SELECT onboarding_completed FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($onboarding_completed);
    $stmt->fetch();
    $stmt->close();

    $currentPage = basename($_SERVER['PHP_SELF']);
    $role = $_SESSION['role'] ?? 'user';
    if ($onboarding_completed == 0 && $role !== 'admin' && $currentPage !== 'onboarding.php' && $currentPage !== 'logout.php') {
        header("Location: onboarding.php");
        exit;
    }

    if (!isset($appName)) {
        $appName = ucwords(str_replace('-', ' ', basename(dirname(__DIR__))));
    }
    ?>
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' . $appName : $appName; ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="favicon.png">
    <!-- Currency Configuration -->
    <?php 
    require_once 'includes/CurrencyHelper.php';
    $currencyConfig = CurrencyHelper::getJSConfig($_SESSION['user_currency'] ?? 'PHP');
    ?>
    <script>
        window.userCurrency = <?php echo json_encode($currencyConfig); ?>;
    </script>
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
</head>

<body class="bg-body-tertiary">

    <div class="d-flex" id="wrapper">
