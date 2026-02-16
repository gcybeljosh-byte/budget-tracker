<?php
session_start();
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit;
}

$pageTitle = "Savings Management";
$pageHeader = "Savings Tracking";
include 'includes/header.php';
?>

<?php include 'includes/sidebar.php'; ?>

<!-- Page Content -->
<div id="page-content-wrapper">
    <?php 
    // Custom Nav Content for Savings Page - Uniform Circular Plus Button
    $extraNavContent = '<button class="btn btn-primary rounded-circle shadow-sm p-0 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;" data-bs-toggle="modal" data-bs-target="#addSavingsModal">
        <i class="fas fa-plus fa-lg"></i>
    </button>';
    include 'includes/navbar.php'; 
    ?>

    <div class="container-fluid px-4 py-4">
        <!-- Alerts/Notifications -->
        <div id="alertContainer"></div>

        <!-- Dashboard-style Horizontal Stats Cards -->
        <div class="row g-4 mb-4">
            <!-- Total Savings -->
            <div class="col-md-4">
                <div class="card h-100 bg-gradient-success text-white border-0 shadow-sm rounded-4">
                    <div class="card-body">
                        <h5 class="card-title text-opacity-75"><i class="fas fa-piggy-bank me-2"></i>Total Savings (<?php echo $_SESSION['user_currency'] ?? 'PHP'; ?>)</h5>
                        <h2 class="display-6 fw-bold mb-0" id="statTotal">₱0.00</h2>
                    </div>
                </div>
            </div>
            <!-- This Month's Savings -->
            <div class="col-md-4">
                <div class="card h-100 bg-gradient-primary text-white border-0 shadow-sm rounded-4">
                    <div class="card-body">
                        <h5 class="card-title text-opacity-75"><i class="fas fa-calendar-alt me-2"></i>This Month</h5>
                        <h2 class="display-6 fw-bold mb-0" id="statMonth">₱0.00</h2>
                    </div>
                </div>
            </div>
            <!-- This Year's Savings -->
            <div class="col-md-4">
                <div class="card h-100 bg-dark text-white border-0 shadow-sm rounded-4">
                    <div class="card-body">
                        <h5 class="card-title text-opacity-75"><i class="fas fa-chart-line me-2"></i>This Year</h5>
                        <h2 class="display-6 fw-bold mb-0" id="statYear">₱0.00</h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Main Content: Recent Savings Table -->
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold text-dark">Recent Savings</h5>
                        <div class="input-group" style="max-width: 250px;">
                            <span class="input-group-text bg-light border-0"><i class="fas fa-search text-muted small"></i></span>
                            <input type="text" class="form-control bg-light border-0 small" id="savingsSearch" placeholder="Search savings...">
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="savingsTable">
                                <thead class="bg-light text-secondary small text-uppercase fw-bold">
                                    <tr>
                                        <th class="px-4 py-3">Date</th>
                                        <th class="py-3">Description</th>
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
                            <span class="input-group-text bg-light border-end-0">₱</span>
                            <input type="number" class="form-control rounded-3 border-start-0" name="amount" step="0.01" min="0" required placeholder="0.00">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Description</label>
                        <input type="text" class="form-control rounded-3" name="description" placeholder="e.g. Monthly Savings" required>
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
                            <span class="input-group-text bg-light border-end-0">₱</span>
                            <input type="number" class="form-control rounded-3 border-start-0" name="amount" id="editSavingsAmount" step="0.01" min="0" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Description</label>
                        <input type="text" class="form-control rounded-3" name="description" id="editSavingsDesc" required>
                    </div>
                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-primary rounded-pill py-2 fw-bold shadow-sm">Update Record</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let table;
    
    function initTable() {
        if (table) table.destroy();
        table = $('#savingsTable').DataTable({
            responsive: true,
            order: [[0, 'desc']],
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
        fetch('api/savings.php?action=stats')
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
        fetch('api/savings.php')
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    const list = document.getElementById('savingsList');
                    list.innerHTML = '';
                    result.data.forEach(item => {
                        list.innerHTML += `
                            <tr>
                                <td class="px-4 fw-medium text-dark">${item.date}</td>
                                <td class="text-secondary">${item.description}</td>
                                <td class="text-end fw-bold text-success">${formatCurrency(item.amount)}</td>
                                <td class="px-4 text-end">
                                    <button class="btn btn-sm btn-light text-primary me-1 edit-savings rounded-circle" 
                                        data-id="${item.id}" 
                                        data-date="${item.date}" 
                                        data-desc="${item.description}" 
                                        data-amount="${item.amount}">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-light text-danger delete-savings rounded-circle" data-id="${item.id}">
                                        <i class="fas fa-trash"></i>
                                    </button>
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
        
        fetch('api/savings.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                Swal.fire({ icon: 'success', title: 'Record Saved', timer: 1500, showConfirmButton: false });
                bootstrap.Modal.getInstance(document.getElementById('addSavingsModal')).hide();
                this.reset();
                fetchStats();
                fetchSavings();
            } else {
                Swal.fire('Error', result.message, 'error');
            }
        });
    });

    // Edit Savings Form
    document.getElementById('editSavingsForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        fetch('api/savings.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                Swal.fire({ icon: 'success', title: 'Record Updated', timer: 1500, showConfirmButton: false });
                bootstrap.Modal.getInstance(document.getElementById('editSavingsModal')).hide();
                fetchStats();
                fetchSavings();
            } else {
                Swal.fire('Error', result.message, 'error');
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
                confirmButtonColor: '#ff3b30',
                confirmButtonText: 'Yes, delete',
                borderRadius: '1rem'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('action', 'delete');
                    formData.append('id', id);
                    
                    fetch('api/savings.php', { method: 'POST', body: formData })
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            Swal.fire({ icon: 'success', title: 'Deleted', timer: 1000, showConfirmButton: false });
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
});
</script>
