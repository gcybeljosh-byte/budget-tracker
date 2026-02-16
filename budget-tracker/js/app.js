import DataManager from './data.js';

// App Logic
class BudgetApp {
    constructor() {
        this.dataManager = new DataManager();
        this.initEventListeners();
        this.render();
    }

    initEventListeners() {
        // Allowance logic moved to allowance.php inline script
        // Expense logic moved to expenses.php inline script
        // Report Generation logic moved to reports.php inline script

        this.initInactivityTimer();
    }

    initInactivityTimer() {
        this.idleTime = 0;
        this.idleThreshold = 300; // 5 minutes in seconds
        this.warningThreshold = 270; // 4 minutes 30 seconds
        this.isWarningShown = false;

        const resetTimer = () => {
            if (this.isWarningShown) {
                Swal.close();
                this.isWarningShown = false;
            }
            this.idleTime = 0;
        };

        ['mousedown', 'mousemove', 'keydown', 'scroll', 'touchstart', 'click'].forEach(evt => {
            document.addEventListener(evt, resetTimer, true);
        });

        this.idleInterval = setInterval(() => {
            this.idleTime++;
            if (this.idleTime >= this.warningThreshold && !this.isWarningShown) {
                this.isWarningShown = true;
                this.showSecurityWarning();
            }
            if (this.idleTime >= this.idleThreshold) {
                clearInterval(this.idleInterval);
                window.location.href = 'logout.php?auto=1';
            }
        }, 1000);
    }

    showSecurityWarning() {
        const timeLeft = this.idleThreshold - this.idleTime;
        Swal.fire({
            title: 'Inactivity Warning',
            html: `Your session will expire in <b>${timeLeft}</b> seconds due to inactivity.`,
            icon: 'warning',
            confirmButtonText: 'I\'m still here!',
            timer: timeLeft * 1000,
            timerProgressBar: true,
            allowOutsideClick: false,
            didOpen: () => {
                const b = Swal.getHtmlContainer().querySelector('b');
                this.countdownInterval = setInterval(() => {
                    const remaining = this.idleThreshold - this.idleTime;
                    if (b) b.textContent = Math.max(0, remaining);
                }, 1000);
            },
            willClose: () => clearInterval(this.countdownInterval)
        });
    }

    switchTab(targetLink) {
        // Remove active class from all links
        document.querySelectorAll('.navbar-nav .nav-link').forEach(link => link.classList.remove('active'));
        // Add active class to clicked link
        targetLink.classList.add('active');

        // Hide all sections
        document.querySelectorAll('.tab-content').forEach(section => {
            section.classList.add('d-none');
            section.classList.remove('active-tab');
        });

        // Show target section
        const targetId = targetLink.getAttribute('href').substring(1);
        const targetSection = document.getElementById(targetId);
        targetSection.classList.remove('d-none');
        targetSection.classList.add('active-tab');

        // Refresh data if needed when switching
        if (targetId === 'dashboard') this.renderDashboard();
        if (targetId === 'allowance') this.renderAllowanceTable();
        if (targetId === 'expenses') this.renderExpenseTable();
    }

    setupDatePicker(elementId) {
        const el = document.getElementById(elementId);
        if (el) {
            el.value = new Date().toISOString().split('T')[0];
        }
    }

    /* --- Data Handling Wrappers --- */

    // handleAddAllowance removed


    // handleAddExpense removed


    // handleEditAllowance/UpdateAllowance removed


    // handleEditExpense/UpdateExpense removed


    /* --- Rendering --- */

    /* --- DataTables & Rendering --- */

    async render() {
        if (document.getElementById('dashTotalAllowance')) {
            await this.renderDashboard();
        }
        if (document.getElementById('allowanceTable')) {
            // Render logic moved to allowance.php inline script
        }
        if (document.getElementById('expensesTable')) {
            // Render logic moved to expenses.php inline script
        }
        if (document.getElementById('reportChart')) {
            // Render logic moved to reports.php inline script
        }
    }

    // Initialize or get existing DataTable
    initDataTable(tableId, options = {}) {
        if ($.fn.DataTable.isDataTable(`#${tableId}`)) {
            return $(`#${tableId}`).DataTable();
        }
        return $(`#${tableId}`).DataTable({
            responsive: true,
            order: [[0, 'desc']], // Default sort by first column (Date) desc
            ...options
        });
    }

    async renderDashboard() {
        // Logic moved to index.php inline script
    }


    updateElement(id, value) {
        const el = document.getElementById(id);
        if (el) el.textContent = value;
    }

    // renderRecentTransactions moved to index.php


    // renderAllowanceTable removed


    // renderExpenseTable removed


    /* --- Charts & Reports --- */

    // renderDashboardChart moved to index.php


    // renderReports moved to reports.php


    async deleteItem(id, type) {
        if (confirm('Are you sure you want to delete this entry?')) {
            let result;
            if (type === 'allowance') {
                result = await this.dataManager.deleteAllowance(id);
            } else {
                result = await this.dataManager.deleteExpense(id);
            }

            if (result && result.success) {
                this.showNotification(result.message, 'info'); // 'info' is usually blue, maybe 'success' is better?
                // Update all views
                this.render();
            } else {
                this.showNotification(result?.message || 'Error deleting item', 'danger');
            }
        }
    }

    /* --- Helpers --- */

    formatCurrency(amount) {
        return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(amount);
    }

    showNotification(message, type = 'info') {
        const container = document.getElementById('alertContainer');
        if (!container) return;

        // Map bootstrap colors for Tailwind-ish look if needed, but alerts work with Bootstrap classes
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-dismissible fade show`;
        alert.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        container.appendChild(alert);
        setTimeout(() => alert.remove(), 3000);
    }
}

// Initialize App
document.addEventListener('DOMContentLoaded', () => {
    window.app = new BudgetApp();
});
