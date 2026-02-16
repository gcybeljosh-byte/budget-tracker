        <!-- Sidebar -->
        <div class="border-end" id="sidebar-wrapper">
            <div class="sidebar-heading pb-2">
                <i class="fas fa-wallet me-2"></i>
                <div>
                    <div style="line-height: 1;"><?php echo $appName; ?></div>
                    <div class="opacity-25 fw-bold text-uppercase" style="font-size: 0.55rem; letter-spacing: 0.5px; line-height: 1; margin-top: 4px;">
                        Powered By AI Help Desk
                    </div>
                </div>
            </div>
            <div class="list-group list-group-flush mt-3">
                <?php
                $currentPage = basename($_SERVER['PHP_SELF']);
                ?>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <a href="admin_dashboard.php" class="list-group-item list-group-item-action <?php echo ($currentPage == 'admin_dashboard.php') ? 'active' : ''; ?>">
                    <i class="fas fa-user-shield me-2"></i> Admin Dashboard
                </a>
                <?php endif; ?>
                <?php if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'): ?>
                <div class="px-4 py-2 small fw-bold text-secondary text-uppercase opacity-50" style="letter-spacing: 1px; font-size: 0.65rem;">General</div>
                <a href="index.php" class="list-group-item list-group-item-action <?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                </a>
                
                <div class="px-4 py-2 mt-3 small fw-bold text-secondary text-uppercase opacity-50" style="letter-spacing: 1px; font-size: 0.65rem;">Finance</div>
                <a href="allowance.php" class="list-group-item list-group-item-action <?php echo ($currentPage == 'allowance.php') ? 'active' : ''; ?>">
                    <i class="fas fa-hand-holding-dollar me-2"></i> Allowance
                </a>
                <a href="expenses.php" class="list-group-item list-group-item-action <?php echo ($currentPage == 'expenses.php') ? 'active' : ''; ?>">
                    <i class="fas fa-receipt me-2"></i> Expenses
                </a>
                <a href="savings.php" class="list-group-item list-group-item-action <?php echo ($currentPage == 'savings.php') ? 'active' : ''; ?>">
                    <i class="fas fa-piggy-bank me-2"></i> Savings
                </a>

                <div class="px-4 py-2 mt-3 small fw-bold text-secondary text-uppercase opacity-50" style="letter-spacing: 1px; font-size: 0.65rem;">Journals</div>
                <a href="journal.php" class="list-group-item list-group-item-action <?php echo ($currentPage == 'journal.php') ? 'active' : ''; ?>">
                    <i class="fas fa-book-open me-2"></i> Journal
                </a>
                
                <div class="px-4 py-2 mt-3 small fw-bold text-secondary text-uppercase opacity-50" style="letter-spacing: 1px; font-size: 0.65rem;">Analysis</div>
                <a href="reports.php" class="list-group-item list-group-item-action <?php echo ($currentPage == 'reports.php') ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line me-2"></i> Reports
                </a>
                <a href="chat_history.php" class="list-group-item list-group-item-action <?php echo ($currentPage == 'chat_history.php') ? 'active' : ''; ?>">
                    <i class="fas fa-history me-2"></i> Chat History
                </a>
                <?php endif; ?>
            </div>
        </div>
