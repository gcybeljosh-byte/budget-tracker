<?php
session_start();
session_destroy();
$redirect = "login.php?logout=success";
if (isset($_GET['auto'])) {
    $redirect .= "&auto=1";
}
if (isset($_GET['maintenance'])) {
    $redirect .= "&maintenance=1";
}
header("Location: " . $redirect);
exit;
