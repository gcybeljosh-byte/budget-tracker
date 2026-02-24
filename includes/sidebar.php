        <!-- Sidebar -->
        <div id="sidebar-wrapper">
            <div class="sidebar-heading pb-2">
                <div class="premium-logo-container me-3" style="width: 40px; height: 40px;">
                    <img src="<?php echo SITE_URL; ?>assets/images/favicon.png" alt="Logo" style="width: 100%; height: 100%; object-fit: contain;">
                </div>
                <div>
                    <div style="line-height: 1;"><?php echo defined('APP_NAME') ? APP_NAME : $appName; ?></div>
                    <div class="opacity-25 fw-bold text-uppercase" style="font-size: 0.55rem; letter-spacing: 0.5px; line-height: 1; margin-top: 4px;">
                        Powered By Help Desk
                    </div>
                </div>
            </div>
            <div class="list-group list-group-flush mt-3">
                <?php
                $currentPage = basename($_SERVER['PHP_SELF']);
                $currentDir = basename(dirname($_SERVER['PHP_SELF']));
                ?>
                <?php if ($_SESSION['role'] !== 'superadmin'): ?>
                    <!-- General Section -->
                    <div class="px-4 py-2 small fw-bold text-secondary text-uppercase opacity-50" style="letter-spacing: 1px; font-size: 0.65rem;">General</div>
                    <a href="<?php echo SITE_URL; ?>core/dashboard.php" class="list-group-item list-group-item-action <?php echo ($currentPage == 'dashboard.php' && $currentDir == 'core') ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                <?php endif; ?>

                <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['superadmin', 'admin'])): ?>
                    <!-- Administration Section -->
                    <div class="px-4 py-2 mt-3 small fw-bold text-secondary text-uppercase opacity-50" style="letter-spacing: 1px; font-size: 0.65rem;">Administration</div>
                    <a href="<?php echo SITE_URL; ?>admin/dashboard.php" class="list-group-item list-group-item-action <?php echo ($currentPage == 'dashboard.php' && $currentDir == 'admin') ? 'active' : ''; ?>">
                        <i class="fas fa-user-shield me-2"></i> Admin Dashboard
                    </a>
                    <?php if ($_SESSION['role'] === 'superadmin'): ?>
                        <a href="<?php echo SITE_URL; ?>admin/logs.php" class="list-group-item list-group-item-action <?php echo ($currentPage == 'logs.php') ? 'active' : ''; ?>">
                            <i class="fas fa-history me-2"></i> Activity Logs
                        </a>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($_SESSION['role'] !== 'superadmin'): ?>
                    <div class="px-4 py-2 mt-3 small fw-bold text-secondary text-uppercase opacity-50" style="letter-spacing: 1px; font-size: 0.65rem;">Finance</div>
                    <a href="<?php echo SITE_URL; ?>core/allowance.php" class="list-group-item list-group-item-action <?php echo ($currentPage == 'allowance.php') ? 'active' : ''; ?>">
                        <i class="fas fa-hand-holding-dollar me-2" style="width: 20px;"></i> Allowance
                    </a>
                    <a href="<?php echo SITE_URL; ?>core/expenses.php" class="list-group-item list-group-item-action <?php echo ($currentPage == 'expenses.php') ? 'active' : ''; ?>">
                        <i class="fas fa-receipt me-2" style="width: 20px;"></i> Expenses
                    </a>
                    <a href="<?php echo SITE_URL; ?>core/savings.php" class="list-group-item list-group-item-action <?php echo ($currentPage == 'savings.php') ? 'active' : ''; ?>">
                        <i class="fas fa-piggy-bank me-2" style="width: 20px;"></i> Savings
                    </a>
                    <a href="<?php echo SITE_URL; ?>core/bills.php" class="list-group-item list-group-item-action <?php echo ($currentPage == 'bills.php') ? 'active' : ''; ?>">
                        <i class="fas fa-file-invoice-dollar me-2" style="width: 20px;"></i> Bills &amp; Subscriptions
                    </a>

                    <div class="px-4 py-2 mt-3 small fw-bold text-secondary text-uppercase opacity-50" style="letter-spacing: 1px; font-size: 0.65rem;">Journaling</div>
                    <a href="<?php echo SITE_URL; ?>core/journal.php" class="list-group-item list-group-item-action <?php echo ($currentPage == 'journal.php') ? 'active' : ''; ?>">
                        <i class="fas fa-book-open me-2" style="width: 20px;"></i> Journal
                    </a>
                    <a href="<?php echo SITE_URL; ?>core/goals.php" class="list-group-item list-group-item-action <?php echo ($currentPage == 'goals.php') ? 'active' : ''; ?>">
                        <i class="fas fa-bullseye me-2" style="width: 20px;"></i> Goals
                    </a>

                    <div class="px-4 py-2 mt-3 small fw-bold text-secondary text-uppercase opacity-50" style="letter-spacing: 1px; font-size: 0.65rem;">Analysis</div>
                    <a href="<?php echo SITE_URL; ?>core/reports.php" class="list-group-item list-group-item-action <?php echo ($currentPage == 'reports.php') ? 'active' : ''; ?>">
                        <i class="fas fa-chart-line me-2" style="width: 20px;"></i> Reports
                    </a>
                    <a href="<?php echo SITE_URL; ?>core/analytics.php" class="list-group-item list-group-item-action <?php echo ($currentPage == 'analytics.php') ? 'active' : ''; ?>">
                        <i class="fas fa-chart-bar me-2" style="width: 20px;"></i> Analytics
                    </a>
                    <a href="<?php echo SITE_URL; ?>core/history_log.php" class="list-group-item list-group-item-action <?php echo ($currentPage == 'history_log.php') ? 'active' : ''; ?>">
                        <i class="fas fa-history me-2" style="width: 20px;"></i> Chat History
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sidebar Overlay Backdrop (closes sidebar when tapped on mobile/tablet) -->
        <div id="sidebar-overlay"></div>