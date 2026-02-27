<?php
$pageTitle = 'Expenses';
$pageHeader = 'Expense Management';
$extraNavContent = '<button class="btn btn-danger rounded-circle shadow-sm p-0 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;" data-bs-toggle="modal" data-bs-target="#addExpenseModal">
    <i class="fas fa-plus fa-lg"></i>
</button>';

include '../includes/header.php';
?>

<?php include '../includes/sidebar.php'; ?>

<!-- Page Content -->
<div id="page-content-wrapper">

    <?php include '../includes/navbar.php'; ?>

    <div class="container-fluid px-4 py-4">
        <div id="alertContainer"></div>

        <!-- Statistics Cards -->
        <div class="row g-3 mb-4">
            <!-- Total Expenses -->
            <div class="col-md-6 stagger-item">
                <div class="card h-100 bg-gradient-danger text-white border-0">
                    <div class="card-body">
                        <h5 class="card-title text-opacity-75"><i class="fas fa-receipt me-2"></i>Total Expenses (<?php echo $_SESSION['user_currency'] ?? 'PHP'; ?>)</h5>
                        <h2 class="display-6 fw-bold mb-0" id="totalExpenses"><?php echo CurrencyHelper::getSymbol($_SESSION['user_currency'] ?? 'PHP'); ?>0.00</h2>
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

        <div class="row mb-3 align-items-center">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 rounded-pill-start ps-3">
                        <i class="fas fa-search text-secondary"></i>
                    </span>
                    <input type="text" id="expenseSearch" class="form-control border-start-0 rounded-pill-end py-2" placeholder="Search Date, Category, Description...">
                </div>
            </div>
            <div class="col-md-8 text-end">
                <button class="btn btn-outline-secondary rounded-pill px-3 fw-bold small" data-bs-toggle="modal" data-bs-target="#budgetLimitsModal">
                    <i class="fas fa-sliders-h me-2"></i>Budget Limits
                </button>
            </div>
        </div>

        <!-- Budget Limits Overview -->
        <div id="budgetLimitsOverview" class="mb-4"></div>

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
                                <th class="border-0 py-3 text-secondary small text-uppercase fw-bold text-center">Payment Method</th>
                                <th class="border-0 py-3 text-secondary small text-uppercase fw-bold text-center">Expense Source</th>
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
                    <input type="hidden" id="expenseGroupId" name="group_id" value="">
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
                        <div class="mt-2 d-none" id="newCategoryInputContainer">
                            <input type="text" class="form-control rounded-3" id="expenseNewCategory" placeholder="Enter new category name">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Description</label>
                        <input type="text" class="form-control rounded-3" id="expenseDesc" placeholder="e.g. Lunch" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Amount</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><?php echo CurrencyHelper::getSymbol($_SESSION['user_currency'] ?? 'PHP'); ?></span>
                            <input type="number" class="form-control rounded-3 border-start-0" id="expenseAmount" step="0.01" min="0" required placeholder="0.00">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Payment Method</label>
                            <select class="form-select rounded-3" id="expenseSourceType">
                                <option value="Cash" selected>Cash</option>
                                <option value="GCash">GCash</option>
                                <option value="Maya">Maya</option>
                                <option value="Bank">Bank</option>
                                <option value="Electronic">Other Electronic</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Expense Source</label>
                            <select class="form-select rounded-3" id="expenseSource">
                                <option value="Allowance" selected>Allowance</option>
                                <option value="Savings">Savings</option>
                            </select>
                        </div>
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
                    <input type="hidden" id="editExpenseGroupId" name="group_id" value="">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Date</label>
                        <input type="date" class="form-control rounded-3" id="editExpenseDate" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Category</label>
                        <select class="form-select rounded-3 category-select" id="editExpenseCategory" required>
                            <!-- Dynamic Content -->
                        </select>
                        <div class="mt-2 d-none" id="editNewCategoryInputContainer">
                            <input type="text" class="form-control rounded-3" id="editExpenseNewCategory" placeholder="Enter new category name">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Description</label>
                        <input type="text" class="form-control rounded-3" id="editExpenseDesc" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Amount</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><?php echo CurrencyHelper::getSymbol($_SESSION['user_currency'] ?? 'PHP'); ?></span>
                            <input type="number" class="form-control rounded-3 border-start-0" id="editExpenseAmount" step="0.01" min="0" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Payment Method</label>
                            <select class="form-select rounded-3" id="editExpenseSourceType">
                                <option value="Cash">Cash</option>
                                <option value="GCash">GCash</option>
                                <option value="Maya">Maya</option>
                                <option value="Bank">Bank</option>
                                <option value="Electronic">Other Electronic</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Expense Source</label>
                            <select class="form-select rounded-3" id="editExpenseSource">
                                <option value="Allowance">Allowance</option>
                                <option value="Savings">Savings</option>
                            </select>
                        </div>
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
                <div class="d-flex justify-content-between align-items-center mb-0">
                    <h5 class="modal-title fw-bold">Manage Categories</h5>
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal" data-bs-target="#addExpenseModal" data-bs-toggle="modal">
                        <i class="fas fa-arrow-left me-1"></i> Back
                    </button>
                </div>
                <button type="button" class="btn-close d-none" data-bs-dismiss="modal"></button>
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

<!-- Budget Limits Modal -->
<div class="modal fade" id="budgetLimitsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header border-0 p-4 pb-0">
                <h5 class="modal-title fw-bold"><i class="fas fa-sliders-h me-2 text-secondary"></i>Budget Limits per Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted small mb-3">Set a monthly spending cap per category. The expenses page will show progress bars indicating how close you are to each limit.</p>

                <!-- Manual Set Form -->
                <form id="budgetLimitForm" class="d-flex gap-2 mb-4" novalidate>
                    <select id="limitCategory" class="form-select rounded-3" required></select>
                    <input type="number" id="limitAmount" class="form-control rounded-3" style="max-width:140px;" placeholder="Amount" step="0.01" min="0.01" required>
                    <button type="submit" class="btn btn-dark rounded-pill px-4 fw-bold">Set</button>
                </form>

                <!-- AI Suggest Section -->
                <div class="rounded-4 p-4 mb-4" style="background: linear-gradient(135deg, #f5f3ff 0%, #ede9fe 100%); border: 1.5px solid #c4b5fd;">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#a855f7);display:flex;align-items:center;justify-content:center;">
                            <i class="fas fa-robot text-white" style="font-size:0.85rem;"></i>
                        </div>
                        <div>
                            <div class="fw-bold small text-dark">AI Budget Planner</div>
                            <div class="text-muted" style="font-size:0.72rem;">Enter your total allowance and let AI suggest limits for each category</div>
                        </div>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <div class="input-group" style="max-width:220px;">
                            <span class="input-group-text bg-white border-end-0"><?php echo CurrencyHelper::getSymbol($_SESSION['user_currency'] ?? 'PHP'); ?></span>
                            <input type="number" id="aiAllowanceInput" class="form-control border-start-0" placeholder="Your allowance" step="0.01" min="1">
                        </div>
                        <button type="button" id="aiSuggestBtn" class="btn btn-sm rounded-pill px-4 fw-bold text-white" style="background:linear-gradient(135deg,#6366f1,#a855f7);border:none;">
                            <i class="fas fa-wand-magic-sparkles me-1"></i>AI Suggest
                        </button>
                    </div>
                    <!-- Suggestion Results -->
                    <div id="aiSuggestions" class="mt-3 d-none">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="small fw-bold text-dark">Suggested Limits</div>
                            <button type="button" id="applyAllLimits" class="btn btn-sm btn-success rounded-pill px-3 fw-bold">
                                <i class="fas fa-check-double me-1"></i>Apply All
                            </button>
                        </div>
                        <div id="suggestionList" class="d-flex flex-column gap-2"></div>
                        <div class="text-muted mt-2" style="font-size:0.72rem;"><i class="fas fa-info-circle me-1"></i>Click Apply All to set all limits at once, or apply individually.</div>
                    </div>
                </div>

                <div id="existingLimitsList"></div>
            </div>
        </div>
    </div>
</div>


<script>
    // Global State & UI References
    let expenseTableBody, expenseForm, editExpenseForm;
    window.currentExpenses = []; // Global storage for fetched data

    // URL Context
    const urlParams = new URLSearchParams(window.location.search);
    const activeGroupId = urlParams.get('group_id');

    // --- Core Functions (Global Scope) ---

    function fetchCategories() {
        const baseUrl = window.SITE_URL || '';
        fetch(baseUrl + 'api/categories.php')
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
            // Add "Add New..." option
            const addNewOption = document.createElement('option');
            addNewOption.value = 'ADD_NEW';
            addNewOption.textContent = '+ Add New Category...';
            addNewOption.classList.add('fw-bold', 'text-primary');
            select.appendChild(addNewOption);

            // Auto-select the newly added category if it exists
            if (window.lastAddedCategory) {
                select.value = window.lastAddedCategory;
                // Also hide the "New Category" input container if it was open
                const containerId = select.id === 'expenseCategory' ? 'newCategoryInputContainer' : 'editNewCategoryInputContainer';
                const container = document.getElementById(containerId);
                if (container) container.classList.add('d-none');
            } else if (currentVal) {
                select.value = currentVal;
            }
        });
        // Clear it after one use to prevent unwanted overrides later
        window.lastAddedCategory = null;
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
        const baseUrl = window.SITE_URL || '';
        const query = activeGroupId ? `&group_id=${activeGroupId}` : '';
        fetch(baseUrl + 'api/expenses.php?t=' + new Date().getTime() + query)
            .then(response => response.json())
            .then(data => {
                if (data.success && Array.isArray(data.data)) {
                    window.currentExpenses = data.data;
                    renderTable(data.data);
                } else {
                    console.error('Invalid data format:', data);
                }
            })
            .catch(error => console.error('Error fetching expenses:', error));
    }

    function formatCurrency(amount) {
        const val = parseFloat(amount) || 0;
        return new Intl.NumberFormat(window.userCurrency.locale, {
            style: 'currency',
            currency: window.userCurrency.code
        }).format(val);
    }

    function fetchDashboardStats() {
        const baseUrl = window.SITE_URL || '';
        const query = activeGroupId ? `&group_id=${activeGroupId}` : '';
        fetch(baseUrl + 'api/dashboard.php?t=' + new Date().getTime() + query)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const totalEl = document.getElementById('totalExpenses');
                    const balanceEl = document.getElementById('remainingBalance');
                    if (totalEl) totalEl.textContent = formatCurrency(data.total_expenses);
                    if (balanceEl) balanceEl.textContent = formatCurrency(data.balance);
                }
            })
            .catch(error => console.error('Error fetching stats:', error));
    }

    function showAlert(message, type) {
        Swal.fire({
            icon: type === 'danger' ? 'error' : 'success',
            title: type === 'danger' ? 'Error' : 'Success',
            text: message,
            showConfirmButton: false,
            confirmButtonColor: '#6366f1',
            timer: 2000
        });
    }

    function renderTable(expenses) {
        if (!expenseTableBody) return;

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
                <td class="text-center"><span class="badge ${item.expense_source === 'Savings' ? 'bg-success-subtle text-success' : item.expense_source === 'Allowance' ? 'bg-primary-subtle text-primary' : 'bg-secondary-subtle text-secondary'} rounded-pill small">${item.expense_source}</span></td>
                <td class="text-end">
                    <button class="btn btn-sm btn-light text-primary me-1 rounded-circle" onclick="editExpense(${item.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-light text-danger rounded-circle" onclick="deleteExpense(${item.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
            expenseTableBody.appendChild(row);
        });

        // Re-initialize DataTable
        const table = $('#expensesTable').DataTable({
            responsive: true,
            order: [
                [0, 'desc']
            ],
            searching: true,
            dom: "<'row'<'col-sm-12'tr>><'row pagination-container'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>"
        });

        const searchInput = document.getElementById('expenseSearch');
        if (searchInput) {
            searchInput.addEventListener('keyup', function() {
                table.search(this.value).draw();
            });
        }
    }

    function editExpense(id) {
        const item = window.currentExpenses.find(e => e.id == id);
        if (!item) return;

        document.getElementById('editExpenseId').value = item.id;
        document.getElementById('editExpenseDate').value = item.date;
        document.getElementById('editExpenseCategory').value = item.category;
        document.getElementById('editExpenseDesc').value = item.description;
        document.getElementById('editExpenseAmount').value = item.amount;
        document.getElementById('editExpenseSourceType').value = item.source_type || 'Cash';
        document.getElementById('editExpenseSource').value = item.expense_source || 'Allowance';
        document.getElementById('editExpenseGroupId').value = item.group_id || '';

        const modal = new bootstrap.Modal(document.getElementById('editExpenseModal'));
        modal.show();
    }

    function deleteExpense(id) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!',
            confirmButtonColor: '#6366f1',
            cancelButtonColor: '#d33'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);

                const baseUrl = window.SITE_URL || '';
                fetch(baseUrl + 'api/expenses.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            Swal.fire({
                                title: 'Deleted!',
                                text: result.message,
                                icon: 'success',
                                confirmButtonColor: '#6366f1'
                            });
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

    function deleteCategory(id) {
        if (!confirm('Are you sure you want to delete this category?')) return;
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', id);

        const baseUrl = window.SITE_URL || '';
        fetch(baseUrl + 'api/categories.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(result => {
                if (result.success) {
                    fetchCategories();
                } else {
                    showAlert(result.message, 'danger');
                }
            });
    }

    function loadBudgetLimits() {
        const baseUrl = window.SITE_URL || '';
        fetch(baseUrl + 'api/category_limits.php')
            .then(r => r.json())
            .then(d => {
                if (!d.success || !d.data.length) {
                    const overview = document.getElementById('budgetLimitsOverview');
                    if (overview) overview.innerHTML = '';
                    return;
                }
                const items = d.data.map(lim => {
                    const pct = lim.limit_amount > 0 ? Math.min(100, (lim.spent / lim.limit_amount) * 100) : 0;
                    const bar = pct >= 90 ? 'bg-danger' : (pct >= 70 ? 'bg-warning' : 'bg-success');
                    const sym = window.userCurrency ? (window.userCurrency.code === 'PHP' ? '₱' : '$') : '₱';
                    return `
                        <div class="d-flex flex-column flex-md-row align-items-md-center gap-2 mb-2">
                            <div style="min-width:130px;"><span class="fw-semibold small">${lim.category}</span></div>
                            <div class="flex-grow-1">
                                <div class="progress" style="height:8px;">
                                    <div class="progress-bar ${bar}" style="width:${pct.toFixed(1)}%"></div>
                                </div>
                            </div>
                            <div class="small text-muted text-nowrap">${sym}${parseFloat(lim.spent).toLocaleString() } / ${sym}${parseFloat(lim.limit_amount).toLocaleString()}</div>
                        </div>`;
                }).join('');

                const overview = document.getElementById('budgetLimitsOverview');
                if (overview) {
                    overview.innerHTML = `
                        <div class="card border-0 shadow-sm rounded-4 p-4 mb-2">
                            <h6 class="fw-bold mb-3 text-secondary small text-uppercase"><i class="fas fa-sliders-h me-2"></i>Monthly Budget Limits</h6>
                            ${items}
                        </div>`;
                }

                // Render in Modal list
                const list = document.getElementById('existingLimitsList');
                if (list) {
                    const sym = window.userCurrency ? (window.userCurrency.code === 'PHP' ? '₱' : '$') : '₱';
                    list.innerHTML = '<h6 class="fw-bold small text-secondary mb-2">Existing Limits</h6>' +
                        d.data.map(lim => `
                        <div class="d-flex align-items-center justify-content-between bg-light rounded-3 px-3 py-2 mb-2">
                            <span class="small fw-semibold">${lim.category}</span>
                            <span class="small text-muted">${sym}${parseFloat(lim.limit_amount).toLocaleString()}</span>
                            <button class="btn btn-sm btn-outline-danger rounded-pill px-2 py-0 delete-limit-btn" data-cat="${lim.category}">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>`).join('');
                    list.querySelectorAll('.delete-limit-btn').forEach(btn => {
                        btn.addEventListener('click', () => {
                            fetch(baseUrl + 'api/category_limits.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({
                                    action: 'delete',
                                    category: btn.dataset.cat
                                })
                            }).then(r => r.json()).then(d => {
                                if (d.success) loadBudgetLimits();
                            });
                        });
                    });
                }
            });
    }

    function saveSingleLimit(category, amount) {
        const baseUrl = window.SITE_URL || '';
        return fetch(baseUrl + 'api/category_limits.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'save',
                category: category,
                limit_amount: amount
            })
        }).then(r => r.json());
    }

    function startTutorial() {
        if (window.seenTutorials && window.seenTutorials['expenses.php']) return;

        const steps = [{
                title: 'Manage Your Expenses',
                text: 'This page helps you track every cent you spend. Let\'s see how it works.',
                icon: 'info',
                confirmButtonText: 'Show Me'
            },
            {
                title: 'Expense Stats',
                text: 'Quickly see your total spending and remaining balance here.',
                icon: 'receipt',
                confirmButtonText: 'Next',
                target: '.row.g-3.mb-4'
            },
            {
                title: 'Search & Filter',
                text: 'Easily find specific transactions by searching for category, date, or description.',
                icon: 'search',
                confirmButtonText: 'Next',
                target: '#expenseSearch'
            },
            {
                title: 'Add New Expense',
                text: 'Click this button to record a new expense. You can categorize them and even select the payment source.',
                icon: 'plus',
                confirmButtonText: 'Finish',
                target: '[data-bs-target="#addExpenseModal"]'
            }
        ];

        function showStep(index) {
            if (index >= steps.length) {
                if (typeof markPageTutorialSeen === 'function') markPageTutorialSeen('expenses.php');
                return;
            }

            const step = steps[index];
            Swal.fire({
                title: step.title,
                text: step.text,
                icon: 'info',
                confirmButtonText: step.confirmButtonText,
                confirmButtonColor: '#6366f1',
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
                else if (result.dismiss === Swal.DismissReason.cancel) {
                    if (typeof markPageTutorialSeen === 'function') markPageTutorialSeen('expenses.php');
                }
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

    document.addEventListener('DOMContentLoaded', function() {
        expenseTableBody = document.getElementById('expenseTableBody');
        expenseForm = document.getElementById('expenseForm');
        editExpenseForm = document.getElementById('editExpenseForm');

        // Initial Load
        if (activeGroupId) {
            const gidInput = document.getElementById('expenseGroupId');
            if (gidInput) gidInput.value = activeGroupId;
        }
        fetchCategories();
        fetchExpenses();
        fetchDashboardStats();
        loadBudgetLimits();

        // Setup Date Picker Default
        const dateInputs = ['expenseDate', 'editExpenseDate'];
        dateInputs.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = new Date().toISOString().split('T')[0];
        });

        // --- Page Context Listeners ---

        // Handle "Add New..." Category Selection
        document.querySelectorAll('.category-select').forEach(select => {
            select.addEventListener('change', function() {
                const isAdd = this.id === 'expenseCategory';
                const containerId = isAdd ? 'newCategoryInputContainer' : 'editNewCategoryInputContainer';
                const container = document.getElementById(containerId);

                if (this.value === 'ADD_NEW') {
                    container.classList.remove('d-none');
                    const input = container.querySelector('input');
                    if (input) input.focus();
                } else {
                    container.classList.add('d-none');
                }
            });
        });



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

            const categorySelect = document.getElementById('expenseCategory');
            let category = categorySelect.value;
            const newCategory = document.getElementById('expenseNewCategory').value.trim();

            if (category === 'ADD_NEW') {
                if (!newCategory) {
                    showAlert('Please enter a category name', 'danger');
                    return;
                }
                category = newCategory;
            }

            const formData = new FormData();
            formData.append('action', 'add');
            formData.append('date', document.getElementById('expenseDate').value);
            formData.append('category', category);
            formData.append('description', document.getElementById('expenseDesc').value);
            formData.append('amount', document.getElementById('expenseAmount').value);
            formData.append('source_type', document.getElementById('expenseSourceType').value);
            formData.append('expense_source', document.getElementById('expenseSource').value);
            if (activeGroupId) {
                formData.append('group_id', activeGroupId);
            }

            fetch('<?php echo SITE_URL; ?>api/expenses.php', {
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

        // Add Category
        const addCategoryForm = document.getElementById('addCategoryForm');
        if (addCategoryForm) {
            addCategoryForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const nameInput = document.getElementById('newCategoryName');
                const name = nameInput.value.trim();
                if (!name) return;

                const formData = new FormData();
                formData.append('action', 'add');
                formData.append('name', name);

                const baseUrl = window.SITE_URL || '';
                fetch(baseUrl + 'api/categories.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(result => {
                        if (result.success) {
                            showAlert(result.message, 'success');
                            nameInput.value = '';
                            fetchCategories();

                            // Return to Add Expense Modal
                            const manageModalEl = document.getElementById('manageCategoriesModal');
                            const addModalEl = document.getElementById('addExpenseModal');

                            const manageModal = bootstrap.Modal.getInstance(manageModalEl);
                            if (manageModal) manageModal.hide();

                            // Wait for hide to finish then show add expense modal
                            manageModalEl.addEventListener('hidden.bs.modal', function handler() {
                                const addModal = new bootstrap.Modal(addModalEl);
                                addModal.show();
                                manageModalEl.removeEventListener('hidden.bs.modal', handler);
                            }, {
                                once: true
                            });

                        } else {
                            showAlert(result.message, 'danger');
                        }
                    })
                    .catch(error => console.error('Error adding category:', error));
            });
        }

        // Update Expense
        editExpenseForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const categorySelect = document.getElementById('editExpenseCategory');
            let category = categorySelect.value;
            const newCategory = document.getElementById('editExpenseNewCategory').value.trim();

            if (category === 'ADD_NEW') {
                if (!newCategory) {
                    showAlert('Please enter a category name', 'danger');
                    return;
                }
                category = newCategory;
            }

            const formData = new FormData();
            formData.append('action', 'edit');
            formData.append('id', document.getElementById('editExpenseId').value);
            formData.append('date', document.getElementById('editExpenseDate').value);
            formData.append('category', category);
            formData.append('description', document.getElementById('editExpenseDesc').value);
            formData.append('amount', document.getElementById('editExpenseAmount').value);
            formData.append('source_type', document.getElementById('editExpenseSourceType').value);
            formData.append('expense_source', document.getElementById('editExpenseSource').value);

            fetch('<?php echo SITE_URL; ?>api/expenses.php', {
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

        <?php if (!isset($seen_tutorials['expenses.php'])): ?>
            setTimeout(startTutorial, 1000);
        <?php endif; ?>

        // Populate category select in Budget Limits Modal from existing categories
        document.getElementById('budgetLimitsModal').addEventListener('show.bs.modal', () => {
            // First: immediately populate from the already-loaded expense category select
            const expenseSel = document.getElementById('expenseCategory');
            const limitSel = document.getElementById('limitCategory');
            if (limitSel && expenseSel) {
                const opts = Array.from(expenseSel.options)
                    .filter(o => o.value && o.value !== 'ADD_NEW' && o.value !== '')
                    .map(o => `<option value="${o.value}">${o.text}</option>`)
                    .join('');
                if (opts) limitSel.innerHTML = opts;
            }

            // Then: also re-fetch from API to stay up-to-date (e.g. after adding new category)
            const baseUrl = window.SITE_URL || '';
            fetch(baseUrl + 'api/categories.php')
                .then(r => r.json())
                .then(d => {
                    if (limitSel && d.success && d.data && d.data.length) {
                        limitSel.innerHTML = d.data.map(c => `<option value="${c.name}">${c.name}</option>`).join('');
                    }
                })
                .catch(err => console.error('Budget limit category fetch failed:', err));

            loadBudgetLimits();
        });

        document.getElementById('budgetLimitForm').addEventListener('submit', e => {
            e.preventDefault();
            const cat = document.getElementById('limitCategory').value;
            const amt = parseFloat(document.getElementById('limitAmount').value);

            if (!cat) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Select a Category',
                    text: 'Please choose a category first.',
                    timer: 1800,
                    showConfirmButton: false
                });
                return;
            }
            if (!amt || amt <= 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Invalid Amount',
                    text: 'Please enter a positive amount greater than 0.',
                    timer: 1800,
                    showConfirmButton: false
                });
                return;
            }

            const baseUrl = window.SITE_URL || '';
            fetch(baseUrl + 'api/category_limits.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'save',
                    category: cat,
                    limit_amount: amt
                })
            }).then(r => r.json()).then(d => {
                if (d.success) {
                    document.getElementById('limitAmount').value = '';
                    loadBudgetLimits();
                    Swal.fire({
                        icon: 'success',
                        title: 'Limit Saved!',
                        timer: 1200,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: d.message,
                        confirmButtonColor: '#6366f1'
                    });
                }
            }).catch(() => Swal.fire('Error', 'Could not connect to the server.', 'error'));
        });

        // ── AI Budget Planner ──────────────────────────────────────────────────────
        const currencySymbol = '<?php echo CurrencyHelper::getSymbol($_SESSION["user_currency"] ?? "PHP"); ?>';


        document.getElementById('aiSuggestBtn').addEventListener('click', () => {
            const allowance = parseFloat(document.getElementById('aiAllowanceInput').value);
            if (!allowance || allowance <= 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Enter Allowance',
                    text: 'Please enter your total monthly allowance amount.',
                    timer: 2000,
                    showConfirmButton: false
                });
                return;
            }

            const btn = document.getElementById('aiSuggestBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Generating…';

            const baseUrl = window.SITE_URL || '';
            fetch(baseUrl + 'api/ai_budget_plan.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        allowance: allowance
                    })
                })
                .then(r => r.json())
                .then(d => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-wand-magic-sparkles me-1"></i>AI Suggest';

                    if (!d.success) {
                        Swal.fire({
                            icon: 'error',
                            title: 'AI Error',
                            text: d.message || 'Could not generate a plan.',
                            confirmButtonColor: '#6366f1'
                        });
                        return;
                    }

                    // Store suggestions for Apply All
                    window._aiSuggestions = d.suggestions;

                    // Render suggestion rows
                    const list = document.getElementById('suggestionList');
                    list.innerHTML = d.suggestions.map((s, i) => `
                <div class="d-flex align-items-center justify-content-between bg-white rounded-3 px-3 py-2 shadow-sm" style="border:1px solid #e9d8fd;">
                    <div class="d-flex flex-column" style="min-width:0;flex:1;">
                        <span class="small fw-semibold text-dark text-truncate">${s.category}</span>
                        <span class="text-muted" style="font-size:0.7rem;">${s.reason}</span>
                    </div>
                    <div class="d-flex align-items-center gap-2 ms-2">
                        <span class="fw-bold small" style="color:#6366f1;">${currencySymbol}${parseFloat(s.amount).toLocaleString('en-PH',{minimumFractionDigits:2})}</span>
                        <button type="button" class="btn btn-sm rounded-pill fw-bold apply-single-limit px-3" data-idx="${i}" style="background:linear-gradient(135deg,#6366f1,#a855f7);color:#fff;border:none;font-size:0.75rem;">Apply</button>
                    </div>
                </div>`).join('');

                    // Individual apply handlers
                    list.querySelectorAll('.apply-single-limit').forEach(btn => {
                        btn.addEventListener('click', function() {
                            const s = window._aiSuggestions[this.dataset.idx];
                            saveSingleLimit(s.category, s.amount).then(res => {
                                if (res.success) {
                                    this.textContent = '✓';
                                    this.disabled = true;
                                    this.style.background = '#22c55e';
                                    loadBudgetLimits();
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: res.message,
                                        confirmButtonColor: '#6366f1'
                                    });
                                }
                            });
                        });
                    });

                    document.getElementById('aiSuggestions').classList.remove('d-none');
                })
                .catch(() => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-wand-magic-sparkles me-1"></i>AI Suggest';
                    Swal.fire({
                        icon: 'error',
                        title: 'Connection Error',
                        text: 'Could not reach the AI service. Please try again.',
                        confirmButtonColor: '#6366f1'
                    });
                });
        });

        document.getElementById('applyAllLimits').addEventListener('click', function() {
            const suggestions = window._aiSuggestions;
            if (!suggestions || !suggestions.length) return;

            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Applying…';
            const me = this;

            Promise.all(suggestions.map(s => saveSingleLimit(s.category, s.amount)))
                .then(results => {
                    const failed = results.filter(r => !r.success);
                    me.disabled = false;
                    me.innerHTML = '<i class="fas fa-check-double me-1"></i>Applied!';
                    me.style.background = '#22c55e';
                    // Mark all rows as applied
                    document.querySelectorAll('.apply-single-limit').forEach(b => {
                        b.textContent = '✓';
                        b.disabled = true;
                        b.style.background = '#22c55e';
                    });
                    loadBudgetLimits();
                    if (failed.length === 0) {
                        Swal.fire({
                            icon: 'success',
                            title: 'All Limits Applied!',
                            text: `${suggestions.length} category limits saved successfully.`,
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Partial Success',
                            text: `${suggestions.length - failed.length} of ${suggestions.length} limits saved.`
                        });
                    }
                })
                .catch(() => {
                    me.disabled = false;
                    me.innerHTML = '<i class="fas fa-check-double me-1"></i>Apply All';
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Some limits could not be saved.',
                        confirmButtonColor: '#6366f1'
                    });
                });
        });

        loadBudgetLimits();
    });
</script>

<?php include '../includes/footer.php'; ?>