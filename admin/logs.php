<?php
$pageTitle = 'Activity Logs';
$pageHeader = 'System Audit Logs';
include '../includes/header.php';

// Security Check: Superadmin Only
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: " . SITE_URL . "auth/login.php");
    exit;
}

// Fetch Users with Log Counts
$usersStmt = $conn->query("
    SELECT u.id, u.username, u.first_name, u.last_name, u.last_activity, u.role,
           (SELECT COUNT(*) FROM activity_logs WHERE user_id = u.id) as log_count
    FROM users u 
    ORDER BY last_activity DESC
");
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
                            foreach ($allUsers as $u) {
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
                        <?php foreach ($allUsers as $u):
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

            <!-- Users List Table -->
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-users-cog me-2 text-primary"></i>Manage User Activity Logs</h5>
                        <button onclick="window.location.reload()" class="btn btn-sm btn-light rounded-pill px-3 fw-bold border shadow-sm">
                            <i class="fas fa-sync-alt me-1"></i> Refresh
                        </button>
                    </div>
                    <div class="table-responsive p-0">
                        <table class="table table-hover mb-0 align-middle" id="usersTable">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4 py-3 text-secondary small text-uppercase fw-bold">User</th>
                                    <th class="py-3 text-secondary small text-uppercase fw-bold">Role</th>
                                    <th class="py-3 text-secondary small text-uppercase fw-bold text-center">Log Count</th>
                                    <th class="py-3 text-secondary small text-uppercase fw-bold">Last Activity</th>
                                    <th class="py-3 text-secondary small text-uppercase fw-bold text-end pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($allUsers as $u): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($u['first_name'] . ' ' . $u['last_name']); ?></div>
                                            <div class="small text-muted">@<?php echo htmlspecialchars($u['username']); ?></div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border rounded-pill px-3"><?php echo strtoupper($u['role']); ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary rounded-pill"><?php echo $u['log_count']; ?></span>
                                        </td>
                                        <td>
                                            <div class="small text-dark fw-bold"><?php echo $u['last_activity'] ? date('M d, Y H:i', strtotime($u['last_activity'])) : 'Never'; ?></div>
                                        </td>
                                        <td class="text-end pe-4">
                                            <button class="btn btn-sm btn-primary rounded-pill px-3 view-logs" data-id="<?php echo $u['id']; ?>" data-name="<?php echo htmlspecialchars($u['first_name']); ?>">
                                                <i class="fas fa-eye me-1"></i> View Logs
                                            </button>
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

<!-- User Logs Modal -->
<div class="modal fade" id="userLogsModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-light border-0 p-4">
                <div>
                    <h5 class="modal-title fw-bold mb-0">Activity History: <span id="modalUserName" class="text-primary"></span></h5>
                    <p class="small text-muted mb-0">Detailed audit trail for this user</p>
                </div>
                <div class="d-flex gap-2">
                    <button id="deleteAllLogs" class="btn btn-danger btn-sm rounded-pill px-3 fw-bold shadow-sm">
                        <i class="fas fa-trash-alt me-1"></i> Delete All Logs
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="userLogsTable">
                        <thead class="bg-light sticky-top">
                            <tr>
                                <th class="ps-4 py-3 text-secondary small text-uppercase">Timestamp</th>
                                <th class="py-3 text-secondary small text-uppercase">Action</th>
                                <th class="py-3 text-secondary small text-uppercase">IP & Device</th>
                                <th class="py-3 text-secondary small text-uppercase pe-4">Details</th>
                            </tr>
                        </thead>
                        <tbody id="userLogsBody">
                            <!-- Populated via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-gradient-success {
        background: linear-gradient(135deg, #28a745 0%, #198754 100%);
    }

    .pulse {
        animation: pulse-green 2s infinite;
    }

    @keyframes pulse-green {
        0% {
            transform: scale(0.95);
            box-shadow: 0 0 0 0 rgba(25, 135, 84, 0.7);
        }

        70% {
            transform: scale(1);
            box-shadow: 0 0 0 10px rgba(25, 135, 84, 0);
        }

        100% {
            transform: scale(0.95);
            box-shadow: 0 0 0 0 rgba(25, 135, 84, 0);
        }
    }

    .sticky-top {
        z-index: 1020;
    }
</style>

<script>
    $(document).ready(function() {
        $('#usersTable').DataTable({
            pageLength: 25,
            order: [
                [3, 'desc']
            ],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Find a user..."
            }
        });

        let currentUserModalId = null;

        $('.view-logs').on('click', function() {
            const userId = $(this).data('id');
            const userName = $(this).data('name');
            currentUserModalId = userId;

            $('#modalUserName').text(userName);
            fetchUserLogs(userId);
            $('#userLogsModal').modal('show');
        });

        function fetchUserLogs(userId) {
            $('#userLogsBody').html('<tr><td colspan="4" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>');

            fetch(`<?php echo SITE_URL; ?>api/admin_logs.php?action=fetch_user_logs&user_id=${userId}`)
                .then(res => res.json())
                .then(result => {
                    if (result.success) {
                        let html = '';
                        if (result.data.length === 0) {
                            html = '<tr><td colspan="4" class="text-center py-4 text-muted">No logs found for this user.</td></tr>';
                        } else {
                            result.data.forEach(log => {
                                const date = new Date(log.created_at);
                                const dateStr = date.toLocaleDateString('en-US', {
                                    month: 'short',
                                    day: 'numeric',
                                    year: 'numeric'
                                });
                                const timeStr = date.toLocaleTimeString('en-US', {
                                    hour12: false
                                });

                                html += `
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark small">${dateStr}</div>
                                        <div class="text-muted" style="font-size: 0.75rem;">${timeStr}</div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border rounded-pill px-3 py-1 fw-bold text-uppercase" style="font-size: 0.65rem;">
                                            ${log.action_type.replace(/_/g, ' ')}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="small text-dark fw-bold">${log.ip_address}</div>
                                        <div class="text-muted text-truncate" style="max-width: 150px; font-size: 0.7rem;" title="${log.user_agent}">
                                            ${log.user_agent}
                                        </div>
                                    </td>
                                    <td class="pe-4">
                                        <div class="small text-secondary fw-medium">${log.description}</div>
                                    </td>
                                </tr>
                            `;
                            });
                        }
                        $('#userLogsBody').html(html);
                    }
                });
        }

        $('#deleteAllLogs').on('click', function() {
            if (!currentUserModalId) return;

            Swal.fire({
                title: 'Delete All Logs?',
                text: "This action cannot be undone for this user.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Yes, delete everything'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('action', 'delete_user_logs');
                    formData.append('user_id', currentUserModalId);

                    fetch('<?php echo SITE_URL; ?>api/admin_logs.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(res => res.json())
                        .then(result => {
                            if (result.success) {
                                Swal.fire('Deleted!', result.message, 'success');
                                $('#userLogsModal').modal('hide');
                                window.location.reload(); // Refresh to update counts
                            } else {
                                Swal.fire('Error', result.message, 'error');
                            }
                        });
                }
            });
        });
    });
</script>

<?php include '../includes/footer.php'; ?>