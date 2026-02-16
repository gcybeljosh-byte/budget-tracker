<?php
$pageTitle = 'Expenses';
$pageHeader = 'Expense Management';
$extraNavContent = '<button class="btn btn-danger rounded-circle shadow-sm p-0 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;" data-bs-toggle="modal" data-bs-target="#addExpenseModal">
    <i class="fas fa-plus fa-lg"></i>
</button>';

include 'includes/header.php';
?>

    <?php include 'includes/sidebar.php'; ?>

    <!-- Page Content -->
    <div id="page-content-wrapper">

        <?php include 'includes/navbar.php'; ?>

        <div class="container-fluid px-4 py-4">
            <div id="alertContainer"></div>

            <!-- Statistics Cards -->
            <div class="row g-3 mb-4">
                <!-- Total Expenses -->
                <div class="col-md-6">
                    <div class="card h-100 bg-gradient-danger text-white border-0 shadow-sm rounded-4">
                        <div class="card-body">
                            <h5 class="card-title text-opacity-75"><i class="fas fa-receipt me-2"></i>Total Expenses (<?php echo $_SESSION['user_currency'] ?? 'PHP'; ?>)</h5>
                            <h2 class="display-6 fw-bold mb-0" id="totalExpenses">₱0.00</h2>
                        </div>
                    </div>
                </div>
                <!-- Remaining Balance -->
                <div class="col-md-6">
                    <div class="card h-100 bg-gradient-success text-white border-0 shadow-sm rounded-4">
                        <div class="card-body">
                            <h5 class="card-title text-opacity-75"><i class="fas fa-piggy-bank me-2"></i>Remaining Balance (<?php echo $_SESSION['user_currency'] ?? 'PHP'; ?>)</h5>
                            <h2 class="display-6 fw-bold mb-0" id="remainingBalance">₱0.00</h2>
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
                        <input type="text" id="expenseSearch" class="form-control border-start-0 rounded-pill-end py-2" placeholder="Search Date, Category, Description...">
                    </div>
                </div>
            </div>

            <!-- Expenses Table -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table id="expensesTable" class="table align-middle mb-0 table-hover">
                            <thead class="bg-light">
                                <tr>
                                    <th class="border-0 py-3 ps-4 text-secondary small text-uppercase fw-bold">Date</th>
                                    <th class="border-0 py-3 text-secondary small text-uppercase fw-bold">Category</th>
                                    <th class="border-0 py-3 text-secondary small text-uppercase fw-bold">Description</th>
                                    <th class="border-0 py-3 text-secondary small text-uppercase fw-bold text-end">Amount (<?php echo $_SESSION['user_currency'] ?? 'PHP'; ?>)</th>
                                    <th class="border-0 py-3 text-secondary small text-uppercase fw-bold text-center">Source</th>
                                    <th class="border-0 py-3 pe-4 text-secondary small text-uppercase fw-bold text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="expenseTableBody" class="border-top-0">
                                <!-- Expenses will be dynamically inserted here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>


<!-- Add Expense Modal -->
<div class="modal fade" id="addExpenseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header border-bottom-0 p-4 pb-0">
                <h5 class="modal-title fw-bold">Add Expense</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 pt-4">
                <form id="expenseForm">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Date</label>
                        <input type="date" class="form-control rounded-3" id="expenseDate" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase d-flex justify-content-between">
                            Category 
                            <a href="#" class="small text-decoration-none" data-bs-toggle="modal" data-bs-target="#manageCategoriesModal">Manage</a>
                        </label>
                        <select class="form-select rounded-3 category-select" id="expenseCategory" required>
                            <!-- Dynamic Content -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Description</label>
                        <input type="text" class="form-control rounded-3" id="expenseDesc" placeholder="e.g. Lunch" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Amount</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">₱</span>
                            <input type="number" class="form-control rounded-3 border-start-0" id="expenseAmount" step="0.01" min="0" required placeholder="0.00">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Source Type</label>
                        <select class="form-select rounded-3" id="expenseSourceType">
                            <option value="Cash" selected>Cash</option>
                            <option value="GCash">GCash</option>
                            <option value="Maya">Maya</option>
                            <option value="Bank">Bank</option>
                            <option value="Electronic">Other Electronic</option>
                        </select>
                    </div>
                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-danger rounded-pill py-2 fw-bold shadow-sm">Save Expense</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Expense Modal -->
<div class="modal fade" id="editExpenseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header border-bottom-0 p-4 pb-0">
                <h5 class="modal-title fw-bold">Edit Expense</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 pt-4">
                <form id="editExpenseForm">
                    <input type="hidden" id="editExpenseId">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Date</label>
                        <input type="date" class="form-control rounded-3" id="editExpenseDate" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Category</label>
                        <select class="form-select rounded-3 category-select" id="editExpenseCategory" required>
                            <!-- Dynamic Content -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Description</label>
                        <input type="text" class="form-control rounded-3" id="editExpenseDesc" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Amount</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">₱</span>
                            <input type="number" class="form-control rounded-3 border-start-0" id="editExpenseAmount" step="0.01" min="0" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Source Type</label>
                        <select class="form-select rounded-3" id="editExpenseSourceType">
                            <option value="Cash">Cash</option>
                            <option value="GCash">GCash</option>
                            <option value="Maya">Maya</option>
                            <option value="Bank">Bank</option>
                            <option value="Electronic">Other Electronic</option>
                        </select>
                    </div>
                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-primary rounded-pill py-2 fw-bold shadow-sm">Update Expense</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Manage Categories Modal -->
<div class="modal fade" id="manageCategoriesModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header border-bottom-0 p-4 pb-0">
                <h5 class="modal-title fw-bold">Manage Categories</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 pt-4">
                <form id="addCategoryForm" class="mb-4">
                    <div class="input-group">
                        <input type="text" id="newCategoryName" class="form-control border-light rounded-pill-start ps-3" placeholder="New category name..." required>
                        <button class="btn btn-primary px-4 rounded-pill-end fw-bold" type="submit">Add</button>
                    </div>
                </form>
                <div class="list-group list-group-flush" id="categoryList">
                    <!-- Dynamic Content -->
                </div>
            </div>
        </div>
    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    const expenseTableBody = document.getElementById('expenseTableBody');
    const expenseForm = document.getElementById('expenseForm');
    const editExpenseForm = document.getElementById('editExpenseForm');
    
    // Initial Load
    fetchExpenses();
    fetchCategories();
    fetchDashboardStats();

    // Setup Date Picker Default
    const dateInputs = ['expenseDate', 'editExpenseDate'];
    dateInputs.forEach(id => {
        const el = document.getElementById(id);
        if(el) el.value = new Date().toISOString().split('T')[0];
    });

    // --- Functions ---
    
    function fetchCategories() {
        fetch('api/categories.php')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    renderCategorySelects(data.data);
                    renderCategoryList(data.data);
                }
            });
    }

    function renderCategorySelects(categories) {
        const selects = document.querySelectorAll('.category-select');
        selects.forEach(select => {
            const currentVal = select.value;
            select.innerHTML = '<option value="" disabled selected>Select Category</option>';
            categories.forEach(cat => {
                const option = document.createElement('option');
                option.value = cat.name;
                option.textContent = cat.name;
                select.appendChild(option);
            });
            if (currentVal) select.value = currentVal;
        });
    }

    function renderCategoryList(categories) {
        const list = document.getElementById('categoryList');
        if (!list) return;
        list.innerHTML = '';
        categories.forEach(cat => {
            const item = document.createElement('div');
            item.className = 'list-group-item d-flex justify-content-between align-items-center';
            item.innerHTML = `
                <span>${cat.name}</span>
                <button class="btn btn-sm btn-outline-danger border-0 delete-cat-btn" data-id="${cat.id}">
                    <i class="fas fa-times"></i>
                </button>
            `;
            list.appendChild(item);
        });

        document.querySelectorAll('.delete-cat-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                deleteCategory(this.dataset.id);
            });
        });
    }

    function fetchExpenses() {
        fetch('api/expenses.php?t=' + new Date().getTime())
            .then(response => response.json())
            .then(data => {
                if (data.success && Array.isArray(data.data)) {
                     renderTable(data.data);
                } else {
                    console.error('Invalid data format:', data);
                }
            })
            .catch(error => console.error('Error fetching expenses:', error));
    }

    function renderTable(expenses) {
        // Destroy existing DataTable if exists
        if ($.fn.DataTable.isDataTable('#expensesTable')) {
            $('#expensesTable').DataTable().destroy();
        }

        expenseTableBody.innerHTML = '';
        expenses.forEach(item => {
            const isElectronic = item.source_type !== 'Cash';
            const sourceBadge = isElectronic ? 'bg-primary-subtle text-primary' : 'bg-secondary-subtle text-secondary';

            const row = document.createElement('tr');
            row.innerHTML = `
                <td class="fw-medium text-dark">${item.date}</td>
                <td><span class="badge bg-secondary-subtle text-secondary rounded-pill text-uppercase small">${item.category}</span></td>
                <td class="text-secondary">${item.description}</td>
                <td class="text-end"><span class="badge bg-danger-subtle text-danger rounded-pill fw-bold">-${formatCurrency(item.amount)}</span></td>
                <td class="text-center"><span class="badge ${sourceBadge} rounded-pill small">${item.source_type}</span></td>
                <td class="text-end">
                    <button class="btn btn-sm btn-light text-primary me-1 edit-btn rounded-circle" 
                        data-id="${item.id}" 
                        data-date="${item.date}" 
                        data-category="${item.category}"
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
            expenseTableBody.appendChild(row);
        });

        // Re-initialize DataTable with iOS layout
        const table = $('#expensesTable').DataTable({
            responsive: true,
            order: [[0, 'desc']],
            searching: true,
            dom: "<'row'<'col-sm-12'tr>><'row pagination-container'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>"
        });

        // Custom Search Listener
        document.getElementById('expenseSearch').addEventListener('keyup', function() {
            table.search(this.value).draw();
        });

        // Attach Event Listeners
        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                openEditModal(this.dataset);
            });
        });

        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                deleteExpense(this.dataset.id);
            });
        });
    }

    function openEditModal(data) {
        document.getElementById('editExpenseId').value = data.id;
        document.getElementById('editExpenseDate').value = data.date;
        document.getElementById('editExpenseCategory').value = data.category;
        document.getElementById('editExpenseDesc').value = data.desc;
        document.getElementById('editExpenseAmount').value = data.amount;
        document.getElementById('editExpenseSourceType').value = data.source || 'Cash';
        
        const modal = new bootstrap.Modal(document.getElementById('editExpenseModal'));
        modal.show();
    }

    function fetchDashboardStats() {
        fetch('api/dashboard.php?t=' + new Date().getTime())
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('totalExpenses').textContent = formatCurrency(data.total_expenses);
                    document.getElementById('remainingBalance').textContent = formatCurrency(data.balance);
                }
            })
            .catch(error => console.error('Error fetching stats:', error));
    }

    // --- Category Actions ---

    // Add Category
    const addCategoryForm = document.getElementById('addCategoryForm');
    if (addCategoryForm) {
        addCategoryForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const name = document.getElementById('newCategoryName').value;
            const formData = new FormData();
            formData.append('action', 'add');
            formData.append('name', name);

            fetch('api/categories.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(result => {
                    if (result.success) {
                        document.getElementById('newCategoryName').value = '';
                        fetchCategories();
                    } else {
                        showAlert(result.message, 'danger');
                    }
                });
        });
    }

    // Delete Category
    function deleteCategory(id) {
        if (!confirm('Are you sure you want to delete this category?')) return;
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', id);

        fetch('api/categories.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(result => {
                if (result.success) {
                    fetchCategories();
                } else {
                    showAlert(result.message, 'danger');
                }
            });
    }

    // --- AI Action Listener ---
    window.addEventListener('aiActionCompleted', function(e) {
        console.log('AI Action detected in expenses:', e.detail.actionType);
        fetchExpenses();
        if (e.detail.actionType === 'add_category') {
            fetchCategories();
        }
    });

    window.addEventListener('storage', function(e) {
        if (e.key === 'budget_tracker_ai_action') {
            fetchExpenses();
            const actionData = JSON.parse(e.newValue);
            if (actionData.type === 'add_category') {
                fetchCategories();
            }
        }
    });

    // --- Form Submissions ---

    // Add Expense
    expenseForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData();
        formData.append('action', 'add');
        formData.append('date', document.getElementById('expenseDate').value);
        formData.append('category', document.getElementById('expenseCategory').value);
        formData.append('description', document.getElementById('expenseDesc').value);
        formData.append('amount', document.getElementById('expenseAmount').value);
        formData.append('source_type', document.getElementById('expenseSourceType').value);

        fetch('api/expenses.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                showAlert(result.message, 'success');
                expenseForm.reset();
                document.getElementById('expenseDate').value = new Date().toISOString().split('T')[0];
                bootstrap.Modal.getOrCreateInstance(document.getElementById('addExpenseModal')).hide();
                fetchExpenses();
                fetchDashboardStats();
            } else {
                showAlert(result.message, 'danger');
            }
        })
        .catch(error => console.error('Error adding:', error));
    });

    // Update Expense
    editExpenseForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData();
        formData.append('action', 'edit');
        formData.append('id', document.getElementById('editExpenseId').value);
        formData.append('date', document.getElementById('editExpenseDate').value);
        formData.append('category', document.getElementById('editExpenseCategory').value);
        formData.append('description', document.getElementById('editExpenseDesc').value);
        formData.append('amount', document.getElementById('editExpenseAmount').value);
        formData.append('source_type', document.getElementById('editExpenseSourceType').value);

        fetch('api/expenses.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(result => {
             if (result.success) {
                showAlert(result.message, 'success');
                bootstrap.Modal.getOrCreateInstance(document.getElementById('editExpenseModal')).hide();
                fetchExpenses();
                fetchDashboardStats();
            } else {
                showAlert(result.message, 'danger');
            }
        })
        .catch(error => console.error('Error updating:', error));
    });

    // Delete Expense
    function deleteExpense(id) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);

                fetch('api/expenses.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(result => {
                    if(result.success) {
                        Swal.fire(
                            'Deleted!',
                            result.message,
                            'success'
                        );
                        fetchExpenses();
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
});
</script>

<?php include 'includes/footer.php'; ?>