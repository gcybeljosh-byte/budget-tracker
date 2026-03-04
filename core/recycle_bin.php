<?php
$pageTitle = 'Recycle Bin';
$pageHeader = 'Recycle Bin';
include '../includes/header.php';

if (!isset($_SESSION['id'])) {
    header("Location: " . SITE_URL . "auth/login.php");
    exit;
}
?>

<?php include '../includes/sidebar.php'; ?>

<div id="page-content-wrapper">
    <?php include '../includes/navbar.php'; ?>

    <div class="container-fluid px-4 py-4">

        <!-- Page Header -->
        <div class="d-flex align-items-start justify-content-between mb-4 fade-up flex-wrap gap-3">
            <div>
                <h4 class="fw-bold mb-1"><i class="fas fa-trash-restore me-2 text-danger"></i>Recycle Bin</h4>
                <p class="text-secondary small mb-0">Review, restore, or permanently delete your soft-deleted records.</p>
            </div>
        </div>

        <!-- Filter Tabs -->
        <ul class="nav nav-pills gap-2 mb-4 flex-wrap" id="binTabs">
            <li class="nav-item">
                <button class="nav-link active rounded-pill px-4" data-filter="all">
                    <i class="fas fa-layer-group me-1"></i> All
                    <span class="badge bg-white text-danger ms-1 rounded-pill" id="cnt-all">0</span>
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link rounded-pill px-4" data-filter="expenses">
                    <i class="fas fa-receipt me-1"></i> Expenses
                    <span class="badge bg-white text-dark ms-1 rounded-pill" id="cnt-expenses">0</span>
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link rounded-pill px-4" data-filter="allowances">
                    <i class="fas fa-hand-holding-dollar me-1"></i> Allowances
                    <span class="badge bg-white text-dark ms-1 rounded-pill" id="cnt-allowances">0</span>
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link rounded-pill px-4" data-filter="savings">
                    <i class="fas fa-piggy-bank me-1"></i> Savings
                    <span class="badge bg-white text-dark ms-1 rounded-pill" id="cnt-savings">0</span>
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link rounded-pill px-4" data-filter="bills">
                    <i class="fas fa-file-invoice-dollar me-1"></i> Bills
                    <span class="badge bg-white text-dark ms-1 rounded-pill" id="cnt-bills">0</span>
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link rounded-pill px-4" data-filter="goals">
                    <i class="fas fa-bullseye me-1"></i> Goals
                    <span class="badge bg-white text-dark ms-1 rounded-pill" id="cnt-goals">0</span>
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link rounded-pill px-4" data-filter="journals">
                    <i class="fas fa-book-open me-1"></i> Journals
                    <span class="badge bg-white text-dark ms-1 rounded-pill" id="cnt-journals">0</span>
                </button>
            </li>
        </ul>

        <!-- Search -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text bg-app-alt border-dim rounded-pill-start ps-3">
                        <i class="fas fa-search text-secondary"></i>
                    </span>
                    <input type="text" id="binSearch" class="form-control bg-app-alt border-dim text-main border-start-0 rounded-pill-end py-2" placeholder="Search deleted records...">
                </div>
            </div>
        </div>

        <!-- Cards Grid -->
        <div id="binCards" class="row g-3"></div>

        <!-- Empty State -->
        <div id="emptyState" class="text-center py-5 d-none">
            <i class="fas fa-check-circle text-success mb-3" style="font-size: 3rem; opacity: 0.5;"></i>
            <h5 class="text-muted fw-semibold">Recycle Bin is Empty</h5>
            <p class="text-muted small">All your records are safe. Deleted records will appear here.</p>
        </div>

        <?php include '../includes/footer.php'; ?>
    </div>
</div>

<!-- Detail / Action Modal -->
<div class="modal fade" id="recordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header border-0 px-4 pt-4 pb-2">
                <div>
                    <h5 class="fw-bold mb-0" id="modalTitle">Record Details</h5>
                    <small class="text-muted" id="modalType">—</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 pb-2">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="p-3 bg-app-alt rounded-3">
                            <div class="small text-muted fw-bold text-uppercase mb-1" style="font-size:.65rem;letter-spacing:.5px;">Amount</div>
                            <div class="fw-bold fs-5 text-primary" id="modalAmount">—</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 bg-app-alt rounded-3">
                            <div class="small text-muted fw-bold text-uppercase mb-1" style="font-size:.65rem;letter-spacing:.5px;">Date</div>
                            <div class="fw-semibold" id="modalDate">—</div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="p-3 bg-danger bg-opacity-10 rounded-3">
                            <div class="small text-danger fw-bold text-uppercase mb-1" style="font-size:.65rem;letter-spacing:.5px;">Deleted On</div>
                            <div class="fw-semibold text-danger" id="modalDeletedAt">—</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4 gap-2">
                <button class="btn btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal"><i class="fas fa-times me-1"></i>Close</button>
                <button class="btn btn-success rounded-pill px-4 shadow-sm" id="btnRestore"><i class="fas fa-trash-restore me-2"></i>Restore</button>
                <button class="btn btn-danger rounded-pill px-4 shadow-sm" id="btnPermDelete"><i class="fas fa-times-circle me-2"></i>Delete Forever</button>
            </div>
        </div>
    </div>
</div>

<style>
    .bin-card {
        cursor: pointer;
        transition: transform .2s, box-shadow .2s;
    }

    .bin-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, .1) !important;
    }

    .type-badge-expenses {
        background: rgba(239, 68, 68, .1);
        color: #ef4444;
    }

    .type-badge-allowances {
        background: rgba(16, 185, 129, .1);
        color: #10b981;
    }

    .type-badge-savings {
        background: rgba(99, 102, 241, .1);
        color: #6366f1;
    }

    .type-badge-bills {
        background: rgba(245, 158, 11, .1);
        color: #f59e0b;
    }

    .type-badge-goals {
        background: rgba(236, 72, 153, .1);
        color: #ec4899;
    }

    .type-badge-journals {
        background: rgba(100, 116, 139, .1);
        color: #64748b;
    }

    .type-icon-expenses {
        color: #ef4444;
    }

    .type-icon-allowances {
        color: #10b981;
    }

    .type-icon-savings {
        color: #6366f1;
    }

    .type-icon-bills {
        color: #f59e0b;
    }

    .type-icon-goals {
        color: #ec4899;
    }

    .type-icon-journals {
        color: #64748b;
    }

    .nav-pills .nav-link.active {
        background: var(--bs-danger);
    }
</style>

<script>
    const SITE_URL = '<?php echo SITE_URL; ?>';
    const CURRENCY = '<?php echo addslashes($_SESSION['user_currency'] ?? 'PHP'); ?>';

    let allRecords = [];
    let currentRecord = null;
    let activeFilter = 'all';

    const TYPE_ICONS = {
        expenses: 'fa-receipt',
        allowances: 'fa-hand-holding-dollar',
        savings: 'fa-piggy-bank',
        bills: 'fa-file-invoice-dollar',
        goals: 'fa-bullseye',
        journals: 'fa-book-open'
    };
    const TYPE_LABELS = {
        expenses: 'Expense',
        allowances: 'Allowance',
        savings: 'Savings',
        bills: 'Bill',
        goals: 'Goal',
        journals: 'Journal'
    };

    function fmt(n) {
        return parseFloat(n || 0).toLocaleString('en-PH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function fmtDate(str) {
        if (!str) return '—';
        return new Date(str).toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        });
    }

    function updateCounts(records) {
        const types = ['expenses', 'allowances', 'savings', 'bills', 'goals', 'journals'];
        document.getElementById('cnt-all').textContent = records.length;
        types.forEach(t => {
            const cntId = document.getElementById('cnt-' + t);
            if (cntId) {
                cntId.textContent = records.filter(r => r.type === t).length;
            }
        });
    }

    function buildCards(records) {
        const container = document.getElementById('binCards');
        const empty = document.getElementById('emptyState');
        container.innerHTML = '';

        if (!records.length) {
            empty.classList.remove('d-none');
            return;
        }
        empty.classList.add('d-none');

        records.forEach(r => {
            const icon = TYPE_ICONS[r.type] ?? 'fa-folder';
            const colEl = document.createElement('div');
            colEl.className = 'col-sm-6 col-lg-4 col-xl-3 fade-up';
            colEl.innerHTML = `
            <div class="card border-0 shadow-sm rounded-4 h-100 bin-card" data-id="${r.id}" data-type="${r.type}">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center type-badge-${r.type}" style="width:42px;height:42px;flex-shrink:0;">
                            <i class="fas ${icon} type-icon-${r.type}" style="font-size:.95rem;"></i>
                        </div>
                        <div class="overflow-hidden">
                            <div class="fw-bold text-truncate">${r.label || '—'}</div>
                            <span class="badge rounded-pill px-2 py-1 type-badge-${r.type}" style="font-size:.6rem;">${TYPE_LABELS[r.type]}</span>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-end">
                        <div>
                            <div class="small text-muted" style="font-size:.7rem;">Amount</div>
                            <div class="fw-bold text-primary">${r.type === 'journals' ? '—' : fmt(r.amount)}</div>
                        </div>
                        <div class="text-end">
                            <div class="small text-danger" style="font-size:.65rem;">
                                <i class="fas fa-trash-alt me-1"></i>${fmtDate(r.deleted_at)}
                            </div>
                        </div>
                    </div>
                </div>
            </div>`;
            container.appendChild(colEl);
        });

        document.querySelectorAll('.bin-card').forEach(c => {
            c.addEventListener('click', () => openModal(c.dataset.id, c.dataset.type));
        });
    }

    function openModal(id, type) {
        const r = allRecords.find(x => x.id == id && x.type === type);
        if (!r) return;
        currentRecord = r;

        document.getElementById('modalTitle').textContent = r.label || '—';
        document.getElementById('modalType').textContent = TYPE_LABELS[r.type] + ' Record';
        document.getElementById('modalAmount').textContent = (r.type === 'journals') ? '—' : fmt(r.amount);
        document.getElementById('modalDate').textContent = fmtDate(r.record_date);
        document.getElementById('modalDeletedAt').textContent = fmtDate(r.deleted_at);

        new bootstrap.Modal(document.getElementById('recordModal')).show();
    }

    function doAction(action) {
        if (!currentRecord) return;
        $.post(SITE_URL + 'api/user_recycle_bin.php', {
            action,
            type: currentRecord.type,
            id: currentRecord.id
        }, function(res) {
            if (res.success) {
                bootstrap.Modal.getInstance(document.getElementById('recordModal')).hide();
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
            title: 'Restore this record?',
            text: 'This record will be moved back to its original section.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Restore',
            confirmButtonColor: '#22c55e',
            cancelButtonColor: '#6b7280'
        }).then(r => r.isConfirmed && doAction('restore'));
    });

    document.getElementById('btnPermDelete').addEventListener('click', () => {
        Swal.fire({
            title: 'Delete Forever?',
            text: 'This record will be permanently erased and cannot be recovered.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Delete Forever',
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6b7280'
        }).then(r => r.isConfirmed && doAction('permanent_delete'));
    });

    // Tab filter
    document.querySelectorAll('#binTabs .nav-link').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('#binTabs .nav-link').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            activeFilter = this.dataset.filter;
            applyFilter();
        });
    });

    // Search
    document.getElementById('binSearch').addEventListener('input', applyFilter);

    function applyFilter() {
        const q = document.getElementById('binSearch').value.toLowerCase();
        let filtered = activeFilter === 'all' ? allRecords : allRecords.filter(r => r.type === activeFilter);
        filtered = filtered.filter(r => (r.label || '').toLowerCase().includes(q));
        buildCards(filtered);
    }

    function loadBin() {
        $.post(SITE_URL + 'api/user_recycle_bin.php', {
            action: 'list'
        }, function(res) {
            if (res.success) {
                allRecords = res.data;
                updateCounts(allRecords);
                applyFilter();
            }
        }, 'json');
    }

    $(document).ready(loadBin);
</script>