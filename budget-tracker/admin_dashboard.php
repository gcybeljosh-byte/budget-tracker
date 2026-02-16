<?php
$pageTitle = 'Admin Dashboard';
$pageHeader = 'Admin Overview';
include 'includes/header.php';
include 'includes/db.php';

// Security Check
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Fetch all users
$stmt = $conn->prepare("SELECT id, username, first_name, last_name, email, contact_number, created_at, role, status FROM users ORDER BY created_at DESC");
$stmt->execute();
$result = $stmt->get_result();
$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}
$stmt->close();
?>

    <?php include 'includes/sidebar.php'; ?>

    <!-- Page Content -->
    <div id="page-content-wrapper">

        <?php include 'includes/navbar.php'; ?>

        <div class="container-fluid px-4 py-4">

            <!-- Stats Row -->
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden" style="background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);">
                        <div class="card-body p-4 position-relative">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h6 class="text-white text-uppercase fw-bold mb-0" style="letter-spacing: 1px; font-size: 0.85rem; opacity: 0.9;">Total Users</h6>
                                </div>
                                <div class="bg-white bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                    <i class="fas fa-users text-white fa-lg"></i>
                                </div>
                            </div>
                            <h2 class="display-4 fw-bold text-white mb-0"><?php echo count($users); ?></h2>
                            <div class="mt-2">
                                <span class="badge bg-white bg-opacity-25 text-white fw-normal px-2 py-1" style="font-size: 0.75rem;">
                                    <i class="fas fa-chart-line me-1"></i> Active Platform Users
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Users Search -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 rounded-pill-start ps-3">
                            <i class="fas fa-search text-secondary"></i>
                        </span>
                        <input type="text" id="userSearch" class="form-control border-start-0 rounded-pill-end py-2" placeholder="Search Users...">
                    </div>
                </div>
            </div>

            <!-- Users Table -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white py-4 px-4 border-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-primary fw-bold" style="letter-spacing: -0.5px;">Registered Users</h5>
                    <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-2">
                        <i class="fas fa-users me-1"></i> <?php echo count($users); ?> Members
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table id="usersTable" class="table align-middle mb-0 table-hover">
                            <thead class="bg-light">
                                <tr>
                                    <th class="border-0 py-3 ps-4 text-secondary small text-uppercase fw-bold" style="letter-spacing: 1px;">User</th>
                                    <th class="border-0 py-3 text-secondary small text-uppercase fw-bold" style="letter-spacing: 1px;">Contact</th>
                                    <th class="border-0 py-3 text-secondary small text-uppercase fw-bold" style="letter-spacing: 1px;">Role</th>
                                    <th class="border-0 py-3 text-secondary small text-uppercase fw-bold" style="letter-spacing: 1px;">Status</th>
                                    <th class="border-0 py-3 text-secondary small text-uppercase fw-bold" style="letter-spacing: 1px;">Joined</th>
                                    <th class="border-0 py-3 pe-4 text-secondary small text-uppercase fw-bold text-end" style="letter-spacing: 1px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr style="transition: all 0.2s;">
                                    <td class="ps-4 py-3">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-placeholder rounded-circle text-white d-flex align-items-center justify-content-center me-3 shadow-sm"
                                                 style="width: 45px; height: 45px; font-weight: bold; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                                <?php echo strtoupper(substr($user['first_name'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark mb-0"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                                                <div class="small text-muted">@<?php echo htmlspecialchars($user['username']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3">
                                        <div class="d-flex flex-column">
                                            <span class="text-dark small mb-1"><i class="fas fa-envelope text-muted me-2"></i><?php echo htmlspecialchars($user['email']); ?></span>
                                            <span class="text-muted small"><i class="fas fa-phone text-muted me-2"></i><?php echo htmlspecialchars($user['contact_number']); ?></span>
                                        </div>
                                    </td>
                                    <td class="py-3">
                                        <?php if($user['role'] === 'admin'): ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3 py-2">
                                                <i class="fas fa-shield-alt me-1"></i> Admin
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2">
                                                <i class="fas fa-user me-1"></i> User
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3">
                                        <div class="form-check form-switch p-0 d-flex align-items-center gap-2">
                                            <input class="form-check-input ms-0 status-toggle" type="checkbox" 
                                                   data-user-id="<?php echo $user['id']; ?>"
                                                   <?php echo $user['status'] === 'active' ? 'checked' : ''; ?>
                                                   <?php echo $user['id'] == $_SESSION['id'] ? 'disabled' : ''; ?>>
                                            <span class="badge <?php echo $user['status'] === 'active' ? 'bg-success' : 'bg-secondary'; ?> status-label" style="font-size: 0.65rem;">
                                                <?php echo ucfirst($user['status']); ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="text-secondary small py-3">
                                        <i class="far fa-calendar-alt me-2"></i><?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                                    </td>
                                    <td class="pe-4 text-end py-3">
                                        <button class="btn btn-light text-primary hover-primary rounded-circle shadow-sm me-1 edit-user-btn" 
                                            style="width: 32px; height: 32px; padding: 0;" title="Edit User"
                                            data-id="<?php echo $user['id']; ?>"
                                            data-firstname="<?php echo htmlspecialchars($user['first_name']); ?>"
                                            data-lastname="<?php echo htmlspecialchars($user['last_name']); ?>"
                                            data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                            data-role="<?php echo $user['role']; ?>">
                                            <i class="fas fa-pen" style="font-size: 0.8rem;"></i>
                                        </button>
                                        <?php if($user['id'] != $_SESSION['id']): ?>
                                        <button class="btn btn-light text-danger hover-danger rounded-circle shadow-sm delete-user-btn" 
                                            style="width: 32px; height: 32px; padding: 0;" title="Delete User"
                                            data-id="<?php echo $user['id']; ?>">
                                            <i class="fas fa-trash" style="font-size: 0.8rem;"></i>
                                        </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>


    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content border-0 rounded-4 shadow-lg">
                <div class="modal-header border-bottom-0 p-4 pb-0">
                    <h5 class="modal-title fw-bold">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 pt-4">
                    <form id="editUserForm">
                        <input type="hidden" id="editUserId" name="user_id">
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-bold text-secondary text-uppercase">First Name</label>
                                <input type="text" class="form-control rounded-3" id="editFirstName" name="first_name" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold text-secondary text-uppercase">Last Name</label>
                                <input type="text" class="form-control rounded-3" id="editLastName" name="last_name" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Email Address</label>
                            <input type="email" class="form-control rounded-3" id="editEmail" name="email" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Role</label>
                            <select class="form-select rounded-3" id="editRole" name="role">
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary rounded-pill py-2 fw-bold shadow-sm">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

<?php include 'includes/footer.php'; ?>

<script>
    $(document).ready(function() {
        const table = $('#usersTable').DataTable({
            responsive: true,
            order: [[4, 'desc']], // Sort by Joined Date
            dom: "<'row'<'col-sm-12'tr>><'row pagination-container'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            language: {
                search: ""
            }
        });

        // Custom Search Logic
        $('#userSearch').on('keyup', function() {
            table.search(this.value).draw();
        });

        // Handle Status Toggle (Delegated)
        $('#usersTable').on('change', '.status-toggle', function() {
            const checkbox = $(this);
            const userId = checkbox.data('user-id');
            const isActive = checkbox.is(':checked');
            const statusLabel = checkbox.siblings('.status-label');
            
            checkbox.prop('disabled', true);
            
            $.ajax({
                url: 'api/admin_user_status.php',
                method: 'POST',
                data: {
                    user_id: userId,
                    status: isActive ? 'active' : 'inactive'
                },
                success: function(response) {
                    if (response.success) {
                        statusLabel.text(isActive ? 'Active' : 'Inactive');
                        statusLabel.removeClass('bg-success bg-secondary').addClass(isActive ? 'bg-success' : 'bg-secondary');
                        Swal.fire({
                            icon: 'success',
                            title: 'Updated!',
                            text: response.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                    } else {
                        checkbox.prop('checked', !isActive);
                        Swal.fire('Error', response.message, 'error');
                    }
                    checkbox.prop('disabled', false);
                },
                error: function() {
                    checkbox.prop('checked', !isActive);
                    checkbox.prop('disabled', false);
                    Swal.fire('Error', 'Server communication failure', 'error');
                }
            });
        });

        // --- Edit User Logic ---
        $('#usersTable').on('click', '.edit-user-btn', function() {
            const btn = $(this);
            const data = btn.data();
            
            $('#editUserId').val(data.id);
            $('#editFirstName').val(data.firstname);
            $('#editLastName').val(data.lastname);
            $('#editEmail').val(data.email);
            $('#editRole').val(data.role);
            
            new bootstrap.Modal(document.getElementById('editUserModal')).show();
        });

        $('#editUserForm').on('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'update');

            $.ajax({
                url: 'api/admin_users.php',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Success', response.message, 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Server error', 'error');
                }
            });
        });

        // --- Delete User Logic ---
        $('#usersTable').on('click', '.delete-user-btn', function() {
            const id = $(this).data('id');
            
            Swal.fire({
                title: 'Delete User?',
                text: "This action cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('api/admin_users.php', { action: 'delete', user_id: id }, function(response) {
                        if (response.success) {
                            Swal.fire('Deleted!', response.message, 'success').then(() => location.reload());
                        } else {
                            Swal.fire('Error', response.message, 'error');
                        }
                    }, 'json');
                }
            });
        });
    });
</script>
