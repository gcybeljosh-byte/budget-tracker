<?php
session_start();
header("Content-Type: application/json");
require_once '../includes/db.php';
require_once '../includes/NotificationHelper.php';
$notificationHelper = new NotificationHelper($conn);

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['id'];
$response = ['success' => false, 'message' => 'Invalid request'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'create_group':
            $name = trim($_POST['name'] ?? '');
            $desc = trim($_POST['description'] ?? '');

            if ($name) {
                $stmt = $conn->prepare("INSERT INTO shared_groups (owner_id, name, description) VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $user_id, $name, $desc);
                if ($stmt->execute()) {
                    $group_id = $conn->insert_id;
                    // Add owner as active admin
                    $stmt = $conn->prepare("INSERT INTO shared_group_members (group_id, user_id, role, status, joined_at) VALUES (?, ?, 'admin', 'active', NOW())");
                    $stmt->bind_param("ii", $group_id, $user_id);
                    $stmt->execute();

                    $response = ['success' => true, 'message' => 'Group created successfully', 'group_id' => $group_id];
                    logActivity($conn, $user_id, 'group_create', "Created shared group: $name");
                } else {
                    $response = ['success' => false, 'message' => 'Database error: ' . $conn->error];
                }
                $stmt->close();
            } else {
                $response = ['success' => false, 'message' => 'Group name is required'];
            }
            break;

        case 'update_group':
            $group_id = intval($_POST['group_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $desc = trim($_POST['description'] ?? '');

            // Verify ownership/admin
            $stmt = $conn->prepare("SELECT id FROM shared_group_members WHERE group_id = ? AND user_id = ? AND role = 'admin' AND status = 'active'");
            $stmt->bind_param("ii", $group_id, $user_id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                exit;
            }
            $stmt->close();

            if ($name) {
                $stmt = $conn->prepare("UPDATE shared_groups SET name = ?, description = ? WHERE id = ?");
                $stmt->bind_param("ssi", $name, $desc, $group_id);
                if ($stmt->execute()) {
                    $response = ['success' => true, 'message' => 'Group updated successfully'];
                    logActivity($conn, $user_id, 'group_update', "Updated shared group: $name");
                } else {
                    $response = ['success' => false, 'message' => 'Database error'];
                }
                $stmt->close();
            }
            break;

        case 'delete_group':
            $group_id = intval($_POST['group_id'] ?? 0);

            // Only owner can delete (owner_id in shared_groups)
            $stmt = $conn->prepare("SELECT id FROM shared_groups WHERE id = ? AND owner_id = ?");
            $stmt->bind_param("ii", $group_id, $user_id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                echo json_encode(['success' => false, 'message' => 'Only the group owner can delete the group']);
                exit;
            }
            $stmt->close();

            $stmt = $conn->prepare("DELETE FROM shared_groups WHERE id = ?");
            $stmt->bind_param("i", $group_id);
            if ($stmt->execute()) {
                $response = ['success' => true, 'message' => 'Group deleted successfully'];
                logActivity($conn, $user_id, 'group_delete', "Deleted shared group ID: $group_id");
            } else {
                $response = ['success' => false, 'message' => 'Database error'];
            }
            $stmt->close();
            break;

        case 'invite_member':
            $group_id = $_POST['group_id'] ?? 0;
            $email = trim($_POST['email'] ?? '');

            // Verify if user is admin of the group
            $stmt = $conn->prepare("SELECT id FROM shared_group_members WHERE group_id = ? AND user_id = ? AND role = 'admin' AND status = 'active'");
            $stmt->bind_param("ii", $group_id, $user_id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                echo json_encode(['success' => false, 'message' => 'Only group admins can invite members']);
                exit;
            }
            $stmt->close();

            // Find user by email
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res->num_rows === 0) {
                echo json_encode(['success' => false, 'message' => 'User not found with this email']);
                exit;
            }
            $target_user_id = $res->fetch_assoc()['id'];
            $stmt->close();

            // Invite
            $stmt = $conn->prepare("INSERT INTO shared_group_members (group_id, user_id, status) VALUES (?, ?, 'pending')");
            $stmt->bind_param("ii", $group_id, $target_user_id);
            if ($stmt->execute()) {
                $response = ['success' => true, 'message' => 'Invitation sent'];
                logActivity($conn, $user_id, 'group_invite', "Invited $email to group ID $group_id");

                // Get Group Name for notification
                $group_stmt = $conn->prepare("SELECT name FROM shared_groups WHERE id = ?");
                $group_stmt->bind_param("i", $group_id);
                $group_stmt->execute();
                $group_name = $group_stmt->get_result()->fetch_assoc()['name'] ?? 'a Shared Wallet';
                $group_stmt->close();

                // Get Inviter Name
                $inviter_stmt = $conn->prepare("SELECT first_name FROM users WHERE id = ?");
                $inviter_stmt->bind_param("i", $user_id);
                $inviter_stmt->execute();
                $inviter_name = $inviter_stmt->get_result()->fetch_assoc()['first_name'] ?? 'Someone';
                $inviter_stmt->close();

                $notificationHelper->addNotification(
                    $target_user_id,
                    'group_invitation',
                    "📩 $inviter_name invited you to join '$group_name'. Check Collaborative page to respond."
                );
            } else {
                $response = ['success' => false, 'message' => 'User is already a member or invited'];
            }
            $stmt->close();
            break;

        case 'respond_invitation':
            $invite_id = $_POST['invite_id'] ?? 0;
            $status = $_POST['status'] ?? ''; // 'active' or 'delete'

            if ($status === 'active') {
                $stmt = $conn->prepare("UPDATE shared_group_members SET status = 'active', joined_at = NOW() WHERE id = ? AND user_id = ?");
                $stmt->bind_param("ii", $invite_id, $user_id);
            } else {
                $stmt = $conn->prepare("DELETE FROM shared_group_members WHERE id = ? AND user_id = ?");
                $stmt->bind_param("ii", $invite_id, $user_id);
            }

            if ($stmt->execute() && $conn->affected_rows > 0) {
                $response = ['success' => true, 'message' => ($status === 'active' ? 'Invitation accepted' : 'Invitation declined')];
            } else {
                $response = ['success' => false, 'message' => 'Invitation not found or unauthorized'];
            }
            $stmt->close();
            break;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'my_groups';

    if ($action === 'my_groups') {
        $stmt = $conn->prepare("
            SELECT g.*, m.role, m.status as member_status, m.id as membership_id,
            (SELECT COUNT(*) FROM shared_group_members WHERE group_id = g.id AND status = 'active') as member_count
            FROM shared_groups g
            JOIN shared_group_members m ON g.id = m.group_id
            WHERE m.user_id = ?
            ORDER BY g.created_at DESC
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $groups = [];
        while ($row = $result->fetch_assoc()) {
            $groups[] = $row;
        }
        $stmt->close();
        $response = ['success' => true, 'data' => $groups];
    } elseif ($action === 'group_details') {
        $group_id = $_GET['group_id'] ?? 0;
        // Verify membership
        $stmt = $conn->prepare("SELECT role FROM shared_group_members WHERE group_id = ? AND user_id = ? AND status = 'active'");
        $stmt->bind_param("ii", $group_id, $user_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        $stmt->close();

        // Get members
        $stmt = $conn->prepare("
            SELECT m.*, u.username, u.email, u.profile_image 
            FROM shared_group_members m 
            JOIN users u ON m.user_id = u.id 
            WHERE m.group_id = ?
        ");
        $stmt->bind_param("i", $group_id);
        $stmt->execute();
        $members = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $response = ['success' => true, 'data' => ['members' => $members]];
    }
}

echo json_encode($response);
$conn->close();
