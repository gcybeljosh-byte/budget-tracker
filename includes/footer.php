    </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    </script>
    <script type="module" src="<?php echo SITE_URL; ?>assets/js/app.js"></script>
    <script src="<?php echo SITE_URL; ?>assets/js/clock.js"></script>
    <script src="<?php echo SITE_URL; ?>assets/js/smooth-interactions.js"></script>
    <!-- Chat Widget -->
    <?php include __DIR__ . '/chat_widget.php'; ?>

    <script>
        // Logout Confirmation
        function confirmLogout(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Ready to Leave?',
                text: "Select 'Logout' below if you are ready to end your current session.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Logout',
                confirmButtonColor: '#6366f1',
                cancelButtonColor: '#d33',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '<?php echo SITE_URL; ?>auth/logout.php';
                }
            });
        }

        // Help Desk Deep Analysis Trigger
        function triggerAiAnalysis(module) {
            const chatInput = document.getElementById('widgetUserMessage');
            const chatForm = document.getElementById('widgetChatForm');
            const chatWidget = document.getElementById('aiChatWidget');

            if (!chatInput || !chatForm) return;

            // Open widget if closed
            if (chatWidget && chatWidget.style.display === 'none') {
                toggleChatWidget();
            }

            let prompt = "";
            switch (module) {
                case 'savings':
                    prompt = "Analyze my savings growth and sources for this year. Am I on track?";
                    break;
                case 'expenses':
                    prompt = "Analyze my recent expenses. Where is my money going and how can I save more?";
                    break;
                case 'reports':
                    prompt = "Explain my current financial report. What are the key takeaways and where can I improve?";
                    break;
            }

            if (prompt) {
                chatInput.value = prompt;
                // Auto-submit after a short delay for a premium feel
                setTimeout(() => {
                    chatForm.dispatchEvent(new Event('submit'));
                }, 500);
            }
        }
    </script>

    <script>
        /* ── Smooth Page Transition ── */
        (function() {
            // Fade body out before navigating away
            document.addEventListener('click', function(e) {
                const link = e.target.closest('a[href]');
                if (!link) return;
                const href = link.getAttribute('href');
                // Skip: no href, same-page anchors, js: links, Bootstrap toggles, blank targets, or links with their own onclick (e.g. logout confirm)
                if (!href || href.startsWith('#') || href.startsWith('javascript') ||
                    link.hasAttribute('data-bs-toggle') || link.target === '_blank' ||
                    link.hasAttribute('onclick')) return;
                e.preventDefault();
                document.body.style.transition = 'opacity 0.18s ease';
                document.body.style.opacity = '0';
                setTimeout(() => {
                    window.location.href = href;
                }, 180);
            });
        })();
    </script>

    <!-- Anti-Inspection & DevTools Protection -->
    <script>
        (function() {
            // Disable Right-click
            document.addEventListener('contextmenu', e => e.preventDefault());

            // Forbidden Keys Detection
            document.addEventListener('keydown', function(e) {
                // F12, Ctrl+Shift+I, Ctrl+Shift+J, Ctrl+U, Ctrl+S
                if (
                    e.keyCode === 123 ||
                    (e.ctrlKey && e.shiftKey && (e.keyCode === 73 || e.keyCode === 74 || e.keyCode === 67)) ||
                    (e.ctrlKey && (e.keyCode === 85 || e.keyCode === 83))
                ) {
                    e.preventDefault();
                    return false;
                }
            });

            // Periodic Console Clearing
            /*
            setInterval(() => {
                console.clear();
            }, 1000);
            */

            // DevTools simple detection via window size
            const threshold = 160;
            const checkDevTools = () => {
                if (window.outerWidth - window.innerWidth > threshold || window.outerHeight - window.innerHeight > threshold) {
                    // If DevTools is likely open, we could redirect or show a warning
                    // For now, just keeping the console cleared is a good first step.
                }
            };
            window.addEventListener('resize', checkDevTools);
        })();
    </script>

    <!-- Session Timeout Script -->
    <script>
        (function() {
            // Minutes of inactivity before warning (5 minutes)
            const INACTIVITY_LIMIT = 5 * 60 * 1000;
            // Seconds to wait for a response after warning (60 seconds)
            const GRACE_PERIOD = 60;

            let idleTimer;
            let warningTimer;
            let countdown = GRACE_PERIOD;
            let isWarningVisible = false;

            function resetIdleTimer() {
                if (isWarningVisible) return;

                clearTimeout(idleTimer);
                idleTimer = setTimeout(showTimeoutWarning, INACTIVITY_LIMIT);
            }

            function showTimeoutWarning() {
                isWarningVisible = true;
                countdown = GRACE_PERIOD;

                Swal.fire({
                    title: 'Session Expiring',
                    html: `You have been inactive for 5 minutes. You will be logged out in <strong id="timeout-countdown">${countdown}</strong> seconds for your security.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Stay Logged In',
                    cancelButtonText: 'Logout',
                    confirmButtonColor: '#6366f1',
                    cancelButtonColor: '#d33',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => {
                        warningTimer = setInterval(() => {
                            countdown--;
                            const timerDisplay = document.getElementById('timeout-countdown');
                            if (timerDisplay) timerDisplay.textContent = countdown;

                            if (countdown <= 0) {
                                clearInterval(warningTimer);
                                performForcedLogout();
                            }
                        }, 1000);
                    }
                }).then((result) => {
                    clearInterval(warningTimer);
                    if (result.isConfirmed) {
                        keepSessionAlive();
                    } else if (result.dismiss === Swal.DismissReason.cancel) {
                        window.location.href = '<?php echo SITE_URL; ?>auth/logout.php';
                    }
                });
            }

            function keepSessionAlive() {
                fetch('<?php echo SITE_URL; ?>api/session_keep_alive.php')
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            isWarningVisible = false;
                            resetIdleTimer();
                        } else {
                            // If keep-alive fails (e.g. session already lost), force logout
                            performForcedLogout();
                        }
                    })
                    .catch(() => performForcedLogout());
            }

            function performForcedLogout() {
                window.location.href = '<?php echo SITE_URL; ?>auth/logout.php?auto=1';
            }

            // Events that count as activity
            const activityEvents = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];
            activityEvents.forEach(evt => {
                document.addEventListener(evt, resetIdleTimer, true);
            });

            // Start the timer
            resetIdleTimer();
        })();
    </script>

    </body>

    </html>