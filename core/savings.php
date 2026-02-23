<?php
session_start();
if (!isset($_SESSION['id'])) {
    header("Location: " . SITE_URL . "auth/login.php");
    exit;
}

$pageTitle = "Savings Management";
$pageHeader = "Savings Tracking";
include '../includes/header.php';
?>

<?php include '../includes/sidebar.php'; ?>

<!-- Page Content -->
<div id="page-content-wrapper">
    <?php
    // Custom Nav Content for Savings Page - Uniform Circular Plus Button
    $extraNavContent = '<button class="btn btn-success rounded-circle shadow-sm p-0 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;" data-bs-toggle="modal" data-bs-target="#addSavingsModal">
        <i class="fas fa-plus fa-lg"></i>
    </button>';
    include '../includes/navbar.php';
    ?>

    <div class="container-fluid px-4 py-4">
        <!-- Alerts/Notifications -->
        <div id="alertContainer"></div>

        <!-- Premium Stats Cards -->
        <div class="row g-4 mb-4">
            <!-- Total Savings -->
            <div class="col-md-4 stagger-item">
                <div class="card h-100 border-0 shadow-sm rounded-4 bg-light overflow-hidden transition-all hover-lift">
                    <div class="card-body d-flex align-items-center p-4">
                        <div class="rounded-circle bg-success-subtle p-3 me-3 text-success shadow-sm">
                            <i class="fas fa-piggy-bank fa-xl"></i>
                        </div>
                        <div>
                            <h6 class="text-secondary small fw-bold text-uppercase mb-1" style="font-size: 0.65rem;">Total Savings</h6>
                            <h4 class="fw-bold mb-0" id="statTotal"><?php echo CurrencyHelper::getSymbol($_SESSION['user_currency'] ?? 'PHP'); ?>0.00</h4>
                        </div>
                    </div>
                </div>
            </div>
            <!-- This Month -->
            <div class="col-md-4 stagger-item">
                <div class="card h-100 border-0 shadow-sm rounded-4 bg-light overflow-hidden transition-all hover-lift">
                    <div class="card-body d-flex align-items-center p-4">
                        <div class="rounded-circle bg-primary-subtle p-3 me-3 text-primary shadow-sm">
                            <i class="fas fa-calendar-check fa-xl"></i>
                        </div>
                        <div>
                            <h6 class="text-secondary small fw-bold text-uppercase mb-1" style="font-size: 0.65rem;">Deposited This Month</h6>
                            <h4 class="fw-bold mb-0" id="statMonth"><?php echo CurrencyHelper::getSymbol($_SESSION['user_currency'] ?? 'PHP'); ?>0.00</h4>
                        </div>
                    </div>
                </div>
            </div>
            <!-- This Year -->
            <div class="col-md-4 stagger-item">
                <div class="card h-100 border-0 shadow-sm rounded-4 bg-light overflow-hidden transition-all hover-lift">
                    <div class="card-body d-flex align-items-center p-4">
                        <div class="rounded-circle bg-info-subtle p-3 me-3 text-info shadow-sm">
                            <i class="fas fa-chart-line fa-xl"></i>
                        </div>
                        <div>
                            <h6 class="text-secondary small fw-bold text-uppercase mb-1" style="font-size: 0.65rem;">Deposited This Year</h6>
                            <h4 class="fw-bold mb-0" id="statYear"><?php echo CurrencyHelper::getSymbol($_SESSION['user_currency'] ?? 'PHP'); ?>0.00</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Main Content: Recent Savings Table -->
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold text-dark">Savings & Withdrawals</h5>
                        <div class="d-flex align-items-center gap-2">
                            <div class="input-group" style="max-width: 250px;">
                                <span class="input-group-text bg-light border-0"><i class="fas fa-search text-muted small"></i></span>
                                <input type="text" class="form-control bg-light border-0 small" id="savingsSearch" placeholder="Search savings...">
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="savingsTable">
                                <thead class="bg-light text-secondary small text-uppercase fw-bold">
                                    <tr>
                                        <th class="px-4 py-3">Date</th>
                                        <th class="py-3">Description</th>
                                        <th class="py-3">Type</th>
                                        <th class="py-3 text-start">Source</th>
                                        <th class="py-3 text-end">Amount</th>
                                        <th class="px-4 py-3 text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="savingsList">
                                    <!-- Dynamic Content -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Savings Modal -->
<div class="modal fade" id="addSavingsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header border-bottom-0 p-4 pb-0">
                <h5 class="modal-title fw-bold">Add Savings</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 pt-4">
                <form id="savingsForm">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Date</label>
                        <input type="date" class="form-control rounded-3" name="date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Amount</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><?php echo CurrencyHelper::getSymbol($_SESSION['user_currency'] ?? 'PHP'); ?></span>
                            <input type="number" class="form-control rounded-3 border-start-0" name="amount" step="0.01" min="0" required placeholder="0.00">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Description</label>
                        <input type="text" class="form-control rounded-3" name="description" placeholder="e.g. Monthly Savings" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Source</label>
                        <select class="form-select rounded-3" name="source_type" required>
                            <option value="Cash">Cash</option>
                            <option value="GCash">GCash</option>
                            <option value="Maya">Maya</option>
                            <option value="Bank">Bank</option>
                            <option value="Electronic">Electronic</option>
                        </select>
                    </div>
                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-primary rounded-pill py-2 fw-bold shadow-sm">Save Record</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Savings Modal -->
<div class="modal fade" id="editSavingsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header border-bottom-0 p-4 pb-0">
                <h5 class="modal-title fw-bold">Edit Savings</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 pt-4">
                <form id="editSavingsForm">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="editSavingsId">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Date</label>
                        <input type="date" class="form-control rounded-3" name="date" id="editSavingsDate" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Amount</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><?php echo CurrencyHelper::getSymbol($_SESSION['user_currency'] ?? 'PHP'); ?></span>
                            <input type="number" class="form-control rounded-3 border-start-0" name="amount" id="editSavingsAmount" step="0.01" min="0" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Description</label>
                        <input type="text" class="form-control rounded-3" name="description" id="editSavingsDesc" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Source</label>
                        <select class="form-select rounded-3" name="source_type" id="editSavingsSource" required>
                            <option value="Cash">Cash</option>
                            <option value="GCash">GCash</option>
                            <option value="Maya">Maya</option>
                            <option value="Bank">Bank</option>
                            <option value="Electronic">Electronic</option>
                        </select>
                    </div>
                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-primary rounded-pill py-2 fw-bold shadow-sm">Update Record</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        let table;

        function initTable() {
            if (table) table.destroy();
            table = $('#savingsTable').DataTable({
                responsive: true,
                order: [
                    [0, 'desc']
                ],
                dom: '<"row"<"col-sm-12"tr>><"row pagination-container"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                pageLength: 10,
                language: {
                    emptyTable: "No savings records found"
                }
            });

            document.getElementById('savingsSearch').addEventListener('keyup', function() {
                table.search(this.value).draw();
            });
        }

        function fetchStats() {
            fetch('<?php echo SITE_URL; ?>api/savings.php?action=stats')
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        const data = result.data;
                        document.getElementById('statTotal').textContent = formatCurrency(data.total);
                        document.getElementById('statMonth').textContent = formatCurrency(data.monthly);
                        document.getElementById('statYear').textContent = formatCurrency(data.yearly);
                    }
                })
                .catch(error => console.error('Error fetching savings stats:', error));
        }

        function fetchSavings() {
            fetch('<?php echo SITE_URL; ?>api/savings.php')
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        const list = document.getElementById('savingsList');
                        list.innerHTML = '';
                        result.data.forEach(item => {
                            const isWithdrawal = item.type === 'withdrawal';
                            const amountClass = isWithdrawal ? 'text-danger' : 'text-success';
                            const amountSign = isWithdrawal ? '-' : '+';
                            const badge = isWithdrawal ?
                                '<span class="badge bg-danger-subtle text-danger rounded-pill small">Withdrawal</span>' :
                                '<span class="badge bg-success-subtle text-success rounded-pill small">Savings</span>';

                            list.innerHTML += `
                            <tr>
                                <td class="px-4 fw-medium text-dark">${item.date}</td>
                                <td class="text-secondary">${item.description}</td>
                                <td>${badge}</td>
                                <td class="text-start"><span class="badge ${(item.source_type && item.source_type !== 'Cash') ? 'bg-primary-subtle text-primary' : 'bg-secondary-subtle text-secondary'} rounded-pill small">${item.source_type || 'Cash'}</span></td>
                                <td class="text-end fw-bold ${amountClass}">${amountSign}${formatCurrency(item.amount)}</td>
                                <td class="px-4 text-end">
                                    ${!isWithdrawal ? `
                                        <button class="btn btn-sm btn-light text-primary me-1 edit-savings rounded-circle" 
                                            data-id="${item.id}" 
                                            data-date="${item.date}" 
                                            data-desc="${item.description}" 
                                            data-amount="${item.amount}"
                                            data-source="${item.source_type || 'Cash'}">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-light text-danger delete-savings rounded-circle" data-id="${item.id}">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    ` : `
                                        <a href="expenses.php" class="btn btn-sm btn-light text-secondary rounded-circle" title="Manage in Expenses">
                                            <i class="fas fa-external-link-alt"></i>
                                        </a>
                                    `}
                                </td>
                            </tr>
                        `;
                        });
                        initTable();
                    }
                });
        }

        function formatCurrency(amount) {
            const val = parseFloat(amount) || 0;
            return new Intl.NumberFormat(window.userCurrency.locale, {
                style: 'currency',
                currency: window.userCurrency.code
            }).format(val);
        }

        // Add Savings Form
        document.getElementById('savingsForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('<?php echo SITE_URL; ?>api/savings.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Record Saved',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        bootstrap.Modal.getInstance(document.getElementById('addSavingsModal')).hide();
                        this.reset();
                        fetchStats();
                        fetchSavings();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: result.message,
                            confirmButtonColor: '#6366f1'
                        });
                    }
                });
        });

        // Edit Savings Form
        document.getElementById('editSavingsForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('<?php echo SITE_URL; ?>api/savings.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Record Updated',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        bootstrap.Modal.getInstance(document.getElementById('editSavingsModal')).hide();
                        fetchStats();
                        fetchSavings();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: result.message,
                            confirmButtonColor: '#6366f1'
                        });
                    }
                });
        });

        // Button Listeners (Delegated)
        document.addEventListener('click', function(e) {
            // Edit Button
            const editBtn = e.target.closest('.edit-savings');
            if (editBtn) {
                const data = editBtn.dataset;
                document.getElementById('editSavingsId').value = data.id;
                document.getElementById('editSavingsDate').value = data.date;
                document.getElementById('editSavingsAmount').value = data.amount;
                document.getElementById('editSavingsDesc').value = data.desc;
                document.getElementById('editSavingsSource').value = data.source;
                new bootstrap.Modal(document.getElementById('editSavingsModal')).show();
            }

            // Delete Button
            const delBtn = e.target.closest('.delete-savings');
            if (delBtn) {
                const id = delBtn.dataset.id;
                Swal.fire({
                    title: 'Delete this record?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#6366f1',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const formData = new FormData();
                        formData.append('action', 'delete');
                        formData.append('id', id);

                        fetch('<?php echo SITE_URL; ?>api/savings.php', {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.json())
                            .then(result => {
                                if (result.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Deleted',
                                        timer: 1000,
                                        showConfirmButton: false
                                    });
                                    fetchStats();
                                    fetchSavings();
                                }
                            });
                    }
                });
            }
        });

        fetchStats();
        fetchSavings();

        // --- Page Tutorial ---
        <?php if (!isset($seen_tutorials['savings.php'])): ?>

            function startTutorial() {
                if (window.seenTutorials['savings.php']) return;

                const steps = [{
                        title: 'Savings Tracker',
                        text: 'Every penny saved is a step towards your goals. Let\'s see how to manage it.',
                        icon: 'info',
                        confirmButtonText: 'Continue'
                    },
                    {
                        title: 'Savings Overview',
                        text: 'Track your total savings and your progress for this month and year.',
                        icon: 'piggy-bank',
                        confirmButtonText: 'Next',
                        target: '.row.g-4.mb-4'
                    },
                    {
                        title: 'Savings History',
                        text: 'Review all your savings records and withdrawals here.',
                        icon: 'list-ul',
                        confirmButtonText: 'Next',
                        target: '#savingsTable'
                    },
                    {
                        title: 'Deposit Savings',
                        text: 'Record new savings by transferring from your allowance or other sources.',
                        icon: 'plus',
                        confirmButtonText: 'Finish',
                        target: '[data-bs-target="#addSavingsModal"]'
                    }
                ];

                function showStep(index) {
                    if (index >= steps.length) {
                        markPageTutorialSeen('savings.php');
                        return;
                    }
                    const step = steps[index];
                    Swal.fire({
                        title: step.title,
                        text: step.text,
                        icon: 'info',
                        confirmButtonText: step.confirmButtonText,
                        showCancelButton: true,
                        cancelButtonText: 'Skip',
                        reverseButtons: true,
                        allowOutsideClick: false,
                        didOpen: () => {
                            if (step.target) {
                                const el = document.querySelector(step.target);
                                if (el) {
                                    el.scrollIntoView({
                                        behavior: 'smooth',
                                        block: 'center'
                                    });
                                    el.classList.add('tutorial-highlight');
                                    setTimeout(() => el.classList.remove('tutorial-highlight'), 3000);
                                }
                            }
                        }
                    }).then((result) => {
                        if (result.isConfirmed) showStep(index + 1);
                        else if (result.dismiss === Swal.DismissReason.cancel) markPageTutorialSeen('savings.php');
                    });
                }
                if (!document.getElementById('tutorial-styles')) {
                    const style = document.createElement('style');
                    style.id = 'tutorial-styles';
                    style.textContent = `.tutorial-highlight { outline: 4px solid var(--primary); outline-offset: 4px; border-radius: 12px; transition: outline 0.3s ease; z-index: 9999; position: relative; }`;
                    document.head.appendChild(style);
                }
                showStep(0);
            }
            setTimeout(startTutorial, 1000);
        <?php endif; ?>
    });
</script>