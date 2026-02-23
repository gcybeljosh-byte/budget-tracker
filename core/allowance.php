<?php
// session_start(); // Handled in header.php

$pageTitle = 'Allowance';
$pageHeader = 'Allowance Management';
// Extra content for the navbar
$extraNavContent = '<button class="btn btn-primary rounded-circle shadow-sm p-0 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;" data-bs-toggle="modal" data-bs-target="#addAllowanceModal">
    <i class="fas fa-plus fa-lg"></i>
</button>';

include '../includes/header.php';
include '../includes/db.php';
?>

    <?php include '../includes/sidebar.php'; ?>

    <!-- Page Content -->
    <div id="page-content-wrapper">

        <?php include '../includes/navbar.php'; ?>

        <div class="container-fluid px-4 py-4">
            <div id="alertContainer"></div>

            <div class="row mb-3">
                <!-- Total Allowance -->
                <div class="col-md-6 mb-3 mb-md-0 stagger-item">
                    <div class="card h-100 bg-gradient-primary text-white border-0">
                        <div class="card-body">
                            <h5 class="card-title text-opacity-75"><i class="fas fa-hand-holding-dollar me-2"></i>Total Allowance (<?php echo $_SESSION['user_currency'] ?? 'PHP'; ?>)</h5>
                            <h2 class="display-6 fw-bold mb-0" id="totalAllowance"><?php echo CurrencyHelper::getSymbol($_SESSION['user_currency'] ?? 'PHP'); ?>0.00</h2>
                        </div>
                    </div>
                </div>
                <!-- Remaining Balance -->
                <div class="col-md-6 stagger-item">
                    <div class="card h-100 bg-gradient-success text-white border-0">
                        <div class="card-body">
                            <h5 class="card-title text-opacity-75"><i class="fas fa-piggy-bank me-2"></i>Remaining Balance (<?php echo $_SESSION['user_currency'] ?? 'PHP'; ?>)</h5>
                            <h2 class="display-6 fw-bold mb-0" id="remainingBalance"><?php echo CurrencyHelper::getSymbol($_SESSION['user_currency'] ?? 'PHP'); ?>0.00</h2>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 rounded-pill-start ps-3">
                            <i class="fas fa-search text-secondary"></i>
                        </span>
                        <input type="text" id="allowanceSearch" class="form-control border-start-0 rounded-pill-end py-2" placeholder="Search Date, Description, Source...">
                    </div>
                </div>
            </div>

            <!-- Allowance Table -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table id="allowanceTable" class="table align-middle mb-0 table-hover">
                            <thead class="bg-light">
                                <tr>
                                    <th class="border-0 py-3 ps-4 text-secondary small text-uppercase fw-bold">Date</th>
                                    <th class="border-0 py-3 text-secondary small text-uppercase fw-bold">Description</th>
                                    <th class="border-0 py-3 text-secondary small text-uppercase fw-bold text-end">Amount (<?php echo $_SESSION['user_currency'] ?? 'PHP'; ?>)</th>
                                    <th class="border-0 py-3 text-secondary small text-uppercase fw-bold text-center">Source</th>
                                    <th class="border-0 py-3 pe-4 text-secondary small text-uppercase fw-bold text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="allowanceTableBody" class="border-top-0">
                                <!-- Allowances will be dynamically inserted here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

<!-- Add Allowance Modal -->
<div class="modal fade" id="addAllowanceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header border-bottom-0 p-4 pb-0">
                <h5 class="modal-title fw-bold">Add Allowance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 pt-4">
                <form id="allowanceForm">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Date</label>
                        <input type="date" class="form-control rounded-3" id="allowanceDate" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Description</label>
                        <input type="text" class="form-control rounded-3" id="allowanceDesc" placeholder="e.g. Monthly Allowance" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Amount</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><?php echo CurrencyHelper::getSymbol($_SESSION['user_currency'] ?? 'PHP'); ?></span>
                            <input type="number" class="form-control rounded-3 border-start-0" id="allowanceAmount" step="0.01" min="0" required placeholder="0.00">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Source Type</label>
                        <select class="form-select rounded-3" id="allowanceSourceType">
                            <option value="Cash" selected>Cash</option>
                            <option value="GCash">GCash</option>
                            <option value="Maya">Maya</option>
                            <option value="Bank">Bank</option>
                            <option value="Electronic">Other Electronic</option>
                        </select>
                    </div>
                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-primary rounded-pill py-2 fw-bold shadow-sm">Save Allowance</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Allowance Modal -->
<div class="modal fade" id="editAllowanceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header border-bottom-0 p-4 pb-0">
                <h5 class="modal-title fw-bold">Edit Allowance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 pt-4">
                <form id="editAllowanceForm">
                    <input type="hidden" id="editAllowanceId">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Date</label>
                        <input type="date" class="form-control rounded-3" id="editAllowanceDate" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Description</label>
                        <input type="text" class="form-control rounded-3" id="editAllowanceDesc" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Amount</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><?php echo CurrencyHelper::getSymbol($_SESSION['user_currency'] ?? 'PHP'); ?></span>
                            <input type="number" class="form-control rounded-3 border-start-0" id="editAllowanceAmount" step="0.01" min="0" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Source Type</label>
                        <select class="form-select rounded-3" id="editAllowanceSourceType">
                            <option value="Cash">Cash</option>
                            <option value="GCash">GCash</option>
                            <option value="Maya">Maya</option>
                            <option value="Bank">Bank</option>
                            <option value="Electronic">Other Electronic</option>
                        </select>
                    </div>
                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-primary rounded-pill py-2 fw-bold shadow-sm">Update Allowance</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    const allowanceTableBody = document.getElementById('allowanceTableBody');
    const allowanceForm = document.getElementById('allowanceForm');
    const editAllowanceForm = document.getElementById('editAllowanceForm');
    
    // Initial Load
    fetchAllowances();
    fetchDashboardStats();

    // Setup Date Picker Default
    const dateInputs = ['allowanceDate', 'editAllowanceDate'];
    dateInputs.forEach(id => {
        const el = document.getElementById(id);
        if(el) el.value = new Date().toISOString().split('T')[0];
    });

    // --- Functions ---
    
    function fetchAllowances() {
        fetch('<?php echo SITE_URL; ?>api/allowance.php?t=' + new Date().getTime())
            .then(response => response.json())
            .then(data => {
                if (data.success && Array.isArray(data.data)) {
                     renderTable(data.data);
                } else {
                    console.error('Invalid data format:', data);
                }
            })
            .catch(error => console.error('Error fetching allowances:', error));
    }

    function renderTable(allowances) {
        // Destroy existing DataTable if exists
        if ($.fn.DataTable.isDataTable('#allowanceTable')) {
            $('#allowanceTable').DataTable().destroy();
        }

        allowanceTableBody.innerHTML = '';
        allowances.forEach(item => {
            const isElectronic = item.source_type !== 'Cash';
            const sourceBadge = isElectronic ? 'bg-primary-subtle text-primary' : 'bg-secondary-subtle text-secondary';
            
            const row = document.createElement('tr');
            row.innerHTML = `
                <td class="fw-medium text-dark">${item.date}</td>
                <td class="text-secondary">${item.description}</td>
                <td class="text-end"><span class="badge bg-success-subtle text-success rounded-pill fw-bold">+${formatCurrency(item.amount)}</span></td>
                <td class="text-center"><span class="badge ${sourceBadge} rounded-pill small">${item.source_type}</span></td>
                <td class="text-end">
                    <button class="btn btn-sm btn-light text-primary me-1 edit-btn rounded-circle" 
                        data-id="${item.id}" 
                        data-date="${item.date}" 
                        data-desc="${item.description}" 
                        data-amount="${item.amount}"
                        data-source="${item.source_type}">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-light text-danger delete-btn rounded-circle" data-id="${item.id}">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
            allowanceTableBody.appendChild(row);
        });

        // Re-initialize DataTable with iOS layout
        const table = $('#allowanceTable').DataTable({
            responsive: true,
            order: [[0, 'desc']],
            searching: true,
            dom: "<'row'<'col-sm-12'tr>><'row pagination-container'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>"
        });

        // Custom Search Listener
        document.getElementById('allowanceSearch').addEventListener('keyup', function() {
            table.search(this.value).draw();
        });

        // Attach Event Listeners to Buttons (since we re-rendered)
        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                openEditModal(this.dataset);
            });
        });

        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                deleteAllowance(this.dataset.id);
            });
        });
    }

    function openEditModal(data) {
        document.getElementById('editAllowanceId').value = data.id;
        document.getElementById('editAllowanceDate').value = data.date;
        document.getElementById('editAllowanceDesc').value = data.desc;
        document.getElementById('editAllowanceAmount').value = data.amount;
        document.getElementById('editAllowanceSourceType').value = data.source || 'Cash';
        
        const modal = new bootstrap.Modal(document.getElementById('editAllowanceModal'));
        modal.show();
    }

    function fetchDashboardStats() {
        fetch('<?php echo SITE_URL; ?>api/dashboard.php?t=' + new Date().getTime())
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('totalAllowance').textContent = formatCurrency(data.total_allowance);
                    document.getElementById('remainingBalance').textContent = formatCurrency(data.balance);
                }
            })
            .catch(error => console.error('Error fetching stats:', error));
    }

    // --- Form Submissions ---

    // Add Allowance
    allowanceForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData();
        formData.append('action', 'add');
        formData.append('date', document.getElementById('allowanceDate').value);
        formData.append('description', document.getElementById('allowanceDesc').value);
        formData.append('amount', document.getElementById('allowanceAmount').value);
        formData.append('source_type', document.getElementById('allowanceSourceType').value);

        fetch('<?php echo SITE_URL; ?>api/allowance.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                showAlert(result.message, 'success');
                const addedAmount = parseFloat(document.getElementById('allowanceAmount').value);
                allowanceForm.reset();
                document.getElementById('allowanceDate').value = new Date().toISOString().split('T')[0];
                bootstrap.Modal.getOrCreateInstance(document.getElementById('addAllowanceModal')).hide();
                fetchAllowances();
                fetchDashboardStats();

                // Offer AI budget plan after a short delay
                setTimeout(() => {
                    Swal.fire({
                        title: '🤖 AI Budget Planner',
                        html: `You added <b><?php echo CurrencyHelper::getSymbol($_SESSION['user_currency'] ?? 'PHP'); ?>${addedAmount.toLocaleString('en-PH',{minimumFractionDigits:2})}</b> allowance.<br><br>Would you like AI to suggest monthly budget limits per category based on this amount?`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: '<i class="fas fa-wand-magic-sparkles me-1"></i> Yes, Suggest!',
                        cancelButtonText: 'Maybe later'
                    }).then(r => {
                        if (r.isConfirmed) {
                            Swal.fire({
                                title: '🤖 Generating Budget Plan…',
                                html: 'AI is calculating your suggested limits.<br><small class="text-muted">This takes a few seconds.</small>',
                                allowOutsideClick: false,
                                didOpen: () => Swal.showLoading()
                            });

                            fetch('<?php echo SITE_URL; ?>api/ai_budget_plan.php', {
                                method: 'POST',
                                headers: {'Content-Type': 'application/json'},
                                body: JSON.stringify({allowance: addedAmount})
                            })
                            .then(r => r.json())
                            .then(d => {
                                if (!d.success) {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'AI Error',
                                        text: d.message,
                                        confirmButtonColor: '#6366f1'
                                    });
                                    return;
                                }
                                const sym = '<?php echo CurrencyHelper::getSymbol($_SESSION["user_currency"] ?? "PHP"); ?>';
                                const rows = d.suggestions.map(s =>
                                    `<tr><td class="text-start py-1 small">${s.category}</td><td class="text-end py-1 small fw-bold" style="color:#6366f1;">${sym}${parseFloat(s.amount).toLocaleString('en-PH',{minimumFractionDigits:2})}</td></tr>`
                                ).join('');

                                Swal.fire({
                                    title: '✨ AI Budget Plan Ready',
                                    html: `<p class="text-muted small mb-3">Based on your <b>${sym}${addedAmount.toLocaleString('en-PH',{minimumFractionDigits:2})}</b> allowance:</p>
                                           <table class="table table-sm table-borderless"><thead><tr><th class="text-start text-muted small">Category</th><th class="text-end text-muted small">Suggested Limit</th></tr></thead><tbody>${rows}</tbody></table>
                                           <p class="text-muted" style="font-size:0.75rem;">Go to <b>Expenses → Budget Limits</b> to apply these suggestions using the AI Suggest button.</p>`,
                                    icon: 'success',
                                    showCancelButton: true,
                                    confirmButtonText: 'Go to Expenses',
                                    cancelButtonText: 'Dismiss',
                                    width: 480
                                }).then(nav => {
                                    if (nav.isConfirmed) {
                                        window.location.href = '<?php echo SITE_URL; ?>core/expenses.php';
                                    }
                                });
                            })
                            .catch(() => {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: 'Could not reach the AI service.',
                                    confirmButtonColor: '#6366f1'
                                });
                            });
                        }
                    });
                }, 600);
            } else {
                showAlert(result.message, 'danger');
            }
        })
        .catch(error => console.error('Error adding:', error));
    });

    // Update Allowance
    editAllowanceForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData();
        formData.append('action', 'edit');
        formData.append('id', document.getElementById('editAllowanceId').value);
        formData.append('date', document.getElementById('editAllowanceDate').value);
        formData.append('description', document.getElementById('editAllowanceDesc').value);
        formData.append('amount', document.getElementById('editAllowanceAmount').value);
        formData.append('source_type', document.getElementById('editAllowanceSourceType').value);

        fetch('<?php echo SITE_URL; ?>api/allowance.php', {
            method: 'POST', // Using POST for edit as per user's API change
            body: formData
        })
        .then(response => response.json())
        .then(result => {
             if (result.success) {
                showAlert(result.message, 'success');
                bootstrap.Modal.getOrCreateInstance(document.getElementById('editAllowanceModal')).hide();
                fetchAllowances();
                fetchDashboardStats();
            } else {
                showAlert(result.message, 'danger');
            }
        })
        .catch(error => console.error('Error updating:', error));
    });

    // Delete Allowance
    function deleteAllowance(id) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#6366f1',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);

                fetch('<?php echo SITE_URL; ?>api/allowance.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(result => {
                    if(result.success) {
                        Swal.fire({
                            title: 'Deleted!',
                            text: result.message,
                            icon: 'success',
                            confirmButtonColor: '#6366f1'
                        });
                        fetchAllowances();
                        fetchDashboardStats();
                    } else {
                        showAlert(result.message, 'danger');
                    }
                })
                .catch(error => console.error('Error deleting:', error));
            }
        });
    }

    // --- Helpers ---

    function formatCurrency(amount) {
        const val = parseFloat(amount) || 0;
        return new Intl.NumberFormat(window.userCurrency.locale, { 
            style: 'currency', 
            currency: window.userCurrency.code 
        }).format(val);
    }

    function showAlert(message, type) {
        Swal.fire({
            icon: type === 'danger' ? 'error' : 'success',
            title: type === 'danger' ? 'Error' : 'Success',
            text: message,
            showConfirmButton: false,
            timer: 2000
        });
    }

    // --- Page Tutorial ---
    <?php if (!isset($seen_tutorials['allowance.php'])): ?>
    function startTutorial() {
        if (window.seenTutorials['allowance.php']) return;

        const steps = [
            {
                title: 'Allowance Management',
                text: 'Track your income and budget sources right here.',
                icon: 'info',
                confirmButtonText: 'Next'
            },
            {
                title: 'Allowance Summary',
                text: 'See your total allowance and how much is still available for distribution.',
                icon: 'hand-holding-dollar',
                confirmButtonText: 'Next',
                target: '.row.mb-3'
            },
            {
                title: 'Adding Funds',
                text: 'Add new allowance entries from various sources like bank transfers or cash.',
                icon: 'plus',
                confirmButtonText: 'Finish',
                target: '[data-bs-target="#addAllowanceModal"]'
            }
        ];

        let currentStep = 0;
        function showStep(index) {
            if (index >= steps.length) { markPageTutorialSeen('allowance.php'); return; }
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
                            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            el.classList.add('tutorial-highlight');
                            setTimeout(() => el.classList.remove('tutorial-highlight'), 3000);
                        }
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) showStep(index + 1);
                else if (result.dismiss === Swal.DismissReason.cancel) markPageTutorialSeen('allowance.php');
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

<?php include '../includes/footer.php'; ?>
