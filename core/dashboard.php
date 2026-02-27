<?php
include '../includes/header.php';
?>
<?php include '../includes/sidebar.php'; ?>

<!-- Page Content -->
<div id="page-content-wrapper">

    <?php include '../includes/navbar.php'; ?>

    <div class="container-fluid px-4 py-4">

        <!-- Personalized Greeting -->
        <div class="mb-4 fade-up">
            <h4 class="fw-bold mb-1">Hello, <span class="text-primary"><?php echo htmlspecialchars($_SESSION['first_name'] ?? 'Guest'); ?></span>!</h4>
            <p class="text-secondary small mb-0">Here's your financial status for <?php echo date('F Y'); ?>.</p>
        </div>

        <!-- Alerts/Notifications -->
        <div id="alertContainer"></div>

        <!-- Primary Metrics Row -->
        <div class="row g-4 mb-4">
            <!-- Total Allowance Card -->
            <div class="col-md-6 col-lg-4 stagger-item">
                <div class="card h-100 border-0 shadow-sm rounded-4 bg-gradient-primary text-white overflow-hidden transition-all hover-lift">
                    <div class="card-body p-4">
                        <h6 class="text-white text-opacity-75 small fw-bold text-uppercase mb-2"><i class="fas fa-hand-holding-dollar me-2"></i>Allowance (<?php echo date('M'); ?>)</h6>
                        <h2 class="fw-bold mb-0" id="dashTotalAllowance"><?php echo CurrencyHelper::getSymbol($_SESSION['user_currency'] ?? 'PHP'); ?>0.00</h2>
                    </div>
                </div>
            </div>
            <!-- Total Expenses Card -->
            <div class="col-md-6 col-lg-4 stagger-item">
                <div class="card h-100 border-0 shadow-sm rounded-4 bg-gradient-danger text-white overflow-hidden transition-all hover-lift">
                    <div class="card-body p-4">
                        <h6 class="text-white text-opacity-75 small fw-bold text-uppercase mb-2"><i class="fas fa-receipt me-2"></i>Expenses (<?php echo date('M'); ?>)</h6>
                        <h2 class="fw-bold mb-0" id="dashTotalExpenses"><?php echo CurrencyHelper::getSymbol($_SESSION['user_currency'] ?? 'PHP'); ?>0.00</h2>
                    </div>
                </div>
            </div>
            <!-- Remaining Balance Card -->
            <div class="col-md-6 col-lg-4 stagger-item">
                <div class="card h-100 border-0 shadow-sm rounded-4 bg-gradient-success text-white overflow-hidden transition-all hover-lift">
                    <div class="card-body p-4">
                        <h6 class="text-white text-opacity-75 small fw-bold text-uppercase mb-2"><i class="fas fa-wallet me-2"></i>Remaining Balance</h6>
                        <h2 class="fw-bold mb-0" id="dashBalance"><?php echo CurrencyHelper::getSymbol($_SESSION['user_currency'] ?? 'PHP'); ?>0.00</h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Access Hub -->
        <div class="row g-3 mb-4 stagger-item">
            <div class="col-12">
                <div class="d-flex align-items-center mb-3">
                    <div class="h6 fw-bold text-uppercase small text-secondary mb-0 letter-spacing-1">Quick Access Hub</div>
                    <div class="flex-grow-1 ms-3 border-bottom opacity-25"></div>
                </div>
                <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3">
                    <!-- Journal -->
                    <div class="col">
                        <div class="card border-0 shadow-sm rounded-4 h-100 bg-white p-3 transition-all hover-lift border-bottom border-primary border-3" onclick="location.href='journal.php'" style="cursor: pointer;">
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle bg-primary-subtle p-2 me-3 text-primary">
                                    <i class="fas fa-book-open fa-sm"></i>
                                </div>
                                <div>
                                    <div class="extra-small text-muted fw-bold" style="font-size: 0.55rem;">JOURNAL</div>
                                    <div class="small fw-bold text-dark text-truncate" id="hubJournalLatest" style="max-width: 90px;">...</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Bills -->
                    <div class="col">
                        <div class="card border-0 shadow-sm rounded-4 h-100 bg-white p-3 transition-all hover-lift border-bottom border-secondary border-3" onclick="location.href='bills.php'" style="cursor: pointer;">
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle bg-secondary-subtle p-2 me-3 text-secondary">
                                    <i class="fas fa-file-invoice-dollar fa-sm"></i>
                                </div>
                                <div>
                                    <div class="extra-small text-muted fw-bold" style="font-size: 0.55rem;">BILLS</div>
                                    <div class="small fw-bold text-dark" id="hubBillsUpcoming">...</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Goals -->
                    <div class="col">
                        <div class="card border-0 shadow-sm rounded-4 h-100 bg-white p-3 transition-all hover-lift border-bottom border-success border-3" onclick="location.href='goals.php'" style="cursor: pointer;">
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle bg-success-subtle p-2 me-3 text-success">
                                    <i class="fas fa-bullseye fa-sm"></i>
                                </div>
                                <div>
                                    <div class="extra-small text-muted fw-bold" style="font-size: 0.55rem;">GOALS</div>
                                    <div class="small fw-bold text-dark" id="hubGoalsActive">...</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Analytics/Trends -->
                    <div class="col">
                        <div class="card border-0 shadow-sm rounded-4 h-100 bg-white p-3 transition-all hover-lift border-bottom border-danger border-3" onclick="location.href='analytics.php'" style="cursor: pointer;">
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle bg-danger-subtle p-2 me-3 text-danger">
                                    <i class="fas fa-chart-line fa-sm"></i>
                                </div>
                                <div>
                                    <div class="extra-small text-muted fw-bold" style="font-size: 0.55rem;">TRENDS</div>
                                    <div class="small fw-bold text-dark text-truncate" id="hubTopCategory" style="max-width: 90px;">...</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Your Wallets Row (Main Body placement) -->
        <div class="row g-3 mb-4 stagger-item">
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-transparent border-0 py-3 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold text-uppercase small text-secondary letter-spacing-1">Your Wallets</h6>
                        <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2 extra-small fw-bold">Active Balances</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="row g-0">
                            <!-- Cash Balance -->
                            <div class="col-md-4 border-end border-light">
                                <div class="d-flex align-items-center p-4 transition-all hover-bg-light h-100">
                                    <div class="rounded-circle bg-secondary-subtle p-3 me-3 text-secondary shadow-sm">
                                        <i class="fas fa-wallet"></i>
                                    </div>
                                    <div>
                                        <div class="extra-small text-muted fw-bold text-uppercase mb-1" style="font-size: 0.6rem; letter-spacing: 1px;">Cash Wallet</div>
                                        <div class="h4 fw-bold text-dark mb-0" id="dashCashBalance"><?php echo CurrencyHelper::getSymbol($_SESSION['user_currency'] ?? 'PHP'); ?>0.00</div>
                                    </div>
                                </div>
                            </div>
                            <!-- Digital Balance -->
                            <div class="col-md-4 border-end border-light">
                                <div class="d-flex align-items-center p-4 transition-all hover-bg-light h-100">
                                    <div class="rounded-circle bg-primary-subtle p-3 me-3 text-primary shadow-sm">
                                        <i class="fas fa-credit-card"></i>
                                    </div>
                                    <div>
                                        <div class="extra-small text-muted fw-bold text-uppercase mb-1" style="font-size: 0.6rem; letter-spacing: 1px;">Digital / Bank</div>
                                        <div class="h4 fw-bold text-dark mb-0" id="dashDigitalBalance"><?php echo CurrencyHelper::getSymbol($_SESSION['user_currency'] ?? 'PHP'); ?>0.00</div>
                                    </div>
                                </div>
                            </div>
                            <!-- Savings Balance -->
                            <div class="col-md-4">
                                <div class="d-flex align-items-center p-4 transition-all hover-bg-light h-100">
                                    <div class="rounded-circle bg-warning-subtle p-3 me-3 text-warning shadow-sm">
                                        <i class="fas fa-piggy-bank"></i>
                                    </div>
                                    <div>
                                        <div class="extra-small text-muted fw-bold text-uppercase mb-1" style="font-size: 0.6rem; letter-spacing: 1px;">Savings Account</div>
                                        <div class="h4 fw-bold text-dark mb-0" id="dashSavingsBalance"><?php echo CurrencyHelper::getSymbol($_SESSION['user_currency'] ?? 'PHP'); ?>0.00</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
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
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-transparent border-0 py-3">
                        <h5 class="mb-0 fw-bold">Spending Overview</h5>
                    </div>
                    <div class="card-body" style="height: 350px;">
                        <canvas id="dashboardChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Sidebar column -->
            <div class="col-lg-4">
                <!-- Financial Streaks Card -->
                <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden transition-all hover-lift border-start border-danger border-4 mb-4" id="streakCard">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-2">
                            <div class="rounded-circle bg-danger-subtle p-2 me-2 text-danger">
                                <i class="fas fa-fire-alt small streak-pulse"></i>
                            </div>
                            <h6 class="text-secondary small fw-bold text-uppercase mb-0">No-Spend Streak</h6>
                        </div>
                        <h2 class="fw-bold mb-0 text-dark"><span id="dashStreakCount">0</span> Days</h2>
                        <div class="extra-small text-muted mt-1" id="dashStreakMax">Best: 0 days</div>
                    </div>
                </div>

                <!-- Achievements Widget -->
                <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
                    <div class="card-header bg-transparent border-0 py-3 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold text-uppercase small text-secondary">Achievements</h6>
                        <span class="badge bg-warning-subtle text-warning rounded-pill px-2 py-1 extra-small" id="achievementCount">0/6</span>
                    </div>
                    <div class="card-body p-3">
                        <div class="achievement-grid d-flex flex-wrap gap-2" id="dashAchievementList">
                            <!-- Achievements will be injected here -->
                        </div>
                    </div>
                </div>

                <!-- Upcoming Bills Widget -->
                <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden border-start border-primary border-4">
                    <div class="card-header bg-transparent border-0 py-3 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold text-uppercase small text-secondary">Upcoming Bills</h6>
                        <a href="bills.php" class="text-primary small fw-bold text-decoration-none">View All</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush border-top-0" id="dashUpcomingBillsList">
                            <div class="p-4 text-center text-muted small">Loading bills...</div>
                        </div>
                    </div>
                </div>

                <!-- Daily Insights Widget -->
                <div class="card border-0 shadow-sm rounded-4 bg-dark text-white p-4 mb-4 position-relative overflow-hidden transition-all hover-lift">
                    <div style="position:absolute; top:-10px; right:-10px; opacity:0.1; font-size: 5rem;">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h6 class="small fw-bold text-uppercase text-white text-opacity-50 mb-3" style="font-size: 0.65rem;">Daily Spending Insight</h6>
                    <div class="d-flex align-items-end mb-3">
                        <h3 class="fw-bold mb-0 me-2" id="dashDailyAvg"><?php echo CurrencyHelper::getSymbol($_SESSION['user_currency'] ?? 'PHP'); ?>0.00</h3>
                        <span class="small text-white text-opacity-50 pb-1">/ avg day</span>
                    </div>
                    <div class="progress rounded-pill bg-white bg-opacity-10 mb-2" style="height: 6px;">
                        <div id="dashUtilBar" class="progress-bar bg-info" style="width: 0%"></div>
                    </div>
                    <div class="extra-small text-white text-opacity-75" id="dashUtilText" style="font-size: 0.6rem;">0% spent</div>
                </div>

                <!-- Safe-to-Spend Card -->
                <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden transition-all hover-lift border-start border-info border-4 mb-4" id="safeToSpendCard">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-2">
                            <div class="rounded-circle bg-info-subtle p-2 me-2 text-info" id="safeToSpendIcon">
                                <i class="fas fa-shield-halved small"></i>
                            </div>
                            <h6 class="text-secondary small fw-bold text-uppercase mb-0">Safe-to-Spend</h6>
                        </div>
                        <h2 class="fw-bold mb-0 text-dark" id="dashSafeToSpend"><?php echo CurrencyHelper::getSymbol($_SESSION['user_currency'] ?? 'PHP'); ?>0.00</h2>
                        <div class="extra-small text-muted mt-1" id="safeToSpendDays">Calculating...</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Transactions (Full Width) -->
        <div class="row g-4 mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-transparent border-0 py-3">
                        <h5 class="mb-0 fw-bold">Recent Transactions</h5>
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

    </div> <!-- Close container-fluid -->
</div> <!-- Close page-content-wrapper -->

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
        @keyframes fire-pulse {
            0% { transform: scale(1); filter: drop-shadow(0 0 0px #f43f5e); }
            50% { transform: scale(1.2); filter: drop-shadow(0 0 8px #f43f5e); }
            100% { transform: scale(1); filter: drop-shadow(0 0 0px #f43f5e); }
        }
        .achievement-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
        }
        .achievement-icon.locked {
            opacity: 0.3;
            filter: grayscale(1);
        }
        .achievement-icon:hover {
            transform: scale(1.1) rotate(5deg);
            z-index: 10;
        }
        .achievement-badge-large {
            width: 80px;
            height: 80px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            margin: 0 auto;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
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

        const urlParams = new URLSearchParams(window.location.search);
        const activeGroupId = urlParams.get('group_id');

        function fetchDashboardData() {
            const query = activeGroupId ? `&group_id=${activeGroupId}` : '';
            fetch('<?php echo SITE_URL; ?>api/dashboard.php?t=' + new Date().getTime() + query)
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

        // Initial fetch
        fetchDashboardData();

        // Initialize Form with Group ID if active
        if (activeGroupId) {
            document.querySelectorAll('input[name="group_id"]').forEach(input => {
                input.value = activeGroupId;
            });
        }

        function updateDashboard(data) {
            updateElement('dashTotalAllowance', formatCurrency(data.total_allowance));
            updateElement('dashTotalExpenses', formatCurrency(data.total_expenses));
            updateElement('dashBalance', formatCurrency(data.balance));
            document.getElementById('dashCashBalance').textContent = formatCurrency(data.cash_balance);
            document.getElementById('dashDigitalBalance').textContent = formatCurrency(data.digital_balance);
            document.getElementById('dashSavingsBalance').textContent = formatCurrency(data.total_savings);

            // Update Safe-to-Spend
            if (data.analytics && data.analytics.safe_to_spend) {
                const sts = data.analytics.safe_to_spend;
                const stsCard = document.getElementById('safeToSpendCard');
                const stsIcon = document.getElementById('safeToSpendIcon');
                updateElement('dashSafeToSpend', formatCurrency(sts.daily_limit));
                updateElement('safeToSpendDays', `${sts.remaining_days} days left in month`);
                // Color Logic (Safe-to-Spend)
                stsCard.classList.remove('border-info', 'border-warning', 'border-danger');
                stsIcon.classList.remove('bg-info-subtle', 'text-info', 'bg-warning-subtle', 'text-warning', 'bg-danger-subtle', 'text-danger', 'bg-danger', 'text-white');

                if (sts.daily_limit <= 0) {
                    stsCard.classList.add('border-danger');
                    stsIcon.className = 'rounded-circle bg-danger p-2 me-2 text-white';
                } else if (sts.daily_limit < 100) {
                    stsCard.classList.add('border-warning');
                    stsIcon.className = 'rounded-circle bg-warning-subtle p-2 me-2 text-warning';
                } else {
                    stsCard.classList.add('border-info');
                    stsIcon.className = 'rounded-circle bg-info-subtle p-2 me-2 text-info';
                }
            }

            // Update Hub Data
            if (data.journal_summary) {
                updateElement('hubJournalLatest', data.journal_summary.date);
            } else {
                updateElement('hubJournalLatest', 'No entries');
            }

            if (data.goals_summary) {
                updateElement('hubGoalsActive', `${data.goals_summary.active}/${data.goals_summary.total} active`);
            }

            if (data.analytics) {
                updateElement('hubForecast', formatCurrency(data.analytics.projected_spending));
                updateElement('hubTopCategory', data.analytics.top_category || 'None');

                // Daily Avg & Util
                updateElement('dashDailyAvg', formatCurrency(data.analytics.daily_average));
                const util = Math.min(100, (data.total_expenses / data.total_allowance) * 100) || 0;
                document.getElementById('dashUtilBar').style.width = util + '%';
                updateElement('dashUtilText', util.toFixed(1) + '% of monthly allowance spent');
            }

            updateElement('hubReportsThisMonth', `${data.reports_count || 0} this month`);

            // Bills Hub
            if (data.upcoming_bills) {
                updateElement('hubBillsUpcoming', `${data.upcoming_bills.length} upcoming`);
                renderUpcomingBillsSide(data.upcoming_bills);
            }

            renderDashboardChart(data.category_spending);
            renderRecentTransactions(data.recent_transactions);

            // --- Gamification Logic ---
            fetch('<?php echo SITE_URL; ?>api/gamification.php')
                .then(res => res.json())
                .then(gamData => {
                    if (gamData.success) {
                        updateGamificationUI(gamData);
                    }
                });
        }

        function updateGamificationUI(data) {
            // Update Streak
            const streak = data.streaks.find(s => s.streak_type === 'no_spend');
            if (streak) {
                document.getElementById('dashStreakCount').textContent = streak.current_count;
                document.getElementById('dashStreakMax').textContent = `Best: ${streak.max_count} days`;

                // Pulse effect if streak is active
                const icon = document.querySelector('.streak-pulse');
                if (streak.current_count > 0) {
                    icon.style.animation = 'fire-pulse 1.5s infinite';
                } else {
                    icon.style.animation = 'none';
                }
            }

            // Update Achievements
            const list = document.getElementById('dashAchievementList');
            const countBadge = document.getElementById('achievementCount');
            list.innerHTML = '';

            let unlockedCount = 0;
            data.achievements.forEach(ach => {
                if (ach.is_unlocked) unlockedCount++;

                const item = document.createElement('div');
                item.className = `achievement-icon ${ach.is_unlocked ? '' : 'locked'}`;
                item.title = `${ach.name}: ${ach.description}`;
                item.style.backgroundColor = ach.is_unlocked ? ach.badge_color : '#e2e8f0';
                item.innerHTML = `<i class="${ach.icon}"></i>`;

                // Check if we should notify user of a NEWLY unlocked achievement
                if (ach.is_unlocked && !localStorage.getItem(`ach_${ach.slug}`)) {
                    localStorage.setItem(`ach_${ach.slug}`, 'seen');
                    showAchievementCelebration(ach);
                }

                list.appendChild(item);
            });
            countBadge.textContent = `${unlockedCount}/${data.achievements.length}`;
        }

        function showAchievementCelebration(ach) {
            Swal.fire({
                title: '🏆 Achievement Unlocked!',
                html: `
                    <div class="text-center mb-3">
                        <div class="achievement-badge-large" style="background: ${ach.badge_color}">
                            <i class="${ach.icon} fa-2x"></i>
                        </div>
                    </div>
                    <div class="h5 fw-bold mb-1">${ach.name}</div>
                    <div class="text-muted small">${ach.description}</div>
                `,
                showConfirmButton: true,
                confirmButtonText: 'Awesome!',
                confirmButtonColor: '#6366f1',
                backdrop: `rgba(99, 102, 241, 0.2)`
            });
            // Trigger confetti if available (optional)
        }

        function renderUpcomingBillsSide(bills) {
            const list = document.getElementById('dashUpcomingBillsList');
            if (!list) return;
            list.innerHTML = '';
            if (bills.length === 0) {
                list.innerHTML = '<div class="p-4 text-center text-muted small">All caught up! No bills due.</div>';
                return;
            }
            bills.forEach(bill => {
                const item = document.createElement('div');
                item.className = 'list-group-item border-0 border-bottom d-flex justify-content-between align-items-center py-3 px-4 transition-all hover-bg-light';
                item.innerHTML = `
                <div>
                    <div class="fw-bold small text-dark">${bill.title}</div>
                    <div class="text-muted extra-small">${bill.due_date} • ${bill.category}</div>
                </div>
                <div class="fw-bold text-primary small">${formatCurrency(bill.amount)}</div>
            `;
                list.appendChild(item);
            });
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

            const isDark = document.body.getAttribute('data-bs-theme') === 'dark';
            const textColor = isDark ? '#ffffff' : '#1c1c1e';

            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: ['#007aff', '#ff3b30', '#34c759', '#ff9500', '#5856d6', '#af52de'],
                        borderColor: isDark ? '#1c1c1e' : '#ffffff',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: window.innerWidth < 768 ? 'bottom' : 'right',
                            labels: {
                                color: textColor,
                                font: {
                                    family: "'Inter', sans-serif",
                                    weight: '600'
                                }
                            }
                        }
                    }
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
                columns: [{
                        data: 'type',
                        render: function(data) {
                            if (data === 'expenses') return '<span class="badge bg-danger-subtle text-danger rounded-pill">Expense</span>';
                            if (data === 'savings') return '<span class="badge bg-success-subtle text-success rounded-pill">Savings</span>';
                            return '<span class="badge bg-primary-subtle text-primary rounded-pill">Allowance</span>';
                        }
                    },
                    {
                        data: 'description'
                    },
                    {
                        data: 'date'
                    },
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
                order: [
                    [2, 'desc']
                ],
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

        // --- Welcome Tutorial ---
        <?php if (!isset($seen_tutorials['index.php'])): ?>

            function startTutorial() {
                if (window.seenTutorials['index.php']) return;

                const steps = [{
                        title: '👋 Welcome!',
                        text: 'Welcome to your personalized Budget Tracker! Let\'s take a quick tour of everything available to you.',
                        icon: 'success'
                    },
                    {
                        title: '📊 Dashboard Overview',
                        text: 'Your financial snapshot — Total Allowance, Total Expenses, and Remaining Balance — is always here at a glance.',
                        icon: 'info'
                    },
                    {
                        title: '💸 Track Expenses & Allowance',
                        text: 'Use the sidebar to log your daily expenses and income. You can also set spending limits per category on the Expenses page.',
                        icon: 'info'
                    },
                    {
                        title: '🎯 Financial Goals',
                        text: 'Head to Journaling → Goals to set saving targets. Track progress with visual bars and contribute funds anytime.',
                        icon: 'info'
                    },
                    {
                        title: '📈 Analytics & Insights',
                        text: 'Go to Analysis → Analytics to see your Expense Trends, a Spending Heatmap calendar, and an AI Budget Forecast for the rest of the month.',
                        icon: 'info'
                    },
                    {
                        title: '🤖 AI Help Desk',
                        text: 'Tap the floating AI button anytime to ask questions, get advice, or even create goals and log transactions by just typing naturally.',
                        icon: 'info'
                    },
                    {
                        title: '🚀 You\'re all set!',
                        text: 'You\'re ready to start managing your budget smarter. Good luck!',
                        icon: 'success'
                    }
                ];

                let currentStep = 0;

                function showStep() {
                    if (currentStep >= steps.length) {
                        markPageTutorialSeen('index.php');
                        return;
                    }

                    Swal.fire({
                        title: steps[currentStep].title,
                        text: steps[currentStep].text,
                        icon: steps[currentStep].icon,
                        showCancelButton: true,
                        confirmButtonText: currentStep === steps.length - 1 ? '🎉 Let\'s Go!' : 'Next →',
                        confirmButtonColor: '#6366f1',
                        cancelButtonColor: '#d33',
                        cancelButtonText: 'Skip Tour'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            currentStep++;
                            showStep();
                        } else if (result.dismiss === Swal.DismissReason.cancel) {
                            markPageTutorialSeen('index.php');
                        }
                    });
                }

                showStep();
            }

            setTimeout(startTutorial, 1500);
        <?php endif; ?>
    });
</script>

<?php include '../includes/footer.php'; ?>