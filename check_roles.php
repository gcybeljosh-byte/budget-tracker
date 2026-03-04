<?php
require 'includes/db.php';
$res = $conn->query("SELECT id, username, email, role, length(role) as role_len FROM users");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
