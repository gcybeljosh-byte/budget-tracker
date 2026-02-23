<?php
$pageTitle = 'Admin Dashboard';
$pageHeader = 'Admin Overview';
include '../includes/header.php';
include '../includes/db.php';

// Security Check
if (!isset($_SESSION['id']) || !in_array($_SESSION['role'], ['superadmin', 'admin'])) {
    header("Location: " . SITE_URL . "auth/login.php");
    exit;
}

// Self-Healing: If no Superadmin exists, the first Admin to log in becomes one
$saCheck = $conn->query("SELECT id FROM users WHERE role = 'superadmin' LIMIT 1");
if ($saCheck->num_rows === 0 && $_SESSION['role'] === 'admin') {
    $currentId = $_SESSION['id'];
    $conn->query("UPDATE users SET role = 'superadmin' WHERE id = $currentId");
    $_SESSION['role'] = 'superadmin';
}

// Fetch all users - Superadmins first, then Admins, then by creation date
$stmt = $conn->prepare("SELECT id, username, first_name, last_name, email, contact_number, profile_picture, created_at, role, status, plaintext_password FROM users ORDER BY (role = 'superadmin') DESC, (role = 'admin') DESC, created_at DESC");
$stmt->execute();
$result = $stmt->get_result();
$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}
$stmt->close();
?>

<?php include '../includes/sidebar.php'; ?>

<!-- Page Content -->
<div id="page-content-wrapper">

    <?php include '../includes/navbar.php'; ?>

    <div class="container-fluid px-4 py-4">

        <!-- Personalized Greeting -->
        <div class="mb-4 fade-up">
            <h4 class="fw-bold mb-1">Welcome, <span class="text-primary"><?php echo htmlspecialchars($_SESSION['first_name'] ?? 'Admin'); ?></span>!</h4>
            <p class="text-secondary small mb-0">System administrative overview for <?php echo date('F d, Y'); ?>.</p>
        </div>

        <!-- Stats Row -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card h-100 bg-gradient-primary text-white border-0 shadow-sm rounded-4">
                    <div class="card-body p-4">
                        <h5 class="card-title text-opacity-75 mb-3"><i class="fas fa-users me-2"></i>Total Users</h5>
                        <h2 class="display-5 fw-bold mb-0"><?php echo count($users); ?></h2>
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
                <style>
                    #usersTable tbody tr.main-row {
                        cursor: pointer;
                        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                    }

                    #usersTable tbody tr.main-row:hover {
                        background-color: rgba(99, 102, 241, 0.03);
                    }

                    #usersTable tbody tr.main-row.expanded {
                        background-color: rgba(99, 102, 241, 0.06);
                        border-bottom-color: transparent !important;
                    }

                    .detail-pane {
                        background-color: #fcfdfe;
                        border: none !important;
                    }

                    .collapse-wrapper {
                        overflow: hidden;
                        transition: all 0.35s ease;
                        border-top: 1px solid #f1f4f9;
                        border-bottom: 2px solid #eef2f7;
                        box-shadow: inset 0 2px 10px rgba(0, 0, 0, 0.01);
                    }

                    .info-label {
                        font-size: 0.65rem;
                        text-transform: uppercase;
                        font-weight: 700;
                        color: #94a3b8;
                        letter-spacing: 0.5px;
                        margin-bottom: 2px;
                    }

                    .info-value {
                        font-size: 0.9rem;
                        font-weight: 600;
                        color: #1e293b;
                    }

                    .expand-icon {
                        transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
                        color: #cbd5e1;
                    }

                    tr.expanded .expand-icon {
                        transform: rotate(180deg);
                        color: var(--primary);
                    }

                    .avatar-placeholder {
                        font-size: 1.2rem;
                    }

                    .user-info-text {
                        max-width: 250px;
                    }
                </style>
                <div class="table-responsive">
                    <table id="usersTable" class="table align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="border-0 py-3 ps-4 text-secondary small text-uppercase fw-bold" style="letter-spacing: 1px; width: 40%;">User Account</th>
                                <th class="border-0 py-3 text-secondary small text-uppercase fw-bold text-center" style="letter-spacing: 1px;">Role</th>
                                <th class="border-0 py-3 text-secondary small text-uppercase fw-bold text-center" style="letter-spacing: 1px;">Status</th>
                                <th class="border-0 py-3 pe-4 text-secondary small text-uppercase fw-bold text-end" style="letter-spacing: 1px; width: 40px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr class="main-row" data-user-id="<?php echo $user['id']; ?>" aria-expanded="false">
                                    <td class="ps-4 py-3">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-container position-relative me-3">
                                                <?php if (!empty($user['profile_picture']) && file_exists(ROOT_PATH . $user['profile_picture'])): ?>
                                                    <img src="<?php echo SITE_URL . htmlspecialchars($user['profile_picture']); ?>"
                                                        class="rounded-circle shadow-sm"
                                                        style="width: 45px; height: 45px; object-fit: cover; border: 2px solid #fff;">
                                                <?php else: ?>
                                                    <div class="avatar-placeholder rounded-circle text-white d-flex align-items-center justify-content-center shadow-sm"
                                                        style="width: 45px; height: 45px; font-weight: bold; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                                        <?php echo strtoupper(substr($user['first_name'], 0, 1)); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="user-info-text overflow-hidden">
                                                <div class="fw-bold text-dark text-truncate mb-0"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                                                <div class="small text-muted text-truncate"><?php echo htmlspecialchars($user['email']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center py-3" data-order="<?php if ($user['role'] === 'superadmin') echo 2;
                                                                                elseif ($user['role'] === 'admin') echo 1;
                                                                                else echo 0; ?>">
                                        <?php if ($user['role'] === 'superadmin'): ?>
                                            <span class="badge bg-dark text-white rounded-pill px-3 py-1" style="font-size: 0.65rem; background: linear-gradient(135deg, #1e293b 0%, #334155 100%);">SUPERADMIN</span>
                                        <?php elseif ($user['role'] === 'admin'): ?>
                                            <span class="badge bg-danger text-white rounded-pill px-3 py-1" style="font-size: 0.65rem;">ADMIN</span>
                                        <?php else: ?>
                                            <span class="badge bg-primary text-white rounded-pill px-3 py-1" style="font-size: 0.65rem;">USER</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center py-3">
                                        <span class="badge <?php echo $user['status'] === 'active' ? 'bg-success' : 'bg-secondary'; ?> rounded-pill px-3 py-1" style="font-size: 0.65rem;">
                                            <?php echo strtoupper($user['status']); ?>
                                        </span>
                                    </td>
                                    <td class="pe-4 text-end py-3">
                                        <i class="fas fa-chevron-down expand-icon text-muted" style="font-size: 0.8rem;"></i>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Hidden Templates for Expansion -->
        <div id="detailTemplates" class="d-none">
            <?php foreach ($users as $user): ?>
                <div id="tpl_user_<?php echo $user['id']; ?>">
                    <div class="collapse-wrapper">
                        <div class="p-4 bg-light bg-opacity-10">
                            <div class="row g-4 mb-4">
                                <?php if ($_SESSION['role'] === 'superadmin'): ?>
                                    <div class="col-md-3">
                                        <div class="info-label">Credentials</div>
                                        <div class="d-flex flex-column gap-1">
                                            <div class="d-flex align-items-center">
                                                <small class="text-muted me-2" style="width: 35px;">User:</small>
                                                <span class="info-value me-2" id="username_<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['username']); ?></span>
                                                <button class="btn btn-sm btn-link p-0 text-decoration-none" onclick="copyValue('username_<?php echo $user['id']; ?>', this)" title="Copy Username">
                                                    <i class="fas fa-copy" style="font-size: 0.8rem;"></i>
                                                </button>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <small class="text-muted me-2" style="width: 35px;">Pass:</small>
                                                <span class="info-value text-primary me-2 password-text"
                                                    id="password_<?php echo $user['id']; ?>"
                                                    data-pass="<?php echo htmlspecialchars($user['plaintext_password'] ?: ''); ?>">••••••••</span>
                                                <button class="btn btn-sm btn-link p-0 text-decoration-none me-2 toggle-pass-btn" title="Toggle Password">
                                                    <i class="fas fa-eye" style="font-size: 0.8rem;"></i>
                                                </button>
                                                <button class="btn btn-sm btn-link p-0 text-decoration-none" onclick="copyPassword('password_<?php echo $user['id']; ?>', this)" title="Copy Password">
                                                    <i class="fas fa-copy" style="font-size: 0.8rem;"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <div class="col-md-3 overflow-hidden">
                                    <div class="info-label">Full Name</div>
                                    <div class="info-value text-truncate"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                                </div>
                                <div class="col-md-3">
                                    <div class="info-label">Contact Number</div>
                                    <div class="info-value"><?php echo htmlspecialchars($user['contact_number'] ?: 'N/A'); ?></div>
                                </div>
                                <div class="col-md-3 text-md-end">
                                    <div class="info-label">Member Since</div>
                                    <div class="info-value"><?php echo date('F d, Y', strtotime($user['created_at'])); ?></div>
                                </div>
                            </div>

                            <div class="d-flex flex-wrap align-items-center justify-content-between p-3 bg-white rounded-3 shadow-sm border border-light gap-3">
                                <div class="d-flex align-items-center">
                                    <div class="form-check form-switch p-0 d-flex align-items-center gap-2">
                                        <span class="info-label mb-0 me-2" style="white-space: nowrap;">Account Status:</span>
                                        <input class="form-check-input ms-0 status-toggle" type="checkbox"
                                            data-user-id="<?php echo $user['id']; ?>"
                                            <?php echo $user['status'] === 'active' ? 'checked' : ''; ?>
                                            <?php echo $user['id'] == $_SESSION['id'] ? 'disabled' : ''; ?>>
                                    </div>
                                </div>
                                <div class="d-flex gap-2 ms-auto">
                                    <?php if ($_SESSION['role'] === 'superadmin' || ($user['role'] !== 'superadmin' && $user['role'] !== 'admin')): ?>
                                        <button class="btn btn-primary btn-sm px-4 rounded-pill edit-user-btn"
                                            style="background-color: #0d6efd; border-color: #0d6efd;"
                                            data-id="<?php echo $user['id']; ?>"
                                            data-firstname="<?php echo htmlspecialchars($user['first_name']); ?>"
                                            data-lastname="<?php echo htmlspecialchars($user['last_name']); ?>"
                                            data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                            data-username="<?php echo $_SESSION['role'] === 'superadmin' ? htmlspecialchars($user['username']) : ''; ?>"
                                            data-password="<?php echo $_SESSION['role'] === 'superadmin' ? htmlspecialchars($user['plaintext_password']) : ''; ?>"
                                            data-role="<?php echo $user['role']; ?>">
                                            <i class="fas fa-user-edit me-2"></i>Edit
                                        </button>
                                    <?php else: ?>
                                        <div class="d-flex align-items-center">
                                            <span class="text-muted small" style="font-size: 0.72rem; letter-spacing: 0.3px;">
                                                <i class="fas fa-lock me-1 opacity-50"></i>Contact Super Administrator for More Access
                                            </span>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($_SESSION['role'] === 'superadmin' && $user['id'] != $_SESSION['id']): ?>
                                        <button class="btn btn-danger btn-sm px-4 rounded-pill delete-user-btn"
                                            style="background-color: #dc3545; border-color: #dc3545;"
                                            data-id="<?php echo $user['id']; ?>">
                                            <i class="fas fa-trash-alt me-2"></i>Delete
                                        </button>
                                    <?php elseif ($user['id'] == $_SESSION['id']): ?>
                                        <button class="btn btn-danger btn-sm px-4 rounded-pill disabled opacity-50"
                                            style="background-color: #dc3545; border-color: #dc3545;"
                                            title="You cannot delete yourself">
                                            <i class="fas fa-trash-alt me-2"></i>Delete
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
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
                            <?php if ($_SESSION['role'] === 'superadmin'): ?>
                                <div class="row g-3 mb-3">
                                    <div class="col-6">
                                        <label class="form-label small fw-bold text-secondary text-uppercase">Username</label>
                                        <input type="text" class="form-control rounded-3 bg-light" id="editUsername" name="username" readonly>
                                        <small class="text-muted" style="font-size: 0.6rem;">Username cannot be changed.</small>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-bold text-secondary text-uppercase">Password</label>
                                        <input type="text" class="form-control rounded-3" id="editPassword" name="password" required>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-secondary text-uppercase">Email Address</label>
                                <input type="email" class="form-control rounded-3" id="editEmail" name="email" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label small fw-bold text-secondary text-uppercase">Role</label>
                                <select class="form-select rounded-3" id="editRole" name="role" <?php echo $_SESSION['role'] !== 'superadmin' ? 'disabled' : ''; ?>>
                                    <option value="user">User</option>
                                    <option value="admin">Admin</option>
                                    <option value="superadmin">Superadmin</option>
                                </select>
                                <?php if ($_SESSION['role'] !== 'superadmin'): ?>
                                    <small class="text-muted mt-1 d-block"><i class="fas fa-lock me-1"></i> Role assignment is restricted to Superadmins.</small>
                                <?php endif; ?>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary rounded-pill py-2 fw-bold shadow-sm">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <?php include '../includes/footer.php'; ?>

        <script>
            $(document).ready(function() {
                const table = $('#usersTable').DataTable({
                    responsive: true,
                    order: [
                        [1, 'desc']
                    ], // Default order by role (admin first)
                    dom: "<'row'<'col-sm-12'tr>><'row pagination-container'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
                    language: {
                        search: ""
                    },
                    columnDefs: [{
                            orderable: false,
                            targets: [3]
                        },
                        {
                            searchable: true,
                            targets: [0]
                        },
                        {
                            searchable: false,
                            targets: [1, 2, 3]
                        }
                    ]
                });

                // --- Expansion Logic (Child Rows) ---
                $('#usersTable tbody').on('click', 'tr.main-row', function() {
                    const tr = $(this);
                    const row = table.row(tr);
                    const userId = tr.data('user-id');
                    const detailContent = $('#tpl_user_' + userId).html();

                    if (row.child.isShown()) {
                        tr.find('.collapse-wrapper').slideUp(350, function() {
                            row.child.hide();
                            tr.removeClass('expanded shadow-sm');
                        });
                    } else {
                        // Close any other open rows
                        table.rows().every(function() {
                            if (this.child.isShown()) {
                                const otherTr = $(this.node());
                                otherTr.find('.collapse-wrapper').slideUp(200);
                                this.child.hide();
                                otherTr.removeClass('expanded shadow-sm');
                            }
                        });

                        row.child(detailContent, 'detail-pane-child').show();
                        tr.addClass('expanded shadow-sm');

                        const childRow = tr.next();
                        childRow.find('.collapse-wrapper').hide().slideDown(350);
                    }
                });

                // Delegate actions for child row content
                $('#usersTable').on('click', '.toggle-pass-btn', function(e) {
                    e.stopPropagation();
                    const btn = $(this);
                    const icon = btn.find('i');
                    const passSpan = btn.siblings('.password-text');
                    const plaintext = passSpan.data('pass');

                    if (icon.hasClass('fa-eye')) {
                        passSpan.text(plaintext);
                        icon.removeClass('fa-eye').addClass('fa-eye-slash');
                    } else {
                        passSpan.text('••••••••');
                        icon.removeClass('fa-eye-slash').addClass('fa-eye');
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
                        url: '<?php echo SITE_URL; ?>api/admin_user_status.php',
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
                                    showConfirmButton: false,
                                    confirmButtonColor: '#6366f1'
                                });
                            } else {
                                checkbox.prop('checked', !isActive);
                                Swal.fire({
                                    title: 'Error',
                                    text: response.message,
                                    icon: 'error',
                                    confirmButtonColor: '#6366f1'
                                });
                            }
                            checkbox.prop('disabled', false);
                        },
                        error: function() {
                            checkbox.prop('checked', !isActive);
                            checkbox.prop('disabled', false);
                            Swal.fire({
                                title: 'Error',
                                text: 'Server communication failure',
                                icon: 'error',
                                confirmButtonColor: '#6366f1'
                            });
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
                    $('#editUsername').val(data.username);
                    $('#editPassword').val(data.password);
                    $('#editRole').val(data.role);

                    new bootstrap.Modal(document.getElementById('editUserModal')).show();
                });

                $('#editUserForm').on('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    formData.append('action', 'update');

                    $.ajax({
                        url: '<?php echo SITE_URL; ?>api/admin_users.php',
                        method: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    title: 'Success',
                                    text: response.message,
                                    icon: 'success',
                                    confirmButtonColor: '#6366f1'
                                }).then(() => location.reload());
                            } else {
                                Swal.fire({
                                    title: 'Error',
                                    text: response.message,
                                    icon: 'error',
                                    confirmButtonColor: '#6366f1'
                                });
                            }
                        },
                        error: function() {
                            Swal.fire({
                                title: 'Error',
                                text: 'Server error',
                                icon: 'error',
                                confirmButtonColor: '#6366f1'
                            });
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
                        confirmButtonText: 'Yes, delete it!',
                        confirmButtonColor: '#6366f1',
                        cancelButtonColor: '#d33'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.post('<?php echo SITE_URL; ?>api/admin_users.php', {
                                action: 'delete',
                                user_id: id
                            }, function(response) {
                                if (response.success) {
                                    Swal.fire({
                                        title: 'Deleted!',
                                        text: response.message,
                                        icon: 'success',
                                        confirmButtonColor: '#6366f1'
                                    }).then(() => location.reload());
                                } else {
                                    Swal.fire({
                                        title: 'Error',
                                        text: response.message,
                                        icon: 'error',
                                        confirmButtonColor: '#6366f1'
                                    });
                                }
                            }, 'json');
                        }
                    });
                });

            });

            function copyValue(id, btn) {
                const text = document.getElementById(id).textContent;
                navigator.clipboard.writeText(text);

                const originalIcon = btn.innerHTML;
                btn.innerHTML = '<span class="text-success fw-bold" style="font-size: 0.65rem;">COPIED!</span>';
                setTimeout(() => {
                    btn.innerHTML = originalIcon;
                }, 2000);
            }

            function copyPassword(id, btn) {
                const span = document.getElementById(id);
                const text = span.getAttribute('data-pass');
                navigator.clipboard.writeText(text);

                const originalIcon = btn.innerHTML;
                btn.innerHTML = '<span class="text-success fw-bold" style="font-size: 0.65rem;">COPIED!</span>';
                setTimeout(() => {
                    btn.innerHTML = originalIcon;
                }, 2000);
            }
        </script>