<?php
$pageTitle = 'Bills & Subscriptions';
$pageHeader = 'Bill Reminders';
$extraNavContent = '<button class="btn btn-primary rounded-circle shadow-sm p-0 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;" data-bs-toggle="modal" data-bs-target="#addBillModal">
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

        <!-- Statistics & Summaries -->
        <div class="row g-3 mb-4">
            <div class="col-md-4 stagger-item">
                <div class="card h-100 border-0 shadow-sm rounded-4">
                    <div class="card-body p-4">
                        <h6 class="text-secondary small text-uppercase fw-bold mb-3">Total Monthly Commitment</h6>
                        <h2 class="fw-bold text-dark mb-0" id="totalMonthlyBills"><?php echo CurrencyHelper::getSymbol($_SESSION['user_currency'] ?? 'PHP'); ?>0.00</h2>
                        <p class="small text-muted mt-2 mb-0">Calculated from active monthly subscriptions.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 stagger-item">
                <div class="card h-100 border-0 shadow-sm rounded-4 bg-primary text-white">
                    <div class="card-body p-4">
                        <h6 class="text-white text-opacity-75 small text-uppercase fw-bold mb-3">Upcoming (Next 7 Days)</h6>
                        <h2 class="fw-bold mb-0" id="upcomingBillsCount">0</h2>
                        <p class="small text-white text-opacity-75 mt-2 mb-0" id="upcomingBillsAmount">Total: <?php echo CurrencyHelper::getSymbol($_SESSION['user_currency'] ?? 'PHP'); ?>0.00</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 stagger-item">
                <div class="card h-100 border-0 shadow-sm rounded-4">
                    <div class="card-body p-4 d-flex align-items-center justify-content-center">
                        <div class="text-center">
                            <i class="fas fa-calendar-check fa-2x text-success mb-2"></i>
                            <h6 class="fw-bold mb-0">Financial Discipline</h6>
                            <p class="small text-muted mb-0">Never miss a payment.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-12">
                <h5 class="fw-bold mb-3">Active Subscriptions & Bills</h5>
                <div class="row g-3" id="billsList">
                    <!-- Bills will be dynamically inserted here -->
                </div>
                <div id="noBillsMessage" class="text-center py-5 d-none">
                    <div class="mb-3 text-muted opacity-25">
                        <i class="fas fa-file-invoice-dollar fa-5x"></i>
                    </div>
                    <p class="text-secondary mt-3">No recurring bills found. Start tracking by clicking the + button.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bill Details Modal -->
<div class="modal fade" id="billDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header border-0 p-4 pb-0">
                <h5 class="modal-title fw-bold" id="billDetailTitle">Bill Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="text-center mb-4">
                    <h2 class="display-6 fw-bold text-primary mb-1" id="billDetailAmount">₱0.00</h2>
                    <p class="text-muted small fw-bold text-uppercase" style="letter-spacing: 1px;">Recurring Amount</p>
                </div>

                <div class="row g-3">
                    <div class="col-6">
                        <div class="p-3 bg-light rounded-3">
                            <small class="text-muted d-block mb-1">Frequency</small>
                            <span class="fw-bold fs-6" id="billDetailFrequency">-</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 bg-light rounded-3">
                            <small class="text-muted d-block mb-1">Source</small>
                            <span class="fw-bold fs-6 text-primary" id="billDetailSource">-</span>
                        </div>
                    </div>
                </div>

                <div class="mt-3 p-3 bg-info-subtle rounded-3 d-flex align-items-center">
                    <div class="me-3">
                        <i class="fas fa-calendar-alt text-info fa-lg"></i>
                    </div>
                    <div>
                        <small class="text-info-emphasis d-block fw-bold small">Next Due Date</small>
                        <span class="fw-bold" id="billDetailDueDate">-</span>
                    </div>
                </div>

                <div class="mt-3 p-3 bg-light rounded-3">
                    <small class="text-muted d-block mb-1">Category</small>
                    <span class="fw-bold" id="billDetailCategory">-</span>
                </div>

                <hr class="my-4 opacity-10">

                <div class="d-grid gap-2">
                    <button class="btn btn-primary rounded-pill fw-bold py-2 edit-bill-from-detail">
                        <i class="fas fa-edit me-2"></i>Edit Details
                    </button>
                    <button class="btn btn-success rounded-pill fw-bold py-2 mark-paid-from-detail">
                        <i class="fas fa-check me-2"></i>Mark as Paid
                    </button>
                    <button class="btn btn-outline-secondary rounded-pill fw-bold py-2" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Bill Modal -->
<div class="modal fade" id="editBillModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header border-bottom-0 p-4 pb-0">
                <h5 class="modal-title fw-bold">Edit Bill</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 pt-4">
                <form id="editBillForm">
                    <input type="hidden" id="editBillId">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Title / Name</label>
                        <input type="text" class="form-control rounded-3" id="editBillTitle" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Amount</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><?php echo CurrencyHelper::getSymbol($_SESSION['user_currency'] ?? 'PHP'); ?></span>
                            <input type="number" class="form-control rounded-3 border-start-0" id="editBillAmount" step="0.01" min="0" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Next Due Date</label>
                            <input type="date" class="form-control rounded-3" id="editBillDueDate" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Frequency</label>
                            <select class="form-select rounded-3" id="editBillFrequency">
                                <option value="monthly">Monthly</option>
                                <option value="yearly">Yearly</option>
                                <option value="weekly">Weekly</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Category</label>
                            <select class="form-select rounded-3" id="editBillCategory">
                                <option value="Utilities">Utilities</option>
                                <option value="Entertainment">Entertainment</option>
                                <option value="Food">Food & Dining</option>
                                <option value="Transport">Transport</option>
                                <option value="Healthcare">Healthcare</option>
                                <option value="Housing">Housing</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Payment Method</label>
                            <select class="form-select rounded-3" id="editBillSourceType">
                                <option value="Cash">Cash</option>
                                <option value="GCash">GCash</option>
                                <option value="Maya">Maya</option>
                                <option value="Bank">Bank</option>
                            </select>
                        </div>
                    </div>
                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-primary rounded-pill py-2 fw-bold shadow-sm">Update Bill Tracker</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Add Bill Modal -->
<div class="modal fade" id="addBillModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header border-bottom-0 p-4 pb-0">
                <h5 class="modal-title fw-bold">Add Bill / Subscription</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 pt-3">
                <form id="addBillForm">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Title / Name</label>
                        <input type="text" class="form-control rounded-3" id="billTitle" placeholder="e.g. Netflix, Electricity" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Amount</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><?php echo CurrencyHelper::getSymbol($_SESSION['user_currency'] ?? 'PHP'); ?></span>
                            <input type="number" class="form-control rounded-3 border-start-0" id="billAmount" step="0.01" min="0.01" required placeholder="0.00">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Next Due Date</label>
                            <input type="date" class="form-control rounded-3" id="billDueDate" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Frequency</label>
                            <select class="form-select rounded-3" id="billFrequency">
                                <option value="monthly" selected>Monthly</option>
                                <option value="weekly">Weekly</option>
                                <option value="yearly">Yearly</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Category</label>
                            <select class="form-select rounded-3" id="billCategory">
                                <option value="Utilities" selected>Utilities</option>
                                <option value="Entertainment">Entertainment</option>
                                <option value="Food">Food &amp; Dining</option>
                                <option value="Transport">Transport</option>
                                <option value="Healthcare">Healthcare</option>
                                <option value="Housing">Housing</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Payment Method</label>
                            <select class="form-select rounded-3" id="billSourceType">
                                <option value="Cash" selected>Cash</option>
                                <option value="GCash">GCash</option>
                                <option value="Maya">Maya</option>
                                <option value="Bank">Bank</option>
                            </select>
                        </div>
                    </div>
                    <div class="d-grid mt-3">
                        <button type="submit" class="btn btn-primary rounded-pill py-2 fw-bold shadow-sm">
                            <i class="fas fa-plus me-2"></i>Add Bill
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const billsList = document.getElementById('billsList');
        const noBillsMessage = document.getElementById('noBillsMessage');
        const addBillForm = document.getElementById('addBillForm');
        let allBills = [];

        // Initial Load
        fetchBills();

        // Default Due Date to today
        if (document.getElementById('billDueDate')) {
            document.getElementById('billDueDate').value = new Date().toISOString().split('T')[0];
        }

        function renderBills(bills) {
            billsList.innerHTML = '';
            if (bills.length === 0) {
                noBillsMessage.classList.remove('d-none');
                return;
            }
            noBillsMessage.classList.add('d-none');

            bills.forEach(bill => {
                const daysLeft = getDaysLeft(bill.due_date);
                let statusBadge = '';
                let cardClass = '';

                if (daysLeft < 0) {
                    statusBadge = '<span class="badge bg-danger rounded-pill">Overdue</span>';
                    cardClass = 'border-danger';
                } else if (daysLeft <= 3) {
                    statusBadge = `<span class="badge bg-warning text-dark rounded-pill">Due in ${daysLeft} days</span>`;
                    cardClass = 'border-warning';
                } else {
                    statusBadge = `<span class="badge bg-light text-secondary border rounded-pill">Next: ${bill.due_date}</span>`;
                }

                const col = document.createElement('div');
                col.className = 'col-md-6 col-lg-4 stagger-item';
                col.innerHTML = `
                <div class="card h-100 border-0 shadow-sm rounded-4 p-3 bill-card ${cardClass}" onclick="showBillDetails(${bill.id})" style="cursor: pointer;">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h6 class="fw-bold mb-0">${bill.title}</h6>
                            <span class="text-muted extra-small text-uppercase fw-bold" style="font-size: 0.65rem;">${bill.category} • ${bill.frequency}</span>
                        </div>
                        <div class="dropdown" onclick="event.stopPropagation()">
                            <button class="btn btn-sm btn-light rounded-circle" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></button>
                            <ul class="dropdown-menu dropdown-menu-end border-0 shadow rounded-3">
                                <li><a class="dropdown-item edit-bill" href="#" data-id="${bill.id}"><i class="fas fa-edit me-2"></i>Edit</a></li>
                                <li><a class="dropdown-item text-danger delete-bill" href="#" data-id="${bill.id}"><i class="fas fa-trash me-2"></i>Remove</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="my-3">
                        <h4 class="fw-bold mb-1">${formatCurrency(bill.amount)}</h4>
                        ${statusBadge}
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-auto pt-2 border-top">
                        <div class="small text-muted">Via ${bill.source_type}</div>
                        <div class="text-end">
                            <span class="extra-small text-muted d-block" style="font-size: 0.65rem;">Last Payment</span>
                            <span class="small fw-bold text-success">${bill.last_paid_at ? 'Paid: ' + bill.last_paid_at : 'Never Paid'}</span>
                        </div>
                    </div>
                </div>
            `;
                billsList.appendChild(col);
            });

            // Event Listeners
            document.querySelectorAll('.edit-bill').forEach(btn => {
                btn.onclick = (e) => {
                    e.preventDefault();
                    openEditBillModal(btn.dataset.id);
                };
            });

            document.querySelectorAll('.delete-bill').forEach(btn => {
                btn.onclick = (e) => {
                    e.preventDefault();
                    deleteBill(btn.dataset.id);
                };
            });
        }

        function fetchBills() {
            fetch('<?php echo SITE_URL; ?>api/bills.php?action=list&t=' + new Date().getTime())
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        allBills = data.data;
                        renderBills(data.data);
                        updateStats(data.data);
                    }
                })
                .catch(err => console.error('Error:', err));
        }

        function updateStats(bills) {
            let totalMonthly = 0;
            let upcomingCount = 0;
            let upcomingAmount = 0;

            bills.forEach(bill => {
                if (bill.is_active) {
                    // Approximate monthly cost for stats
                    if (bill.frequency === 'monthly') totalMonthly += parseFloat(bill.amount);
                    else if (bill.frequency === 'weekly') totalMonthly += parseFloat(bill.amount) * 4;
                    else if (bill.frequency === 'yearly') totalMonthly += parseFloat(bill.amount) / 12;

                    const days = getDaysLeft(bill.due_date);
                    if (days >= 0 && days <= 7) {
                        upcomingCount++;
                        upcomingAmount += parseFloat(bill.amount);
                    }
                }
            });

            document.getElementById('totalMonthlyBills').textContent = formatCurrency(totalMonthly);
            document.getElementById('upcomingBillsCount').textContent = upcomingCount;
            document.getElementById('upcomingBillsAmount').textContent = 'Total: ' + formatCurrency(upcomingAmount);
        }

        function markBillPaid(id) {
            Swal.fire({
                title: 'Confirm Payment',
                text: 'This will record an expense and move the due date forward.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, Paid IT',
                confirmButtonColor: '#6366f1'
            }).then(result => {
                if (result.isConfirmed) {
                    fetch('<?php echo SITE_URL; ?>api/bills.php?action=pay', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                id: id
                            })
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire('Success', data.message, 'success');
                                fetchBills();
                                // Also trigger dashboard update if it's open (not applicable here directly)
                            } else {
                                Swal.fire('Error', data.message, 'error');
                            }
                        });
                }
            });
        }


        window.showBillDetails = function(id) {
            const bill = allBills.find(b => b.id == id);
            if (!bill) return;

            document.getElementById('billDetailTitle').textContent = bill.title;
            document.getElementById('billDetailAmount').textContent = formatCurrency(bill.amount);
            document.getElementById('billDetailFrequency').textContent = bill.frequency.charAt(0).toUpperCase() + bill.frequency.slice(1);
            document.getElementById('billDetailSource').textContent = bill.source_type;
            document.getElementById('billDetailDueDate').textContent = bill.due_date;
            document.getElementById('billDetailCategory').textContent = bill.category;

            const editBtn = document.querySelector('.edit-bill-from-detail');
            editBtn.onclick = () => {
                bootstrap.Modal.getInstance(document.getElementById('billDetailsModal')).hide();
                setTimeout(() => openEditBillModal(id), 400);
            };

            const payBtn = document.querySelector('.mark-paid-from-detail');
            payBtn.onclick = () => {
                bootstrap.Modal.getInstance(document.getElementById('billDetailsModal')).hide();
                markBillPaid(id);
            };

            new bootstrap.Modal(document.getElementById('billDetailsModal')).show();
        }

        function openEditBillModal(id) {
            const bill = allBills.find(b => b.id == id);
            if (!bill) return;

            document.getElementById('editBillId').value = bill.id;
            document.getElementById('editBillTitle').value = bill.title;
            document.getElementById('editBillAmount').value = bill.amount;
            document.getElementById('editBillDueDate').value = bill.due_date;
            document.getElementById('editBillFrequency').value = bill.frequency;
            document.getElementById('editBillCategory').value = bill.category;
            document.getElementById('editBillSourceType').value = bill.source_type;

            new bootstrap.Modal(document.getElementById('editBillModal')).show();
        }

        document.getElementById('editBillForm').onsubmit = function(e) {
            e.preventDefault();
            const payload = {
                id: document.getElementById('editBillId').value,
                title: document.getElementById('editBillTitle').value,
                amount: document.getElementById('editBillAmount').value,
                due_date: document.getElementById('editBillDueDate').value,
                frequency: document.getElementById('editBillFrequency').value,
                category: document.getElementById('editBillCategory').value,
                source_type: document.getElementById('editBillSourceType').value
            };

            fetch('<?php echo SITE_URL; ?>api/bills.php?action=edit', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        bootstrap.Modal.getInstance(document.getElementById('editBillModal')).hide();
                        fetchBills();
                        Swal.fire('Success', 'Bill updated successfully!', 'success');
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                });
        };

        function deleteBill(id) {
            Swal.fire({
                title: 'Remove Bill?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!',
                confirmButtonColor: '#d33'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('<?php echo SITE_URL; ?>api/bills.php?action=delete', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                id: id
                            })
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                fetchBills();
                            }
                        });
                }
            });
        }

        addBillForm.onsubmit = function(e) {
            e.preventDefault();
            const payload = {
                title: document.getElementById('billTitle').value,
                amount: document.getElementById('billAmount').value,
                due_date: document.getElementById('billDueDate').value,
                frequency: document.getElementById('billFrequency').value,
                category: document.getElementById('billCategory').value,
                source_type: document.getElementById('billSourceType').value
            };

            fetch('<?php echo SITE_URL; ?>api/bills.php?action=add', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        bootstrap.Modal.getInstance(document.getElementById('addBillModal')).hide();
                        addBillForm.reset();
                        fetchBills();
                        Swal.fire('Success', 'Bill tracking enabled!', 'success');
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                });
        };

        function getDaysLeft(dateStr) {
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const due = new Date(dateStr);
            due.setHours(0, 0, 0, 0);
            const diffTime = due - today;
            return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        }

        function formatCurrency(amount) {
            return new Intl.NumberFormat(window.userCurrency.locale, {
                style: 'currency',
                currency: window.userCurrency.code
            }).format(amount);
        }

        // --- Page Tutorial ---
        <?php if (!isset($seen_tutorials['bills.php'])): ?>

            function startTutorial() {
                if (window.seenTutorials && window.seenTutorials['bills.php']) return;

                const steps = [{
                        title: '📋 Bills & Subscriptions Hub',
                        text: 'This is where you manage all recurring payments — rent, Netflix, utilities, and more. Never miss a due date again!'
                    },
                    {
                        title: '📊 Stats at a Glance',
                        text: 'See your Total Monthly Commitment and how many bills are due in the next 7 days — all in one place.',
                        target: '.row.g-3.mb-4'
                    },
                    {
                        title: '💳 Your Bill Cards',
                        text: 'Each card shows the bill name, amount, due date, and status. Cards glow yellow when due soon and red when overdue.',
                        target: '#billsList'
                    },
                    {
                        title: '✅ Mark as Paid',
                        text: 'Hit "Mark as Paid" on a card to record the expense automatically. The system will also advance the due date based on the bill\'s frequency.'
                    },
                    {
                        title: '➕ Add a New Bill',
                        text: 'Click the + button in the top-right corner to add a new recurring bill or subscription.',
                        target: '[data-bs-target="#addBillModal"]'
                    },
                    {
                        title: '🔔 Deadline Alerts',
                        text: 'The system automatically notifies you 7, 3, and 1 day before each bill is due — and again on the due date. Check the bell icon 🔔 in the navbar.'
                    }
                ];

                if (!document.getElementById('tutorial-styles')) {
                    const style = document.createElement('style');
                    style.id = 'tutorial-styles';
                    style.textContent = `.tutorial-highlight { outline: 4px solid #6366f1; outline-offset: 4px; border-radius: 12px; transition: outline 0.3s ease; z-index: 9999; position: relative; }`;
                    document.head.appendChild(style);
                }

                function showStep(index) {
                    if (index >= steps.length) {
                        markPageTutorialSeen('bills.php');
                        return;
                    }
                    const step = steps[index];
                    Swal.fire({
                        title: step.title,
                        text: step.text,
                        icon: 'info',
                        confirmButtonText: index === steps.length - 1 ? '🎉 Got it!' : 'Next →',
                        confirmButtonColor: '#6366f1',
                        showCancelButton: true,
                        cancelButtonText: 'Skip Tour',
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
                                    setTimeout(() => el.classList.remove('tutorial-highlight'), 2500);
                                }
                            }
                        }
                    }).then((result) => {
                        if (result.isConfirmed) showStep(index + 1);
                        else if (result.dismiss === Swal.DismissReason.cancel) markPageTutorialSeen('bills.php');
                    });
                }

                showStep(0);
            }

            setTimeout(startTutorial, 1000);
        <?php endif; ?>
    });
</script>

<?php include '../includes/footer.php'; ?>