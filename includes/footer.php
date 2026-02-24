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

    </body>

    </html>