<?php
$pageTitle = 'Reports';
$pageHeader = 'Financial Reports';
include 'includes/header.php';
?>

    <?php include 'includes/sidebar.php'; ?>

    <!-- Page Content -->
    <div id="page-content-wrapper">

        <?php include 'includes/navbar.php'; ?>

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
                    <div class="col-md-3">
                        <div class="card h-100 border-0 shadow-sm rounded-4 bg-light">
                            <div class="card-body text-center p-4">
                                <h6 class="text-secondary small text-uppercase fw-bold mb-3">Total Allowance</h6>
                                <h3 class="fw-bold text-success mb-0" id="reportIncome">₱0.00</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card h-100 border-0 shadow-sm rounded-4 bg-light">
                            <div class="card-body text-center p-4">
                                <h6 class="text-secondary small text-uppercase fw-bold mb-3">Total Expenses</h6>
                                <h3 class="fw-bold text-danger mb-0" id="reportExpenses">₱0.00</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card h-100 border-0 shadow-sm rounded-4 bg-light">
                            <div class="card-body text-center p-4">
                                <h5 class="card-title text-opacity-75"><i class="fas fa-hand-holding-dollar me-2"></i>Total Saved (<?php echo $_SESSION['user_currency'] ?? 'PHP'; ?>)</h5>
                                <h4 class="fw-bold text-primary mb-0" id="reportSavings">₱0.00</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
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
                                    <h5 class="fw-bold mb-0" id="reportDailyAvg">₱0.00</h5>
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
                                    <p class="small mb-0 fst-italic" id="aiQuickTip">Analyzing your data...</p>
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


<?php include 'includes/footer.php'; ?>

<!-- PDF Generation Libraries -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

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

    if(generateBtn) generateBtn.addEventListener('click', fetchReportData);
    if(downloadBtn) downloadBtn.addEventListener('click', downloadPDF);

    function fetchReportData() {
        const type = reportTypeSelect.value;
        const category = reportCategorySelect.value;
        const date = reportDateInput.value;

        if(!date) {
            Swal.fire('Input Required', 'Please select a date or timeframe.', 'warning');
            return;
        }

        fetch(`api/reports.php?type=${type}&date=${date}&t=` + new Date().getTime())
            .then(response => response.json())
            .then(data => {
                if(data.success) {
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
        
        const utilization = data.analytics.budget_utilization.toFixed(1) + '%';
        document.getElementById('utilizationBar').style.width = utilization;
        updateElement('utilizationText', `${utilization} of allowance spent`);
        
        updateElement('reportDailyAvg', formatCurrency(data.analytics.daily_average_expense));

        // Update Chart
        renderReportChart(data, category);
    }

    function updateElement(id, value) {
        const el = document.getElementById(id);
        if(el) el.textContent = value;
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
                plugins: { legend: { position: 'bottom' } },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { callback: value => '₱' + value.toLocaleString() }
                    }
                }
            }
        });
    }

    function updateAIRecommendation(data) {
        const tipEl = document.getElementById('aiQuickTip');
        let tip = "Keep up the good work!";
        
        if (data.analytics.savings_rate < 5) {
            tip = "Try to set aside more for savings early in the month.";
        } else if (data.analytics.budget_utilization > 80) {
            tip = "Your spending is high relative to your allowance. Consider cutting back.";
        } else if (data.total_allowance === 0) {
             tip = "No income recorded for this period. Try adding an allowance!";
        } else {
            tip = "Great balance! You are maintaining a healthy savings rate.";
        }
        tipEl.textContent = tip;
    }

    async function downloadPDF() {
        const { jsPDF } = window.jspdf;
        const content = document.getElementById('reportContent');
        const period = document.getElementById('reportLabel').textContent;
        const category = reportCategorySelect.options[reportCategorySelect.selectedIndex].text;
        
        if (!content) return;

        downloadBtn.disabled = true;
        downloadBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Exporting...';

        try {
            const canvas = await html2canvas(content, {
                scale: 2,
                useCORS: true,
                logging: false,
                backgroundColor: '#ffffff'
            });
            
            const imgData = canvas.toDataURL('image/png');
            const pdf = new jsPDF('p', 'mm', 'a4');
            const pdfWidth = pdf.internal.pageSize.getWidth();
            const pdfHeight = (canvas.height * pdfWidth) / canvas.width;
            
            // PDF Header
            pdf.setFillColor(99, 102, 241); // Primary Theme Color
            pdf.rect(0, 0, 210, 30, 'F');
            pdf.setTextColor(255, 255, 255);
            pdf.setFontSize(22);
            pdf.setFont("helvetica", "bold");
            pdf.text("FINANCIAL REPORT", 15, 20);
            
            pdf.setFontSize(10);
            pdf.setFont("helvetica", "normal");
            pdf.text(`Generated: ${new Date().toLocaleString()}`, 150, 20);

            // Report Info
            pdf.setTextColor(40, 40, 40);
            pdf.setFontSize(14);
            pdf.text(`Period: ${period}`, 15, 45);
            pdf.text(`Category: ${category}`, 15, 52);

            // Content Image
            pdf.addImage(imgData, 'PNG', 5, 60, pdfWidth - 10, pdfHeight - 10);
            
            pdf.save(`BudgetTracker_Report_${period.replace(/ /g, '_')}.pdf`);
            
        } catch (error) {
            console.error('PDF Error:', error);
            Swal.fire('Error', 'Failed to generate PDF. Check console for details.', 'error');
        } finally {
            downloadBtn.disabled = false;
            downloadBtn.innerHTML = '<i class="fas fa-file-export me-2"></i>Export PDF';
        }
    }

    function formatCurrency(amount) {
        return new Intl.NumberFormat(window.userCurrency.locale, { 
            style: 'currency', 
            currency: window.userCurrency.code 
        }).format(amount);
    }
});
</script>