<?php
$pageTitle = 'Dashboard';
$pageHeader = 'Dashboard Overview';
include 'includes/header.php';
?>

    <?php include 'includes/sidebar.php'; ?>

    <!-- Page Content -->
    <div id="page-content-wrapper">

        <?php include 'includes/navbar.php'; ?>

        <div class="container-fluid px-4 py-4">
            
            <!-- Personalized Greeting -->
            <div class="mb-4 fade-up">
                <h4 class="fw-bold mb-1">Hello, <span class="text-primary"><?php echo htmlspecialchars($_SESSION['first_name'] ?? 'Guest'); ?></span>!</h4>
                <p class="text-secondary small mb-0">Here's your financial status for <?php echo date('F Y'); ?>.</p>
            </div>

            <!-- Alerts/Notifications -->
            <div id="alertContainer"></div>

            <!-- Dashboard Content -->
            <div class="row g-4 mb-4">
                <!-- Total Allowance Card -->
                <div class="col-md-4">
                    <div class="card h-100 bg-gradient-primary text-white">
                        <div class="card-body">
                            <h5 class="card-title text-opacity-75"><i
                                    class="fas fa-hand-holding-dollar me-2"></i>Total Allowance</h5>
                            <h2 class="display-5 fw-bold" id="dashTotalAllowance">₱0.00</h2>
                        </div>
                    </div>
                </div>
                <!-- Total Expenses Card -->
                <div class="col-md-4">
                    <div class="card h-100 bg-gradient-danger text-white">
                        <div class="card-body">
                            <h5 class="card-title text-opacity-75"><i class="fas fa-receipt me-2"></i>Total Expenses
                            </h5>
                            <h2 class="display-5 fw-bold" id="dashTotalExpenses"><?php echo CurrencyHelper::getSymbol($_SESSION['user_currency'] ?? 'PHP'); ?>0.00</h2>
                        </div>
                    </div>
                </div>
                <!-- Remaining Balance Card -->
                <div class="col-md-4">
                    <div class="card h-100 bg-gradient-success text-white">
                        <div class="card-body">
                            <h5 class="card-title text-opacity-75"><i class="fas fa-piggy-bank me-2"></i>Remaining Balance</h5>
                            <h2 class="display-5 fw-bold" id="dashBalance"><?php echo CurrencyHelper::getSymbol($_SESSION['user_currency'] ?? 'PHP'); ?>0.00</h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- New Row for Specific Balances -->
            <div class="row g-4 mb-4">
                <!-- Cash Balance Card -->
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm rounded-4 bg-light overflow-hidden transition-all hover-lift">
                        <div class="card-body d-flex align-items-center p-4">
                            <div class="rounded-circle bg-secondary-subtle p-3 me-3 text-secondary shadow-sm">
                                <i class="fas fa-wallet fa-xl"></i>
                            </div>
                            <div>
                                <h6 class="text-secondary small fw-bold text-uppercase mb-1" style="font-size: 0.65rem;">Cash Balance</h6>
                                <h4 class="fw-bold mb-0" id="dashCashBalance"><?php echo CurrencyHelper::getSymbol($_SESSION['user_currency'] ?? 'PHP'); ?>0.00</h4>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Bank/E-Wallet Balance Card -->
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm rounded-4 bg-light overflow-hidden transition-all hover-lift">
                        <div class="card-body d-flex align-items-center p-4">
                            <div class="rounded-circle bg-primary-subtle p-3 me-3 text-primary shadow-sm">
                                <i class="fas fa-credit-card fa-xl"></i>
                            </div>
                            <div>
                                <h6 class="text-secondary small fw-bold text-uppercase mb-1" style="font-size: 0.65rem;">Bank/E-Wallet Balance</h6>
                                <h4 class="fw-bold mb-0" id="dashDigitalBalance"><?php echo CurrencyHelper::getSymbol($_SESSION['user_currency'] ?? 'PHP'); ?>0.00</h4>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Total Savings Card -->
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm rounded-4 bg-light overflow-hidden transition-all hover-lift">
                        <div class="card-body d-flex align-items-center p-4">
                            <div class="rounded-circle bg-warning-subtle p-3 me-3 text-warning shadow-sm">
                                <i class="fas fa-piggy-bank fa-xl"></i>
                            </div>
                            <div>
                                <h6 class="text-secondary small fw-bold text-uppercase mb-1" style="font-size: 0.65rem;">Total Savings</h6>
                                <h4 class="fw-bold mb-0" id="dashSavingsBalance"><?php echo CurrencyHelper::getSymbol($_SESSION['user_currency'] ?? 'PHP'); ?>0.00</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Full Width Column: Charts & Tables -->
                <div class="col-12">
                    <div class="row align-items-center mb-4">
                        <div class="col">
                            <h1 class="h3 fw-bold mb-0">Financial Overview</h1>
                            <p class="text-muted small mb-0 d-flex align-items-center">
                                <span class="live-pulse me-2"></span>
                                Real-time data synchronized
                            </p>
                        </div>
                        <div class="col-auto">
                            <button class="btn btn-outline-primary rounded-pill btn-sm px-3" onclick="fetchDashboardData()">
                                <i class="fas fa-sync-alt me-2"></i>Refresh Now
                            </button>
                        </div>
                    </div>
                    <!-- Spending Overview Chart -->
                    <div class="card mb-4">
                        <div class="card-header bg-transparent border-0 py-3">
                            <h5 class="mb-0">Spending Overview</h5>
                        </div>
                        <div class="card-body" style="height: 350px;">
                            <canvas id="dashboardChart"></canvas>
                        </div>
                    </div>

                    <!-- Recent Transactions -->
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                        <div class="card-header bg-transparent border-0 py-3">
                            <h5 class="mb-0">Recent Transactions</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table id="dashboardTable" class="table table-hover align-middle mb-0" style="width:100%">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Type</th>
                                            <th>Description</th>
                                            <th>Date</th>
                                            <th class="text-end">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody id="dashboardTableBody">
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- Pulsing Badge Styles ---
    const style = document.createElement('style');
    style.textContent = `
        .live-pulse {
            width: 8px;
            height: 8px;
            background-color: #10b981;
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 0 0 rgba(16, 185, 129, 0.4);
            animation: pulse-green 2s infinite;
        }
        @keyframes pulse-green {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }
    `;
    document.head.appendChild(style);

    // --- Dashboard Data Logic ---
    fetchDashboardData();

    // Polling Logic (Every 30 seconds)
    let dashboardPolling = setInterval(() => {
        if (!document.hidden) {
            fetchDashboardData();
        }
    }, 30000);

    // Clean up if navigating away (relevant for SPAs, but good practice)
    window.addEventListener('beforeunload', () => clearInterval(dashboardPolling));

    function fetchDashboardData() {
        fetch('api/dashboard.php?t=' + new Date().getTime())
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateDashboard(data);
                } else {
                    console.error('Dashboard API Error:', data.message);
                }
            })
            .catch(error => console.error('Error fetching dashboard data:', error));
    }

    function updateDashboard(data) {
        updateElement('dashTotalAllowance', formatCurrency(data.total_allowance));
        updateElement('dashTotalExpenses', formatCurrency(data.total_expenses));
        updateElement('dashBalance', formatCurrency(data.balance));
                document.getElementById('dashCashBalance').textContent = formatCurrency(data.cash_balance);
                document.getElementById('dashDigitalBalance').textContent = formatCurrency(data.digital_balance);
                document.getElementById('dashSavingsBalance').textContent = formatCurrency(data.total_savings);
        renderDashboardChart(data.category_spending);
        renderRecentTransactions(data.recent_transactions);
    }

    function updateElement(id, value) {
        const el = document.getElementById(id);
        if (el) el.textContent = value;
    }

    function renderDashboardChart(categorySpending) {
         const canvas = document.getElementById('dashboardChart');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        const labels = Object.keys(categorySpending);
        const data = Object.values(categorySpending);

        const existingChart = Chart.getChart(canvas);
        if (existingChart) existingChart.destroy();

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: ['#3b82f6', '#ef4444', '#10b981', '#f59e0b', '#6366f1', '#8b5cf6'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'right' } }
            }
        });
    }

    function renderRecentTransactions(transactions) {
        if (!document.getElementById('dashboardTable')) return;
        if ($.fn.DataTable.isDataTable('#dashboardTable')) {
            $('#dashboardTable').DataTable().destroy();
        }

        const tableBody = document.getElementById('dashboardTableBody');
        tableBody.innerHTML = ''; 

        $('#dashboardTable').DataTable({
            data: transactions,
            columns: [
                { 
                    data: 'type',
                    render: function(data) {
                        return data === 'expenses' 
                            ? '<span class="badge bg-danger-subtle text-danger rounded-pill">Expense</span>' 
                            : '<span class="badge bg-success-subtle text-success rounded-pill">Allowance</span>';
                    }
                },
                { data: 'description' },
                { data: 'date' },
                { 
                    data: 'amount',
                    className: 'text-end',
                    render: function(data, type, row) {
                         const isExpense = row.type === 'expenses';
                         const color = isExpense ? 'text-danger' : 'text-success';
                         const sign = isExpense ? '-' : '+';
                         return `<span class="fw-bold ${color}">${sign}${formatCurrency(data)}</span>`;
                    }
                }
            ],
            pageLength: 5,
            lengthChange: false,
            searching: false,
            dom: "<'row'<'col-sm-12'tr>><'row pagination-container'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            order: [[2, 'desc']], 
            responsive: true
        });
    }

    // --- AI Action Listener ---
    // Listen for AI actions from the chat widget to refresh data
    window.addEventListener('aiActionCompleted', function(e) {
        console.log('AI Action detected:', e.detail.actionType);
        fetchDashboardData();
    });

    // Also listen for storage events in case chat is in another tab
    window.addEventListener('storage', function(e) {
        if (e.key === 'budget_tracker_ai_action') {
            fetchDashboardData();
        }
    });

    function formatCurrency(amount) {
        return new Intl.NumberFormat(window.userCurrency.locale, { 
            style: 'currency', 
            currency: window.userCurrency.code 
        }).format(amount);
    }
});
</script>

<?php include 'includes/footer.php'; ?>
