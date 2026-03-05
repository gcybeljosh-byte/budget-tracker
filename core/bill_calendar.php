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
                let amount = arg.event.extendedProps.amount;
                let description = arg.event.extendedProps.description;
                let html = `<div class="fc-content">
                                <div class="fc-title fw-bold text-truncate">${arg.event.title}</div>`;
                if (description) {
                    html += `<div class="fc-description extra-small opacity-75 text-truncate" style="font-size: 0.65rem;">${description}</div>`;
                }
                html += `</div>`;
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
        padding: 2px 5px;
        font-size: 0.85rem;
        border-radius: 6px;
    }
</style>

<?php include '../includes/footer.php'; ?>