<?php
$pageTitle = 'Recycle Bin';
$pageHeader = 'Recycle Bin';
include '../includes/header.php';
include '../includes/db.php';

// Security Check: Superadmin only
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: " . SITE_URL . "auth/login.php");
    exit;
}
?>

<?php include '../includes/sidebar.php'; ?>

<div id="page-content-wrapper">
    <?php include '../includes/navbar.php'; ?>

    <div class="container-fluid px-4 py-4">

        <!-- Page Header (Empty - Title is in Navbar) -->
        <div class="d-flex align-items-center justify-content-end mb-4 fade-up">
            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-10 rounded-pill px-3 py-2" id="deletedCountBadge">
                <i class="fas fa-users me-1"></i> <span id="deletedCount">0</span> Deleted Users
            </span>
        </div>

        <!-- Search -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text bg-app-alt border-dim rounded-pill-start ps-3">
                        <i class="fas fa-search text-secondary"></i>
                    </span>
                    <input type="text" id="binSearch" class="form-control bg-app-alt border-dim text-main border-start-0 rounded-pill-end py-2" placeholder="Search deleted users...">
                </div>
            </div>
        </div>

        <!-- Cards Grid -->
        <div id="binCards" class="row g-4">
            <!-- Cards rendered by JS -->
        </div>

        <!-- Empty State -->
        <div id="emptyState" class="text-center py-5 d-none">
            <div class="mb-3">
                <i class="fas fa-check-circle text-success" style="font-size: 3rem; opacity: 0.5;"></i>
            </div>
            <h5 class="text-muted fw-semibold">Recycle Bin is Empty</h5>
            <p class="text-muted small">No deleted users to show. Deleted users will appear here.</p>
        </div>

        <?php include '../includes/footer.php'; ?>
    </div>
</div>

<!-- User Detail Modal -->
<div class="modal fade" id="userDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header border-0 pb-0 px-4 pt-4">
                <div class="d-flex align-items-center gap-3">
                    <div id="modalAvatar" class="rounded-circle text-white d-flex align-items-center justify-content-center shadow"
                        style="width:56px;height:56px;font-size:1.4rem;background:linear-gradient(135deg,#667eea,#764ba2);flex-shrink:0;"></div>
                    <div>
                        <h5 class="fw-bold mb-0" id="modalName">User Name</h5>
                        <small class="text-muted" id="modalUsername">@username</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 pb-2 pt-3">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="p-3 bg-app-alt rounded-3">
                            <div class="small text-muted fw-bold text-uppercase mb-1" style="letter-spacing:.5px;font-size:.65rem;">Email</div>
                            <div class="fw-semibold" id="modalEmail">—</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-app-alt rounded-3">
                            <div class="small text-muted fw-bold text-uppercase mb-1" style="letter-spacing:.5px;font-size:.65rem;">Contact</div>
                            <div class="fw-semibold" id="modalContact">—</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-app-alt rounded-3">
                            <div class="small text-muted fw-bold text-uppercase mb-1" style="letter-spacing:.5px;font-size:.65rem;">Role</div>
                            <div id="modalRole">—</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-app-alt rounded-3">
                            <div class="small text-muted fw-bold text-uppercase mb-1" style="letter-spacing:.5px;font-size:.65rem;">Registered</div>
                            <div class="fw-semibold" id="modalCreated">—</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-danger bg-opacity-10 rounded-3">
                            <div class="small text-danger fw-bold text-uppercase mb-1" style="letter-spacing:.5px;font-size:.65rem;">Deleted On</div>
                            <div class="fw-semibold text-danger" id="modalDeletedAt">—</div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-warning rounded-3 border-0 small mt-3">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Data intact:</strong> All financial data is preserved and will be accessible once the user is restored.
                </div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4 gap-2 justify-content-end">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Close
                </button>
                <button type="button" class="btn btn-success rounded-pill px-4 shadow-sm" id="btnRestore">
                    <i class="fas fa-trash-restore me-2"></i>Restore User
                </button>
                <button type="button" class="btn btn-danger rounded-pill px-4 shadow-sm" id="btnPermanentDelete">
                    <i class="fas fa-skull-crossbones me-2"></i>Permanently Delete
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    let allDeletedUsers = [];
    let currentUserId = null;

    const SITE_URL = '<?php echo SITE_URL; ?>';
    const ROLE_COLORS = {
        superadmin: 'bg-dark text-white',
        admin: 'bg-danger text-white',
        user: 'bg-primary text-white'
    };

    function formatDate(str) {
        if (!str) return '—';
        return new Date(str).toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        });
    }

    function buildCards(users) {
        const container = document.getElementById('binCards');
        const empty = document.getElementById('emptyState');
        container.innerHTML = '';

        if (!users.length) {
            empty.classList.remove('d-none');
            return;
        }
        empty.classList.add('d-none');

        users.forEach(u => {
            const initials = (u.first_name?.charAt(0) ?? '?').toUpperCase();
            const roleBadge = ROLE_COLORS[u.role] ?? 'bg-secondary text-white';
            const card = document.createElement('div');
            card.className = 'col-sm-6 col-lg-4 col-xl-3 fade-up';
            card.innerHTML = `
            <div class="card border-0 shadow-sm rounded-4 h-100 user-bin-card" style="cursor:pointer;transition:transform .2s,box-shadow .2s;" data-id="${u.id}"
                onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 24px rgba(0,0,0,.1)'"
                onmouseout="this.style.transform='';this.style.boxShadow=''">
                <div class="card-body p-4 text-center">
                    <div class="rounded-circle text-white d-flex align-items-center justify-content-center mx-auto mb-3 shadow"
                        style="width:56px;height:56px;font-size:1.3rem;background:linear-gradient(135deg,#94a3b8,#64748b);">${initials}</div>
                    <h6 class="fw-bold mb-1 text-truncate">${u.first_name} ${u.last_name}</h6>
                    <div class="small text-muted text-truncate mb-2">@${u.username}</div>
                    <span class="badge ${roleBadge} rounded-pill px-3 mb-3" style="font-size:.6rem;">${u.role.toUpperCase()}</span>
                    <div class="d-flex align-items-center justify-content-center gap-1 text-danger small">
                        <i class="fas fa-trash-alt" style="font-size:.75rem;"></i>
                        <span style="font-size:.75rem;">Deleted ${formatDate(u.deleted_at)}</span>
                    </div>
                </div>
            </div>`;
            container.appendChild(card);
        });

        document.querySelectorAll('.user-bin-card').forEach(card => {
            card.addEventListener('click', () => openModal(card.dataset.id));
        });
    }

    function openModal(userId) {
        const u = allDeletedUsers.find(x => x.id == userId);
        if (!u) return;
        currentUserId = u.id;

        const roleClass = ROLE_COLORS[u.role] ?? 'bg-secondary text-white';
        document.getElementById('modalAvatar').textContent = (u.first_name?.charAt(0) ?? '?').toUpperCase();
        document.getElementById('modalName').textContent = `${u.first_name} ${u.last_name}`;
        document.getElementById('modalUsername').textContent = '@' + u.username;
        document.getElementById('modalEmail').textContent = u.email || '—';
        document.getElementById('modalContact').textContent = u.contact_number || '—';
        document.getElementById('modalRole').innerHTML = `<span class="badge ${roleClass} rounded-pill px-3" style="font-size:.65rem;">${u.role.toUpperCase()}</span>`;
        document.getElementById('modalCreated').textContent = formatDate(u.created_at);
        document.getElementById('modalDeletedAt').textContent = formatDate(u.deleted_at);

        new bootstrap.Modal(document.getElementById('userDetailModal')).show();
    }

    function doAction(action, successMsg) {
        if (!currentUserId) return;
        $.post(SITE_URL + 'api/admin_recycle_bin.php', {
            action,
            user_id: currentUserId
        }, function(res) {
            if (res.success) {
                bootstrap.Modal.getInstance(document.getElementById('userDetailModal')).hide();
                Swal.fire({
                    icon: 'success',
                    title: 'Done!',
                    text: res.message,
                    timer: 2000,
                    showConfirmButton: false,
                    confirmButtonColor: '#6366f1'
                });
                loadBin();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: res.message,
                    confirmButtonColor: '#6366f1'
                });
            }
        }, 'json');
    }

    document.getElementById('btnRestore').addEventListener('click', () => {
        Swal.fire({
            title: 'Restore this user?',
            text: 'They will regain full access to their account and all their data.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, restore!',
            confirmButtonColor: '#22c55e',
            cancelButtonColor: '#6b7280'
        }).then(r => r.isConfirmed && doAction('restore'));
    });

    document.getElementById('btnPermanentDelete').addEventListener('click', () => {
        Swal.fire({
            title: 'Permanently Delete?',
            text: 'This cannot be undone. All user data will be erased forever.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, erase forever!',
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6b7280'
        }).then(r => r.isConfirmed && doAction('permanent_delete'));
    });

    document.getElementById('binSearch').addEventListener('input', function() {
        const q = this.value.toLowerCase();
        const filtered = allDeletedUsers.filter(u =>
            (u.first_name + ' ' + u.last_name + ' ' + u.username + ' ' + u.email).toLowerCase().includes(q)
        );
        buildCards(filtered);
    });

    function loadBin() {
        $.post(SITE_URL + 'api/admin_recycle_bin.php', {
            action: 'list'
        }, function(res) {
            if (res.success) {
                allDeletedUsers = res.data;
                document.getElementById('deletedCount').textContent = allDeletedUsers.length;
                buildCards(allDeletedUsers);
            }
        }, 'json');
    }

    $(document).ready(loadBin);
</script>