<?php
$pageTitle = 'Activity Logs';
$pageHeader = 'System Audit Logs';
include '../includes/header.php';

// Security Check: Superadmin Only
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: " . SITE_URL . "auth/login.php");
    exit;
}

// Fetch Activity Logs
$stmt = $conn->prepare("
    SELECT al.*, u.username, u.first_name, u.last_name, u.role, u.last_activity
    FROM activity_logs al
    JOIN users u ON al.user_id = u.id
    ORDER BY al.id DESC
    LIMIT 500
");
$stmt->execute();
$logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch Online Status
$usersStmt = $conn->query("SELECT id, username, first_name, last_name, last_activity, role FROM users ORDER BY last_activity DESC");
$allUsers = $usersStmt->fetch_all(MYSQLI_ASSOC);
?>

<?php include '../includes/sidebar.php'; ?>

<div id="page-content-wrapper">
    <?php include '../includes/navbar.php'; ?>

    <div class="container-fluid px-4 py-4">
        
        <!-- Personalized Greeting -->
        <div class="mb-4 fade-up">
            <h4 class="fw-bold mb-1">System Logs, <span class="text-primary"><?php echo htmlspecialchars($_SESSION['first_name'] ?? 'Admin'); ?></span></h4>
            <p class="text-secondary small mb-0">Audit and presence monitoring as of <?php echo date('F d, Y'); ?>.</p>
        </div>
        <div class="row g-4">
            <!-- stats -->
            <div class="col-md-4">
                <div class="card h-100 bg-gradient-success text-white border-0 shadow-sm rounded-4">
                    <div class="card-body p-4">
                        <h5 class="card-title text-opacity-75 mb-3"><i class="fas fa-users-viewfinder me-2"></i>Online Users</h5>
                        <h2 class="display-5 fw-bold mb-0">
                            <?php 
                            $onlineCount = 0;
                            foreach($allUsers as $u) {
                                if ($u['last_activity'] && strtotime($u['last_activity']) > strtotime('-5 minutes')) $onlineCount++;
                            }
                            echo $onlineCount;
                            ?>
                        </h2>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                    <h6 class="fw-bold mb-3 text-dark"><i class="fas fa-circle text-success me-2 small pulse"></i>User Presence</h6>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach($allUsers as $u): 
                            $isOnline = ($u['last_activity'] && strtotime($u['last_activity']) > strtotime('-5 minutes'));
                            $statusClass = $isOnline ? 'bg-success' : 'bg-secondary';
                            $roleBadge = ($u['role'] === 'admin') ? '<span class="badge bg-warning text-dark ms-1" style="font-size:0.5rem;">ADMIN</span>' : '';
                        ?>
                            <div class="d-flex align-items-center bg-light px-3 py-2 rounded-pill border shadow-sm">
                                <span class="rounded-circle <?php echo $statusClass; ?> me-2" style="width: 10px; height: 10px; <?php echo $isOnline ? 'box-shadow: 0 0 8px #198754;' : ''; ?>"></span>
                                <span class="small fw-bold">@<?php echo htmlspecialchars($u['username']); ?><?php echo $roleBadge; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Activity Logs Table -->
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-history me-2 text-primary"></i>System Activity Logs</h5>
                        <button onclick="window.location.reload()" class="btn btn-sm btn-light rounded-pill px-3 fw-bold border shadow-sm">
                            <i class="fas fa-sync-alt me-1"></i> Refresh
                        </button>
                    </div>
                    <div class="table-responsive p-0">
                        <table class="table table-hover mb-0 align-middle" id="logsTable">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4 py-3 text-secondary small text-uppercase fw-bold">Timestamp</th>
                                    <th class="py-3 text-secondary small text-uppercase fw-bold">User</th>
                                    <th class="py-3 text-secondary small text-uppercase fw-bold">Action</th>
                                    <th class="py-3 text-secondary small text-uppercase fw-bold">IP & Device</th>
                                    <th class="py-3 text-secondary small text-uppercase fw-bold">Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($logs as $log): ?>
                                    <tr style="transition: all 0.2s;">
                                        <td class="ps-4">
                                            <div class="fw-bold text-dark small"><?php echo date('M d, Y', strtotime($log['created_at'])); ?></div>
                                            <div class="text-muted" style="font-size: 0.75rem;"><?php echo date('H:i:s', strtotime($log['created_at'])); ?></div>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark mb-0"><?php echo htmlspecialchars($log['first_name']); ?></div>
                                            <div class="small text-muted">@<?php echo htmlspecialchars($log['username']); ?></div>
                                        </td>
                                        <td>
                                            <?php 
                                            $actionColors = [
                                                'login'           => 'bg-success',
                                                'registration'    => 'bg-info',
                                                'login_failed'    => 'bg-danger',
                                                'logout'          => 'bg-secondary',
                                                'expense_add'     => 'bg-danger',
                                                'expense_edit'    => 'bg-warning text-dark',
                                                'expense_delete'  => 'bg-dark',
                                                'allowance_add'   => 'bg-primary',
                                                'allowance_edit'  => 'bg-primary bg-opacity-75',
                                                'allowance_delete'=> 'bg-dark',
                                                'savings_add'     => 'bg-success',
                                                'savings_edit'    => 'bg-success bg-opacity-75',
                                                'savings_delete'  => 'bg-dark',
                                                'goal_add'        => 'bg-purple',
                                                'goal_contribute' => 'bg-success',
                                                'goal_delete'     => 'bg-dark',
                                                'budget_limit_set'=> 'bg-secondary',
                                            ];
                                            $badgeClass = $actionColors[$log['action_type']] ?? 'bg-primary';
                                            ?>
                                            <span class="badge <?php echo $badgeClass; ?> rounded-pill px-3 py-1 fw-bold text-uppercase" style="font-size: 0.65rem;">
                                                <?php echo str_replace('_', ' ', $log['action_type']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="small text-dark fw-bold"><?php echo htmlspecialchars($log['ip_address']); ?></div>
                                            <div class="text-muted text-truncate" style="max-width: 150px; font-size: 0.7rem;" title="<?php echo htmlspecialchars($log['user_agent']); ?>">
                                                <?php echo htmlspecialchars($log['user_agent']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="small text-secondary fw-medium"><?php echo htmlspecialchars($log['description']); ?></div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.bg-gradient-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.pulse { animation: pulse-green 2s infinite; }
@keyframes pulse-green {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(25, 135, 84, 0.7); }
    70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(25, 135, 84, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(25, 135, 84, 0); }
}
</style>

<script>
$(document).ready(function() {
    $('#logsTable').DataTable({
        pageLength: 25,
        order: [[0, 'desc']],
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search logs...",
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>
