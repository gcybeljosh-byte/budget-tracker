<?php
include '../includes/header.php';
?>
<?php include '../includes/sidebar.php'; ?>

<div id="page-content-wrapper">
    <?php include '../includes/navbar.php'; ?>

    <div class="container-fluid px-4 py-4">
        <div class="mb-4 fade-up">
            <h4 class="fw-bold mb-1">Statement of Accounts</h4>
            <p class="text-secondary small mb-0">Historical summary of your monthly financial performance.</p>
        </div>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 staggered-item">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Month</th>
                                <th class="text-end">Total Income</th>
                                <th class="text-end">Total Spent</th>
                                <th class="text-end">Net Savings</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody id="statementsTableBody">
                            <!-- Dynamic Content -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        fetchStatements();

        function fetchStatements() {
            fetch('<?php echo SITE_URL; ?>api/statements.php')
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        renderStatements(data.data);
                    }
                })
                .catch(err => console.error('Error fetching statements:', err));
        }

        function renderStatements(statements) {
            const tbody = document.getElementById('statementsTableBody');
            tbody.innerHTML = '';

            if (statements.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted">No historical data available yet.</td></tr>';
                return;
            }

            statements.forEach(s => {
                const netColor = s.net >= 0 ? 'text-success' : 'text-danger';
                const row = `
                <tr class="transition-all hover-lift">
                    <td class="fw-bold">${s.month_name}</td>
                    <td class="text-end text-success">+${formatCurrency(s.income)}</td>
                    <td class="text-end text-danger">-${formatCurrency(s.expenses)}</td>
                    <td class="text-end fw-bold ${netColor}">${formatCurrency(s.net)}</td>
                    <td class="text-center">
                        <button class="btn btn-primary btn-sm rounded-pill px-3" onclick="viewDetailedReport('${s.month}')">
                            <i class="fas fa-file-pdf me-2"></i>View Report
                        </button>
                    </td>
                </tr>
            `;
                tbody.insertAdjacentHTML('beforeend', row);
            });
        }

        window.viewDetailedReport = function(month) {
            // Redirect to reports page with the specific month
            const reportUrl = `reports.php?type=monthly&date=${month}-01`;
            location.href = reportUrl;
        };

        function formatCurrency(amount) {
            return new Intl.NumberFormat(window.userCurrency.locale, {
                style: 'currency',
                currency: window.userCurrency.code
            }).format(amount);
        }

        // --- Page Tutorial ---
        <?php if (!isset($seen_tutorials['statements.php'])): ?>
            if (!(window.seenTutorials && window.seenTutorials['statements.php'])) {
                const steps = [{
                        title: '📄 Statement of Accounts',
                        text: 'This page shows a monthly summary of your financial performance — income earned, money spent, and net savings for each period.'
                    },
                    {
                        title: '💰 Total Income',
                        text: 'The green column shows your total allowances and income received that month.'
                    },
                    {
                        title: '💸 Total Spent & Net Savings',
                        text: 'The red column shows total expenses. Net Savings is what remained — green means you saved money, red means you overspent.',
                        target: '#statementsTableBody'
                    },
                    {
                        title: '📊 View Detailed Report',
                        text: 'Click "View Report" on any row to jump to the full monthly breakdown — categorized expenses, savings, and AI insights for that period.'
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
                        markPageTutorialSeen('statements.php');
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
                        else if (result.dismiss === Swal.DismissReason.cancel) markPageTutorialSeen('statements.php');
                    });
                }

                setTimeout(() => showStep(0), 1000);
            }
        <?php endif; ?>
    });
</script>

<?php include '../includes/footer.php'; ?>