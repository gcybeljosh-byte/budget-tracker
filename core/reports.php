<?php
$pageTitle = 'Reports';
$pageHeader = 'Financial Reports';
include '../includes/header.php';
?>

<?php include '../includes/sidebar.php'; ?>

<!-- Page Content -->
<div id="page-content-wrapper">

    <?php include '../includes/navbar.php'; ?>

    <div class="container-fluid px-4 py-4">
        <div id="alertContainer"></div>

        <!-- Filters -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Report Category</label>
                        <select class="form-select rounded-3" id="reportCategory">
                            <option value="all">All Transactions</option>
                            <option value="allowance">Allowance Only</option>
                            <option value="expense">Expense Only</option>
                            <option value="savings">Savings Only</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Timeframe</label>
                        <select class="form-select rounded-3" id="reportType">
                            <option value="weekly">Weekly</option>
                            <option value="monthly" selected>Monthly</option>
                            <option value="yearly">Yearly</option>
                            <option value="specific">Specific Date</option>
                        </select>
                    </div>
                    <div class="col-md-3" id="dateInputCol">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Select Date/Month/Year</label>
                        <input type="month" class="form-control rounded-3" id="reportDateFilter">
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary w-100 rounded-pill fw-bold shadow-sm" id="generateReportBtn">
                            <i class="fas fa-sync-alt me-2"></i>Generate
                        </button>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-success w-100 rounded-pill fw-bold shadow-sm" id="downloadPdfBtn">
                            <i class="fas fa-file-export me-2"></i>Export PDF
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div id="reportContent">
            <!-- Data Analytics Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-3 stagger-item">
                    <div class="card h-100 border-0 shadow-sm rounded-4 bg-light">
                        <div class="card-body text-center p-4">
                            <h6 class="text-secondary small text-uppercase fw-bold mb-3">Total Allowance</h6>
                            <h3 class="fw-bold text-success mb-0" id="reportIncome"><?php echo CurrencyHelper::getSymbol($_SESSION['user_currency'] ?? 'PHP'); ?>0.00</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 stagger-item">
                    <div class="card h-100 border-0 shadow-sm rounded-4 bg-light">
                        <div class="card-body text-center p-4">
                            <h6 class="text-secondary small text-uppercase fw-bold mb-3">Total Expenses</h6>
                            <h3 class="fw-bold text-danger mb-0" id="reportExpenses"><?php echo CurrencyHelper::getSymbol($_SESSION['user_currency'] ?? 'PHP'); ?>0.00</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 stagger-item">
                    <div class="card h-100 border-0 shadow-sm rounded-4 bg-light">
                        <div class="card-body text-center p-4">
                            <h5 class="card-title text-opacity-75"><i class="fas fa-hand-holding-dollar me-2"></i>Total Saved (<?php echo $_SESSION['user_currency'] ?? 'PHP'; ?>)</h5>
                            <h3 class="fw-bold text-primary mb-0" id="reportSavings"><?php echo CurrencyHelper::getSymbol($_SESSION['user_currency'] ?? 'PHP'); ?>0.00</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 stagger-item">
                    <div class="card h-100 border-0 shadow-sm rounded-4 bg-light">
                        <div class="card-body text-center p-4">
                            <h6 class="text-secondary small text-uppercase fw-bold mb-3">Savings Rate</h6>
                            <h4 class="fw-bold text-dark mb-0" id="reportSavingsRate">0%</h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm rounded-4">
                        <div class="card-body p-4">
                            <h6 class="text-secondary small text-uppercase fw-bold mb-3"><i class="fas fa-chart-pie me-2"></i>Budget Utilization</h6>
                            <div class="progress rounded-pill mb-2" style="height: 12px;">
                                <div id="utilizationBar" class="progress-bar bg-primary" role="progressbar" style="width: 0%"></div>
                            </div>
                            <p class="small text-muted mb-0" id="utilizationText">0% of allowance spent</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm rounded-4">
                        <div class="card-body p-4 d-flex align-items-center">
                            <div class="rounded-circle bg-info-subtle p-3 me-3 text-info">
                                <i class="fas fa-calculator fa-lg"></i>
                            </div>
                            <div>
                                <h6 class="text-secondary small text-uppercase fw-bold mb-1">Daily Avg. Expense</h6>
                                <h5 class="fw-bold mb-0" id="reportDailyAvg"><?php echo CurrencyHelper::getSymbol($_SESSION['user_currency'] ?? 'PHP'); ?>0.00</h5>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm rounded-4" id="aiInsightCard">
                        <div class="card-body p-4 d-flex align-items-center">
                            <div class="rounded-circle p-3 me-3 text-white" style="background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);">
                                <i class="fas fa-robot fa-lg"></i>
                            </div>
                            <div>
                                <h6 class="text-secondary small text-uppercase fw-bold mb-1">AI Recommendation</h6>
                                <p class="small mb-2 fst-italic" id="aiQuickTip">Analyzing your data...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chart -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="card-header bg-white py-3 px-4 border-bottom">
                    <h5 class="mb-0 fw-bold">Financial Analysis - <span id="reportLabel" class="text-primary"></span></h5>
                </div>
                <div class="card-body p-4">
                    <div style="height: 380px;">
                        <canvas id="reportChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<!-- PDF Generation Libraries -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const reportCategorySelect = document.getElementById('reportCategory');
        const reportTypeSelect = document.getElementById('reportType');
        const reportDateInput = document.getElementById('reportDateFilter');
        const generateBtn = document.getElementById('generateReportBtn');
        const downloadBtn = document.getElementById('downloadPdfBtn');

        // Handle Timeframe Type Change
        reportTypeSelect.addEventListener('change', function() {
            if (this.value === 'yearly') {
                reportDateInput.type = 'number';
                reportDateInput.value = new Date().getFullYear();
                reportDateInput.placeholder = 'YYYY';
            } else if (this.value === 'specific' || this.value === 'weekly') {
                reportDateInput.type = 'date';
                reportDateInput.value = new Date().toISOString().slice(0, 10);
            } else {
                reportDateInput.type = 'month';
                reportDateInput.value = new Date().toISOString().slice(0, 7);
            }
        });

        // Initial Load
        fetchReportData();

        if (generateBtn) generateBtn.addEventListener('click', fetchReportData);
        if (downloadBtn) downloadBtn.addEventListener('click', downloadPDF);

        function fetchReportData() {
            const type = reportTypeSelect.value;
            const category = reportCategorySelect.value;
            const date = reportDateInput.value;

            if (!date) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Input Required',
                    text: 'Please select a date or timeframe.',
                    confirmButtonColor: '#6366f1'
                });
                return;
            }

            fetch(`<?php echo SITE_URL; ?>api/reports.php?type=${type}&date=${date}&t=` + new Date().getTime())
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateReportUI(data, category);
                        // Inject into AI context for real-time tip
                        updateAIRecommendation(data);
                    } else {
                        console.error('Report Error:', data.message);
                    }
                })
                .catch(error => console.error('Error fetching report:', error));
        }

        function updateReportUI(data, category) {
            document.getElementById('reportLabel').textContent = data.period;

            // Update Summary Cards
            updateElement('reportIncome', formatCurrency(data.total_allowance));
            updateElement('reportExpenses', formatCurrency(data.total_expenses));
            updateElement('reportSavings', formatCurrency(data.total_savings));

            // Analytics
            const savingsRate = data.analytics.savings_rate.toFixed(1) + '%';
            updateElement('reportSavingsRate', savingsRate);

            // Budget utilization should only look at Allowance expenses
            const utilization = data.analytics.budget_utilization.toFixed(1) + '%';
            document.getElementById('utilizationBar').style.width = utilization;
            updateElement('utilizationText', `${utilization} of allowance spent`);

            updateElement('reportDailyAvg', formatCurrency(data.analytics.daily_average_expense));

            // Update Chart
            renderReportChart(data, category);
        }

        function updateElement(id, value) {
            const el = document.getElementById(id);
            if (el) el.textContent = value;
        }

        function renderReportChart(data, category) {
            const canvas = document.getElementById('reportChart');
            if (!canvas) return;

            const ctx = canvas.getContext('2d');
            const existingChart = Chart.getChart(canvas);
            if (existingChart) existingChart.destroy();

            let labels = [];
            let datasets = [];

            if (category === 'all' || category === 'allowance') {
                datasets.push({
                    label: 'Allowance',
                    data: [data.total_allowance],
                    backgroundColor: '#10b981',
                    borderRadius: 8
                });
            }
            if (category === 'all' || category === 'expense') {
                datasets.push({
                    label: 'Expenses',
                    data: [data.total_expenses],
                    backgroundColor: '#ef4444',
                    borderRadius: 8
                });
            }
            if (category === 'all' || category === 'savings') {
                datasets.push({
                    label: 'Savings',
                    data: [data.total_savings],
                    backgroundColor: '#3b82f6',
                    borderRadius: 8
                });
            }

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: [data.period],
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: value => '₱' + value.toLocaleString()
                            }
                        }
                    }
                }
            });
        }

        function updateAIRecommendation(data) {
            const tipEl = document.getElementById('aiQuickTip');
            let tip = "Keep up the good work!";

            if (data.total_allowance === 0) {
                tip = "No income recorded for this period. Try adding an allowance to see insights!";
            } else if (data.analytics.savings_rate < 5) {
                tip = "Try to set aside more for savings early in the month.";
            } else if (data.analytics.budget_utilization > 80) {
                tip = "Your spending is high relative to your allowance. Consider cutting back.";
            } else {
                tip = "Great balance! You are maintaining a healthy savings rate.";
            }
            tipEl.textContent = tip;
        }

        async function downloadPDF() {
            const {
                jsPDF
            } = window.jspdf;
            const period = document.getElementById('reportLabel').textContent;
            const category = reportCategorySelect.options[reportCategorySelect.selectedIndex].text;
            const size = 'a4';

            downloadBtn.disabled = true;
            downloadBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Exporting...';

            try {
                // 1. Fetch Fresh Data (Ensure we have details)
                const type = reportTypeSelect.value;
                const date = reportDateInput.value;
                const response = await fetch(`<?php echo SITE_URL; ?>api/reports.php?type=${type}&date=${date}&t=` + new Date().getTime());
                const data = await response.json();

                // Allow export even if data is not successful but let's try to proceed if we have a user_name
                if (!data.success && !data.user_name) throw new Error(data.message || 'Could not fetch report data');

                // If data.success is false but we have data (like empty arrays), jspdf-autotable handles it.

                // 2. Initialize PDF
                const pdf = new jsPDF('p', 'mm', size);
                const pageWidth = pdf.internal.pageSize.getWidth();
                const pageHeight = pdf.internal.pageSize.getHeight();
                const margin = 15;
                let currentY = 20;

                // 3. Add Header
                pdf.setFillColor(30, 30, 30); // Darker Gray/Black for minimalist
                pdf.rect(0, 0, pageWidth, 40, 'F');

                pdf.setTextColor(255, 255, 255);
                pdf.setFontSize(24);
                pdf.setFont("helvetica", "bold");
                pdf.text("FINANCIAL STATEMENT", margin, 20);

                pdf.setFontSize(10);
                pdf.setFont("helvetica", "normal");
                pdf.text("Budget Tracker System", margin, 28);
                pdf.text(`Generated on: ${new Date().toLocaleString()}`, pageWidth - margin, 28, {
                    align: 'right'
                });

                // 4. Report Details Section
                currentY = 55;
                pdf.setTextColor(40, 40, 40);
                pdf.setFontSize(11);
                pdf.setFont("helvetica", "bold");
                pdf.text(`Account Name: `, margin, currentY);
                pdf.setFont("helvetica", "normal");
                pdf.text(`${data.user_name}`, margin + 35, currentY);

                currentY += 7;
                pdf.setFont("helvetica", "bold");
                pdf.text(`Reporting Period: `, margin, currentY);
                pdf.setFont("helvetica", "normal");
                pdf.text(`${data.period}`, margin + 35, currentY);

                currentY += 7;
                pdf.setFont("helvetica", "bold");
                pdf.text(`Report Subject: `, margin, currentY);
                pdf.setFont("helvetica", "normal");
                pdf.text(`${category} Transactions`, margin + 35, currentY);

                // 5. Summary Table (Financial Position)
                currentY += 15;
                pdf.setFontSize(13);
                pdf.setFont("helvetica", "bold");
                pdf.setTextColor(30, 30, 30);
                pdf.text("EXECUTIVE SUMMARY", margin, currentY);
                currentY += 5;

                pdf.autoTable({
                    startY: currentY,
                    head: [
                        ['Financial Metric', 'Amount']
                    ],
                    body: [
                        ['Total Allowance / Income', formatCurrency(data.total_allowance, true)],
                        ['Allowance Expenses', formatCurrency(data.allowance_expenses, true)],
                        ['Total Saved (Allocated)', formatCurrency(data.total_savings, true)],
                        ['Net Cash Flow (Remaining)', formatCurrency(data.total_allowance - data.allowance_expenses - data.total_savings, true)],
                        ['Savings Rate', data.analytics.savings_rate.toFixed(2) + '%'],
                        ['Budget Utilization', data.analytics.budget_utilization.toFixed(2) + '%']
                    ],
                    margin: {
                        left: margin,
                        right: margin
                    },
                    theme: 'grid',
                    headStyles: {
                        fillColor: [60, 60, 60],
                        textColor: [255, 255, 255]
                    },
                    styles: {
                        fontSize: 10,
                        cellPadding: 4
                    }
                });

                currentY = pdf.lastAutoTable.finalY + 15;

                // 6. Detailed Tables (Allowances)
                if (data.details.allowances.length > 0 && (reportCategorySelect.value === 'all' || reportCategorySelect.value === 'allowance')) {
                    pdf.setFontSize(12);
                    pdf.setTextColor(30, 30, 30);
                    pdf.text("ALLOWANCE TRANSACTIONS", margin, currentY);
                    pdf.autoTable({
                        startY: currentY + 5,
                        head: [
                            ['Date', 'Description', 'Source', 'Amount']
                        ],
                        body: data.details.allowances.map(a => [a.date, a.description, a.source_type, formatCurrency(a.amount, true)]),
                        margin: {
                            left: margin,
                            right: margin
                        },
                        theme: 'striped',
                        headStyles: {
                            fillColor: [80, 80, 80]
                        },
                        styles: {
                            fontSize: 9
                        }
                    });
                    currentY = pdf.lastAutoTable.finalY + 15;
                }

                // Check for page break
                if (currentY > pageHeight - 70) {
                    pdf.addPage();
                    currentY = 20;
                }

                // 7. Detailed Tables (Expenses)
                if (data.details.expenses.length > 0 && (reportCategorySelect.value === 'all' || reportCategorySelect.value === 'expense')) {
                    pdf.setFontSize(12);
                    pdf.setTextColor(30, 30, 30);
                    pdf.text("EXPENSE TRANSACTIONS", margin, currentY);
                    pdf.autoTable({
                        startY: currentY + 5,
                        head: [
                            ['Date', 'Category', 'Description', 'Source', 'Amount']
                        ],
                        body: data.details.expenses.map(e => [e.date, e.category, e.description, e.source_type, formatCurrency(e.amount, true)]),
                        margin: {
                            left: margin,
                            right: margin
                        },
                        theme: 'striped',
                        headStyles: {
                            fillColor: [80, 80, 80]
                        },
                        styles: {
                            fontSize: 9
                        }
                    });
                    currentY = pdf.lastAutoTable.finalY + 15;
                }

                // Check for page break
                if (currentY > pageHeight - 70) {
                    pdf.addPage();
                    currentY = 20;
                }

                // 8. Detailed Tables (Savings)
                if (data.details.savings.length > 0 && (reportCategorySelect.value === 'all' || reportCategorySelect.value === 'savings')) {
                    pdf.setFontSize(12);
                    pdf.setTextColor(30, 30, 30);
                    pdf.text("SAVINGS ALLOCATIONS", margin, currentY);
                    pdf.autoTable({
                        startY: currentY + 5,
                        head: [
                            ['Date', 'Description', 'Amount']
                        ],
                        body: data.details.savings.map(s => [s.date, s.description, formatCurrency(s.amount, true)]),
                        margin: {
                            left: margin,
                            right: margin
                        },
                        theme: 'striped',
                        headStyles: {
                            fillColor: [80, 80, 80]
                        },
                        styles: {
                            fontSize: 9
                        }
                    });
                    currentY = pdf.lastAutoTable.finalY + 15;
                }

                // 9. Signature Section - Always on bottom of last page
                // If the table is too close to bottom, add a new page
                if (currentY > pageHeight - 65) {
                    pdf.addPage();
                    currentY = 20;
                }

                const sigY = pageHeight - 40;
                pdf.setTextColor(40, 40, 40);
                pdf.setFontSize(10);

                // Draw a separator line before signature area if same page
                pdf.setDrawColor(200, 200, 200);
                pdf.line(margin, sigY - 15, pageWidth - margin, sigY - 15);

                // Left: Prepared By
                const leftSigLineStart = margin;
                const leftSigLineWidth = 75;
                const leftSigLineCenter = leftSigLineStart + (leftSigLineWidth / 2);

                pdf.setDrawColor(40, 40, 40);
                pdf.line(leftSigLineStart, sigY, leftSigLineStart + leftSigLineWidth, sigY);
                pdf.setFont("helvetica", "normal");
                pdf.text("PREPARED BY (Signature over Printed Name)", leftSigLineCenter, sigY + 5, {
                    align: 'center'
                });
                pdf.setFont("helvetica", "bold");
                pdf.text(data.user_name.toUpperCase(), leftSigLineCenter, sigY - 3, {
                    align: 'center'
                });

                // Right: Date
                const today = new Date().toISOString().split('T')[0];
                const rightSigLineWidth = 60;
                const rightSigLineStart = pageWidth - margin - rightSigLineWidth;
                const rightSigLineCenter = rightSigLineStart + (rightSigLineWidth / 2);

                pdf.setFont("helvetica", "normal");
                pdf.line(rightSigLineStart, sigY, rightSigLineStart + rightSigLineWidth, sigY);
                pdf.text("DATE SIGNED", rightSigLineCenter, sigY + 5, {
                    align: 'center'
                });
                pdf.setFont("helvetica", "bold");
                pdf.text(today, rightSigLineCenter, sigY - 3, {
                    align: 'center'
                });

                const pdfBlob = pdf.output('blob');
                const formData = new FormData();
                const archiveName = `Financial_Report_${data.period.replace(/ /g, '_')}.pdf`;
                formData.append('pdf', pdfBlob);
                formData.append('name', archiveName);

                fetch('<?php echo SITE_URL; ?>api/save_report.php', {
                        method: 'POST',
                        body: formData
                    }).then(r => r.json())
                    .then(res => console.log('Archive status:', res))
                    .catch(err => console.error('Archive error:', err));

                pdf.save(archiveName);

            } catch (error) {
                console.error('PDF Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to generate PDF: ' + error.message,
                    confirmButtonColor: '#6366f1'
                });
            } finally {
                downloadBtn.disabled = false;
                downloadBtn.innerHTML = '<i class="fas fa-file-export me-2"></i>Export PDF';
            }
        }

        function formatCurrency(amount, forPdf = false) {
            if (forPdf) {
                // Use currency code (PHP, USD, EUR) in PDF for best compatibility
                return window.userCurrency.code + ' ' + amount.toLocaleString(window.userCurrency.locale, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }
            return new Intl.NumberFormat(window.userCurrency.locale, {
                style: 'currency',
                currency: window.userCurrency.code
            }).format(amount);
        }

        // --- Page Tutorial ---
        <?php if (!isset($seen_tutorials['reports.php'])): ?>

            function startTutorial() {
                if (window.seenTutorials['reports.php']) return;

                const steps = [{
                        title: 'Financial Reports',
                        text: 'Visualize your spending habits and gain deep insights into your finances.',
                        icon: 'info',
                        confirmButtonText: 'Next'
                    },
                    {
                        title: 'Report Filters',
                        text: 'Select specific categories and date ranges to customize your report.',
                        icon: 'filter',
                        confirmButtonText: 'Next',
                        target: '.card.shadow-sm.mb-4:first-of-type'
                    },
                    {
                        title: 'Summary Stats',
                        text: 'Get a quick glance at your total expenses and category breakdowns.',
                        icon: 'chart-pie',
                        confirmButtonText: 'Next',
                        target: '.row.g-4.mb-4'
                    },
                    {
                        title: 'Visual Charts',
                        text: 'Easily spot trends with interactive charts showing your data over time.',
                        icon: 'chart-line',
                        confirmButtonText: 'Next',
                        target: '#reportChart'
                    },
                    {
                        title: 'Export to PDF',
                        text: 'Need a physical copy? Generate and download a PDF version of your report.',
                        icon: 'file-pdf',
                        confirmButtonText: 'Finish',
                        target: '#downloadPdfBtn'
                    }
                ];

                function showStep(index) {
                    if (index >= steps.length) {
                        markPageTutorialSeen('reports.php');
                        return;
                    }
                    const step = steps[index];
                    Swal.fire({
                        title: step.title,
                        text: step.text,
                        icon: 'info',
                        confirmButtonText: step.confirmButtonText,
                        showCancelButton: true,
                        cancelButtonText: 'Skip',
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
                                    setTimeout(() => el.classList.remove('tutorial-highlight'), 3000);
                                }
                            }
                        }
                    }).then((result) => {
                        if (result.isConfirmed) showStep(index + 1);
                        else if (result.dismiss === Swal.DismissReason.cancel) markPageTutorialSeen('reports.php');
                    });
                }
                if (!document.getElementById('tutorial-styles')) {
                    const style = document.createElement('style');
                    style.id = 'tutorial-styles';
                    style.textContent = `.tutorial-highlight { outline: 4px solid var(--primary); outline-offset: 4px; border-radius: 12px; transition: outline 0.3s ease; z-index: 9999; position: relative; }`;
                    document.head.appendChild(style);
                }
                showStep(0);
            }
            setTimeout(startTutorial, 1000);
        <?php endif; ?>
    });
</script>