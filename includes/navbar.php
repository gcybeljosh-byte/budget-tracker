            <!-- Top Navbar -->
            <nav class="navbar navbar-expand-lg">
                <div class="container-fluid px-0">

                    <!-- LEFT: Burger + Page Title -->
                    <div class="d-flex align-items-center gap-2 flex-grow-1 overflow-hidden">
                        <button class="navbar-burger-btn" id="menu-toggle" aria-label="Toggle sidebar">
                            <i class="fas fa-bars"></i>
                        </button>
                        <h2 class="navbar-page-title m-0">
                            <?php echo isset($pageHeader) ? $pageHeader : 'Dashboard Overview'; ?>
                        </h2>
                    </div>

                    <!-- RIGHT (always visible on all sizes): Notifications + Profile -->
                    <?php
                    $currentPage = basename($_SERVER['PHP_SELF']);
                    $currentDir  = basename(dirname($_SERVER['PHP_SELF']));
                    $isDashboard = ($currentPage === 'dashboard.php' && ($currentDir == 'core' || $currentDir == 'admin'))
                        || $currentPage === 'profile.php'
                        || $currentPage === 'settings.php'
                        || $currentPage === 'logs.php';
                    ?>
                    <div class="d-flex align-items-center gap-2 flex-shrink-0">

                        <!-- Desktop-only: Clock -->
                        <?php if ($isDashboard): ?>
                            <div class="d-none d-lg-flex align-items-center border-end pe-3 me-1">
                                <div id="realtime-clock" class="text-muted fw-bold small" style="min-width: 80px; text-align: center;">--:--:--</div>
                            </div>
                        <?php endif; ?>

                        <!-- Desktop-only: Online Indicator -->
                        <?php if ($isDashboard): ?>
                            <div class="d-none d-lg-flex align-items-center me-1">
                                <div class="d-flex align-items-center bg-white rounded-pill px-3 py-1 shadow-sm border border-success-subtle">
                                    <div class="bg-success rounded-circle me-2" style="width: 8px; height: 8px; animation: glow-pulse 2s infinite;"></div>
                                    <span class="text-success small fw-bold" style="font-size: 0.7rem;">Online</span>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Page Action Buttons (Visible on all sizes) -->
                        <?php if (isset($extraNavContent)): ?>
                            <div class="ps-3 me-1">
                                <?php echo str_replace('ms-auto', '', $extraNavContent); ?>
                            </div>
                        <?php endif; ?>

                        <!-- Notifications (always visible on tablet + mobile) -->
                        <?php if ($isDashboard):
                            if (!isset($conn) || !$conn) {
                                require_once __DIR__ . '/db.php';
                            }
                            require_once __DIR__ . '/NotificationHelper.php';
                            $notificationHelper = new NotificationHelper($conn);

                            if ($_SESSION['role'] !== 'superadmin') {
                                $notificationHelper->checkScheduledReminders($_SESSION['id']);
                                $notificationHelper->checkLowAllowance($_SESSION['id']);
                                $notificationHelper->checkBillDeadlines($_SESSION['id']);
                            }

                            $unreadNotifications = $notificationHelper->getUnreadNotifications($_SESSION['id'], $_SESSION['role']);
                            $unreadCount = count($unreadNotifications);
                        ?>
                            <div class="dropdown">
                                <a class="nav-link position-relative px-2" href="#" id="notificationDropdown" role="button"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-bell text-secondary fs-5"></i>
                                    <?php if ($unreadCount > 0): ?>
                                        <span class="position-absolute top-1 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem;">
                                            <?php echo $unreadCount; ?>
                                        </span>
                                    <?php endif; ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end shadow border-0 p-0 overflow-hidden notification-dropdown-menu"
                                    aria-labelledby="notificationDropdown">
                                    <li class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0 fw-bold small text-uppercase text-secondary">Notifications</h6>
                                        <button class="btn btn-link p-0 text-decoration-none small fw-bold <?php echo $unreadCount === 0 ? 'd-none' : ''; ?>"
                                            id="markAllRead" style="font-size: 0.75rem;">Mark all as Read</button>
                                    </li>
                                    <div id="notificationList" class="p-3" style="max-height: 380px; overflow-y: auto;">
                                        <?php if ($unreadCount > 0): ?>
                                            <?php foreach ($unreadNotifications as $notif): ?>
                                                <li class="mb-3 pb-3 border-bottom notification-item">
                                                    <div class="d-flex align-items-start">
                                                        <div class="<?php
                                                                    if (strpos($notif['type'], 'reminder') !== false) echo 'bg-info-subtle text-info';
                                                                    elseif ($notif['type'] === 'low_allowance') echo 'bg-danger-subtle text-danger';
                                                                    elseif (strpos($notif['type'], 'bill_deadline') !== false) echo 'bg-warning-subtle text-warning';
                                                                    elseif ($notif['type'] === 'new_user') echo 'bg-primary-subtle text-primary';
                                                                    else echo 'bg-success-subtle text-success';
                                                                    ?> p-2 rounded-circle me-3">
                                                            <i class="fas <?php
                                                                            if (strpos($notif['type'], 'reminder') !== false) echo 'fa-clock';
                                                                            elseif ($notif['type'] === 'low_allowance') echo 'fa-exclamation-triangle';
                                                                            elseif (strpos($notif['type'], 'bill_deadline') !== false) echo 'fa-file-invoice-dollar';
                                                                            elseif ($notif['type'] === 'new_user') echo 'fa-user-plus';
                                                                            else echo 'fa-hand-holding-usd';
                                                                            ?> small"></i>
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-0 small fw-bold"><?php
                                                                                            if (strpos($notif['type'], 'reminder') !== false) echo 'Expense Reminder';
                                                                                            elseif ($notif['type'] === 'low_allowance') echo 'Low Balance Alert';
                                                                                            elseif (strpos($notif['type'], 'bill_deadline') !== false) echo 'Bill Deadline';
                                                                                            elseif ($notif['type'] === 'new_user') echo 'New User Joined';
                                                                                            else echo 'Allowance Added';
                                                                                            ?></h6>
                                                            <p class="mb-0 text-muted" style="font-size: 0.75rem;"><?php echo htmlspecialchars($notif['message']); ?></p>
                                                            <small class="text-muted" style="font-size: 0.65rem;"><?php echo date('M d, g:i a', strtotime($notif['created_at'])); ?></small>
                                                        </div>
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                    <li class="p-2 text-center bg-light <?php echo $unreadCount > 0 ? 'd-none' : ''; ?>" id="noNotifications">
                                        <span class="text-muted small">No new notifications</span>
                                    </li>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <!-- Profile Avatar (always visible on tablet + mobile) -->
                        <?php if (isset($_SESSION['id']) && $isDashboard): ?>
                            <div class="dropdown">
                                <a class="nav-link dropdown-toggle d-flex align-items-center py-0 ps-1" href="#"
                                    id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <div id="navbarProfilePicContainer"
                                        class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center overflow-hidden shadow-sm"
                                        style="width: 36px; height: 36px; border: 2px solid #fff; flex-shrink: 0;">
                                        <?php if (isset($_SESSION['profile_picture']) && !empty($_SESSION['profile_picture'])): ?>
                                            <img src="<?php echo SITE_URL . htmlspecialchars($_SESSION['profile_picture']); ?>"
                                                alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                                        <?php else: ?>
                                            <i class="fas fa-user" style="font-size: 0.85rem;"></i>
                                        <?php endif; ?>
                                    </div>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3 mt-2" aria-labelledby="navbarDropdown">
                                    <li class="px-3 py-2 border-bottom bg-light">
                                        <span class="text-secondary small fw-bold text-uppercase">My Account</span>
                                    </li>
                                    <li><a class="dropdown-item py-2" href="<?php echo SITE_URL; ?>core/profile.php"><i class="fas fa-user-circle me-2 text-primary"></i>View Profile</a></li>
                                    <li><a class="dropdown-item py-2" href="<?php echo SITE_URL; ?>core/settings.php"><i class="fas fa-cog me-2 text-secondary"></i>Settings</a></li>
                                    <li>
                                        <hr class="dropdown-divider mx-2">
                                    </li>
                                    <li><a class="dropdown-item py-2 text-danger" href="<?php echo SITE_URL; ?>auth/logout.php" onclick="confirmLogout(event)"><i class="fas fa-sign-out-alt me-2 text-danger"></i>Sign Out</a></li>
                                </ul>
                            </div>
                        <?php endif; ?>

                    </div><!-- end RIGHT -->
                </div>
            </nav>

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const markAllRead = document.getElementById('markAllRead');
                    const notificationBadge = document.querySelector('#notificationDropdown .badge');
                    const notificationList = document.getElementById('notificationList');
                    const noNotifications = document.getElementById('noNotifications');
                    let currentCount = <?php echo isset($unreadCount) ? $unreadCount : 0; ?>;

                    function playNotificationSound() {
                        const sound = document.getElementById('notificationSound');
                        if (sound) {
                            sound.play().catch(e => console.log("Audio playback requires user interaction."));
                        }
                    }

                    function checkNewNotifications() {
                        fetch('<?php echo SITE_URL; ?>api/notifications.php?action=count')
                            .then(response => response.json())
                            .then(data => {
                                if (data.success && data.count > currentCount) {
                                    playNotificationSound();
                                    currentCount = data.count;
                                    location.reload();
                                }
                            });
                    }

                    if (markAllRead) {
                        markAllRead.addEventListener('click', function(e) {
                            e.preventDefault();
                            e.stopPropagation();

                            fetch('<?php echo SITE_URL; ?>api/notifications.php?action=mark_read', {
                                    method: 'POST'
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        if (notificationBadge) notificationBadge.classList.add('d-none');
                                        if (notificationList) {
                                            notificationList.innerHTML = '';
                                            notificationList.classList.add('d-none');
                                        }
                                        if (noNotifications) noNotifications.classList.remove('d-none');
                                        if (markAllRead) markAllRead.classList.add('d-none');
                                        currentCount = 0;
                                    }
                                });
                        });
                    }

                    if (currentCount > 0) {
                        setTimeout(playNotificationSound, 1000);
                    }

                    // Poll every 60 seconds for new notifications
                    setInterval(checkNewNotifications, 60000);
                });
            </script>

            <!-- Notification Sound -->
            <audio id="notificationSound" preload="auto">
                <source src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3" type="audio/mpeg">
            </audio>

            <!-- ═══════════════════════════════════════════════════════
                 MOBILE BOTTOM NAVIGATION BAR
                 Visible only on mobile (≤576px) via CSS
                 ═══════════════════════════════════════════════════════ -->
            <?php if ($_SESSION['role'] !== 'superadmin'): ?>
                <nav id="mobile-bottom-nav" aria-label="Mobile Navigation">
                    <a href="<?php echo SITE_URL; ?>core/dashboard.php"
                        class="bottom-nav-item <?php echo ($currentPage == 'dashboard.php' && $currentDir == 'core') ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Home</span>
                    </a>
                    <a href="<?php echo SITE_URL; ?>core/expenses.php"
                        class="bottom-nav-item <?php echo ($currentPage == 'expenses.php') ? 'active' : ''; ?>">
                        <i class="fas fa-receipt"></i>
                        <span>Expenses</span>
                    </a>
                    <a href="<?php echo SITE_URL; ?>core/savings.php"
                        class="bottom-nav-item <?php echo ($currentPage == 'savings.php') ? 'active' : ''; ?>">
                        <i class="fas fa-piggy-bank"></i>
                        <span>Savings</span>
                    </a>
                    <a href="<?php echo SITE_URL; ?>core/bills.php"
                        class="bottom-nav-item <?php echo ($currentPage == 'bills.php') ? 'active' : ''; ?>">
                        <i class="fas fa-file-invoice-dollar"></i>
                        <span>Bills</span>
                    </a>
                    <a href="<?php echo SITE_URL; ?>core/history_log.php"
                        class="bottom-nav-item <?php echo ($currentPage == 'history_log.php') ? 'active' : ''; ?>">
                        <i class="fas fa-history"></i>
                        <span>History</span>
                    </a>
                    <a href="<?php echo SITE_URL; ?>core/profile.php"
                        class="bottom-nav-item <?php echo ($currentPage == 'profile.php') ? 'active' : ''; ?>">
                        <i class="fas fa-user-circle"></i>
                        <span>Profile</span>
                    </a>
                </nav>
            <?php endif; ?>

            <script>
                // Sidebar overlay — close sidebar when backdrop is tapped
                (function() {
                    const overlay = document.getElementById('sidebar-overlay');
                    const toggle = document.getElementById('menu-toggle');
                    if (!overlay || !toggle) return;

                    overlay.addEventListener('click', function() {
                        document.body.classList.remove('sb-sidenav-toggled');
                        overlay.classList.remove('active');
                    });

                    // Also wire the overlay's active class to the toggle button
                    // (works alongside the existing toggleWrapper script)
                    const observer = new MutationObserver(function() {
                        const isMobileOrTablet = window.innerWidth <= 991;
                        if (document.body.classList.contains('sb-sidenav-toggled') && isMobileOrTablet) {
                            overlay.classList.add('active');
                        } else {
                            overlay.classList.remove('active');
                        }
                    });
                    observer.observe(document.body, {
                        attributes: true,
                        attributeFilter: ['class']
                    });
                })();
            </script>