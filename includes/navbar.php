            <!-- Top Navbar -->
            <nav class="navbar navbar-expand-lg">
                <div class="container-fluid px-0">
                    <div class="d-flex align-items-center">
                        <button class="btn btn-link me-2 p-0 shadow-none border-0" id="menu-toggle" style="font-size: 1.25rem;">
                            <i class="fas fa-bars"></i>
                        </button>
                        <h2 class="m-0"><?php echo isset($pageHeader) ? $pageHeader : 'Dashboard Overview'; ?></h2>
                    </div>

                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent"
                        aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>

                    <div class="collapse navbar-collapse" id="navbarSupportedContent">
                        <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center gap-3">

                            <!-- 1. Informational: Clock -->
                            <?php
                            $currentPage = basename($_SERVER['PHP_SELF']);
                            $currentDir = basename(dirname($_SERVER['PHP_SELF']));
                            $isDashboard = ($currentPage === 'dashboard.php' && ($currentDir == 'core' || $currentDir == 'admin')) || $currentPage === 'profile.php' || $currentPage === 'settings.php' || $currentPage === 'logs.php';
                            if ($isDashboard):
                            ?>
                                <li class="nav-item d-none d-lg-block border-end pe-3">
                                    <div id="realtime-clock" class="text-muted fw-bold small" style="min-width: 80px; text-align: center;">--:--:--</div>
                                </li>
                            <?php endif; ?>

                            <!-- 2. Tools: Notifications -->
                            <?php if ($isDashboard):
                                if (!isset($conn) || !$conn) {
                                    require_once __DIR__ . '/db.php';
                                }
                                require_once __DIR__ . '/NotificationHelper.php';
                                $notificationHelper = new NotificationHelper($conn);

                                // Only regular users and admins get balance/reminder alerts
                                if ($_SESSION['role'] !== 'superadmin') {
                                    $notificationHelper->checkScheduledReminders($_SESSION['id']);
                                    $notificationHelper->checkLowAllowance($_SESSION['id']);
                                    $notificationHelper->checkBillDeadlines($_SESSION['id']);
                                }

                                $unreadNotifications = $notificationHelper->getUnreadNotifications($_SESSION['id'], $_SESSION['role']);
                                $unreadCount = count($unreadNotifications);
                            ?>
                                <li class="nav-item dropdown me-1">
                                    <a class="nav-link position-relative px-2" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-bell text-secondary fs-5"></i>
                                        <?php if ($unreadCount > 0): ?>
                                            <span class="position-absolute top-1 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem;">
                                                <?php echo $unreadCount; ?>
                                            </span>
                                        <?php endif; ?>
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 p-0 overflow-hidden" aria-labelledby="notificationDropdown" style="width: 320px;">
                                        <li class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0 fw-bold small text-uppercase text-secondary">Notifications</h6>
                                            <button class="btn btn-link p-0 text-decoration-none small fw-bold <?php echo $unreadCount === 0 ? 'd-none' : ''; ?>" id="markAllRead" style="font-size: 0.75rem;">Mark all as Read</button>
                                        </li>
                                        <div id="notificationList" class="p-3" style="max-height: 400px; overflow-y: auto;">
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
                                </li>
                            <?php endif; ?>

                            <!-- 3. Status: Online Indicator -->
                            <?php if ($isDashboard): ?>
                                <li class="nav-item d-none d-md-block me-2">
                                    <div class="d-flex align-items-center bg-white rounded-pill px-3 py-1 shadow-sm border border-success-subtle">
                                        <div class="bg-success rounded-circle me-2" style="width: 8px; height: 8px; animation: glow-pulse 2s infinite;"></div>
                                        <span class="text-success small fw-bold" style="font-size: 0.7rem;">Online</span>
                                    </div>
                                </li>
                            <?php endif; ?>

                            <!-- 4. Actions: Page Specific Buttons (+) -->
                            <?php if (isset($extraNavContent)): ?>
                                <li class="nav-item border-start ps-3 me-2">
                                    <?php echo str_replace('ms-auto', '', $extraNavContent); ?>
                                </li>
                            <?php endif; ?>

                            <!-- 5. Identity: Profile Dropdown -->
                            <?php if (isset($_SESSION['id']) && $isDashboard): ?>
                                <li class="nav-item dropdown border-start ps-3">
                                    <a class="nav-link dropdown-toggle d-flex align-items-center py-0" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <div id="navbarProfilePicContainer" class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2 overflow-hidden shadow-sm" style="width: 38px; height: 38px; border: 2px solid #fff;">
                                            <?php if (isset($_SESSION['profile_picture']) && !empty($_SESSION['profile_picture'])): ?>
                                                <img src="<?php echo SITE_URL . htmlspecialchars($_SESSION['profile_picture']); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                                            <?php else: ?>
                                                <i class="fas fa-user"></i>
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
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
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