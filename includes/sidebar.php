        <!-- Sidebar -->
        <div id="sidebar-wrapper">
            <?php
            // Determine dashboard URL based on role
            $roleStr = isset($_SESSION['role']) ? strtolower(trim($_SESSION['role'])) : '';
            $dashUrl = ($roleStr === 'superadmin') ? SITE_URL . 'admin/dashboard.php' : SITE_URL . 'core/dashboard.php';
            ?>
            <a href="<?php echo $dashUrl; ?>" class="sidebar-heading pb-2 text-decoration-none" style="color: inherit; transition: opacity 0.2s ease;" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">
                <div class="premium-logo-container me-3 rounded-circle overflow-hidden shadow-sm" style="width: 40px; height: 40px; background: white;">
                    <img src="<?php echo SITE_URL; ?>assets/images/favicon_rounded.png" alt="Logo" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                </div>
                <div>
                    <div style="line-height: 1;"><?php echo defined('APP_NAME') ? APP_NAME : $appName; ?></div>
                    <div class="opacity-25 fw-bold text-uppercase" style="font-size: 0.55rem; letter-spacing: 0.5px; line-height: 1; margin-top: 4px;">
                        Powered By Help Desk
                    </div>
                </div>
            </a>
            <div class="list-group list-group-flush mt-3">
                <?php
                $currentPage = basename($_SERVER['PHP_SELF']);
                $currentDir = basename(dirname($_SERVER['PHP_SELF']));

                // Helper function to check permissions. Default is TRUE if the key is missing (for backwards compatibility)
                $hasPerm = function ($featureKey) {
                    // Superadmins bypass all feature restrictions
                    if (isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin') return true;
                    if (!isset($_SESSION['permissions']) || !is_array($_SESSION['permissions'])) return true;
                    return !isset($_SESSION['permissions'][$featureKey]) || $_SESSION['permissions'][$featureKey] === true;
                };
                ?>
                <?php if ($roleStr !== 'superadmin'): ?>
                    <!-- General Section -->
                    <div class="px-4 py-2 small fw-bold text-secondary text-uppercase opacity-50" style="letter-spacing: 1px; font-size: 0.65rem;">General</div>
                    <?php if ($hasPerm('view_dashboard')): ?>
                        <a href="<?php echo SITE_URL; ?>core/dashboard.php" class="list-group-item list-group-item-action <?php echo ($currentPage == 'dashboard.php' && $currentDir == 'core') ? 'active' : ''; ?>">
                            <i class="fas fa-tachometer-alt me-2" style="width: 20px;"></i> Dashboard
                        </a>
                    <?php endif; ?>
                    <?php if ($hasPerm('manage_bills')): ?>
                        <a href="<?php echo SITE_URL; ?>core/bill_calendar.php" class="list-group-item list-group-item-action <?php echo ($currentPage == 'bill_calendar.php') ? 'active' : ''; ?>">
                            <i class="fas fa-calendar-alt me-2" style="width: 20px;"></i> Bill Calendar
                        </a>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($roleStr === 'superadmin' || $roleStr === 'admin'): ?>
                    <!-- Administration Section -->
                    <div class="px-4 py-2 mt-3 small fw-bold text-secondary text-uppercase opacity-50" style="letter-spacing: 1px; font-size: 0.65rem;">Administration</div>
                    <a href="<?php echo SITE_URL; ?>admin/dashboard.php" class="list-group-item list-group-item-action <?php echo ($currentPage == 'dashboard.php' && $currentDir == 'admin') ? 'active' : ''; ?>">
                        <i class="fas fa-user-shield me-2"></i> Admin Dashboard
                    </a>
                    <?php if ($roleStr === 'superadmin'): ?>
                        <?php if ($hasPerm('view_activity_log')): ?>
                            <a href="<?php echo SITE_URL; ?>admin/logs.php" class="list-group-item list-group-item-action <?php echo ($currentPage == 'logs.php') ? 'active' : ''; ?>">
                                <i class="fas fa-history me-2"></i> Activity Logs
                            </a>
                        <?php endif; ?>
                        <a href="<?php echo SITE_URL; ?>admin/recycle_bin.php" class="list-group-item list-group-item-action <?php echo ($currentPage == 'recycle_bin.php') ? 'active' : ''; ?>">
                            <i class="fas fa-trash-restore me-2"></i> Recycle Bin
                        </a>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($roleStr !== 'superadmin'): ?>
                    <div class="px-4 py-2 mt-3 small fw-bold text-secondary text-uppercase opacity-50" style="letter-spacing: 1px; font-size: 0.65rem;">Finance</div>
                    <?php if ($hasPerm('manage_income')): ?>
                        <a href="<?php echo SITE_URL; ?>core/allowance.php" class="list-group-item list-group-item-action <?php echo ($currentPage == 'allowance.php') ? 'active' : ''; ?>">
                            <i class="fas fa-hand-holding-dollar me-2" style="width: 20px;"></i> Allowance
                        </a>
                    <?php endif; ?>
                    <?php if ($hasPerm('manage_expenses')): ?>
                        <a href="<?php echo SITE_URL; ?>core/expenses.php" class="list-group-item list-group-item-action <?php echo ($currentPage == 'expenses.php') ? 'active' : ''; ?>">
                            <i class="fas fa-receipt me-2" style="width: 20px;"></i> Expenses
                        </a>
                    <?php endif; ?>
                    <?php if ($hasPerm('manage_savings')): ?>
                        <a href="<?php echo SITE_URL; ?>core/savings.php" class="list-group-item list-group-item-action <?php echo ($currentPage == 'savings.php') ? 'active' : ''; ?>">
                            <i class="fas fa-piggy-bank me-2" style="width: 20px;"></i> Savings
                        </a>
                    <?php endif; ?>
                    <?php if ($hasPerm('manage_bills')): ?>
                        <a href="<?php echo SITE_URL; ?>core/bills.php" class="list-group-item list-group-item-action <?php echo ($currentPage == 'bills.php') ? 'active' : ''; ?>">
                            <i class="fas fa-file-invoice-dollar me-2" style="width: 20px;"></i> Bills &amp; Subscriptions
                        </a>
                    <?php endif; ?>

                    <div class="px-4 py-2 mt-3 small fw-bold text-secondary text-uppercase opacity-50" style="letter-spacing: 1px; font-size: 0.65rem;">Journaling</div>
                    <a href="<?php echo SITE_URL; ?>core/journal.php" class="list-group-item list-group-item-action <?php echo ($currentPage == 'journal.php') ? 'active' : ''; ?>">
                        <i class="fas fa-book-open me-2" style="width: 20px;"></i> Journal
                    </a>
                    <a href="<?php echo SITE_URL; ?>core/goals.php" class="list-group-item list-group-item-action <?php echo ($currentPage == 'goals.php') ? 'active' : ''; ?>">
                        <i class="fas fa-bullseye me-2" style="width: 20px;"></i> Goals
                    </a>

                    <div class="px-4 py-2 mt-3 small fw-bold text-secondary text-uppercase opacity-50" style="letter-spacing: 1px; font-size: 0.65rem;">Analysis</div>
                    <?php if ($hasPerm('view_reports')): ?>
                        <a href="<?php echo SITE_URL; ?>core/reports.php" class="list-group-item list-group-item-action <?php echo ($currentPage == 'reports.php') ? 'active' : ''; ?>">
                            <i class="fas fa-chart-line me-2" style="width: 20px;"></i> Reports
                        </a>
                    <?php endif; ?>
                    <?php if ($hasPerm('view_analytics')): ?>
                        <a href="<?php echo SITE_URL; ?>core/analytics.php" class="list-group-item list-group-item-action <?php echo ($currentPage == 'analytics.php') ? 'active' : ''; ?>">
                            <i class="fas fa-chart-bar me-2" style="width: 20px;"></i> Analytics
                        </a>
                    <?php endif; ?>
                    <?php if ($hasPerm('use_ai_assistant')): ?>
                        <a href="<?php echo SITE_URL; ?>core/history_log.php" class="list-group-item list-group-item-action <?php echo ($currentPage == 'history_log.php') ? 'active' : ''; ?>">
                            <i class="fas fa-history me-2" style="width: 20px;"></i> Chat History
                        </a>
                    <?php endif; ?>
                    <a href="<?php echo SITE_URL; ?>core/recycle_bin.php" class="list-group-item list-group-item-action <?php echo ($currentPage == 'recycle_bin.php') ? 'active' : ''; ?>">
                        <i class="fas fa-trash-restore me-2" style="width: 20px;"></i> Recycle Bin
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sidebar Overlay Backdrop (closes sidebar when tapped on mobile/tablet) -->
        <div id="sidebar-overlay"></div>