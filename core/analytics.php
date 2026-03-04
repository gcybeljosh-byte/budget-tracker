<?php
$pageTitle  = 'Analytics';
$pageHeader = 'Analytics & Insights';
include '../includes/header.php';

// Superadmin or regular user
if ($_SESSION['role'] === 'superadmin') {
    header("Location: " . SITE_URL . "admin/dashboard.php");
    exit;
}
?>
<?php include '../includes/sidebar.php'; ?>

<div id="page-content-wrapper">
    <?php include '../includes/navbar.php'; ?>

    <div class="container-fluid px-4 py-4">

        <!-- Header -->
        <div class="mb-4 fade-up">
            <p class="text-secondary small mb-0">Spending trends, heatmap, and AI-powered budget forecast.</p>
        </div>

        <!-- AI Budget Forecast -->
        <div class="row g-4 mb-4 stagger-item" id="forecastRow">
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4 p-4" id="forecastCard">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary-subtle text-primary rounded-circle p-2 me-3">
                            <i class="fas fa-robot fs-5"></i>
                        </div>
                        <h5 class="fw-bold mb-0">AI Budget Forecast</h5>
                        <span class="badge bg-primary-subtle text-primary rounded-pill ms-2 small">Beta</span>
                    </div>
                    <div class="row g-3" id="forecastStats">
                        <div class="col-12 text-center text-muted py-3"><i class="fas fa-spinner fa-spin me-2"></i>Loading forecast...</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Expense Trends -->
        <div class="row g-4 mb-4 stagger-item">
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4 p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="d-flex align-items-center">
                            <div class="bg-danger-subtle text-danger rounded-circle p-2 me-3">
                                <i class="fas fa-chart-bar fs-5"></i>
                            </div>
                            <h5 class="fw-bold mb-0">Expense Trends</h5>
                        </div>
                        <span class="text-muted small">Last 6 months by category</span>
                    </div>
                    <canvas id="trendsChart" height="90"></canvas>
                    <div id="trendsEmpty" class="text-center text-muted py-4 d-none">
                        <i class="fas fa-chart-bar fa-2x mb-2 opacity-25"></i>
                        <p class="mb-0 small">No expense data yet.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Spending Heatmap -->
        <div class="row g-4 mb-4 stagger-item">
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4 p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="d-flex align-items-center">
                            <div class="bg-warning-subtle text-warning rounded-circle p-2 me-3">
                                <i class="fas fa-fire fs-5"></i>
                            </div>
                            <h5 class="fw-bold mb-0">Spending Heatmap</h5>
                        </div>
                        <span class="text-muted small" id="heatmapMonth"><?php echo date('F Y'); ?></span>
                    </div>
                    <div id="heatmapGrid" class="d-flex flex-wrap gap-1 justify-content-start"></div>
                    <div class="d-flex align-items-center gap-2 mt-3 small text-muted">
                        <span>Low</span>
                        <div class="d-flex gap-1">
                            <?php foreach (['#e0f2fe', '#7dd3fc', '#f6c23e', '#f97316', '#ef4444'] as $c): ?>
                                <div style="width:18px;height:18px;background:<?php echo $c; ?>;border-radius:3px;"></div>
                            <?php endforeach; ?>
                        </div>
                        <span>High</span>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<style>
    .heatmap-day {
        width: 38px;
        height: 38px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.7rem;
        font-weight: 600;
        cursor: default;
        position: relative;
        transition: transform 0.15s;
        border: 1px solid var(--border-dim);
    }

    .heatmap-day:hover {
        transform: scale(1.2);
        z-index: 10;
    }

    .heatmap-day .tooltip-hover {
        display: none;
        position: absolute;
        bottom: 110%;
        left: 50%;
        transform: translateX(-50%);
        background: var(--card-bg);
        color: var(--text-main);
        padding: 4px 8px;
        border: 1px solid var(--border-dim);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        border-radius: 6px;
        white-space: nowrap;
        font-size: 0.65rem;
        z-index: 100;
    }

    .heatmap-day:hover .tooltip-hover {
        display: block;
    }

    .forecast-card {
        background: linear-gradient(135deg, #667eea22, #764ba222);
        border-radius: 12px;
        padding: 16px;
    }
</style>

<script>
    const SITE_URL = '<?php echo SITE_URL; ?>';

    // ─── Forecast ────────────────────────────────────────────────
    fetch(SITE_URL + 'api/analytics.php?action=forecast')
        .then(r => r.json()).then(d => {
            if (!d.success) return;
            const sym = '₱';
            const statusColor = d.is_on_track ? 'success' : 'danger';
            const trendBadge = d.is_on_track ?
                '<span class="badge bg-success-subtle text-success rounded-pill extra-small"><i class="fas fa-check-circle me-1"></i>On Track</span>' :
                '<span class="badge bg-danger-subtle text-danger rounded-pill extra-small"><i class="fas fa-exclamation-triangle me-1"></i>Over Budget Soon</span>';

            const runwayText = d.runway_days === null ? 'More data required' : `${d.runway_days} days`;
            const runwayColor = (d.runway_days === null) ? 'muted' : (d.runway_days < 7 ? 'danger' : (d.runway_days < 14 ? 'warning' : 'success'));

            document.getElementById('forecastStats').innerHTML = `
            <div class="col-md-3">
                <div class="forecast-card text-center">
                    <div class="text-muted small mb-1">Current Balance</div>
                    <div class="fs-4 fw-bold text-primary">${sym}${d.current_balance.toLocaleString('en-PH', {minimumFractionDigits:2})}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="forecast-card text-center">
                    <div class="text-muted small mb-1">Projected End-of-Month</div>
                    <div class="fs-4 fw-bold text-${statusColor}">${sym}${d.projected_balance.toLocaleString('en-PH', {minimumFractionDigits:2})}</div>
                    <div class="mt-1">${trendBadge}</div>
                    <div class="extra-small text-muted mt-2" style="font-size: 0.6rem;" title="${d.basis}">
                        <i class="fas fa-info-circle me-1"></i>Basis: Run rate projection
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="forecast-card text-center">
                    <div class="text-muted small mb-1">Daily Avg Spend</div>
                    <div class="fs-4 fw-bold text-main">${sym}${d.daily_avg_spend.toLocaleString('en-PH', {minimumFractionDigits:2})}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="forecast-card text-center">
                    <div class="text-muted small mb-1">Balance Runway</div>
                    <div class="fs-4 fw-bold text-${runwayColor}">${runwayText}</div>
                </div>
            </div>
        `;
        });

    // ─── Trends Chart ────────────────────────────────────────────
    fetch(SITE_URL + 'api/analytics.php?action=trends')
        .then(r => r.json()).then(d => {
            if (!d.success || !d.datasets.length) {
                document.getElementById('trendsEmpty').classList.remove('d-none');
                return;
            }
            renderTrendsChart(d.labels, d.datasets);
        });

    function renderTrendsChart(labels, datasets) {
        const canvas = document.getElementById('trendsChart');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        const existingChart = Chart.getChart(canvas);
        if (existingChart) existingChart.destroy();

        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        const textColor = isDark ? '#98989d' : '#8e8e93';
        const gridColor = isDark ? 'rgba(255,255,255,0.1)' : '#f0f0f0';

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: datasets
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            color: isDark ? '#ffffff' : '#1c1c1e'
                        }
                    }
                },
                scales: {
                    x: {
                        stacked: false,
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: textColor
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: gridColor
                        },
                        ticks: {
                            color: textColor
                        }
                    }
                }
            }
        });
    }

    window.addEventListener('themeChanged', function() {
        // Re-render chart and heatmap if we have data
        location.reload(); // Simple way for analytics to handle multiple charts/heatmaps
    });

    // ─── Heatmap ─────────────────────────────────────────────────
    fetch(SITE_URL + 'api/analytics.php?action=heatmap')
        .then(r => r.json()).then(d => {
            if (!d.success) return;
            const grid = document.getElementById('heatmapGrid');
            const days = d.days_in_month;
            const data = d.data;
            const vals = Object.values(data).map(Number);
            const max = vals.length ? Math.max(...vals) : 1;
            const today = new Date().getDate();
            const yearMonth = d.month;

            const colors = ['#e0f2fe', '#7dd3fc', '#f6c23e', '#f97316', '#ef4444'];

            for (let day = 1; day <= days; day++) {
                const dateKey = yearMonth + '-' + String(day).padStart(2, '0');
                const amount = data[dateKey] || 0;
                const ratio = max > 0 ? amount / max : 0;
                const ci = amount === 0 ? 0 : Math.min(4, Math.ceil(ratio * 4));
                const isDark = document.documentElement.getAttribute('data-theme') === 'dark';

                const bg = amount === 0 ? (isDark ? 'rgba(255,255,255,0.05)' : '#f8f9fa') : colors[ci];
                const isToday = day === today;

                const el = document.createElement('div');
                el.className = 'heatmap-day';
                el.style.background = bg;
                el.style.color = ci >= 3 ? '#fff' : (isDark ? '#cbd5e1' : '#334155');
                if (isToday) el.style.outline = isDark ? '2px solid #fff' : '2px solid #334155';
                el.innerHTML = `
                    ${day}
                    <div class="tooltip-hover">Day ${day}: ₱${amount.toLocaleString('en-PH', {minimumFractionDigits:2})}</div>
                `;
                grid.appendChild(el);
            }
        });

    // --- Page Tutorial ---
    <?php if (!isset($seen_tutorials['analytics.php'])): ?>
        document.addEventListener('DOMContentLoaded', function() {
            if (window.seenTutorials && window.seenTutorials['analytics.php']) return;

            const steps = [{
                    title: '📈 Analytics & Insights',
                    text: 'This page gives you a deep look at your spending patterns — powered by real data and AI. Let\'s explore the sections!'
                },
                {
                    title: '🤖 AI Budget Forecast',
                    text: 'The AI analyzes your spending history to project your end-of-month balance, daily average spend, and how many days your budget will last.',
                    target: '#forecastCard'
                },
                {
                    title: '📊 Expense Trends',
                    text: 'This bar chart shows your spending by category over the last 6 months. Spot which categories are growing — and where to cut back.',
                    target: '#trendsChart'
                },
                {
                    title: '🔥 Spending Heatmap',
                    text: 'Each square is a day this month. Darker red = higher spend. Hover over any day to see the exact amount you spent.',
                    target: '#heatmapGrid'
                },
                {
                    title: '💡 Use These Insights',
                    text: 'Bring any question to your AI Help Desk! Ask "Am I on track this month?" or "Which category am I overspending?" for personalized advice.'
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
                    markPageTutorialSeen('analytics.php');
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
                    else if (result.dismiss === Swal.DismissReason.cancel) markPageTutorialSeen('analytics.php');
                });
            }

            setTimeout(() => showStep(0), 1200);
        });
    <?php endif; ?>
</script>

<?php include '../includes/footer.php'; ?>