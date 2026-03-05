<?php
$pageTitle = 'Bill Calendar';
$pageHeader = 'Bill Calendar';
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div id="page-content-wrapper">
    <?php include '../includes/navbar.php'; ?>

    <div class="container-fluid px-4 py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <p class="text-muted small mb-0">Visualize your upcoming recurring payments</p>
            </div>
            <a href="bills.php" class="btn btn-primary rounded-pill px-4">
                <i class="fas fa-list me-2"></i>Manage Bills
            </a>
        </div>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-body p-4">
                <div id="calendar"></div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,dayGridWeek'
            },
            themeSystem: 'bootstrap5',
            events: '<?php echo SITE_URL; ?>api/bills.php?action=fetch_events',
            eventContent: function(arg) {
                let amount = parseFloat(arg.event.extendedProps.amount);
                let description = arg.event.extendedProps.description;
                let category = arg.event.extendedProps.category;
                let source = arg.event.extendedProps.source_type;

                const icons = {
                    'Utilities': 'fas fa-lightbulb',
                    'Entertainment': 'fas fa-play-circle',
                    'Food': 'fas fa-utensils',
                    'Transport': 'fas fa-car',
                    'Healthcare': 'fas fa-heartbeat',
                    'Housing': 'fas fa-home',
                    'Other': 'fas fa-tag'
                };
                let iconClass = icons[category] || 'fas fa-file-invoice-dollar';

                let formattedAmount = new Intl.NumberFormat('en-PH', {
                    style: 'currency',
                    currency: 'PHP'
                }).format(amount);

                let html = `
                    <div class="bill-event-card p-1 w-100">
                        <div class="d-flex align-items-center gap-1 mb-1">
                            <i class="${iconClass} opacity-75" style="font-size:0.65rem;"></i>
                            <span class="fw-bold text-truncate" style="font-size: 0.72rem; line-height:1.2;">${arg.event.title}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center" style="font-size: 0.65rem;">
                            <span class="fw-semibold" style="color:#6366f1;">${formattedAmount}</span>
                            <span class="badge px-1" style="font-size:0.55rem;background:rgba(99,102,241,0.1);color:#6366f1;">${source}</span>
                        </div>
                        ${description ? `<div style="font-size:0.58rem;margin-top:2px;opacity:0.6;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;">${description}</div>` : ''}
                    </div>
                `;
                return {
                    html: html
                };
            },
            eventClick: function(info) {
                Swal.fire({
                    title: info.event.title,
                    html: `
                    <div class="text-start">
                        <p><strong>Category:</strong> ${info.event.extendedProps.category}</p>
                        <p><strong>Due Date:</strong> ${info.event.start.toLocaleDateString()}</p>
                        ${info.event.extendedProps.description ? `<p><strong>Description:</strong> ${info.event.extendedProps.description}</p>` : ''}
                    </div>
                `,
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonText: 'View Details',
                    confirmButtonColor: '#6366f1'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'bills.php';
                    }
                });
            },
            eventDidMount: function(info) {
                // Add tooltip or other enhancements if needed
            }
        });
        calendar.render();
    });
</script>

<style>
    .fc .fc-button-primary {
        background-color: #6366f1;
        border-color: #6366f1;
        border-radius: 20px;
        padding: 0.4rem 1.2rem;
        font-weight: 600;
    }

    .fc .fc-button-primary:hover {
        background-color: #4f46e5;
        border-color: #4f46e5;
    }

    .fc .fc-button-primary:disabled {
        background-color: #6366f1;
        opacity: 0.65;
    }

    .fc-theme-bootstrap5 .fc-scrollgrid {
        border-radius: 12px;
        overflow: hidden;
    }

    .fc-event {
        cursor: pointer;
        padding: 0 !important;
        font-size: 0.85rem;
        border-radius: 8px;
        background-color: white !important;
        color: #1e293b !important;
        border: 1px solid #e2e8f0 !important;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        transition: transform 0.1s ease;
        margin: 1px 2px !important;
    }

    .fc-event:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        border-color: #6366f1 !important;
    }

    .fc-daygrid-event-harness {
        margin-bottom: 2px;
    }

    .fc-theme-bootstrap5 .fc-daygrid-day-number {
        font-weight: 700;
        color: #64748b;
        padding: 8px;
    }
</style>

<?php include '../includes/footer.php'; ?>